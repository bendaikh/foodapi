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
        // Update existing order serial numbers to new format (000001, 000002, etc.)
        $orders = DB::table('orders')->orderBy('id')->get();
        
        foreach ($orders as $order) {
            DB::table('orders')
                ->where('id', $order->id)
                ->update(['order_serial_no' => str_pad($order->id, 6, '0', STR_PAD_LEFT)]);
        }
        
        // Reset auto_increment to 1
        DB::statement('ALTER TABLE orders AUTO_INCREMENT = 1');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert order serial numbers to old format (date-based)
        $orders = DB::table('orders')->orderBy('id')->get();
        
        foreach ($orders as $order) {
            $createdDate = date('dmy', strtotime($order->created_at));
            DB::table('orders')
                ->where('id', $order->id)
                ->update(['order_serial_no' => $createdDate . $order->id]);
        }
    }
};
