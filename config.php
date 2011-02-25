<?php

$config = array(
	//your akismet API key
	'api'	=> 'api here',

	//if set to false, use URL set below
	'url_auto'	=> true,
	'url'		=> false,

	'akismet_port'		=> 80,
	'akismet_server'	=> 'rest.akismet.net',
	'akismet_version'	=> 1.1,

	'timeout'	=> 3, //time to wait in seconds before timing out

	//some details about your application (for Akismet)
	'app'		=> 'Testing App for callumacrae/Akismet', //the name of your app
	'app_ver'	=> '0.0.1' //your app version
);
