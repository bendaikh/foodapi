<?php

namespace App\Http\Resources;


use App\Libraries\AppLibrary;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPointResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request) : array
    {
        return [
            'points'                => $this->user_points ?? 0,
            'is_point_applicable'   => $this->is_point_applicable ?? false,
            'user_points'           => $this->user_points ?? 0,
            'applicable_points'     => $this->applicable_points ?? 0,
            'point_discount_amount' => AppLibrary::convertAmountFormat($this->point_discount_amount ?? 0),
            'currency_point_discount_amount'              => AppLibrary::currencyAmountFormat($this->point_discount_amount ?? 0),
        ];
    }
}
