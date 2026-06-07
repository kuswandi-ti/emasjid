<?php

namespace App\Http\Middleware;

use App\Enums\MosqueStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: EnsureMosqueActive
 *
 * Ensures the current mosque (resolved by IdentifyMosque) has an 'active' status.
 * Blocks access if mosque is pending, suspended, or rejected.
 *
 * Must be used AFTER IdentifyMosque middleware.
 */
class EnsureMosqueActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $mosque = app()->bound('current_mosque') ? app('current_mosque') : null;

        if (! $mosque) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Masjid tidak ditemukan. Silakan pilih masjid terlebih dahulu.',
                ], 404);
            }

            abort(404, 'Masjid tidak ditemukan. Silakan pilih masjid terlebih dahulu.');
        }

        if ($mosque->status !== MosqueStatus::Active) {
            $message = match ($mosque->status) {
                MosqueStatus::Pending => 'Masjid masih menunggu persetujuan.',
                MosqueStatus::Suspended => 'Masjid sedang ditangguhkan. Hubungi admin platform.',
                MosqueStatus::Rejected => 'Pendaftaran masjid ditolak.',
                default => 'Masjid tidak aktif.',
            };

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 403);
            }

            abort(403, $message);
        }

        return $next($request);
    }
}
