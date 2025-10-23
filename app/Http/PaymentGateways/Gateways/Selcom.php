<?php

namespace App\Http\PaymentGateways\Gateways;

use Exception;
use App\Enums\Activity;
use App\Enums\GatewayMode;
use App\Models\PaymentGateway;
use App\Services\PaymentService;
use App\Services\PaymentAbstract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Selcom\ApigwClient\Client;



class Selcom extends PaymentAbstract
{
    protected  $selcom;
    protected string $baseUrl;

    public function __construct()
    {
        $paymentService = new PaymentService();
        parent::__construct($paymentService);

        $this->paymentGateway = PaymentGateway::with('gatewayOptions')->where(['slug' => 'selcom'])->first();

        if (!blank($this->paymentGateway)) {
            $this->paymentGatewayOption = $this->paymentGateway->gatewayOptions->pluck('value', 'option');

            $mode = $this->paymentGatewayOption['selcom_mode'] ?? GatewayMode::SANDBOX;

            $this->baseUrl = $mode === GatewayMode::SANDBOX  ? 'https://apigwtest.selcommobile.com' : 'https://apigw.selcommobile.com';
            $this->selcom = new Client(
            $this->baseUrl,
            $this->paymentGatewayOption['selcom_api_key'] ?? '',
            $this->paymentGatewayOption['selcom_client_secret'] ?? '');
        }
    }

    /**
     * Initiate Selcom Payment
     */
    public function payment($order, $request)
    {   
        try {
            $orderArray = array(
                "vendor"           => $this->paymentGatewayOption['selcom_client_id'] ?? '',
                "order_id"         => $order->order_serial_no,
                "buyer_email"      => $order->user->email ?? '',
                "buyer_name"       => $order->user->name ?? '',
                "buyer_phone"      => $order->user->phone ?? '',
                "amount"           => $order->total,
                "currency"         => "TZS",
                "buyer_remarks"    => "None",
                "merchant_remarks" => "None",
                "no_of_items"      => $order->orderItems->count(),

                "redirect_url"     => base64_encode(route('payment.success', ['order' => $order, 'paymentGateway' => 'selcom'])),
                "cancel_url"       => base64_encode(route('payment.cancel', ['order' => $order, 'paymentGateway' => 'selcom'])),
            );
            $orderPath  = "/v1/checkout/create-order-minimal";
            $response   = $this->selcom->postFunc($orderPath, $orderArray);
            
            if ($response["result"] !== "SUCCESS") {
                return redirect()->back()->with('error', $response["message"]);
            } else {
                $gatewayUrl = base64_decode($response['data'][0]['payment_gateway_url']);
                return redirect()->away($gatewayUrl);
            }
            
        } catch (Exception $e) {
            Log::error('Selcom Payment Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }


    public function success($order, $request)
    {
        try {
            $mode         = $this->paymentGatewayOption['selcom_mode'] ?? GatewayMode::SANDBOX;
            $baseUrl      = $mode === GatewayMode::SANDBOX
                ? 'https://apigwtest.selcommobile.com'
                : 'https://apigw.selcommobile.com';

            $clientId     = $this->paymentGatewayOption['selcom_client_id'] ?? '';
            $apiKey       = $this->paymentGatewayOption['selcom_api_key'] ?? '';

            $client = new Client($baseUrl, $clientId, $apiKey);

            $statusPath = "/v1/checkout/order-status";
            $response   = $client->getFunc($statusPath, ["order_id" => $order->order_serial_no]);

            if (!$response || !isset($response['result']) || $response['result'] !== 'SUCCESS') {
                return redirect()->route('payment.fail', ['order'          => $order, 'paymentGateway' => 'selcom'])->with('error', 'Payment validation failed');
            }

            $statusData = $response['data'][0] ?? null;

            if ($statusData && strtoupper($statusData['payment_status']) === 'COMPLETED') {
                $this->paymentService->payment($order, 'selcom', $statusData['transid']);
                return redirect()->route('payment.successful', ['order' => $order])->with('success', trans('all.message.payment_successful'));
            }
            return redirect()->route('payment.fail', ['order'          => $order, 'paymentGateway' => 'selcom'])->with('error', 'Payment not completed');
        } catch (Exception $e) {
            return redirect()->route('payment.fail', ['order'          => $order, 'paymentGateway' => 'selcom'])->with('error', $e->getMessage());
        }
    }

    public function fail($order, $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('payment.index', ['order' => $order])
            ->with('error', trans('all.message.something_wrong'));
    }

    public function cancel($order, $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('home')
            ->with('error', trans('all.message.payment_canceled'));
    }

    public function status(): bool
    {
        return !blank(PaymentGateway::where([
            'slug'   => 'selcom',
            'status' => Activity::ENABLE
        ])->first());
    }
}
