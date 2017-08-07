<?php

if (!isset($i_am_not_direct)) {
	die('This page is not for direct usage');
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

$stmt_find_user = $pdo->prepare("SELECT id FROM users WHERE kycsource = :kycsource AND uuid = :uuid LIMIT 1");
$stmt_create_user = $pdo->prepare("INSERT INTO users (kycsource, uuid) VALUES (:kycsource, :uuid)");

function sql_userid_lookup($kyc, $uuid, $do_create) {
	global $sql_userid;
	global $stmt_find_user, $stmt_create_user;
	$stmt_find_user->execute(array(':kycsource' => $kyc, ':uuid' => $uuid));
	$uid = $stmt_find_user->fetchColumn();
	if ($uid === FALSE && $do_create) {
		if (!$stmt_create_user->execute(array(':kycsource' => $kyc, ':uuid' => $uuid))) {
			echo("(user creation FAILED)");
// 			var_dump($stmt_create_user->errorInfo());
		}
		$stmt_find_user->execute(array(':kycsource' => $kyc, ':uuid' => $uuid));
		$uid = $stmt_find_user->fetchColumn();
	}
	if ($uid === FALSE) {
		$uid = -1;
	}
	$sql_userid = $uid;
}

$stmt_add_userinfo = $pdo->prepare('INSERT INTO userinfo (userid, info) VALUES (:userid, :info)');

function record_userdata() {
	global $sql_userid, $userdata;
	global $stmt_add_userinfo;
	$stmt_add_userinfo->execute(array(':userid' => $sql_userid, ':info' => json_encode($userdata)));
}

$stmt_find_answer = $pdo->prepare("SELECT answer FROM answers WHERE userid = :userid AND pollid = :pollid ORDER BY time DESC LIMIT 1");
$stmt_update_answer = $pdo->prepare("INSERT INTO answers (userid, pollid, answer) VALUES (:userid, :pollid, :answer)");

function get_cur_answer($pollid) {
	global $sql_userid;
	global $stmt_find_answer;
	
	if ($sql_userid == -1) {
		return 'no_answer';
	}
	
	$stmt_find_answer->execute(array(':userid' => $sql_userid, ':pollid' => $pollid));
	$answer = $stmt_find_answer->fetchColumn();
	if ($answer === FALSE) {
		$answer = 'no_answer';
	}
	return $answer;
}

$stmt_get_polls = $pdo->prepare("SELECT id, name, description FROM polls WHERE category = :category AND NOT hidden ORDER BY id");
$stmt_get_changable_polls = $pdo->prepare("SELECT id, name, description FROM polls WHERE category = :category AND NOT hidden AND allowchange ORDER BY id");

function pollcategoryheading($categoryinfo) {
	echo("<h1>".$categoryinfo['title']."</h1>");
	if (@$categoryinfo['details']) {
		echo("<p>".$categoryinfo['details']."</p>");
	}
}

function mypoll($categoryinfo) {
	global $opts;
	global $sql_userid;
	global $pdo;
	global $stmt_get_changable_polls;
	global $stmt_update_answer;
	global $do_save;
	
	$id = $categoryinfo['name'];
	$stmt_get_changable_polls->execute(array(':category' => $id));
	$row = $stmt_get_changable_polls->fetch(PDO::FETCH_ASSOC);
	if ($row === false) {
		return;
	}
	
	pollcategoryheading($categoryinfo);
	echo('<table class="pollsection">');
	echo("<tr><th>Do you agree with this?</th>");
	foreach ($opts as $opt => $optdesc) {
		echo("<th>$optdesc</th>");
	}
	echo("</tr>");
	for ( ; $row !== false; $row = $stmt_get_changable_polls->fetch(PDO::FETCH_ASSOC)) {
		$pollid = $row['id'];
		$val = $row['name'];
		$desc = $row['description'];
		if (isset($_POST["$id/$val"])) {
			$answer = $_POST["$id/$val"];
			if ($do_save) {
				$cur_answer = get_cur_answer($pollid);
				if ($cur_answer != $answer) {
					$stmt_update_answer->execute(array(':userid' => $sql_userid, ':pollid' => $pollid, ':answer' => $answer));
				}
			}
		} else {
			$answer = get_cur_answer($pollid);
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

$stmt_get_pollcategories = $pdo->prepare("SELECT name, title, details FROM pollcategories ORDER BY sort, id");

function polls() {
	global $stmt_get_pollcategories;
	global $userdata;
	global $do_save;
	
	sql_userid_lookup($userdata['kycsource'], $userdata['uuid'], $do_save);
	if ($do_save) {
		record_userdata();
	}
	
	$stmt_get_pollcategories->execute();
	while (($row = $stmt_get_pollcategories->fetch(PDO::FETCH_ASSOC)) !== false) {
		mypoll($row);
	}
}
