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

function pollresults($varbase, $pollid) {
	global $opts, $optcolours;
	global $pdo;
	
	$htmlid = "viewpoll_$varbase";
	$datavar = "polldata_$varbase";
	
	echo("<div data-dojo-type='dojox/charting/widget/Chart' data-dojo-props='theme:dojox.charting.themes.Claro' id='$htmlid' style='width: 200px; height: 200px;'>");
	echo("<div class='plot' name='default' type='Pie' radius='100' fontColor='#000' labelOffset='0'></div>");
	echo("<div class='series' name='n$htmlid' array='$datavar'></div>");
	echo("</div>");
	
	echo("<script>\n");
	echo("$datavar=[");
	
	$mult = rand(1, 10000);
	
	$stmt = $pdo->prepare('SELECT answer, count FROM totals WHERE pollid = :pollid ORDER BY answer');
	$stmt->execute(array(':pollid' => $pollid));
	while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
		$answer = $row['answer'];
		$count = $row['count'] * $mult;
		
		echo("{y:$count,color:'".$optcolours[$answer]."',text:'".$opts[$answer]."'},");
	}
	
	echo("];\n");
	echo("</script>");
}

function categoryresults($id, $title) {
	global $opts;
	global $pdo;
	global $stmt_get_polls;
	
	echo("<h1>$title</h1>");
	echo('<table class="pollsection">');
	$stmt_get_polls->execute(array(':category' => $id));
	while (($row = $stmt_get_polls->fetch(PDO::FETCH_ASSOC)) !== false) {
		$pollid = $row['id'];
		$val = $row['name'];
		$desc = $row['description'];
		echo("<tr class='poll'><th>$desc</th>");
		echo("<td>");
		pollresults($id . '_' . $val, $pollid);
		echo("</td>");
		echo("</tr>");
	}
	echo("</table>");
}

function allresults() {
	global $pollcategories;
	
	echo('<div class="polls">');
	foreach ($pollcategories as $categoryname => $categoryhuman) {
		categoryresults($categoryname, $categoryhuman);
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
