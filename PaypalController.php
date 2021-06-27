<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Redirect;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

class PaypalController extends Controller
{

    public function checkout()
    {
        // Creating an environment
        $clientId = env('PAYPAL_CLIENT_ID');
        $clientSecret = env('PAYPAL_CLIENT_SECRET');

        if (get_setting('paypal_sandbox') == 1) {
            $environment = new SandboxEnvironment($clientId, $clientSecret);
        }
        else {
            $environment = new ProductionEnvironment($clientId, $clientSecret);
        }
        $client = new PayPalHttpClient($environment);


        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
                             "intent" => "CAPTURE",
                             "purchase_units" => [[
                                 "reference_id" => rand(000000,999999),
                                 "amount" => [
                                     "value" => number_format($amount, 2, '.', ''),
                                     "currency_code" => \App\Currency::findOrFail(get_setting('system_default_currency'))->code
                                 ]
                             ]],
                             "application_context" => [
                                  "cancel_url" => url('paypal/payment/cancel'),
                                  "return_url" => url('paypal/payment/success')
                             ]
                         ];

        try {
            // Call API with your client and get a response for your call
            $response = $client->execute($request);
            // If call returns body in response, you can get the deserialized version from the result attribute of the response
            return Redirect::to($response->result->links[1]->href);
        }catch (HttpException $ex) {

        }
    }


    public function cancel(Request $request)
    {
        $request->session()->forget('order_id');
    	return redirect()->route('dashboard');
    }

    public function success(Request $request)
    {
        //dd($request->all());
        // Creating an environment
        $clientId = env('PAYPAL_CLIENT_ID');
        $clientSecret = env('PAYPAL_CLIENT_SECRET');

        if (get_setting('paypal_sandbox') == 1) {
            $environment = new SandboxEnvironment($clientId, $clientSecret);
        }
        else {
            $environment = new ProductionEnvironment($clientId, $clientSecret);
        }
        $client = new PayPalHttpClient($environment);

        // $response->result->id gives the orderId of the order created above

        $ordersCaptureRequest = new OrdersCaptureRequest($request->token);
        $ordersCaptureRequest->prefer('return=representation');
        try {
            // Call API with your client and get a response for your call
            $response = $client->execute($ordersCaptureRequest);

            // If call returns body in response, you can get the deserialized version from the result attribute of the response
        }catch (HttpException $ex) {

        }
    }
}
