<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PointSetupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'point_setup_each_currency_to_points'                               => ['required', 'numeric'],
            'point_setup_points_for_each_currency'                              => ['required', 'numeric'],
            'point_setup_minimum_applicable_points_for_each_order'              => ['required', 'numeric'],
            'point_setup_maximum_applicable_points_for_each_order'              => ['required', 'numeric']
        ];
    }
}