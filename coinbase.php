<?php

function myerr($msg) {
	echo("<html><head><title>Error</title><body>$msg</body></html>");
	die;
}

if (isset($_GET["error"]))
{
    myerr("<pre>OAuth Error: " . $_GET["error"]."\n".'<a href="?retry">Retry</a></pre>');
}

// FIXME: Set a cert file, or OAuth2 PHP module IGNORES SSL ISSUES
$authorizeUrl = 'https://www.coinbase.com/oauth/authorize';
$accessTokenUrl = 'https://api.coinbase.com/oauth/token';
$clientId = 'CLIENTID';
$clientSecret = 'CLIENTSECRET';
$userAgent = 'KYC Poll';

$redirectUrl = "https://luke.dashjr.org/programs/kycpoll/coinbase.php";

require("Client.php");
require("GrantType/IGrantType.php");
require("GrantType/AuthorizationCode.php");

$client = new OAuth2\Client($clientId, $clientSecret, OAuth2\Client::AUTH_TYPE_URI);  // or AUTH_TYPE_FORM??
$client->setCurlOption(CURLOPT_USERAGENT,$userAgent);
// $client->setCurlOption(CURLOPT_HTTPHEADER, array("CB-VERSION: 2017-05-19"));
$client->setCurlOption(CURLOPT_FOLLOWLOCATION, true);

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
}

$client->setAccessToken($_SESSION["access_token"]);
$client->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);

$response = $client->fetch("https://api.coinbase.com/v2/user");
if (!isset($response['result']['data']['id'])) {
	unset($_SESSION['access_token']);
	myerr("Failed to get Coinbase id; <a href='?retry'>Click here to retry</a>");
}

echo('<html><head><title>KYCPoll</title></head><body>');
echo("Hello ".$response['result']['data']['name']."<br>");
echo('<br>');

echo('<form method="POST">');

function mypoll($id, $title, $options) {
	echo("<h2>$title</h2>");
	foreach ($options as $val => $desc) {
		$ischecked = '';
		// TODO: look up current selection
		echo("<input type='radio' name='$id' value='$val'$ischecked> $desc<br>");
	}
}

mypoll('segwit', 'Segwit', array(
	'unconditional' => 'I support Segwit',
	'compromise' => 'I consent to Segwit only bundled with a hardfork',
	'oppose' => 'I oppose Segwit',
));

mypoll('bip148', 'BIP148', array(
	'unconditional' => 'I unconditionally support BIP148',
	'conditional' => 'I support BIP148 only if <input size="3" name="bip148_pct">% of users also support it',
	'oppose' => 'I oppose BIP148',
));

mypoll('blocksizehf', 'Block size hardfork', array(
	'unconditional' => 'I support a hardfork to increase block size',
	'compromise' => 'I consent to a block size increase hardfork only if bundled with Segwit',
	'oppose' => 'I oppose a block size increase hardfork',
));


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
