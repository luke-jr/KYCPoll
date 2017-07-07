<?php

if (!isset($i_am_not_direct)) {
	die('This page is not for direct usage');
}

function pageheader() {
	echo('<!DOCTYPE html>');
	echo("<html lang='en'><head><title>KYCPoll</title>");
	echo('<link rel="stylesheet" type="text/css" href="style.css">');
	echo('<script src="save.js" type="text/javascript"></script>');
	echo("</head><body>");
}

function datadisclosure() {
	global $userdata;
	echo('<div id="welcome">');
	echo('<h1>Hello '.$userdata['coinbase_userdata']['data']['name'].'</h1>');
	echo('<p>This data will be saved with your poll results. If there is too much personal information included, please contact luke-jr to improve the filtering <em>before</em> filling out the poll.</p>');
	echo('<p>Note that if you have not completed KYC with Coinbase, your results will be saved but ignored until you complete KYC <em>and</em> resubmit your poll results.</p>');

	echo('<textarea readonly style="width:100%" rows="15">' . htmlentities(json_encode($userdata, JSON_PRETTY_PRINT), ENT_HTML5 | ENT_NOQUOTES) . '</textarea>');
}

function formbegin() {
	echo('<form action="?" method="post" id="pollform">');
}

function datadisclosure_checkbox() {
	echo('<input id="accept_terms" name="accept_terms" type="checkbox" onclick="accept_terms_clicked()"' . (@$_POST['accept_terms'] ? ' checked' : '') . '><label for="accept_terms">I agree that the server may save this data, and that there is no promise of this data being kept secure</label><br>');

	echo('</div>');
	echo('<div class="polls">');
}

function formend() {
	echo("</form>");
	echo('</div>');
	echo("<br><br>(the save button is on the top-right corner of your window)");
}

function pagefooter() {
	echo("</body></html>");
}

function redirectToCoinbase() {
	echo("<div id='welcome'>");
	echo("<h1>Hello!</h1>");
	echo("<p>To access KYCPoll, please pass the KYC process to ensure you are a real person.</p>");
	echo("<p>KYCPoll currently only supports Coinbase for KYC.</p>");

	echo("<a href='coinbase.php'>Click here to continue</a>");
	echo("</div>");
}
