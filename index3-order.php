<?php

require __DIR__ . '/vendor/autoload.php';

// Your ZeyOS URL here
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
	$order = null;
	if (isset($_POST['checkout'])) {
		// Initialize the checkout process

		// Prepare the cart and reserve the transaction number
		$req = new REST\Client($url);
		$json = $req->post([
			'items' => $_POST['checkout']
		]);
		$trans = parseResult($json);

		// ---- Payment, etc. will happen here ----

		// Create the transaction
		$req = new REST\Client($url . $trans['transactionnum']);
		$json = $req->post([
			'items' => $trans['items']
		]);

		$order = parseResult($json);
	} 

	// Retrieve the items
	$req = new REST\Client($url);
	$json = $req->get();
	$items = parseResult($json);

	$viewData = [
		'baseurl' => $url,
		'items'   => $items,
		'order'   => $order
	];

} catch (Exception $e) {
	$viewTemplate = 'error';
	$viewData = $e;
}

$m = new Mustache_Engine;
header('Content-type: text/html');
echo $m->render(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $viewTemplate . '.mustache'), $viewData);
