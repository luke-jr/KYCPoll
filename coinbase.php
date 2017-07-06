<?php
if (isset($_GET["error"]))
{
    echo("<pre>OAuth Error: " . $_GET["error"]."\n");
    echo('<a href="index.php">Retry</a></pre>');
    die;
}

// FIXME: Set a cert file, or OAuth2 PHP module IGNORES SSL ISSUES
$authorizeUrl = 'https://www.coinbase.com/oauth/authorize';
$accessTokenUrl = 'https://api.coinbase.com/oauth/token';
$clientId = 'CLIENTID';
$clientSecret = 'CLIENTSECRET';
$userAgent = 'testing for now';

$redirectUrl = "https://luke.dashjr.org/tmp/code/cbtest/cbtest_ssl.php";

require("Client.php");
require("GrantType/IGrantType.php");
require("GrantType/AuthorizationCode.php");

$client = new OAuth2\Client($clientId, $clientSecret, OAuth2\Client::AUTH_TYPE_URI);  // or AUTH_TYPE_FORM??
$client->setCurlOption(CURLOPT_USERAGENT,$userAgent);
// $client->setCurlOption(CURLOPT_HTTPHEADER, array("CB-VERSION: 2017-05-19"));
$client->setCurlOption(CURLOPT_FOLLOWLOCATION, true);

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
    $client->setAccessToken($accessTokenResult["access_token"]);
    $client->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);

    $response = $client->fetch("https://api.coinbase.com/v2/user");

//     echo('<strong>Response for fetch user:</strong><pre>');
//     print_r($response);
//     echo('</pre>');
    echo("Hello ".$response['result']['data']['name']." id ".$response['result']['data']['id']."<br>");

    $response = $client->fetch("https://api.coinbase.com/v2/payment-methods");

//     echo('<strong>Response for fetch payment-methods:</strong><pre>');
//     print_r($response);
//     echo('</pre>');
    $found_bank = false;
    $has_kyc = null;
    $foreign_currency = null;
    foreach ($response['result']['data'] as $acct) {
		if (!preg_match('/^(.*_bank_account|bank_wire|_card|interac)$/', $acct['type'])) {
			continue;
		}
		$found_bank = true;
		$buy_limit = $acct['limits']['buy'][0];
		foreach ($buy_limit['next_requirement'] as $req) {
			if (in_array($req, array('jumio'))) {
				$has_kyc = false;
				break 2;
			}
		}
		if ($buy_limit['total']['currency'] != 'USD') {
			$foreign_currency = $buy_limit['total']['currency'];
			continue;
		}
		$avg_daily_buy_limit = $buy_limit['total']['amount'] / $buy_limit['period_in_days'];
		if ($avg_daily_buy_limit > 500) {
			$has_kyc = true;
		}
// 		echo("Buy limit: $avg_daily_buy_limit<br>");
    }
    if ($has_kyc) {
		echo("KYC is OK!");
    } else if ($foreign_currency) {
		echo("Unknown currency $foreign_currency");
    } else if (!$found_bank) {
		echo("No funding source found");
    } else {
		echo("KYC not completed");
    }
}
?>
