Selling products on the Internet has become a quite viable source of income.

Being able to sell your products as fast as possible is critical to test a market and generate cash flow _before_ you allocate time and money into building a fully-fledged online shop.

A very simple and effective way to start selling your products is using ZeyOS and PayPal in order to offer a simple order process on your website.

In this example we will show you how to add a simple checkout widget to your website. Please note we have put all the sample code on our [GitHub page](https://github.com/zeyosinc/demo-ppcheckout) for you to check later.

**Let's get started!**


## Step 1: Create a product in ZeyOS

Before we switch into coding-mode, let's head over to ZeyOS and create some products. Since we don't want to offer all our products on our shop, we first create a category called "Shop".

![Create a category](//raw.githubusercontent.com/zeyosinc/demo-ppcheckout/master/docs/create-category.gif)

Next, create some demo items and add them to the category:

![Create a new item](//raw.githubusercontent.com/zeyosinc/demo-ppcheckout/master/docs/create-item.gif)


## Step 2: Basic page setup

Next, go to your webserver's htdocs directory and create a new project with [PHP](http://php.net). First, we will need to setup [composer](https://getcomposer.org) in order to load the required libraries (such as PayPal, etc.):

```json
{
  "name": "zeyos/ppcheckout",
  "description": "A simple PayPal checkout for ZeyOS",
  "license": "GPL",
  "require": {
    "php": ">=5.6.0",
    "zeyos/rest": "*",
    "mustache/mustache": "*",
    "paypal/rest-api-sdk-php": "*"
  }
}
```

After adding your `composer.json` run `composer install` in your terminal to install the required packages. (If you haven't installed composer, head over to [getcomposer.org](https://getcomposer.org) for the installation details)

For starts, let's create a basic page layout first. For this example I have used a layout I found on [CodePen](http://codepen.io/littlesnippets/pen/jqEajG).

Based on this layout I created a static website, using [mustache](https://mustache.github.io) as a templating engine:

```php
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
```

```html
<!doctype html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang=""> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8" lang=""> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9" lang=""> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang=""> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

        <title>Simple Shop</title>
        <meta name="description" content="Simple PayPal Checkout for ZeyOS">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
        <link rel="stylesheet" href="main.css">
    </head>
    <body>
        <!--[if lt IE 8]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->

        <h1>My Awesome Shop</h1>
    
        <div class="container">
          <div class="row">
            {{#items}}
              <div class="col-md-4">
                <figure class="cartitem">
                  <img src="{{baseurl}}{{ID}}" alt="{{name}}" />
                  <figcaption>
                    <h3>{{name}}</h3>
                    <p>{{description}}</p>
                    <div class="price">
                      {{#regularprice}}<s>{{regularprice}}</s>{{/regularprice}}
                      {{sellingprice}}
                    </div>
                  </figcaption><i class="ion-android-cart"></i>
                  <a href="?checkout={{ID}}"></a>
                </figure>
              </div>
            {{/items}}
          </div>
        </div>
  </body>
</html>
```

This is the result:

![Static template](//raw.githubusercontent.com/zeyosinc/demo-ppcheckout/master/docs/index1-template.png)


## Step 3: Retrieve items from ZeyOS

At the moment we are rendering the shop with a static array of items - next thing we want to do is list items from ZeyOS. For this purpose we create a new API service in ZeyOS. To do this, go the the Enhancements section and add a new Service called "shop" with type "Remote call". Here's the [iXML](https://www.ixmldev.com) script:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE ixml SYSTEM "http://www.ixmldev.com/schema/ixml.dtd">
<ixml>
	<global var="RES" />
	
	<rest:server>
		<!-- 
			Get the product list
		-->
		<rest:resource route="/" method="GET">
			<try>
				<db:select var_result="RES.result" type="assoc">
					<db:fields>
						<db:field>i.ID</db:field>
						<db:field>i.name</db:field>
						<db:field>i.description</db:field>
						<db:field>i.sellingprice</db:field>
						<db:field>i.taxrate</db:field>
						<db:field>i.picbinfile</db:field>
					</db:fields>
					<db:table alias="i">items</db:table>
					<db:join>
						<db:inner alias="t" table="tags">
							<db:is field="t.entity">items</db:is>
							<db:is field1="t.index" field2="i.ID" />
						</db:inner>
					</db:join>
					<db:is field="t.name">Shop</db:is>
					<db:orderby>
						<db:orderfield>i.name</db:orderfield>
					</db:orderby>
				</db:select>
			<catch var="error">
				<set var="RES.error">$error</set>
			</catch>
			</try>
		</rest:resource>

		<!-- 
			The the product details
			
			@param Int ID	
		-->
		<rest:resource route="/:ID" method="GET">
			<db:get var="RES.result" id="$ID" entity="items" />

			<if value1="$RES.result.ID">
				<error>Item not found: $ID</error>
			</if>
		</rest:resource>

		<!-- 
			The the product image
			
			@param Int ID
		-->
		<rest:resource route="/:ID/_image" method="GET">
			<db:get var="item" id="$ID" entity="items">
			 	<db:field>picbinfile</db:field>
			</db:get>

			<header>Content-type: application/octet-stream</header>

			<!-- Return a placeholder (Upload a new image resource) -->
			<if value1="$item.picbinfile">
				<exit>
					<include id=".placeholder" />
				</exit>
			</if>

			<exit>
				<bin:read id="$item.picbinfile" />
			</exit>
		</rest:resource>
		
		<!--
			Create a new order for the product
		
			@param Array items
			@result Array {transactionnum: String, netamount: Float, tax: Float, items: Array}
		-->
		<rest:resource route="/" method="POST">
			<!-- Check if the request contains any items -->
			<is var="REQUEST.items" type="non-array">
				<error>Parameter items not found. Array expected</error>
			</is>
			
			<!-- Build the items array for the transaction -->
			<array var="RES.result.items" />
			<set var="RES.result.netamount">0</set>
			<set var="RES.result.tax">0</set>
			<foreach var="REQUEST.items" var_value="ID">
				<set var="amount">1</set>
				
				<!-- Check if the item exists -->
				<db:get var="item" id="$ID" entity="items" />
				<if value1="$item.ID">
					<error>Item not found: $ID</error>
				</if>
	
				<!-- Build the items array -->
				<array var="RES.result.items[]">
					<item key="type">0</item>
					<item key="subindex">0</item>
					<item key="name">$item.name</item>
					<item key="manufacturer">$item.manufacturer</item>
					<item key="itemnum">$item.itemnum</item>
					<item key="barcode">$item.barcode</item>
					<item key="itemtype">$item.type</item>
					<item key="unit">$item.unit</item>
					<item key="amount">$amount</item>
					<item key="amounttaken">0</item>
					<item key="sellingprice">$item.sellingprice</item>
					<item key="purchaseprice">0</item>
					<item key="rebate">0</item>
					<item key="discount">0</item>
					<item key="discount2">0</item>
					<item key="taxrate">$item.taxrate</item>
					<item key="weight">$item.weight</item>
					<item key="item">$item.ID</item>
					<array key="transactions" />
					<array key="references" />
				</array>
				
				<!-- Aggregate the net amount -->
				<set var="RES.result.netamount">$($RES.result.netamount + $item.sellingprice * $amount)</set>
				<!-- Calculate the tax -->
				<set var="RES.result.tax">$($RES.result.tax + $item.sellingprice * $amount * $item.taxrate / 100)</set>
			</foreach>
			
			<!-- Get the order number -->
			<numformat:next name="billing_ordernum" var="RES.result.transactionnum" />
		</rest:resource>
		
		<!--
			Create a new order for the product
		
			@param Array items
			@param String transactionnum
			@result Array {ID: Int, netamount: Float, tax: Float, items: Array}
		-->
		<rest:resource route="/:transactionnum" method="POST">
			<db:transaction>
				<set var="RES.result.netamount">0</set>
				<set var="RES.result.tax">0</set>
				<foreach var="REQUEST.items" var_value="item">
					<!-- Aggregate the net amount -->
					<set var="RES.result.netamount">$($RES.result.netamount + $item.sellingprice * $amount)</set>
					<!-- Calculate the tax -->
					<set var="RES.result.tax">$($RES.result.tax + $item.sellingprice * $amount * $item.taxrate / 100)</set>
				</foreach>
				<set var="RES.result.transactionnum">$transactionnum</set>
				
				<!-- Create the transaction -->
				<!-- See http://schema.zeyos.com/tables/transactions.html for Schema details --> 
				<db:set entity="transactions" var="RES.result.ID">
					<db:data field="transactionnum">$transactionnum</db:data>
					<db:data field="date">$DATENOW</db:data>
					<!-- <db:data field="account"></db:data> -->
					<db:data field="type">1</db:data>
					<db:data field="status">0</db:data>
					<db:data field="currency">EUR</db:data>
					<db:data field="netamount">$RES.result.netamount</db:data>
					<db:data field="tax">$RES.result.tax</db:data>
					<db:data field="items">
						<encode:json var="REQUEST.items" />
					</db:data>
				</db:set>
			</db:transaction>
		</rest:resource>
	</rest:server>

	<header>Content-type: application/json</header>
	<output>
		<encode:json var="RES" />
	</output>
</ixml>
```

Configure this script as a **remote call service**:

![Shop API](//raw.githubusercontent.com/zeyosinc/demo-ppcheckout/master/docs/shop-api.png)

This REST service does three things:

1. It returns a list of all items that are tagged with the categrory "Shop" as a JSON string. 
2. It displays the image of the item
3. It creates a new order for a selected item

You can test the request yourself if you simply call the API URL in your web browser

![API Result](//raw.githubusercontent.com/zeyosinc/demo-ppcheckout/master/docs/json-result.png)

Let's integrate this service into our existing PHP script:

```php
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

```

We will now get our list of items in the shop:

![Shop Result](//raw.githubusercontent.com/zeyosinc/demo-ppcheckout/master/docs/shop-result.png)


## Step 4: Place the order in ZeyOS

Before we deal with PayPal's checkout and payment process, let's have a look at the order creation process in ZeyOS first. For this purpose, the next version will enable the user to order a product without payment:


```php
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
```


## Step 5: Integration PayPal Express Checkout

We will use [PayPal's PHP SDK](https://github.com/paypal/PayPal-PHP-SDK/blob/master/sample/payments/CreatePaymentUsingPayPal.php) to initialize the payment process.

```php
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
```

## Wrapup

You are now able to sell your products on your Wordpress blog or website. This is of course a very basic script and should only give you a taste of what's possible. Here's a few things that you could do next:

* Showing the availabe stock amount of your products or services
* Show product variants, such as 



