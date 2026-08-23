<?php
/**
 * Monnify (Moniepoint) payment gateway adapter
 *
 * @since 12.9.2
 * @package ScholaPro
 * @subpackage modules/Student_Billing_Premium
 *
 * @link https://developers.monnify.com
 */

/**
 * Monnify base URL depending on mode.
 *
 * @return string https://api.monnify.com (live) or https://sandbox.monnify.com (test).
 */
function _sbp_monnify_base_url()
{
	return _sbp_config( 'MODE' ) === 'live' ?
		'https://api.monnify.com' :
		'https://sandbox.monnify.com';
}

/**
 * Perform an HTTP request (cURL).
 *
 * @param  string $method  GET|POST.
 * @param  string $url     Full URL.
 * @param  array  $headers HTTP headers array.
 * @param  mixed  $body    Request body (array will be JSON encoded).
 *
 * @return array [ 'error' => string ] or [ 'code' => int, 'body' => array|string ].
 */
function _sbp_monnify_http_request( $method, $url, $headers = [], $body = null )
{
	$ch = curl_init();

	curl_setopt_array( $ch, [
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CUSTOMREQUEST => $method,
		CURLOPT_HTTPHEADER => $headers,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_CONNECTTIMEOUT => 15,
	] );

	if ( $body !== null )
	{
		curl_setopt( $ch, CURLOPT_POSTFIELDS, is_string( $body ) ? $body : json_encode( $body ) );
	}

	$response = curl_exec( $ch );

	$error = curl_error( $ch );

	$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );

	curl_close( $ch );

	if ( $error )
	{
		return [ 'error' => $error ];
	}

	$decoded = json_decode( $response, true );

	return [ 'code' => $code, 'body' => is_array( $decoded ) ? $decoded : $response ];
}

/**
 * Authenticate to Monnify and get a Bearer token (valid 1 hour).
 *
 * @return string|false Access token, false on failure.
 */
function _sbp_monnify_auth()
{
	static $token;

	if ( $token )
	{
		return $token;
	}

	$api_key = _sbp_config( 'API_KEY' );

	$secret_key = _sbp_config( 'SECRET_KEY' );

	if ( ! $api_key || ! $secret_key )
	{
		return false;
	}

	$res = _sbp_monnify_http_request(
		'POST',
		_sbp_monnify_base_url() . '/api/v1/auth/login',
		[
			'Authorization: Basic ' . base64_encode( $api_key . ':' . $secret_key ),
			'Content-Type: application/json',
		]
	);

	if ( ! empty( $res['error'] )
		|| empty( $res['body']['responseBody']['accessToken'] ) )
	{
		return false;
	}

	$token = $res['body']['responseBody']['accessToken'];

	return $token;
}

/**
 * Initialize a Monnify transaction (hosted checkout).
 *
 * @param  float  $amount            Amount in NGN.
 * @param  string $payment_reference Unique payment reference.
 * @param  string $customer_name     Customer name.
 * @param  string $customer_email    Customer email.
 * @param  string $description       Payment description.
 * @param  string $redirect_url      URL Monnify redirects to after payment.
 *
 * @return array|false cURL result array, or false on auth failure.
 */
function _sbp_monnify_init_transaction( $amount, $payment_reference, $customer_name, $customer_email, $description, $redirect_url )
{
	$token = _sbp_monnify_auth();

	if ( ! $token )
	{
		return false;
	}

	$payload = [
		'amount' => (float) $amount,
		'customerName' => $customer_name,
		'customerEmail' => $customer_email,
		'paymentReference' => $payment_reference,
		'paymentDescription' => $description,
		'currencyCode' => _sbp_config( 'CURRENCY' ) ? _sbp_config( 'CURRENCY' ) : 'NGN',
		'contractCode' => _sbp_config( 'CONTRACT_CODE' ),
		'redirectUrl' => $redirect_url,
		'paymentMethods' => [ 'CARD', 'ACCOUNT_TRANSFER' ],
	];

	return _sbp_monnify_http_request(
		'POST',
		_sbp_monnify_base_url() . '/api/v1/merchant/transactions/init-transaction',
		[
			'Authorization: Bearer ' . $token,
			'Content-Type: application/json',
		],
		$payload
	);
}

/**
 * Verify a transaction by payment reference (server-side, authoritative).
 *
 * @param  string $payment_reference Payment reference.
 *
 * @return array|false cURL result array, or false on auth failure.
 */
function _sbp_monnify_verify( $payment_reference )
{
	$token = _sbp_monnify_auth();

	if ( ! $token )
	{
		return false;
	}

	return _sbp_monnify_http_request(
		'GET',
		_sbp_monnify_base_url() . '/api/v2/merchant/transactions/query?paymentReference=' . urlencode( $payment_reference ),
		[ 'Authorization: Bearer ' . $token ]
	);
}

/**
 * Verify the Monnify webhook signature.
 *
 * SHA-512(client secret key + request body), compared to the
 * `monnify-signature` header. Sandbox webhooks do not include a signature.
 *
 * @param  string $body      Raw request body.
 * @param  string $signature Signature header value.
 *
 * @return bool True if signature matches.
 */
function _sbp_monnify_verify_signature( $body, $signature )
{
	if ( ! $signature )
	{
		// Sandbox webhooks have no signature header.
		return _sbp_config( 'MODE' ) !== 'live';
	}

	$secret_key = _sbp_config( 'SECRET_KEY' );

	if ( ! $secret_key )
	{
		return false;
	}

	// Primary: HMAC-SHA512 with the client secret as key.
	$computed_hmac = hash_hmac( 'sha512', $body, $secret_key );

	if ( hash_equals( $computed_hmac, (string) $signature ) )
	{
		return true;
	}

	// Fallback (documented formula): SHA-512(client secret + body).
	$computed = hash( 'sha512', $secret_key . $body );

	return hash_equals( $computed, (string) $signature );
}
