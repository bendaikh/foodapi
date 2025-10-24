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
        // Add indexes to orders table for better query performance
        Schema::table('orders', function (Blueprint $table) {
            $table->index('status', 'orders_status_index');
            $table->index('payment_status', 'orders_payment_status_index');
            $table->index('order_datetime', 'orders_order_datetime_index');
            $table->index(['status', 'payment_status'], 'orders_status_payment_status_index');
            $table->index(['order_datetime', 'status'], 'orders_datetime_status_index');
        });

        // Add indexes to items table for better query performance
        Schema::table('items', function (Blueprint $table) {
            $table->index('is_featured', 'items_is_featured_index');
            $table->index('status', 'items_status_index');
            $table->index('item_category_id', 'items_category_id_index');
            $table->index(['is_featured', 'status'], 'items_featured_status_index');
            $table->index(['status', 'item_category_id'], 'items_status_category_index');
        });

        // Add index to users table for role queries
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                // Role might be in a pivot table, skip if doesn't exist
            } else {
                $table->index('role', 'users_role_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_index');
            $table->dropIndex('orders_payment_status_index');
            $table->dropIndex('orders_order_datetime_index');
            $table->dropIndex('orders_status_payment_status_index');
            $table->dropIndex('orders_datetime_status_index');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('items_is_featured_index');
            $table->dropIndex('items_status_index');
            $table->dropIndex('items_category_id_index');
            $table->dropIndex('items_featured_status_index');
            $table->dropIndex('items_status_category_index');
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropIndex('users_role_index');
            }
        });
    }
};
