<?php

namespace App\Http\Controllers\Api\V1\Callback;

use App\Actions\Api\V1\Callback\HandleMidtransCallbackAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Callback\MidtransCallbackRequest;
use App\Traits\WithReturnResponse;
use Illuminate\Http\JsonResponse;

class MidtransController extends Controller
{
    use WithReturnResponse;

    public function callback(MidtransCallbackRequest $request, HandleMidtransCallbackAction $action): JsonResponse
    {
        $action->handle($request->validated());

        return $this->responseWithSuccess('Callback received');
    }
}
