<?php

namespace App\Actions\Cms\Web\Slider;

use App\Models\Web\Slider;
use App\Traits\CacheInvalidator;
use App\Traits\WithMediaCollection;
use Illuminate\Http\UploadedFile;

class UpdateSliderAction
{
    use CacheInvalidator, WithMediaCollection;

    /**
     * Handle the action.
     */
    public function handle(Slider $slider, array $data): bool
    {
        if ($data['image'] ?? null instanceof UploadedFile) {
            $this->saveMedia(
                model: $slider,
                file: $data['image'],
                collection: 'image',
            );
        }

        $result = $slider->update($data);

        // Clear slider cache
        $this->clearSliderCache();

        return $result;
    }
}
