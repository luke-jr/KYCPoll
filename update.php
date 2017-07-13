<?php

if (php_sapi_name() !== 'cli') {
	die('This is meant for CLI only');
}

$i_am_not_direct = true;
require_once('secrets.php');

$pdo = new PDO(
    'mysql:host=localhost;dbname=kycpoll_v1',
    $db_username,
    $db_password
);

function update_user_visibility() {
	global $pdo;
	$stmt_update = $pdo->prepare('UPDATE users SET visible = :visible WHERE id = :userid');
	$stmt = $pdo->prepare('SELECT t1.userid AS userid, t1.info AS info FROM userinfo t1 WHERE t1.time = (SELECT MAX(time) FROM userinfo t2 WHERE t2.userid = t1.userid)');
	$stmt->execute();
	while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
		$userid = $row['userid'];
		$info = json_decode($row['info'], true);
		if ($userid == -1 || !$info) {
			continue;
		}
		
		$needs_jumio = false;
		$has_nonzero_limit = false;
		$buy_level = @$info['coinbase_userdata_old']['user']['buy_level'];
		$sell_level = @$info['coinbase_userdata_old']['user']['sell_level'];
		
		foreach ($info['coinbase_payment_methods']['data'] as $payment_method) {
			foreach ($payment_method['limits'] as $limit_type => $limit_list) {
				if (!in_array($limit_type, array('buy', 'sell'))) {
					continue;
				}
				foreach ($limit_list as $limit_details) {
					if ($limit_details['total']['amount'] > 0) {
						$has_nonzero_limit = true;
					}
					$next_req = $limit_details['next_requirement'];
					if (@$next_req['type'] == 'jumio') {
						$needs_jumio = true;
					}
				}
			}
		}
		
		$allow_visible = $has_nonzero_limit; // && !$needs_jumio;
		$stmt_update->execute(array(':userid' => $userid, ':visible' => $allow_visible));
	}
}

function update_totals() {
	global $pdo;
	$totals = array();
	$stmt = $pdo->prepare('SELECT t1.pollid AS pollid, t1.answer AS answer FROM answers t1 JOIN users ON (t1.userid = users.id) WHERE users.visible AND t1.time = (SELECT MAX(time) FROM answers t2 WHERE t2.userid = t1.userid AND t2.pollid = t1.pollid)');
	$stmt->execute();
	while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
		$pollid = $row['pollid'];
		$answer = $row['answer'];
		
		if ($answer == 'no_answer') {
			continue;
		}
		
		if (!array_key_exists($pollid, $totals)) {
			$totals[$pollid] = array();
		}
		if (!array_key_exists($answer, $totals[$pollid])) {
			$totals[$pollid][$answer] = 0;
		}
		++$totals[$pollid][$answer];
	}
	
	$stmt_update = $pdo->prepare('REPLACE INTO totals (pollid, answer, count) VALUES (:pollid, :answer, :count)');
	foreach ($totals as $pollid => $answers) {
		foreach ($answers as $answer => $count) {
			$stmt_update->execute(array(':pollid' => $pollid, ':answer' => $answer, ':count' => $count));
		}
	}
	
	$stmt_prune = $pdo->prepare('SELECT pollid, answer FROM totals');
	$stmt_prune->execute();
	$stmt_delete = $pdo->prepare('DELETE FROM totals WHERE pollid = :pollid AND answer = :answer');
	while (($row = $stmt_prune->fetch(PDO::FETCH_ASSOC)) !== false) {
		$pollid = $row['pollid'];
		$answer = $row['answer'];
		
		if (array_key_exists($pollid, $totals) && array_key_exists($answer, $totals[$pollid])) {
			continue;
		}
		
		$stmt_delete->execute(array(':pollid' => $pollid, ':answer' => $answer));
	}
}

update_user_visibility();
update_totals();
