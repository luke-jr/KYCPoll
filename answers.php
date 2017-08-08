<?php

$i_am_not_direct = true;

require_once('secrets.php');
require('htmlstuff.php');
require('kycpoll.php');

$optcolours = array(
	'strong_disagree' => '#ff8080',
	'disagree' => '#ffa0a0',
	'unsure' => '#c0c0c0',
	'agree' => '#a0ffa0',
	'strong_agree' => '#80ff80',
);

function getpollresults($pollid) {
	global $pdo;
	$rv = array();
	$stmt = $pdo->prepare('SELECT answer, count FROM totals WHERE pollid = :pollid ORDER BY answer');
	$stmt->execute(array(':pollid' => $pollid));
	while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
		$answer = $row['answer'];
		$count = $row['count'];
		
		$rv[$answer] = $count;
	}
	return $rv;
}

function pollresults($varbase, $answers) {
	global $opts, $optcolours;
	
	$htmlid = "viewpoll_$varbase";
	$datavar = "polldata_$varbase";
	
	echo("<div data-dojo-type='dojox/charting/widget/Chart' data-dojo-props='theme:dojox.charting.themes.Claro' id='$htmlid' style='width: 200px; height: 200px;'>");
	echo("<div class='plot' name='default' type='Pie' radius='100' fontColor='#000' labelOffset='0'></div>");
	echo("<div class='series' name='n$htmlid' array='$datavar'></div>");
	echo("</div>");
	
	echo("<script>\n");
	echo("$datavar=[");
	
	foreach ($answers as $answer => $count) {
		echo("{y:$count,color:'".$optcolours[$answer]."',text:'".$opts[$answer]."'},");
	}
	
	echo("];\n");
	echo("</script>");
}

function get_category_results(&$categoryinfo) {
	global $opts;
	global $pdo;
	global $stmt_get_polls;
	
	$poll_data = array();
	$answer_totals = array();
	$stmt_get_polls->execute(array(':category' => $categoryinfo['name']));
	while (($row = $stmt_get_polls->fetch(PDO::FETCH_ASSOC)) !== false) {
		$row['answers'] = getpollresults($row['id']);
		$answer_totals[] = array_sum($row['answers']);
		$poll_data[] = $row;
	}
	$categoryinfo['polls'] = $poll_data;
	sort($answer_totals);
	$categoryinfo['median_answer_count'] = $answer_totals[floor(count($answer_totals) / 2)];
}

function show_category_results($categoryinfo) {
	global $opts;
	global $pdo;
	global $stmt_get_polls;
	
	$id = $categoryinfo['name'];
	echo("<a name='$id' id='$id'>");
	pollcategoryheading($categoryinfo);
	echo('<table class="pollsection">');
	foreach ($categoryinfo['polls'] as $row) {
		$pollid = $row['id'];
		$val = $row['name'];
		$desc = $row['description'];
		$answers = $row['answers'];
		echo("<tr class='poll'><th>");
		echo($desc);
		echo("<div class='answermeta'>");
		echo("Total answers: " . array_sum($answers));
		echo("</div>");
		echo("</th>");
		echo("<td>");
		pollresults($id . '_' . $val, $answers);
		echo("</td>");
		echo("</tr>");
	}
	echo("</table>");
	echo("</a>");
}

function allresults() {
	global $stmt_get_pollcategories;
	
	$all_category_results = array();
	$stmt_get_pollcategories->execute();
	while (($row = $stmt_get_pollcategories->fetch(PDO::FETCH_ASSOC)) !== false) {
		get_category_results($row);
		$all_category_results[] = $row;
	}
	
	usort($all_category_results, sort_by_median_answer_count(true));
	
	echo('<div class="polls">');
	echo("<a class='btn btnright' href='coinbase.php'>Click here to take the poll</a>");
	foreach ($all_category_results as $row) {
		show_category_results($row);
	}
	echo('</div>');
}

function chartscripting() {
	echo("<script src='//ajax.googleapis.com/ajax/libs/dojo/1.8.9/dojo/dojo.js' data-dojo-config='async: 1, parseOnLoad: 1'></script>");
	echo("<script>\n");
	echo("require([");
	echo('"dojo/parser",');
	echo('"dojox/charting/widget/Chart",');
	echo('"dojox/charting/themes/Claro",');
	echo('"dojox/charting/plot2d/Pie"');
	echo("]);\n");
	echo("</script>");
}

pageheader();
allresults();
chartscripting();
pagefooter();
