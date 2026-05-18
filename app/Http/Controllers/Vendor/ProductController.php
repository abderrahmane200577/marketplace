<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    // ────────────────────────────────────────────────────────────────
    // GET /api/vendor/products
    // Liste tous les produits du vendor connecté (avec filtres)
    // ────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        $query = Product::where('vendor_id', $vendor->id)
            ->with(['category:id,name', 'inventory', 'images'])
            ->withCount('variants');

        // ── Filtres optionnels ──────────────────────────────────────
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('low_stock')) {
            $query->whereHas('inventory', function ($q) {
                $q->whereColumn('quantity', '<=', 'low_stock_threshold');
            });
        }

        // ── Tri ────────────────────────────────────────────────────
        $sortBy  = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowedSorts = ['name', 'price', 'created_at', 'status'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $products = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'products'   => $products->items(),
            'pagination' => [
                'total'        => $products->total(),
                'per_page'     => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // POST /api/vendor/products
    // Créer un nouveau produit avec inventory + variants optionnels
    // ────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        $data = $request->validate([
            // Infos de base
            'name'              => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'category_id'       => ['nullable', 'exists:categories,id'],
            'sku'               => ['nullable', 'string', 'unique:products,sku'],

            // Prix
            'price'             => ['required', 'numeric', 'min:0'],
            'compare_price'     => ['nullable', 'numeric', 'min:0'],
            'cost_price'        => ['nullable', 'numeric', 'min:0'],

            // Statut
            'status'            => ['sometimes', 'in:draft,active,archived'],
            'is_featured'       => ['sometimes', 'boolean'],

            // SEO
            'meta_title'        => ['nullable', 'string', 'max:255'],
            'meta_description'  => ['nullable', 'string'],

            // Thumbnail principal
            'thumbnail'         => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],

            // Images supplémentaires (tableau de fichiers)
            'images'            => ['nullable', 'array', 'max:8'],
            'images.*'          => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],

            // Stock initial
            'quantity'          => ['sometimes', 'integer', 'min:0'],
            'low_stock_threshold' => ['sometimes', 'integer', 'min:0'],

            // Variants (optionnel)
            // ex: [{"attribute": "color", "value": "Red", "price_modifier": 5, "quantity": 10}]
            'variants'              => ['nullable', 'array'],
            'variants.*.attribute'  => ['required_with:variants', 'string', 'max:100'],
            'variants.*.value'      => ['required_with:variants', 'string', 'max:100'],
            'variants.*.price_modifier' => ['sometimes', 'numeric'],
            'variants.*.sku'        => ['nullable', 'string', 'unique:product_variants,sku'],
            'variants.*.quantity'   => ['sometimes', 'integer', 'min:0'],
        ]);

        DB::beginTransaction();

        try {
            // ── 1. Upload du thumbnail ──────────────────────────────
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                $thumbnailPath = $request->file('thumbnail')
                    ->store('products/thumbnails', 'public');
            }

            // ── 2. Créer le produit ─────────────────────────────────
            $product = Product::create([
                'vendor_id'        => $vendor->id,
                'category_id'      => $data['category_id'] ?? null,
                'name'             => $data['name'],
                'description'      => $data['description'] ?? null,
                'sku'              => $data['sku'] ?? null,
                'price'            => $data['price'],
                'compare_price'    => $data['compare_price'] ?? null,
                'cost_price'       => $data['cost_price'] ?? null,
                'thumbnail'        => $thumbnailPath,
                'status'           => $data['status'] ?? 'draft',
                'is_featured'      => $data['is_featured'] ?? false,
                'meta_title'       => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
            ]);

            // ── 3. Upload des images supplémentaires ────────────────
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $imageFile) {
                    $path = $imageFile->store('products/images', 'public');
                    ProductImage::create([
                        'product_id' => $product->id,
                        'url'        => $path,
                        'sort_order' => $index,
                        'is_primary' => $index === 0,
                    ]);
                }
            }

            // ── 4. Créer les variants (si fournis) ──────────────────
            if (!empty($data['variants'])) {
                foreach ($data['variants'] as $variantData) {
                    $variant = ProductVariant::create([
                        'product_id'      => $product->id,
                        'attribute'       => $variantData['attribute'],
                        'value'           => $variantData['value'],
                        'price_modifier'  => $variantData['price_modifier'] ?? 0,
                        'sku'             => $variantData['sku'] ?? null,
                    ]);

                    // Stock pour ce variant
                    $variantQty = $variantData['quantity'] ?? 0;
                    Inventory::create([
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'quantity'   => $variantQty,
                        'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
                    ]);

                    // Log du stock initial
                    if ($variantQty > 0) {
                        InventoryLog::create([
                            'product_id'     => $product->id,
                            'variant_id'     => $variant->id,
                            'change'         => $variantQty,
                            'quantity_after' => $variantQty,
                            'reason'         => 'restock',
                            'reference'      => 'initial_stock',
                            'created_by'     => $request->user()->id,
                        ]);
                    }
                }
            } else {
                // ── 5. Inventory produit sans variants ──────────────
                $quantity = $data['quantity'] ?? 0;
                Inventory::create([
                    'product_id'          => $product->id,
                    'variant_id'          => null,
                    'quantity'            => $quantity,
                    'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
                ]);

                if ($quantity > 0) {
                    InventoryLog::create([
                        'product_id'     => $product->id,
                        'variant_id'     => null,
                        'change'         => $quantity,
                        'quantity_after' => $quantity,
                        'reason'         => 'restock',
                        'reference'      => 'initial_stock',
                        'created_by'     => $request->user()->id,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Product created successfully.',
                'product' => $product->load(['category', 'images', 'variants', 'inventory']),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create product.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ────────────────────────────────────────────────────────────────
    // GET /api/vendor/products/{id}
    // Voir un produit en détail
    // ────────────────────────────────────────────────────────────────

    public function show(Request $request, int $id): JsonResponse
    {
        $vendor  = $request->user()->vendor;
        $product = Product::where('vendor_id', $vendor->id)
            ->with(['category', 'images', 'variants.inventory', 'inventory', 'inventoryLogs' => function ($q) {
                $q->latest()->limit(10);
            }])
            ->findOrFail($id);

        return response()->json(['product' => $product]);
    }

    // ────────────────────────────────────────────────────────────────
    // PUT /api/vendor/products/{id}
    // Modifier un produit
    // ────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id): JsonResponse
    {
        $vendor  = $request->user()->vendor;
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);

        $data = $request->validate([
            'name'             => ['sometimes', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'category_id'      => ['nullable', 'exists:categories,id'],
            'sku'              => ['nullable', 'string', Rule::unique('products', 'sku')->ignore($product->id)],
            'price'            => ['sometimes', 'numeric', 'min:0'],
            'compare_price'    => ['nullable', 'numeric', 'min:0'],
            'cost_price'       => ['nullable', 'numeric', 'min:0'],
            'status'           => ['sometimes', 'in:draft,active,archived'],
            'is_featured'      => ['sometimes', 'boolean'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'thumbnail'        => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],

            // Mise à jour du stock (produit sans variants)
            'quantity'             => ['sometimes', 'integer', 'min:0'],
            'low_stock_threshold'  => ['sometimes', 'integer', 'min:0'],
        ]);

        // ── Nouveau thumbnail ───────────────────────────────────────
        if ($request->hasFile('thumbnail')) {
            if ($product->thumbnail) {
                Storage::disk('public')->delete($product->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')
                ->store('products/thumbnails', 'public');
        }

        $product->update($data);

        // ── Mise à jour du stock si fourni ──────────────────────────
        if ($request->filled('quantity') && $product->inventory) {
            $oldQty  = $product->inventory->quantity;
            $newQty  = (int) $data['quantity'];
            $change  = $newQty - $oldQty;

            $product->inventory->update([
                'quantity'            => $newQty,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? $product->inventory->low_stock_threshold,
            ]);

            if ($change !== 0) {
                InventoryLog::create([
                    'product_id'     => $product->id,
                    'variant_id'     => null,
                    'change'         => $change,
                    'quantity_after' => $newQty,
                    'reason'         => 'adjustment',
                    'reference'      => 'manual_update',
                    'created_by'     => $request->user()->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product->load(['category', 'images', 'variants', 'inventory']),
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // DELETE /api/vendor/products/{id}
    // Soft delete du produit
    // ────────────────────────────────────────────────────────────────

    public function destroy(Request $request, int $id): JsonResponse
    {
        $vendor  = $request->user()->vendor;
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);

        $product->delete(); // soft delete

        return response()->json(['message' => 'Product deleted successfully.']);
    }

    // ────────────────────────────────────────────────────────────────
    // POST /api/vendor/products/{id}/images
    // Ajouter des images à un produit existant
    // ────────────────────────────────────────────────────────────────

    public function addImages(Request $request, int $id): JsonResponse
    {
        $vendor  = $request->user()->vendor;
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);

        $request->validate([
            'images'   => ['required', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $currentCount = $product->images()->count();

        if ($currentCount >= 8) {
            return response()->json([
                'message' => 'Maximum 8 images per product.',
            ], 422);
        }

        $uploaded = [];
        foreach ($request->file('images') as $index => $imageFile) {
            $path = $imageFile->store('products/images', 'public');
            $image = ProductImage::create([
                'product_id' => $product->id,
                'url'        => $path,
                'sort_order' => $currentCount + $index,
                'is_primary' => $currentCount === 0 && $index === 0,
            ]);
            $uploaded[] = $image;
        }

        return response()->json([
            'message' => count($uploaded) . ' image(s) added successfully.',
            'images'  => $uploaded,
        ], 201);
    }

    // ────────────────────────────────────────────────────────────────
    // DELETE /api/vendor/products/{id}/images/{imageId}
    // Supprimer une image spécifique
    // ────────────────────────────────────────────────────────────────

    public function deleteImage(Request $request, int $id, int $imageId): JsonResponse
    {
        $vendor  = $request->user()->vendor;
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);

        $image = ProductImage::where('product_id', $product->id)
            ->findOrFail($imageId);

        Storage::disk('public')->delete($image->url);
        $image->delete();

        // Si c'était l'image primaire → mettre la suivante comme primaire
        if ($image->is_primary) {
            $next = ProductImage::where('product_id', $product->id)
                ->orderBy('sort_order')
                ->first();
            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

        return response()->json(['message' => 'Image deleted successfully.']);
    }
}