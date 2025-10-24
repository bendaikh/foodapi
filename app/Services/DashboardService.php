<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\Item;
use App\Models\User;
use App\Models\Order;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Http\Request;
use App\Libraries\AppLibrary;
use App\Enums\Role as EnumRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Libraries\QueryExceptionLibrary;

class DashboardService
{
    /**
     * @throws Exception
     */
    public function orderStatistics(Request $request)
    {
        try {
            if ($request->first_date && $request->last_date) {
                $first_date = Date('Y-m-d', strtotime($request->first_date));
                $last_date  = Date('Y-m-d', strtotime($request->last_date));
            } else {
                $first_date = Carbon::today()->toDateString();
                $last_date  = Carbon::today()->toDateString();
            }

            $cacheKey = 'dashboard_order_statistics_' . $first_date . '_' . $last_date;
            
            return Cache::remember($cacheKey, 300, function () use ($first_date, $last_date) {
                // Optimize by using a single query with groupBy instead of multiple queries
                $statusCounts = Order::whereDate('order_datetime', '>=', $first_date)
                    ->whereDate('order_datetime', '<=', $last_date)
                    ->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray();

                $orderStatisticsArray = [];
                $orderStatisticsArray["total_order"]            = array_sum($statusCounts);
                $orderStatisticsArray["pending_order"]          = $statusCounts[OrderStatus::PENDING] ?? 0;
                $orderStatisticsArray["accept_order"]           = $statusCounts[OrderStatus::ACCEPTED] ?? 0;
                $orderStatisticsArray["preparing_order"]        = $statusCounts[OrderStatus::PREPARING] ?? 0;
                $orderStatisticsArray["prepared_order"]         = $statusCounts[OrderStatus::PREPARED] ?? 0;
                $orderStatisticsArray["out_for_delivery_order"] = $statusCounts[OrderStatus::OUT_FOR_DELIVERY] ?? 0;
                $orderStatisticsArray["delivered_order"]        = $statusCounts[OrderStatus::DELIVERED] ?? 0;
                $orderStatisticsArray["canceled_order"]         = $statusCounts[OrderStatus::CANCELED] ?? 0;
                $orderStatisticsArray["returned_order"]         = $statusCounts[OrderStatus::RETURNED] ?? 0;
                $orderStatisticsArray["rejected_order"]         = $statusCounts[OrderStatus::REJECTED] ?? 0;

                return $orderStatisticsArray;
            });
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }


    public function orderSummary(Request $request)
    {
        try {
            if ($request->first_date && $request->last_date) {
                $first_date = Date('Y-m-d', strtotime($request->first_date));
                $last_date  = Date('Y-m-d', strtotime($request->last_date));
            } else {
                $first_date = Date('Y-m-01', strtotime(Carbon::today()->toDateString()));
                $last_date  = Date('Y-m-t', strtotime(Carbon::today()->toDateString()));
            }

            $cacheKey = 'dashboard_order_summary_' . $first_date . '_' . $last_date;
            
            return Cache::remember($cacheKey, 300, function () use ($first_date, $last_date) {
                // Optimize: single query with groupBy
                $statusCounts = Order::whereDate('order_datetime', '>=', $first_date)
                    ->whereDate('order_datetime', '<=', $last_date)
                    ->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray();

                $total_order = array_sum($statusCounts);
                $total_delivered = $statusCounts[OrderStatus::DELIVERED] ?? 0;
                $total_canceled = $statusCounts[OrderStatus::CANCELED] ?? 0;
                $total_returned = $statusCounts[OrderStatus::RETURNED] ?? 0;
                $total_rejected = $statusCounts[OrderStatus::REJECTED] ?? 0;

                $orderSummaryArray = [];
                if ($total_order > 0) {
                    $orderSummaryArray["delivered"] = (int) round(($total_delivered * 100) / $total_order);
                    $orderSummaryArray["returned"]  = (int) round(($total_returned * 100) / $total_order);
                    $orderSummaryArray["canceled"] = (int) round(($total_canceled * 100) / $total_order);
                    $orderSummaryArray["rejected"] = (int) round(($total_rejected * 100) / $total_order);
                } else {
                    $orderSummaryArray["delivered"] = 0;
                    $orderSummaryArray["returned"]  = 0;
                    $orderSummaryArray["canceled"] = 0;
                    $orderSummaryArray["rejected"] = 0;
                }

                return $orderSummaryArray;
            });
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function salesSummary(Request $request)
    {
        $order = new Order;
        if ($request->first_date && $request->last_date) {
            $first_date = Date('Y-m-d', strtotime($request->first_date));
            $last_date  = Date('Y-m-d', strtotime($request->last_date));
        } else {
            $first_date = Date('Y-m-01', strtotime(Carbon::today()->toDateString()));
            $last_date  = Date('Y-m-t', strtotime(Carbon::today()->toDateString()));
        }

        $date = date_diff(date_create($first_date), date_create($last_date), false);
        $date_diff = (int)$date->format("%a");

        $total_sales     = AppLibrary::flatAmountFormat($order->whereDate('order_datetime', '>=', $first_date)->whereDate('order_datetime', '<=', $last_date)->where('payment_status', PaymentStatus::PAID)->sum('total'));

        $dateRangeArray = [];
        for ($currentDate = strtotime($first_date); $currentDate <= strtotime($last_date); $currentDate += (86400)) {

            $date = date('Y-m-d', $currentDate);
            $dateRangeArray[] = $date;
        }

        $dateRangeValueArray = [];
        for ($i = 0; $i <= count($dateRangeArray) - 1; $i++) {
            $per_day     = AppLibrary::flatAmountFormat($order->whereDate('order_datetime', $dateRangeArray[$i])->where('payment_status', PaymentStatus::PAID)->sum('total'));
            $dateRangeValueArray[] = floatval($per_day);
        }


        $salesSummaryArray = [];
        if ($date_diff > 0) {
            $salesSummaryArray['total_sales']   = AppLibrary::currencyAmountFormat($total_sales);
            $salesSummaryArray['avg_per_day']   = AppLibrary::currencyAmountFormat($total_sales / $date_diff);
            $salesSummaryArray['per_day_sales'] = $dateRangeValueArray;
        } else {
            $salesSummaryArray['total_sales']   = AppLibrary::currencyAmountFormat($total_sales);
            $salesSummaryArray['avg_per_day']   = AppLibrary::currencyAmountFormat($total_sales);
            $salesSummaryArray['per_day_sales'] = $dateRangeValueArray;
        }

        return $salesSummaryArray;
    }

    public function customerStates(Request $request)
    {
        $order = new Order;
        if ($request->first_date && $request->last_date) {
            $first_date = Date('Y-m-d', strtotime($request->first_date));
            $last_date  = Date('Y-m-d', strtotime($request->last_date));
        } else {
            $first_date = Date('Y-m-01', strtotime(Carbon::today()->toDateString()));
            $last_date  = Date('Y-m-t', strtotime(Carbon::today()->toDateString()));
        }

        $timeArray = ["06:00", "07:00", "08:00", "09:00", "10:00", "11:00", "12:00", "13:00", "14:00", "15:00", "16:00", "17:00", "18:00", "19:00", "20:00", "21:00", "22:00", "23:00"];

        $customerSateArray = [];
        $totalCustomerArray = [];
        $first_time = "";
        $last_time = "";
        for ($i = 0; $i <= count($timeArray) - 1; $i++) {
            $first_time = date('H:i', strtotime($timeArray[$i]));
            $last_time = date('H:i', strtotime($timeArray[$i] . ' +59 minutes'));

            $total_customer     = $order->whereDate('order_datetime', '>=', $first_date)->whereDate('order_datetime', '<=', $last_date)->whereTime('order_datetime', '>=', Carbon::parse($first_time))->whereTime('order_datetime', '<=', Carbon::parse($last_time))->get()->count();
            $totalCustomerArray[] = $total_customer;
        }

        $customerSateArray['total_customers'] = $totalCustomerArray;
        $customerSateArray['times'] = $timeArray;

        return $customerSateArray;
    }

    public function topCustomers()
    {
        try {
            return User::withCount('orders')->orderBy('orders_count', 'desc')->role(EnumRole::CUSTOMER)->limit(8)->get();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function totalSales()
    {
        try {
            return Cache::remember('dashboard_total_sales', 300, function () {
                return Order::where('payment_status', PaymentStatus::PAID)->sum('total');
            });
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function totalOrders()
    {
        try {
            return Cache::remember('dashboard_total_orders', 300, function () {
                return Order::where('status', OrderStatus::DELIVERED)->count();
            });
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function totalCustomers()
    {
        try {
            return Cache::remember('dashboard_total_customers', 300, function () {
                return User::role(EnumRole::CUSTOMER)->count();
            });
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function totalMenuItems()
    {
        try {
            return Cache::remember('dashboard_total_menu_items', 300, function () {
                return Item::count();
            });
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * Get all overview stats in a single call (optimized for performance)
     */
    public function getOverviewStats()
    {
        try {
            return Cache::remember('dashboard_overview_stats', 300, function () {
                // Single query to get both sales and orders count
                $orderStats = Order::selectRaw('
                    SUM(CASE WHEN payment_status = ? THEN total ELSE 0 END) as total_sales,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_orders
                ', [PaymentStatus::PAID, OrderStatus::DELIVERED])
                ->first();

                // Get customers and menu items count in parallel queries
                $totalCustomers = User::role(EnumRole::CUSTOMER)->count();
                $totalMenuItems = Item::count();

                return [
                    'total_sales' => AppLibrary::currencyAmountFormat($orderStats->total_sales ?? 0),
                    'total_orders' => $orderStats->total_orders ?? 0,
                    'total_customers' => $totalCustomers,
                    'total_menu_items' => $totalMenuItems
                ];
            });
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }
}
