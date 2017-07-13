<?php

$i_am_not_direct = true;

require_once('secrets.php');
require('htmlstuff.php');
require('kycpoll.php');
require('privacy.php');

pageheader();

function myerr($msg) {
	echo("Error: $msg</body></html>");
	die;
}

if (isset($_GET["error"]))
{
    myerr("<pre>OAuth Error: " . $_GET["error"]."\n".'<a class="btn" href="?retry">Retry</a></pre>');
}

// FIXME: Set a cert file, or OAuth2 PHP module IGNORES SSL ISSUES
$authorizeUrl = 'https://www.coinbase.com/oauth/authorize';
$accessTokenUrl = 'https://api.coinbase.com/oauth/token';
$clientId = $coinbase_clientId;
$clientSecret = $coinbase_clientSecret;
$userAgent = 'KYC Poll';

$redirectUrl = "https://luke.dashjr.org/programs/kycpoll/coinbase.php";

require("Client.php");
require("GrantType/IGrantType.php");
require("GrantType/AuthorizationCode.php");

$client = new OAuth2\Client($clientId, $clientSecret, OAuth2\Client::AUTH_TYPE_URI);  // or AUTH_TYPE_FORM??
$client->setCurlOption(CURLOPT_USERAGENT,$userAgent);
// $client->setCurlOption(CURLOPT_HTTPHEADER, array("CB-VERSION: 2017-05-19"));
$client->setCurlOption(CURLOPT_FOLLOWLOCATION, true);

session_start();

if (@$_POST['logout']) {
	session_unset();
	echo("Logged out.\n");
}

if (!isset($_SESSION['access_token'])) {
	if (!isset($_GET['code'])) {
		$authUrl = $client->getAuthenticationUrl($authorizeUrl, $redirectUrl, array("scope" => "wallet:payment-methods:read,wallet:payment-methods:limits", "state" => "dawgabsAv6"));
		die("<div id='welcome'><h1>Log in with Coinbase</h1><p>To verify, please login with Coinbase and authorize KYCPoll to review your account information. <strong>Due to Coinbase API limitations, you will need to login twice.</strong></p><a class='btn redirectLink' href='$authUrl'>Click here to login (1 of 2)</a></div>");
	} else {
		$params = array("code" => $_GET["code"], "redirect_uri" => $redirectUrl);
		$response = $client->getAccessToken($accessTokenUrl, "authorization_code", $params);
		
		$accessTokenResult = $response["result"];
		session_unset();
		if (!isset($accessTokenResult["access_token"])) {
			myerr("Failed to get Coinbase id; <a class='btn' href='?retry'>Click here to retry</a>");
		}
		$_SESSION['access_token'] = $accessTokenResult["access_token"];
		unset($_GET['code']);
	}
}

$old_client = new OAuth2\Client($coinbase_old_clientId, $coinbase_old_clientSecret, OAuth2\Client::AUTH_TYPE_URI);  // or AUTH_TYPE_FORM??
$old_client->setCurlOption(CURLOPT_USERAGENT,$userAgent);
// $client->setCurlOption(CURLOPT_HTTPHEADER, array("CB-VERSION: 2017-05-19"));
$old_client->setCurlOption(CURLOPT_FOLLOWLOCATION, true);

if (!isset($_SESSION['access_token_old'])) {
	if (!isset($_GET['code'])) {
		$authUrl = $old_client->getAuthenticationUrl($authorizeUrl, $redirectUrl, array("scope" => "user", "state" => "dawgabsAv6"));
		die("<div id='welcome'><h1>Log in with Coinbase</h1><p>Due to API limitations, we need you to login with Coinbase and authorize KYCPoll to review your account information one more time.</p><a class='btn redirectLink' href='$authUrl'>Click here to login (2 of 2)</a></div>");
	} else {
		$params = array("code" => $_GET["code"], "redirect_uri" => $redirectUrl);
		$response = $old_client->getAccessToken($accessTokenUrl, "authorization_code", $params);
		
		$accessTokenResult = $response["result"];
		if (!isset($accessTokenResult["access_token"])) {
			session_unset();
			myerr("Failed to get Coinbase id; <a class='btn' href='?retry'>Click here to retry</a>");
		}
		$_SESSION['access_token_old'] = $accessTokenResult["access_token"];
	}
}

$do_save = @$_POST['accept_terms'];

if ((!isset($_SESSION['userdata'])) || @$_POST['userdata_refresh']) {
	$client->setAccessToken($_SESSION["access_token"]);
	$client->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);
	
	$response = $client->fetch("https://api.coinbase.com/v2/user");
	if (!isset($response['result']['data']['id'])) {
		session_unset();
		myerr("Failed to get Coinbase id; <a class='btn' href='?retry'>Click here to retry</a>");
	}
	
	$userdata = array(
		'kycsource' => 'coinbase',
		'uuid' => 'coinbase_' . $response['result']['data']['id'],
		'coinbase_userdata' => $response['result'],
	);
	
	$response = $client->fetch("https://api.coinbase.com/v2/payment-methods");
	$userdata['coinbase_payment_methods'] = $response['result'];
	
	$old_client->setAccessToken($_SESSION["access_token_old"]);
	$old_client->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);
	$response = $old_client->fetch("https://api.coinbase.com/v1/users/self");
	$userdata['coinbase_userdata_old'] = $response['result'];
	
	filter_userdata($userdata);
	$_SESSION['userdata'] = $userdata;
	$do_save = false;
// 	echo "Loaded data from Coinbase!<br>";
} else {
	$userdata = $_SESSION['userdata'];
}

echo('<div id="manager"><span id="save_button_placeholder"></span><button onclick="do_logout()">Logout</button></div>');


echo('<div id="welcome">');
echo("<a class='btn btnright' href='answers.php'>Click here to see poll answers</a>");
echo('<h1>Hello '.$userdata['coinbase_userdata']['data']['name'].'</h1>');

datadisclosure();
formbegin();
echo('<input type="submit" name="userdata_refresh" value="Refresh data from Coinbase"><br>');
datadisclosure_checkbox();

echo('</div>');
echo('<div class="polls">');

polls();

formend();
pagefooter();

?>
