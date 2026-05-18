<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VendorController extends Controller
{
    // ────────────────────────────────────────────────────────────────
    // GET /api/vendor/dashboard
    // ────────────────────────────────────────────────────────────────

    public function dashboard(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        // Nombre de produits par statut
        $products = Product::where('vendor_id', $vendor->id)->withTrashed(false);

        $totalProducts  = (clone $products)->count();
        $activeProducts = (clone $products)->where('status', 'active')->count();
        $draftProducts  = (clone $products)->where('status', 'draft')->count();

        // Produits en stock faible (quantity <= low_stock_threshold)
        $lowStockProducts = Inventory::whereHas('product', function ($q) use ($vendor) {
                $q->where('vendor_id', $vendor->id);
            })
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->where('quantity', '>', 0)
            ->with('product:id,name,sku,thumbnail')
            ->get()
            ->map(fn($inv) => [
                'product'   => $inv->product,
                'quantity'  => $inv->quantity,
                'threshold' => $inv->low_stock_threshold,
            ]);

        // Produits en rupture de stock
        $outOfStockCount = Inventory::whereHas('product', function ($q) use ($vendor) {
                $q->where('vendor_id', $vendor->id);
            })
            ->where('quantity', '<=', 0)
            ->count();

        return response()->json([
            'vendor' => [
                'store_name'  => $vendor->store_name,
                'store_slug'  => $vendor->store_slug,
                'logo'        => $vendor->logo,
                'status'      => $vendor->status,
                'member_since'=> $vendor->created_at->format('Y-m-d'),
            ],
            'stats' => [
                'total_products'    => $totalProducts,
                'active_products'   => $activeProducts,
                'draft_products'    => $draftProducts,
                'out_of_stock'      => $outOfStockCount,
                'low_stock_count'   => $lowStockProducts->count(),
            ],
            'low_stock_alerts' => $lowStockProducts,
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // GET /api/vendor/profile
    // ────────────────────────────────────────────────────────────────

    public function profile(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        return response()->json([
            'vendor' => [
                'id'          => $vendor->id,
                'store_name'  => $vendor->store_name,
                'store_slug'  => $vendor->store_slug,
                'description' => $vendor->description,
                'logo'        => $vendor->logo,
                'banner'      => $vendor->banner,
                'phone'       => $vendor->phone,
                'address'     => $vendor->address,
                'city'        => $vendor->city,
                'country'     => $vendor->country,
                'status'      => $vendor->status,
                'approved_at' => $vendor->approved_at,
                'created_at'  => $vendor->created_at,
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // PUT /api/vendor/profile
    // ────────────────────────────────────────────────────────────────

    public function updateProfile(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        $data = $request->validate([
            'store_name'  => ['sometimes', 'string', 'max:255', 'unique:vendors,store_name,' . $vendor->id],
            'description' => ['nullable', 'string', 'max:2000'],
            'phone'       => ['nullable', 'string', 'max:20'],
            'address'     => ['nullable', 'string', 'max:500'],
            'city'        => ['nullable', 'string', 'max:100'],
            'country'     => ['nullable', 'string', 'max:100'],

            // Upload logo (image max 2MB)
            'logo'        => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],

            // Upload banner (image max 5MB)
            'banner'      => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        // ── Gérer l'upload du logo ──────────────────────────────────
        if ($request->hasFile('logo')) {
            // Supprimer l'ancien logo s'il existe
            if ($vendor->logo) {
                Storage::disk('public')->delete($vendor->logo);
            }
            $data['logo'] = $request->file('logo')->store('vendors/logos', 'public');
        }

        // ── Gérer l'upload du banner ────────────────────────────────
        if ($request->hasFile('banner')) {
            if ($vendor->banner) {
                Storage::disk('public')->delete($vendor->banner);
            }
            $data['banner'] = $request->file('banner')->store('vendors/banners', 'public');
        }

        // ── Regénérer le slug si store_name a changé ────────────────
        if (isset($data['store_name']) && $data['store_name'] !== $vendor->store_name) {
            $data['store_slug'] = Str::slug($data['store_name']);
        }

        $vendor->update($data);

        return response()->json([
            'message' => 'Store profile updated successfully.',
            'vendor'  => [
                'id'          => $vendor->id,
                'store_name'  => $vendor->store_name,
                'store_slug'  => $vendor->store_slug,
                'description' => $vendor->description,
                'logo'        => $vendor->logo ? Storage::url($vendor->logo) : null,
                'banner'      => $vendor->banner ? Storage::url($vendor->banner) : null,
                'phone'       => $vendor->phone,
                'address'     => $vendor->address,
                'city'        => $vendor->city,
                'country'     => $vendor->country,
            ],
        ]);
    }
}