<?php

namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class PointSetupResource extends JsonResource
{

    public $info;

    public function __construct($info)
    {
        parent::__construct($info);
        $this->info = $info;
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            "point_setup_each_currency_to_points"                         => $this->info['point_setup_each_currency_to_points'],
            "point_setup_points_for_each_currency"                        => $this->info['point_setup_points_for_each_currency'],
            "point_setup_minimum_applicable_points_for_each_order"        => $this->info['point_setup_minimum_applicable_points_for_each_order'],
            "point_setup_maximum_applicable_points_for_each_order"        => $this->info['point_setup_maximum_applicable_points_for_each_order'],
        ];
    }
}