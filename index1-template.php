<?php

require __DIR__ . '/vendor/autoload.php';

$viewTemplate = basename(__FILE__, '.php');

$viewData = [
		'items' => [
			[
				'ID' => null,
				'name' => 'Test 1',
				'itemnum' => '2341234',
				'sellingprice' => 14.56,
				'taxrate' => 19
			],
			[
				'ID' => null,
				'name' => 'Test 2',
				'itemnum' => '6468546',
				'sellingprice' => 9.12,
				'regularprice' => 20.33,
				'taxrate' => 19
			],
			[
				'ID' => null,
				'name' => 'Test 3',
				'itemnum' => '5344534',
				'sellingprice' => 50.12,
				'taxrate' => 19
			]
		]
	];

$m = new Mustache_Engine;
header('Content-type: text/html');
echo $m->render(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $viewTemplate . '.mustache'), $viewData);

