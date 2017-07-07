<?php

$i_am_not_direct = true;

require('htmlstuff.php');

pageheader();

echo("<div id='welcome'>");
echo("<h1>Hello!</h1>");
echo("<p>To access KYCPoll, please pass the KYC process to ensure you are a real person.</p>");
echo("<p>KYCPoll currently only supports Coinbase for KYC.</p>");

echo("<a href='coinbase.php'>Click here to continue</a>");
echo("</div>");

pagefooter();
