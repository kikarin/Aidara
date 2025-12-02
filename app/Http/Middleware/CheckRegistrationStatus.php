<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRegistrationStatus
{
    /**
     * Handle an incoming request.
     * 
     * Block user dengan registration_status = 'pending' dari akses halaman selain edit page mereka
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Jika user belum login, lanjutkan
        if (!$user) {
            return $next($request);
        }

        // Admin bisa akses semua halaman (cek berdasarkan role_id langsung)
        // Role_id 1 = Superadmin, 11 = Admin
        $isAdmin = in_array($user->current_role_id, [1, 11]);
        if ($isAdmin) {
            return $next($request);
        }

        // Jika user sudah approved, lanjutkan
        if ($user->registration_status !== 'pending') {
            return $next($request);
        }

        // Jika user pending tapi belum punya peserta_id atau peserta_type, lanjutkan (masih di step registrasi)
        if (!$user->peserta_id || !$user->peserta_type) {
            return $next($request);
        }

        // Daftar route yang diizinkan untuk user pending
        $allowedRoutes = [
            // Edit page peserta utama
            'atlet.edit',
            'pelatih.edit',
            'tenaga-pendukung.edit',
            // Store dan update peserta utama
            'atlet.store',
            'atlet.update',
            'pelatih.store',
            'pelatih.update',
            'tenaga-pendukung.store',
            'tenaga-pendukung.update',
            // Sertifikat routes
            'atlet.sertifikat.index',
            'atlet.sertifikat.create',
            'atlet.sertifikat.store',
            'atlet.sertifikat.show',
            'atlet.sertifikat.edit',
            'atlet.sertifikat.update',
            'pelatih.sertifikat.index',
            'pelatih.sertifikat.create',
            'pelatih.sertifikat.store',
            'pelatih.sertifikat.show',
            'pelatih.sertifikat.edit',
            'pelatih.sertifikat.update',
            'tenaga-pendukung.sertifikat.index',
            'tenaga-pendukung.sertifikat.create',
            'tenaga-pendukung.sertifikat.store',
            'tenaga-pendukung.sertifikat.show',
            'tenaga-pendukung.sertifikat.edit',
            'tenaga-pendukung.sertifikat.update',
            // Prestasi routes
            'atlet.prestasi.index',
            'atlet.prestasi.create',
            'atlet.prestasi.store',
            'atlet.prestasi.show',
            'atlet.prestasi.edit',
            'atlet.prestasi.update',
            'pelatih.prestasi.index',
            'pelatih.prestasi.create',
            'pelatih.prestasi.store',
            'pelatih.prestasi.show',
            'pelatih.prestasi.edit',
            'pelatih.prestasi.update',
            'tenaga-pendukung.prestasi.index',
            'tenaga-pendukung.prestasi.create',
            'tenaga-pendukung.prestasi.store',
            'tenaga-pendukung.prestasi.show',
            'tenaga-pendukung.prestasi.edit',
            'tenaga-pendukung.prestasi.update',
            // Dokumen routes
            'atlet.dokumen.index',
            'atlet.dokumen.create',
            'atlet.dokumen.store',
            'atlet.dokumen.show',
            'atlet.dokumen.edit',
            'atlet.dokumen.update',
            'pelatih.dokumen.index',
            'pelatih.dokumen.create',
            'pelatih.dokumen.store',
            'pelatih.dokumen.show',
            'pelatih.dokumen.edit',
            'pelatih.dokumen.update',
            'tenaga-pendukung.dokumen.index',
            'tenaga-pendukung.dokumen.create',
            'tenaga-pendukung.dokumen.store',
            'tenaga-pendukung.dokumen.show',
            'tenaga-pendukung.dokumen.edit',
            'tenaga-pendukung.dokumen.update',
            'logout',
        ];

        // Cek apakah route saat ini diizinkan
        $routeName = $request->route()?->getName();
        
        // Jika route tidak punya nama, anggap tidak diizinkan untuk user pending
        if (!$routeName) {
            return $this->redirectToEditPage($user);
        }
        
        // Jika route diizinkan, lanjutkan
        if (in_array($routeName, $allowedRoutes)) {
            $routeParams = $request->route()->parameters();
            
            // Pastikan user hanya bisa akses/edit data mereka sendiri
            // Cek untuk edit page peserta utama (atlet.edit, pelatih.edit, tenaga-pendukung.edit)
            if (in_array($routeName, ['atlet.edit', 'pelatih.edit', 'tenaga-pendukung.edit'])) {
                $pesertaId = $routeParams['atlet'] ?? $routeParams['pelatih'] ?? $routeParams['tenagaPendukung'] 
                    ?? $routeParams['atlet_id'] ?? $routeParams['pelatih_id'] ?? $routeParams['tenagaPendukungId']
                    ?? $routeParams['atletId'] ?? $routeParams['pelatihId'] ?? null;
                
                if ($pesertaId && $pesertaId != $user->peserta_id) {
                    return $this->redirectToEditPage($user);
                }
            }
            
            // Cek untuk store/update peserta utama (atlet.store, atlet.update, dll)
            if (in_array($routeName, ['atlet.store', 'atlet.update', 'pelatih.store', 'pelatih.update', 'tenaga-pendukung.store', 'tenaga-pendukung.update'])) {
                // Untuk update, cek parameter ID di URL
                if (str_ends_with($routeName, '.update')) {
                    $pesertaId = $routeParams['atlet'] ?? $routeParams['pelatih'] ?? $routeParams['tenagaPendukung'] 
                        ?? $routeParams['atlet_id'] ?? $routeParams['pelatih_id'] ?? $routeParams['tenagaPendukungId']
                        ?? $routeParams['atletId'] ?? $routeParams['pelatihId'] ?? null;
                    
                    if ($pesertaId && $pesertaId != $user->peserta_id) {
                        return $this->redirectToEditPage($user);
                    }
                }
                // Untuk store peserta utama, biasanya ini adalah create baru untuk user mereka sendiri
                // Validasi lebih lanjut akan dilakukan di controller
                // Di middleware, kita hanya izinkan jika route sudah ada di allowedRoutes
            }
            
            // Pastikan user hanya bisa akses sertifikat/prestasi/dokumen mereka sendiri
            if (str_contains($routeName, '.sertifikat.') || str_contains($routeName, '.prestasi.') || str_contains($routeName, '.dokumen.')) {
                // Untuk nested routes, parameter biasanya atlet_id, pelatih_id, atau tenagaPendukungId
                $pesertaId = $routeParams['atlet_id'] ?? $routeParams['pelatih_id'] ?? $routeParams['tenagaPendukungId'] 
                    ?? $routeParams['atlet'] ?? $routeParams['pelatih'] ?? $routeParams['tenagaPendukung']
                    ?? $routeParams['atletId'] ?? $routeParams['pelatihId'] ?? null;
                
                if ($pesertaId && $pesertaId != $user->peserta_id) {
                    return $this->redirectToEditPage($user);
                }
                
                // Untuk store/update nested resources, pastikan peserta_id di request sesuai
                if (str_ends_with($routeName, '.store') || str_ends_with($routeName, '.update')) {
                    // Controller akan memvalidasi bahwa peserta_id sesuai dengan user
                    // Di sini kita hanya memastikan route parameter sesuai
                }
            }
            
            return $next($request);
        }

        // Jika route tidak diizinkan, redirect ke edit page dengan pesan
        return $this->redirectToEditPage($user);
    }

    /**
     * Redirect user ke edit page mereka dengan pesan
     */
    private function redirectToEditPage($user)
    {
        $editRoute = match($user->peserta_type) {
            'atlet' => 'atlet.edit',
            'pelatih' => 'pelatih.edit',
            'tenaga_pendukung' => 'tenaga-pendukung.edit',
            default => null
        };

        if ($editRoute && $user->peserta_id) {
            return redirect()->route($editRoute, $user->peserta_id)
                ->with('error', 'Anda belum di-approve oleh administrator. Silakan lengkapi data diri Anda dan tunggu persetujuan.');
        }

        // Fallback ke dashboard jika tidak ada edit route
        return redirect()->route('dashboard')
            ->with('error', 'Anda belum di-approve oleh administrator. Silakan lengkapi data diri Anda dan tunggu persetujuan.');
    }
}

