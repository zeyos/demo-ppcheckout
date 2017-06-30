<?php

require __DIR__ . '/vendor/autoload.php';
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\ExecutePayment;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

// Your ZeyOS URL here
$redirectUrl = 'http://localhost/ppcheckout/index4-checkout.php';
$url = 'https://cloud.zeyos.com/pepe/remotecall/ppcheckout/';
$viewTemplate = basename(__FILE__, '.php');

/**
 * Decode the result and check for errors
 */
function parseResult($json) {
	$res  = json_decode($json, true);

	// Check if there's an error
	if (is_array($res) && isset($res['error'])) {
		throw new Exception('Server Error: ' . $res['error']);
	} elseif (!isset($res['result'])) {
		throw new Exception('Invalid server response: ' . $json);
	}

	return $res['result'];
}

try {

	function ppApiContext() {
		$apiContext = new ApiContext(
			new OAuthTokenCredential(
				'AdSMlBWtr6TOBVp9w4TeXOnAS34u-e4My1GiubeQrPUPQpWPLzwW8vohKr6UWXl2YdcQBvBj0qTz3ZSz', // clientId
				'EFujFxWs0YlvoNhwzLSrgBT2KNi0XNYoqvYmkr_qaeudMLDE-oZJmwgXCLfMABjeoJvlsrjyDzq4DL0h' // clientSecret
			)
		);

		$apiContext->setConfig([
			'mode' => 'sandbox',
			'log.LogEnabled' => true,
			'log.FileName' => 'paypal.log',
			'log.LogLevel' => 'DEBUG', // PLEASE USE `INFO` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
			'cache.enabled' => true,
			// 'http.CURLOPT_CONNECTTIMEOUT' => 30
			// 'http.headers.PayPal-Partner-Attribution-Id' => '123123123'
			//'log.AdapterFactory' => '\PayPal\Log\DefaultLogFactory' // Factory class implementing \PayPal\Log\PayPalLogFactory
		]);

		return $apiContext;
	}

	if (isset($_REQUEST['checkout'])) {
		session_start();

		// Prepare the cart and reserve the transaction number
		$req = new REST\Client($url);
		$json = $req->post([
			'items' => $_POST['checkout']
		]);
		$trans = parseResult($json);

		// Initialize PayPal
		$payer = new Payer();
		$payer->setPaymentMethod('paypal');

		$items = [];
		foreach ($trans['items'] as $item) {
			// Create the PayPal Item
			$ppItem = new PayPal\Api\Item();
			$ppItem->setName($item['name'])
			       ->setCurrency('EUR')
			       ->setQuantity(1)
			       ->setSku($item['itemnum'])
			       ->setPrice($item['sellingprice']);

			$items[] = $ppItem;
		}

		$itemList = new ItemList();
		$itemList->setItems($items);

		$shipping = 0;
		$tax      = $trans['tax'];
		$netprice = $trans['netamount'];

		// ### Additional payment details
		// Use this optional field to set additional
		// payment information such as tax, shipping
		// charges etc.
		$details = new Details();
		$details->setShipping($shipping)
		        ->setTax($tax)
		        ->setSubtotal($netprice);

		// ### Amount
		// Lets you specify a payment amount.
		// You can also specify additional details
		// such as shipping, tax.
		$amount = new Amount();
		$amount->setCurrency('EUR')
		    ->setTotal($tax + $netprice + $shipping)
		    ->setDetails($details);

		// ### Transaction
		// A transaction defines the contract of a
		// payment - what is the payment for and who
		// is fulfilling it.
		$transaction = new Transaction();
		$transaction->setAmount($amount)
		    ->setItemList($itemList)
		    ->setDescription('PayPal Demo') // Your Shop Name here
		    ->setInvoiceNumber($trans['transactionnum']);

		// ### Redirect urls
		// Set the urls that the buyer must be redirected to after 
		// payment approval/ cancellation.
		$redirectUrls = new RedirectUrls();
		$redirectUrls->setReturnUrl($redirectUrl . '?success=true&transactionnum=' . urlencode($trans['transactionnum']))
		    ->setCancelUrl($redirectUrl . '?success=false');

		// ### Payment
		// A Payment Resource; create one using
		// the above types and intent set to 'sale'
		$payment = new Payment();
		$payment->setIntent('sale')
		    ->setPayer($payer)
		    ->setRedirectUrls($redirectUrls)
		    ->setTransactions([ $transaction ]);

		$apiContext = ppApiContext();
		$payment->create($apiContext);
		header('Location: ' . $payment->getApprovalLink());
		$_SESSION[$trans['transactionnum']] = $trans;

	} elseif (isset($_GET['paymentId']) && isset($_GET['transactionnum'])) {
		session_start();

		if (isset($_GET['success'])) {
			if (!isset($_SESSION[$_GET['transactionnum']])) {
				throw new Exception('No payment session found');
			}

			$apiContext = ppApiContext();
			$order = $_SESSION[$_GET['transactionnum']];

			$paymentId = $_GET['paymentId'];
			$payment = Payment::get($paymentId, $apiContext);

			$shipping = 0;
			$tax      = $order['tax'];
			$netprice = $order['netamount'];

			$details = new Details();
			$details->setShipping($shipping)
			        ->setTax($tax)
			        ->setSubtotal($netprice);

			$amount = new Amount();
			$amount->setCurrency('EUR')
			       ->setTotal($tax + $netprice + $shipping)
			       ->setDetails($details);

			$transaction = new Transaction();
			$transaction->setAmount($amount);

			$execution = new PaymentExecution();
			$execution->setPayerId($_GET['PayerID']);
			$execution->addTransaction($transaction);

			$result = $payment->execute($execution, $apiContext);

			$req = new REST\Client($url . $order['transactionnum']);
			$json = $req->post([
				'items' => $order['items']
			]);

			$order = parseResult($json);
		} else {
			throw new Exception('User Cancelled the Approval');
		}
	}

	// Retrieve the items
	$req = new REST\Client($url);
	$json = $req->get();
	$items = parseResult($json);

	$viewData = [
		'baseurl' => $url,
		'items'   => $items,
		'order'   => isset($order) ? $order : null
	];

} catch (Exception $e) {
	$viewTemplate = 'error';
	$viewData = $e;
}

$m = new Mustache_Engine;
header('Content-type: text/html');
echo $m->render(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $viewTemplate . '.mustache'), $viewData);
