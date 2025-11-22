<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('name')->nullable(); // Optional zone name (e.g., "Zone 1", "Zone 2")
            $table->decimal('max_distance_km', 8, 2); // Maximum distance in kilometers for this zone
            $table->decimal('delivery_price', 13, 6); // Delivery price for this zone
            $table->integer('sort_order')->default(0); // To order zones (e.g., 0-5km, 5-10km)
            $table->unsignedTinyInteger('status')->default(\App\Enums\Status::ACTIVE)->comment(\App\Enums\Status::ACTIVE.'='.trans('statuse.'.\App\Enums\Status::ACTIVE).', ' .\App\Enums\Status::INACTIVE.'='.trans('statuse.'.\App\Enums\Status::INACTIVE));
            $table->string('creator_type')->nullable();
            $table->bigInteger('creator_id')->nullable();
            $table->string('editor_type')->nullable();
            $table->bigInteger('editor_id')->nullable();
            $table->timestamps();
            
            // Index for faster queries
            $table->index(['branch_id', 'status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};


