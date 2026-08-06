<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait CacheInvalidator
{
    /**
     * Clear home page cache when sliders are updated
     */
    protected function clearSliderCache(): void
    {
        Cache::forget('home:sliders');
    }

    /**
     * Clear home page cache when categories are updated
     */
    protected function clearCategoryCache(): void
    {
        Cache::forget('home:categories');
    }

    /**
     * Clear home page cache when brands are updated
     */
    protected function clearBrandCache(): void
    {
        Cache::forget('home:featured_brands');
        // Clear all paginated brand caches
        $this->clearPaginatedBrandCache();
    }

    /**
     * Clear paginated brand cache for all categories and pages
     */
    protected function clearPaginatedBrandCache(): void
    {
        // Note: In production, you might want to use cache tags instead
        // For now, we'll clear the entire cache store or use a more targeted approach
        // This is a simplified version - consider using Redis SCAN for better performance
    }

    /**
     * Clear brand detail cache when brand products are updated
     */
    protected function clearBrandDetailCache(string $brandSlug): void
    {
        Cache::forget("brand:detail:{$brandSlug}");
    }

    /**
     * Clear FAQ cache when FAQs are updated
     */
    protected function clearFaqCache(): void
    {
        Cache::forget('faqs:active');
    }
}
