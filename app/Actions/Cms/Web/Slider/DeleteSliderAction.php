<?php

namespace App\Actions\Cms\Web\Slider;

use App\Models\Web\Slider;
use App\Traits\CacheInvalidator;

class DeleteSliderAction
{
    use CacheInvalidator;

    /**
     * Handle the action.
     */
    public function handle(Slider $slider): ?bool
    {
        $result = $slider->delete();

        // Clear slider cache
        $this->clearSliderCache();

        return $result;
    }
}
