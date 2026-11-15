<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;

class LandingController extends Controller
{
    /**
     * Seksi harga di landing page. Disembunyikan sementara; ubah ke true untuk menampilkannya lagi
     * (paket diambil otomatis dari katalog Paket Langganan Super Admin).
     */
    public const SHOW_PRICING = false;

    /**
     * Halaman depan publik: profil produk, (opsional) paket harga, dan formulir permohonan demo.
     */
    public function index()
    {
        $showPricing = self::SHOW_PRICING;
        $plans = $showPricing
            ? SubscriptionPlan::where('is_active', true)->orderBy('price')->get()
            : collect();

        return view('public.landing', compact('plans', 'showPricing'));
    }
}
