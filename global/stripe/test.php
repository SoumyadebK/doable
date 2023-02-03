<?php require_once("init.php");
if(!empty($_POST)){
	try {
		\Stripe\Stripe::setApiKey('sk_test_EKO1OftnrgUOSzaPqS73SB8v');
		$myCard = array('number' => $_POST['CC_NO'], 'exp_month' => $_POST['EXP_MONTH'], 'exp_year' => $_POST['EXP_YEAR']);
		$charge = \Stripe\Charge::create(array(
			'card' => $myCard,
			'amount' => $_POST['AMOUNT'] * 100,
			'currency' => 'usd',
			'capture' => true,
			'description' => 'Test Payment',
			"metadata" => array("order_id" => "6735"),
			'statement_descriptor' => 'Appso',
			'receipt_email' => 'balaji@codingdesk.in',
			));
		//echo $charge."<br />Json 1:<pre>".$charge;
		echo "<b style='color:red'>".$charge->status."</b>";
	} catch(\Stripe\Error\Card $e) {
		// Since it's a decline, \Stripe\Error\Card will be caught
		$body = $e->getJsonBody();
		$err  = $body['error'];

		print('Message is:' . $err['message'] . "\n");
	} catch (\Stripe\Error\RateLimit $e) {
		echo "Too many requests made to the API too quickly";
	} catch (\Stripe\Error\InvalidRequest $e) {
		echo "Invalid parameters were supplied to Stripe's API";
	} catch (\Stripe\Error\Authentication $e) {
		echo "Authentication with Stripe's API failed";
		// (maybe you changed API keys recently)
	} catch (\Stripe\Error\ApiConnection $e) {
		echo "Network communication with Stripe failed";
	} catch (\Stripe\Error\Base $e) {
		// Display a very generic error to the user, and maybe send
		// yourself an email
		echo "Error";
	} catch (Exception $e) {
		echo "Something else happened, completely unrelated to Stripe"; 
	}
	unset($_POST);
}

/*
	\Stripe\Stripe::setApiKey('sk_test_EKO1OftnrgUOSzaPqS73SB8v');
	$Customer = \Stripe\Customer::create(array(
		"description" => "good customer",
		"email" => 'test@example.com'
	));
	//echo $Customer."<br />Json 1:<pre>".$Customer;
	echo $Customer->id;
*/
?>
<html>
	<head>
		<title>Stripe</title>
	</head>
	<body>
		<form name="form1" method="post" >
			<table>
				
				<tr>
					<td>Amount</td>
					<td><input type="text" name="AMOUNT" value="100" ></td>
				</tr>
				<tr>
					<td>CC NO</td>
					<td><input type="text" name="CC_NO" value="4000056655665556" ></td>
				</tr>
				<tr>
					<td>Exp Month</td>
					<td>
						<select name="EXP_MONTH" >
							<option value="1">1</option>
							<option value="2">2</option>
							<option value="3">3</option>
							<option value="4">4</option>
							<option value="5">5</option>
							<option value="6">6</option>
							<option value="7">7</option>
							<option value="8" selected>8</option>
							<option value="9">9</option>
							<option value="10">10</option>
							<option value="11">11</option>
							<option value="12">12</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>Exp Year</td>
					<td>
						<select name="EXP_YEAR" >
							<option value="2016">2016</option>
							<option value="2017">2017</option>
							<option value="2018" selected>2018</option>
							<option value="2019">2019</option>
							<option value="2020">2020</option>
							<option value="2021">2021</option>
							<option value="2022">2022</option>
							<option value="2023">2023</option>
							<option value="2024">2024</option>
							<option value="2025">2025</option>
						</select>
					</td>
				</tr>
				<tr>
					<td></td>
					<td>
						<input type="submit" value="Submit" />
					</td>
				</tr>
			</table>
			<b>Test CC #</b><br />
			4242424242424242<br />
			4012888888881881<br />
			6011111111111117<br />
			3566002020360505<br /><br />
			
			<b>Test CC # Which Gives Failed Transaction</b><br />
			4000000000000069<br />
			4000000000000002<br />
			4242424242424241<br />
		</form>
	</body>
</html>