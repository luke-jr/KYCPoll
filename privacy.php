<?php

if (!isset($i_am_not_direct)) {
	die('This page is not for direct usage');
}

function filter_if_exists(&$e) {
	if (isset($e)) {
		$e = "(deleted by KYCPoll)";
	}
}

function filter_userdata(&$userdata) {
	foreach ($userdata['coinbase_payment_methods']['data'] as &$payment_method) {
		if (preg_match('/^SEPA Transfer \([\d\w ]*, reference [\d\w]*\)$/', $payment_method['name'])) {
			$payment_method['name'] = 'SEPA Transfer (account info deleted by KYCPoll)';
		} else if (preg_match('/^Paypal Account: /', $payment_method['name'])) {
			$payment_method['name'] = 'Paypal Account (account info deleted by KYCPoll)';
		} else {
			$payment_method['name'] = preg_replace('/[\d*]*\d[\d*]*/', '(number deleted by KYCPoll)', $payment_method['name']);
		}
		foreach ($payment_method['limits'] as $limit_type => &$limit_list) {
			if (!in_array($limit_type, array('buy', 'sell', 'instant_buy'))) {
				continue;
			}
			foreach ($limit_list as &$limit_details) {
				filter_if_exists($limit_details['remaining']);
				filter_if_exists($limit_details['description']);
			}
		}
	}
	filter_if_exists($userdata['coinbase_userdata_old']['user']['balance']['amount']);
}
