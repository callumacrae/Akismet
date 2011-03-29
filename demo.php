<?php

include('akismet.php');

$akismet = new Akismet('trigger_error', 'Could not connect to Akismet');
$array = array(
	'comment_author'	=> 'test-viagra-123', //will always return true :D
);
var_dump($akismet->check_spam($array));