<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // ────────────────────────────────────────────────────────────────
    // GET /api/admin/users
    // ────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = User::with('vendor:id,user_id,store_name,status')
            ->withTrashed(false);

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'users'      => $users->items(),
            'pagination' => [
                'total'        => $users->total(),
                'per_page'     => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
            ],
            'summary' => [
                'total'     => User::count(),
                'admins'    => User::where('role', 'admin')->count(),
                'vendors'   => User::where('role', 'vendor')->count(),
                'customers' => User::where('role', 'customer')->count(),
                'inactive'  => User::where('is_active', false)->count(),
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // GET /api/admin/users/{id}
    // ────────────────────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $user = User::with('vendor')->findOrFail($id);

        return response()->json(['user' => $user]);
    }

    // ────────────────────────────────────────────────────────────────
    // PATCH /api/admin/users/{id}/toggle-active
    // Activer / désactiver un compte user
    // ────────────────────────────────────────────────────────────────

    public function toggleActive(Request $request, int $id): JsonResponse
    {
        $admin = $request->user();

        if ($admin->id === $id) {
            return response()->json([
                'message' => 'You cannot deactivate your own account.',
            ], 422);
        }

        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);

        // Révoquer tous les tokens si désactivé
        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message'   => 'User status updated.',
            'is_active' => $user->is_active,
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // GET /api/admin/dashboard
    // Stats globales de la plateforme
    // ────────────────────────────────────────────────────────────────

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'stats' => [
                'users' => [
                    'total'     => User::count(),
                    'vendors'   => User::where('role', 'vendor')->count(),
                    'customers' => User::where('role', 'customer')->count(),
                ],
                'vendors' => [
                    'pending'  => \App\Models\Vendor::where('status', 'pending')->count(),
                    'approved' => \App\Models\Vendor::where('status', 'approved')->count(),
                ],
                'products' => [
                    'total'    => \App\Models\Product::count(),
                    'active'   => \App\Models\Product::where('status', 'active')->count(),
                    'draft'    => \App\Models\Product::where('status', 'draft')->count(),
                ],
            ],
        ]);
    }
}