<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Smartisan\Settings\Facades\Settings;

class PointSetupTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Settings::group('point_setup')->set([
            'point_setup_each_currency_to_points'                                      => 0,
            'point_setup_points_for_each_currency'                                     => 0,
            'point_setup_minimum_applicable_points_for_each_order'                     => 0,
            'point_setup_maximum_applicable_points_for_each_order'                     => 0,
        ]);
    }
}
