<?php

namespace App\Http\Requests\Main;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:p_p_o_b_products,id',
            'quantity' => 'nullable|integer|min:1|max:99',
        ];
    }
}
