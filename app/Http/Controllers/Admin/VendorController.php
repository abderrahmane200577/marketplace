<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    // ────────────────────────────────────────────────────────────────
    // GET /api/admin/vendors
    // Liste tous les vendors (avec filtres par status)
    // ────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = Vendor::with('user:id,name,email,phone,created_at')
            ->withCount('products');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('store_name', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function ($uq) use ($request) {
                      $uq->where('email', 'like', '%' . $request->search . '%')
                         ->orWhere('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $vendors = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json([
            'vendors'    => $vendors->items(),
            'pagination' => [
                'total'        => $vendors->total(),
                'per_page'     => $vendors->perPage(),
                'current_page' => $vendors->currentPage(),
                'last_page'    => $vendors->lastPage(),
            ],
            // Compteurs rapides par statut
            'summary' => [
                'pending'   => Vendor::where('status', 'pending')->count(),
                'approved'  => Vendor::where('status', 'approved')->count(),
                'rejected'  => Vendor::where('status', 'rejected')->count(),
                'suspended' => Vendor::where('status', 'suspended')->count(),
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // GET /api/admin/vendors/{id}
    // Voir le détail d'un vendor
    // ────────────────────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $vendor = Vendor::with([
            'user:id,name,email,phone,created_at',
            'products' => function ($q) {
                $q->with('inventory')->latest()->limit(5);
            },
        ])
        ->withCount('products')
        ->findOrFail($id);

        return response()->json(['vendor' => $vendor]);
    }

    // ────────────────────────────────────────────────────────────────
    // PATCH /api/admin/vendors/{id}/approve
    // Approuver un vendor
    // ────────────────────────────────────────────────────────────────

    public function approve(Request $request, int $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);

        if ($vendor->isApproved()) {
            return response()->json(['message' => 'Vendor is already approved.'], 422);
        }

        $vendor->update([
            'status'           => 'approved',
            'rejection_reason' => null,
            'approved_at'      => now(),
            'approved_by'      => $request->user()->id,
        ]);

        // TODO (Week 5) : envoyer un email de notification au vendor

        return response()->json([
            'message' => 'Vendor approved successfully.',
            'vendor'  => $vendor->load('user:id,name,email'),
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // PATCH /api/admin/vendors/{id}/reject
    // Rejeter un vendor avec une raison
    // ────────────────────────────────────────────────────────────────

    public function reject(Request $request, int $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $vendor->update([
            'status'           => 'rejected',
            'rejection_reason' => $data['reason'],
            'approved_at'      => null,
            'approved_by'      => null,
        ]);

        return response()->json([
            'message' => 'Vendor rejected.',
            'vendor'  => $vendor->load('user:id,name,email'),
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // PATCH /api/admin/vendors/{id}/suspend
    // Suspendre un vendor approuvé
    // ────────────────────────────────────────────────────────────────

    public function suspend(Request $request, int $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if (!$vendor->isApproved()) {
            return response()->json([
                'message' => 'Only approved vendors can be suspended.',
            ], 422);
        }

        $vendor->update([
            'status'           => 'suspended',
            'rejection_reason' => $data['reason'],
        ]);

        // Désactiver aussi le compte user du vendor
        $vendor->user->update(['is_active' => false]);

        return response()->json(['message' => 'Vendor suspended successfully.']);
    }

    // ────────────────────────────────────────────────────────────────
    // PATCH /api/admin/vendors/{id}/reactivate
    // Réactiver un vendor suspendu
    // ────────────────────────────────────────────────────────────────

    public function reactivate(Request $request, int $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);

        if (!$vendor->isSuspended()) {
            return response()->json([
                'message' => 'Vendor is not suspended.',
            ], 422);
        }

        $vendor->update([
            'status'           => 'approved',
            'rejection_reason' => null,
            'approved_at'      => now(),
            'approved_by'      => $request->user()->id,
        ]);

        $vendor->user->update(['is_active' => true]);

        return response()->json(['message' => 'Vendor reactivated successfully.']);
    }
}