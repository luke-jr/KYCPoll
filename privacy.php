<?php

if (!isset($i_am_not_direct)) {
	die('This page is not for direct usage');
}

function filter_userdata(&$userdata) {
	foreach ($userdata['coinbase_payment_methods']['data'] as &$limitdata) {
		if (preg_match('/^SEPA Transfer \([\d\w ]*, reference [\d\w]*\)$/', $limitdata['name'])) {
			$limitdata['name'] = 'SEPA Transfer (account info deleted by KYCPoll)';
		} else if (preg_match('/^Paypal Account: /', $limitdata['name'])) {
			$limitdata['name'] = 'Paypal Account (account info deleted by KYCPoll)';
		} else {
			$limitdata['name'] = preg_replace('/[\d*]*\d[\d*]*/', '(number deleted by KYCPoll)', $limitdata['name']);
		}
	}
}
