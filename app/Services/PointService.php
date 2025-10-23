<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\FrontendOrder;
use App\Models\OrderPointDiscount;
use Smartisan\Settings\Facades\Settings;
use Illuminate\Support\Facades\Auth;
use App\Models\PointHistory;
use App\Models\User;
use App\Enums\Ask;
use Exception;

class PointService
{

    public function userPointsChecking()
    {
        try {
            $site = Settings::group('site')->all();
            $user = Auth::user();
            $info = [
                'is_point_applicable' => false,
                'user_points' => $user->points,
                'applicable_points' => 0,
                'point_discount_amount' => 0,
            ];
            if ($site['site_point_setup'] == Ask::YES && $user) {

                // if already applied points and the order is pending : 
                $pending_applied_points = FrontendOrder::where([
                    'user_id' => $user->id,
                    'status' => OrderStatus::PENDING,
                    'active' => Ask::YES
                ])
                    ->with('pointDiscount')
                    ->get()
                    ->sum(function ($order) {
                        return $order->pointDiscount ? $order->pointDiscount->applied_points : 0;
                    });


                $user_points = $user->points - $pending_applied_points;
                $info['user_points'] = $user_points;
                $point_setup = Settings::group('point_setup')->all();

                // if user's point is more than or equal of minimum applicable point
                $info['is_point_applicable'] = (int) $point_setup['point_setup_minimum_applicable_points_for_each_order'] <= $user_points && $user_points > 0;

                // applicable points
                if ((int) $point_setup['point_setup_maximum_applicable_points_for_each_order'] <= $user_points) {
                    $info['applicable_points'] = (int) $point_setup['point_setup_maximum_applicable_points_for_each_order'];
                } else if (
                    (int) $point_setup['point_setup_minimum_applicable_points_for_each_order'] <= $user_points
                    && (int) $point_setup['point_setup_maximum_applicable_points_for_each_order'] > $user_points
                ) {
                    $info['applicable_points'] = $user_points;
                }

                // point_discount_amount 
                if ($info['applicable_points'] > 0) {
                    $info['point_discount_amount'] = $info['applicable_points'] / $point_setup['point_setup_points_for_each_currency'];
                }

            }

            return (object) $info;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage(), 422);
        }
    }

    public function calculatePoints($order)
    {
        try {


            $user = User::find($order->user_id);
            $site = Settings::group('site')->all();

            // gift points
            if ($site['site_point_setup'] == Ask::YES) {
                $point_setup = Settings::group('point_setup')->all();
                $total_points = ceil($order->subtotal * $point_setup['point_setup_each_currency_to_points']);

                PointHistory::create([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'points' => $total_points
                ]);

                if ($user) {
                    $user->points = $user->points + $total_points;
                    $user->save();
                }
            }

            // decrease points if applied for discount . 
            $points_discount = OrderPointDiscount::where(['user_id' => $order->user_id, 'order_id' => $order->id])->first();
            if ($points_discount) {
                if ($user) {
                    $user->points = $user->points - $points_discount->applied_points;
                    $user->save();
                }
            }

            return '';

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage(), 422);
        }
    }

    public function returnPoints($order)
    {
        try {
            $user = User::find($order->user_id);
            if ($user) {
                //Remove gift points
                $reward = PointHistory::where(['user_id' => $order->user_id, 'order_id' => $order->id])->first();
                if ($reward) {
                    $user->points -= $reward->points;
                    $user->save();

                    $reward->delete();
                }
            }

            return '';

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage(), 422);
        }
    }
}
