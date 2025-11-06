<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update sort order for menu categories
        // Based on the desired order: BURGERS (1), COMBO MEALS (2), CHICKEN WINGS (3), SIDES (4), DRINKS (5)
        
        DB::table('item_categories')->where('slug', 'burgers')->update(['sort' => 1]);
        DB::table('item_categories')->where('slug', 'combo-meals')->update(['sort' => 2]);
        DB::table('item_categories')->where('slug', 'chicken-wings')->update(['sort' => 3]);
        DB::table('item_categories')->where('slug', 'sides')->update(['sort' => 4]);
        DB::table('item_categories')->where('slug', 'drinks')->update(['sort' => 5]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert to original sort order if needed
        DB::table('item_categories')->where('slug', 'burgers')->update(['sort' => 1]);
        DB::table('item_categories')->where('slug', 'combo-meals')->update(['sort' => 2]);
        DB::table('item_categories')->where('slug', 'sides')->update(['sort' => 3]);
        DB::table('item_categories')->where('slug', 'drinks')->update(['sort' => 4]);
        DB::table('item_categories')->where('slug', 'chicken-wings')->update(['sort' => 5]);
    }
};
