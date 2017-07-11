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
	echo('<a href="https://github.com/luke-jr/KYCPoll/" style="position: absolute; top: 0; left: 0; border: 0;"><img src="https://camo.githubusercontent.com/121cd7cbdc3e4855075ea8b558508b91ac463ac2/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f6c6566745f677265656e5f3030373230302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_left_green_007200.png"></a>');
}

function datadisclosure() {
	global $userdata;
	echo('<p>This data will be saved with your poll results. If there is too much personal information included, please contact luke-jr to improve the filtering <em>before</em> filling out the poll.</p>');
	echo('<p>Note that if you have not completed KYC with Coinbase, your results will be saved but ignored until you complete KYC <em>and</em> resubmit your poll results.</p>');

	echo('<textarea readonly style="width:100%" rows="15">' . htmlentities(json_encode($userdata, JSON_PRETTY_PRINT), ENT_HTML5 | ENT_NOQUOTES) . '</textarea>');
}

function formbegin() {
	echo('<form action="?" method="post" id="pollform">');
}

function datadisclosure_checkbox() {
	echo('<input id="accept_terms" name="accept_terms" type="checkbox" onclick="accept_terms_clicked()"' . (@$_POST['accept_terms'] ? ' checked' : '') . '><label for="accept_terms">I agree that the server may save this data, and that there is no promise of this data being kept secure</label><br>');
}

function formend() {
	echo("</form>");
	echo('</div>');
	echo("<br><br>(the save button is on the top-right corner of your window)");
}

function pagefooter() {
	echo("</body></html>");
}

