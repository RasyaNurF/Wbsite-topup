<?php

namespace App\Actions\Cms\PPOB\PPOBBrand;

use App\Models\PPOB\PPOBBrand;
use App\Traits\CacheInvalidator;

class DeletePPOBBrandAction
{
    use CacheInvalidator;

    /**
     * Handle the action.
     */
    public function handle(PPOBBrand $brand): ?bool
    {
        $brandSlug = $brand->slug;
        $result = $brand->delete();

        // Clear brand cache
        $this->clearBrandCache();
        $this->clearCategoryCache();
        $this->clearBrandDetailCache($brandSlug);

        return $result;
    }
}
