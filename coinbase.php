<?php

$i_am_not_direct = true;

require_once('secrets.php');
require('kycpoll.php');

echo('<!DOCTYPE html>');
echo("<html lang='en'><head><title>KYCPoll</title>");
echo('<link rel="stylesheet" type="text/css" href="style.css">');
echo('<script src="save.js" type="text/javascript"></script>');
echo("</head><body>");

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

echo('This data will be saved with your poll results. If there is too much personal information included, please contact luke-jr to improve the filtering <em>before</em> filling out the poll.<br>');
echo('Note that if you have not completed KYC with Coinbase, your results will be saved but ignored until you complete KYC <em>and</em> resubmit your poll results.<br>');

echo('<textarea readonly style="width:100%" rows="15">' . htmlentities(json_encode($userdata, JSON_PRETTY_PRINT), ENT_HTML5 | ENT_NOQUOTES) . '</textarea>');
echo('<form action="#" method="post" id="pollform">');
echo('<input type="submit" name="userdata_refresh" value="Refresh data from Coinbase"><br>');
// echo('<input type="hidden" name="ABC" value="DEF" id="ABC"><input type="submit"></form>');
// echo('<pre>');var_dump($_POST);echo('</pre>');
echo('<input id="accept_terms" name="accept_terms" type="checkbox" onclick="accept_terms_clicked()"' . (@$_POST['accept_terms'] ? ' checked' : '') . '><label for="accept_terms">I agree that the server may save this data, and that there is no promise of this data being kept secure</label><br>');

polls();

echo("</form>");
echo("<br><br>(the save button is on the top-right corner of your window)");
echo("</body></html>");

?>
