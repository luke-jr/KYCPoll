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
	echo('This data will be saved with your poll results. If there is too much personal information included, please contact luke-jr to improve the filtering <em>before</em> filling out the poll.<br>');
	echo('Note that if you have not completed KYC with Coinbase, your results will be saved but ignored until you complete KYC <em>and</em> resubmit your poll results.<br>');

	echo('<textarea readonly style="width:100%" rows="15">' . htmlentities(json_encode($userdata, JSON_PRETTY_PRINT), ENT_HTML5 | ENT_NOQUOTES) . '</textarea>');
}

function formbegin() {
	echo('<form action="#" method="post" id="pollform">');
}

function datadisclosure_checkbox() {
	echo('<input id="accept_terms" name="accept_terms" type="checkbox" onclick="accept_terms_clicked()"' . (@$_POST['accept_terms'] ? ' checked' : '') . '><label for="accept_terms">I agree that the server may save this data, and that there is no promise of this data being kept secure</label><br>');
}

function formend() {
	echo("</form>");
	echo("<br><br>(the save button is on the top-right corner of your window)");
}

function pagefooter() {
	echo("</body></html>");
}

$opts = array(
	'no_answer' => 'No answer',
	'strong_disagree' => 'Strongly disagree',
	'disagree' => 'Disagree',
	'unsure' => 'Not sure',
	'agree' => 'Agree',
	'strong_agree' => 'Strongly agree',
);

$pdo = new PDO(
    'mysql:host=localhost;dbname=kycpoll_v1',
    $db_username,
    $db_password
);

function sql_userid_lookup($kyc, $uuid) {
	// TODO
}

$stmt_find_poll = $pdo->prepare("SELECT id FROM polls WHERE category = :category AND name = :name LIMIT 1");
$stmt_find_answer = $pdo->prepare("SELECT answer FROM answers WHERE userid = :userid AND pollid = :pollid LIMIT 1");

function mypoll($id, $title, $options) {
	global $opts;
	global $sql_userid;
	global $pdo;
	global $stmt_find_poll, $stmt_find_answer;
	
	echo("<h1>$title</h1>");
	echo('<table class="pollsection">');
	echo("<tr><th>Do you agree with this?</th>");
	foreach ($opts as $opt => $optdesc) {
		echo("<th>$optdesc</th>");
	}
	echo("</tr>");
	foreach ($options as $val => $desc) {
// 		$pdo->prepare('INSERT INTO polls (category, name, description) VALUES (:c, :n, :d)')->execute(array(':c' => $id, ':n' => $val, ':d' => $desc));
		if (isset($_POST["$id/$val"])) {
			$answer = $_POST["$id/$val"];
		} else {
			$stmt_find_poll->execute(array(':category' => $id, ':name' => $val));
			$pollid = $stmt_find_poll->fetchColumn();
			if ($pollid === FALSE) {
				myerr("Unknown poll $id/$val");
			}
			$stmt_find_answer->execute(array(':userid' => $sql_userid, ':pollid' => $pollid));
			$answer = $stmt_find_answer->fetchColumn();
			if ($answer === FALSE) {
				$answer = 'no_answer';
			}
		}
		echo("<tr class='poll'><th>$desc</th>");
		foreach ($opts as $opt => $optdesc) {
			$ischecked = ($opt == $answer) ? " checked" : "";
			echo("<td><input type='radio' name='$id/$val' value='$opt'$ischecked></td>");
		}
		echo("</tr>");
	}
	echo("</table>");
}

function polls() {
	mypoll('segwit', 'Segwit', array(
		'use' => 'I wish to use Segwit myself',
		'softfork' => 'I am okay with others using Segwit',
		'hfbundle' => 'Segwit is okay only bundled with a hardfork',
	));
	
	mypoll('bip148', 'BIP148', array(
		'unconditional' => 'I unconditionally support BIP148',
		'ecmajority' => 'If the economic majority supports BIP148, I will support it too',
		'minermajority' => 'I support BIP148 only if <strong>51%</strong> of miners do',
		'minerminority' => 'I support BIP148 only if <strong>15%</strong> of miners do',
		'powchange' => 'If miners block BIP148, I support a change to the proof-of-work algorithm',
		'oppose' => 'I oppose BIP148',
	));
	
	mypoll('blocksizehf', 'Block size hardfork', array(
		'unconditional' => 'I support a hardfork to increase block size unconditionally',
		'consensus' => 'I support a hardfork to increase block size only with consensus',
		'segwitbundle' => 'I support a block size increase hardfork only if bundled with Segwit',
		'oppose' => 'I oppose a block size increase hardfork',
	));
}
