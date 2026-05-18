<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    // ────────────────────────────────────────────────────────────────
    // GET /api/categories  (public — tout le monde peut lire)
    // ────────────────────────────────────────────────────────────────

    public function index(): JsonResponse
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')   // seulement les parents
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['categories' => $categories]);
    }

    // ────────────────────────────────────────────────────────────────
    // GET /api/categories/{id}  (public)
    // ────────────────────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $category = Category::with(['children', 'parent'])
            ->findOrFail($id);

        return response()->json(['category' => $category]);
    }

    // ────────────────────────────────────────────────────────────────
    // POST /api/admin/categories  (admin only)
    // ────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
            'parent_id'   => ['nullable', 'exists:categories,id'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'is_active'   => ['sometimes', 'boolean'],
            'sort_order'  => ['sometimes', 'integer', 'min:0'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')
                ->store('categories', 'public');
        }

        $data['slug'] = Str::slug($data['name']);

        $category = Category::create($data);

        return response()->json([
            'message'  => 'Category created successfully.',
            'category' => $category,
        ], 201);
    }

    // ────────────────────────────────────────────────────────────────
    // PUT /api/admin/categories/{id}  (admin only)
    // ────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255', 'unique:categories,name,' . $id],
            'description' => ['nullable', 'string'],
            'parent_id'   => ['nullable', 'exists:categories,id'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'is_active'   => ['sometimes', 'boolean'],
            'sort_order'  => ['sometimes', 'integer', 'min:0'],
        ]);

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')
                ->store('categories', 'public');
        }

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return response()->json([
            'message'  => 'Category updated successfully.',
            'category' => $category->load('children'),
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // DELETE /api/admin/categories/{id}  (admin only)
    // ────────────────────────────────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        // Vérifier qu'elle n'a pas de produits liés
        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with existing products. Move products first.',
            ], 422);
        }

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        // Déplacer les sous-catégories vers la catégorie parente
        Category::where('parent_id', $id)->update(['parent_id' => $category->parent_id]);

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully.']);
    }
}