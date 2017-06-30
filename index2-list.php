<?php

require __DIR__ . '/vendor/autoload.php';

// Your ZeyOS URL here
$url = 'https://cloud.zeyos.com/pepe/remotecall/ppcheckout/';
$viewTemplate = basename(__FILE__, '.php');

try {

	$req = new REST\Client($url);

	// Retrieve the items
	$json = $req->get();

	// Decode the result
	$res  = json_decode($json, true);

	// Check if there's an error
	if (is_array($res) && isset($res['error'])) {
		throw new Exception('Server Error: ' . $res['error']);
	} elseif (!isset($res['result'])) {
		throw new Exception('Invalid server response: ' . $json);
	}

	$viewData = [
		'baseurl' => $url,
		'items' => $res['result']
	];

} catch (Exception $e) {
	$viewTemplate = 'error';
	$viewData = $e;
}

$m = new Mustache_Engine;
header('Content-type: text/html');
echo $m->render(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $viewTemplate . '.mustache'), $viewData);
