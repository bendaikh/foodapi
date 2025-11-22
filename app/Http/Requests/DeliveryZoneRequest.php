<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Ensure status is cast to integer
        if ($this->has('status')) {
            $this->merge([
                'status' => (int) $this->status,
            ]);
        }
        
        // Ensure sort_order is cast to integer
        if ($this->has('sort_order')) {
            $this->merge([
                'sort_order' => (int) $this->sort_order,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'name' => ['nullable', 'string', 'max:190'],
            'max_distance_km' => ['required', 'numeric', 'gt:0'],
            'delivery_price' => ['required', 'numeric', 'gte:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'numeric', 'max:24'],
        ];
    }
}


