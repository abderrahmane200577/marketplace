<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorApprovedMiddleware
{
    /**
     * Ensures the authenticated vendor has been approved by admin.
     * Use after 'auth:sanctum' and 'role:vendor'.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user->isVendor()) {
            $vendor = $user->vendor;

            if (! $vendor) {
                return response()->json(['message' => 'Vendor profile not found.'], 404);
            }

            if (! $vendor->isApproved()) {
                return response()->json([
                    'message' => 'Your vendor account is pending admin approval.',
                    'status'  => $vendor->status,
                    'reason'  => $vendor->rejection_reason,
                ], 403);
            }
        }

        return $next($request);
    }
}
