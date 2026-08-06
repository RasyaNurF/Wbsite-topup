<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\PPOB\PPOBBrand;
use App\Models\Web\Faq;
use Illuminate\Support\Facades\Cache;
use Inertia\Response;

class BrandController extends Controller
{
    public function show(PPOBBrand $brand): Response
    {
        $brandSlug = $brand->slug;
        $cacheKey = "brand:detail:{$brandSlug}";

        // Cache brand details for 30 minutes
        $brandData = Cache::remember($cacheKey, 1800, function () use ($brand) {
            $brand->load(['products.media', 'category']);

            $brand->image = $brand->getFirstMediaUrl('image');
            $brand->banner = $brand->getFirstMediaUrl('banner');
            $brand->default_product_image = $brand->getFirstMediaUrl('default_product_image');

            $brand->products->each(function ($product) use ($brand) {
                $product->image = $product->getFirstMediaUrl('image') ?: $brand->default_product_image;
                $product->makeHidden('media');
            });

            $brand->makeHidden('media');

            return $brand;
        });

        // Cache FAQs for 1 hour
        $faqs = Cache::remember('faqs:active', 3600, function () {
            return Faq::where('status', true)->orderBy('order', 'asc')->get();
        });

        $settingTitle = getSetting('title');
        $settingFavicon = getSetting('favicon') ?: '/favicon.svg';

        return inertia()->render('main/BrandDetail', [
            'brand' => $brandData,
            'faqs' => $faqs,
        ])->withViewData([
            'meta' => [
                'title' => "{$brandData->name} - Top Up Murah & Cepat | {$settingTitle}",
                'description' => "Top up {$brandData->name} termurah dan terpercaya di {$settingTitle}. Proses instan, tersedia berbagai metode pembayaran.",
                'keywords' => "top up {$brandData->name}, beli {$brandData->name}, harga {$brandData->name}, {$brandData->name} murah, {$settingTitle}, topup game",
                'author' => $settingTitle,
                'application_name' => $settingTitle,
                'url' => route('product.show', $brandData->slug),
                'image' => $brandData->image ?: (config('app.url').$settingFavicon),
            ],
        ]);
    }
}
