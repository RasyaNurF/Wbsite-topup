<?php

namespace App\Actions\Cms\PPOB\PPOBCategory;

use App\Models\PPOB\PPOBCategory;
use App\Traits\CacheInvalidator;
use App\Traits\WithMediaCollection;
use Illuminate\Http\UploadedFile;

class StorePPOBCategoryAction
{
    use CacheInvalidator, WithMediaCollection;

    /**
     * Handle the action.
     */
    public function handle(array $data): PPOBCategory
    {
        $category = PPOBCategory::create($data);

        if ($data['image'] ?? null instanceof UploadedFile) {
            $this->saveMedia(
                model: $category,
                file: $data['image'],
                collection: 'image',
            );
        }

        // Clear category cache
        $this->clearCategoryCache();

        return $category;
    }
}
