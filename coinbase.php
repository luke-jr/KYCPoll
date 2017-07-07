<?php

$i_am_not_direct = true;

require_once('secrets.php');
require('kycpoll.php');

pageheader();

function myerr($msg) {
	echo("Error: $msg</body></html>");
	die;
}

if (isset($_GET["error"]))
{
    myerr("<pre>OAuth Error: " . $_GET["error"]."\n".'<a href="?retry">Retry</a></pre>');
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

if (isset($_SESSION['access_token'])) {
	// Pass through
} else
if (!isset($_GET["code"]))
{
    $authUrl = $client->getAuthenticationUrl($authorizeUrl, $redirectUrl, array("scope" => "wallet:payment-methods:read,wallet:payment-methods:limits", "state" => "dawgabsAv6"));
    header("Location: ".$authUrl);
    die("Redirect");
}
else
{
    $params = array("code" => $_GET["code"], "redirect_uri" => $redirectUrl);
    $response = $client->getAccessToken($accessTokenUrl, "authorization_code", $params);

    $accessTokenResult = $response["result"];
    $_SESSION['access_token'] = $accessTokenResult["access_token"];
    unset($_SESSION['userdata']);
}

$do_save = @$_POST['accept_terms'];

if ((!isset($_SESSION['userdata'])) || @$_POST['userdata_refresh']) {
	$client->setAccessToken($_SESSION["access_token"]);
	$client->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);
	
	$response = $client->fetch("https://api.coinbase.com/v2/user");
	if (!isset($response['result']['data']['id'])) {
		unset($_SESSION['access_token']);
		myerr("Failed to get Coinbase id; <a href='?retry'>Click here to retry</a>");
	}
	
	$userdata = array(
		'uuid' => 'coinbase_' . $response['result']['data']['id'],
		'coinbase_userdata' => $response['result'],
	);
	
	$response = $client->fetch("https://api.coinbase.com/v2/payment-methods");
	$userdata['coinbase_payment_methods'] = $response['result'];
	
	$_SESSION['userdata'] = $userdata;
	$do_save = false;
// 	echo "Loaded data from Coinbase!<br>";
} else {
	$userdata = $_SESSION['userdata'];
}

echo('<div id="save_button_placeholder"></div>');

echo("Hello ".$userdata['coinbase_userdata']['data']['name']."<br>");
echo('<br>');

datadisclosure();
formbegin();
echo('<input type="submit" name="userdata_refresh" value="Refresh data from Coinbase"><br>');
datadisclosure_checkbox();

polls();

formend();
pagefooter();

?>
