<?php

namespace App\Http\PaymentGateways\Requests;

use App\Enums\Activity;
use Illuminate\Foundation\Http\FormRequest;

class Selcom extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        if (request()->selcom_status == Activity::ENABLE) {
            return [
                'selcom_client_id'     => ['required', 'string'],
                'selcom_client_secret' => ['required', 'string'],
                'selcom_api_key'       => ['required', 'string'],
                'selcom_mode'          => ['required', 'string'],
                'selcom_status'        => ['nullable', 'numeric'],
            ];
        } else {
            return [
                'selcom_client_id'     => ['nullable', 'string'],
                'selcom_client_secret' => ['nullable', 'string'],
                'selcom_api_key'       => ['nullable', 'string'],
                'selcom_mode'          => ['nullable', 'string'],
                'selcom_status'        => ['nullable', 'numeric'],
            ];
        }
    }
}
