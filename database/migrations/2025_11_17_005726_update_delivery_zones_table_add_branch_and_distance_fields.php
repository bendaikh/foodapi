<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add branch_id column if it doesn't exist (nullable first, then we'll set values)
        if (!Schema::hasColumn('delivery_zones', 'branch_id')) {
            Schema::table('delivery_zones', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('id');
            });
            
            // Get the first branch or default branch to assign to existing records
            $defaultBranch = \DB::table('branches')->orderBy('id')->first();
            if ($defaultBranch) {
                // Update existing records to use the first branch
                \DB::table('delivery_zones')->whereNull('branch_id')->update(['branch_id' => $defaultBranch->id]);
            } else {
                // If no branches exist, delete existing delivery zones or set to a placeholder
                // For safety, we'll just delete them
                \DB::table('delivery_zones')->delete();
            }
            
            // Now make branch_id required and add foreign key using raw SQL
            \DB::statement('ALTER TABLE `delivery_zones` MODIFY `branch_id` BIGINT UNSIGNED NOT NULL');
            Schema::table('delivery_zones', function (Blueprint $table) {
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            });
        }
        
        // Rename zone_name to name using raw SQL (MySQL doesn't support renameColumn in all versions)
        if (Schema::hasColumn('delivery_zones', 'zone_name') && !Schema::hasColumn('delivery_zones', 'name')) {
            \DB::statement('ALTER TABLE `delivery_zones` CHANGE `zone_name` `name` VARCHAR(190) NULL');
        } elseif (!Schema::hasColumn('delivery_zones', 'name')) {
            Schema::table('delivery_zones', function (Blueprint $table) {
                $table->string('name')->nullable()->after('branch_id');
            });
        }
        
        // Add other columns
        Schema::table('delivery_zones', function (Blueprint $table) {
            // Add max_distance_km column if it doesn't exist
            if (!Schema::hasColumn('delivery_zones', 'max_distance_km')) {
                $table->decimal('max_distance_km', 8, 2)->after('name');
            }
            
            // Add sort_order column if it doesn't exist
            if (!Schema::hasColumn('delivery_zones', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('delivery_price');
            }
            
            // Add creator and editor columns if they don't exist
            if (!Schema::hasColumn('delivery_zones', 'creator_type')) {
                $table->string('creator_type')->nullable()->after('status');
            }
            if (!Schema::hasColumn('delivery_zones', 'creator_id')) {
                $table->bigInteger('creator_id')->nullable()->after('creator_type');
            }
            if (!Schema::hasColumn('delivery_zones', 'editor_type')) {
                $table->string('editor_type')->nullable()->after('creator_id');
            }
            if (!Schema::hasColumn('delivery_zones', 'editor_id')) {
                $table->bigInteger('editor_id')->nullable()->after('editor_type');
            }
        });
        
        // Add index separately to avoid issues
        try {
            $indexes = \DB::select("SHOW INDEX FROM delivery_zones WHERE Key_name = 'delivery_zones_branch_id_status_sort_order_index'");
            if (empty($indexes)) {
                Schema::table('delivery_zones', function (Blueprint $table) {
                    $table->index(['branch_id', 'status', 'sort_order'], 'delivery_zones_branch_id_status_sort_order_index');
                });
            }
        } catch (\Exception $e) {
            // If branch_id doesn't exist yet, index will fail - that's okay
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            // Drop index if exists
            try {
                $table->dropIndex('delivery_zones_branch_id_status_sort_order_index');
            } catch (\Exception $e) {
                // Index doesn't exist, ignore
            }
            
            // Drop columns
            $columnsToDrop = ['editor_id', 'editor_type', 'creator_id', 'creator_type', 'sort_order', 'max_distance_km'];
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('delivery_zones', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Drop foreign key and branch_id column
            if (Schema::hasColumn('delivery_zones', 'branch_id')) {
                try {
                    $table->dropForeign(['branch_id']);
                } catch (\Exception $e) {
                    // Foreign key doesn't exist, ignore
                }
                $table->dropColumn('branch_id');
            }
        });
    }
};
