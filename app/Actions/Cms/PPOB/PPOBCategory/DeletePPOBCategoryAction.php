<?php

namespace App\Actions\Cms\PPOB\PPOBCategory;

use App\Models\PPOB\PPOBCategory;
use App\Traits\CacheInvalidator;

class DeletePPOBCategoryAction
{
    use CacheInvalidator;

    /**
     * Handle the action.
     */
    public function handle(PPOBCategory $category): ?bool
    {
        $result = $category->delete();

        // Clear category and brand cache
        $this->clearCategoryCache();
        $this->clearBrandCache();

        return $result;
    }
}
