<?php
/**
 * PayPal決済用関数
 * 
 * PayPal決済に必要な関数群
 * 
 * @package
 * @author   村上俊行
 * @version  1.0
 * @since    2023/08/30 初版作成 村上俊行
 */
require DIR_BASE . "_etc/_payment/paypal/paypal_define.php";

/**
 * PayPal Order URL取得
 * @access	private
 * @param	int		$value			ammount
            int		$invoide_id		his_purchase の purchase_no
 * @return	アクセスURL
 */
function get_paypal_orders_url($value, $invoice_id, $member_no) {
	// access tokenの取得
	$access_token = get_paypal_access_token();
	if ($access_token == ""){
		return "";
	}

	$post_data = json_encode([
		"intent" => "CAPTURE",
		"purchase_units" => [
			[
				"amount" => [
					"currency_code" => $GLOBALS["viewAmountType"]["40"],
					"value" => $value
				],
				"invoice_id" => $invoice_id,
				"custom_id" => $member_no
			]
		],
		"payment_source" => [
			"paypal" => [
				"experience_context" => [
					"payment_method_preference" => "IMMEDIATE_PAYMENT_REQUIRED",
					"locale" => "en-US",
					"landing_page" => "LOGIN",
					"shipping_preference" => "NO_SHIPPING",
					"user_action" => "PAY_NOW",
					"return_url" => URL_SSL_SITE . "paypalReturn.php",
					"cancel_url" => URL_SSL_SITE . "paypalCancel.php"
				]
			]
		]
	]);

	// print($post_data . "\n");

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, PAYPAL_API_ORDER_URL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

	$headers = array();
	$headers[] = 'Content-Type: application/json';
	$headers[] = 'Authorization: Bearer ' . $access_token;
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$result = curl_exec($ch);

	$response = json_decode($result, true);
	// print_r($response);

	// linksにある rel = payer-action の href 設定がaccess_urlなので抽出
	$access_url = "";
	foreach( $response["links"] as $k => $v){
		if ($v["rel"] == "payer-action") {
			$access_url = $v["href"];
			break;
		}
	}
	return($access_url);
}

/**
 * PayPal Capture処理
 * @access	private
 * @param	String	$token			return URL で返ってきた tokenの値
 * @return	capture
 */
function set_paypal_capture($token) {
	$capture = [];
	// access tokenの取得
	$access_token = get_paypal_access_token();
	if ($access_token == ""){
		$capture["error"] = "PAYPAL_ACCESS_ERROR [TOKEN]";
		return $capture;
	}
	
	$url = str_replace("{TOKEN}", $token, PAYPAL_API_CAPTURE_URL);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "{}");
	$headers = array();
	$headers[] = 'Content-Type: application/json';
	$headers[] = 'Authorization: Bearer ' . $access_token;
	
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$result = curl_exec($ch);

	$response = json_decode($result, true);
	// print_r($response);
	if (!$response) {
		$capture["error"] = "PAYPAL_ACCESS_ERROR";
		return $capture;
	}

	if (array_key_exists("status", $response)){
		// 設定した invoice_idを取得
		try {
			$capture = $response["purchase_units"][0]["payments"]["captures"][0];
		} catch (Exception $e) {
			$capture["error"] = "PAYPAL_CAPTURE_ERROR [PAYMENTS]";
		}
	} else {
		if (array_key_exists("details", $response)){
			$capture = $response["details"][0];
		} else {
			$capture["error"] = "PAYPAL_CAPTURE_ERROR [PAYMENTS]";
		}
	}
	// print_r($capture);
	return $capture;

}


// paypal アクセストークン取得
/**
 * PayPal アクセストークン取得
 * @access	private
 * @param	なし
 * @return	アクセストークン
 */function get_paypal_access_token() {

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => PAYPAL_API_TOKEN_URL,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ":" . PAYPAL_CLIENT_SECRET,
		CURLOPT_POSTFIELDS => "grant_type=client_credentials",
		CURLOPT_HTTPHEADER => array(
			"Accept: application/json",
			"Accept-Language: en_US"
		),
	));

	$result = curl_exec($curl);
	$response = json_decode($result, true); 
	if ($response == ""){
		return "";
	}

	if (array_key_exists('access_token', $response)) {
		return $response['access_token'];
	} else {
		return "";
	}
}

?>
