<?php

/**
 * @package Akismet Library
 * @author Callum Macrae
 * @version 0.0.1
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

class Akismet
{
	private $config = array();
	private $version = '0.0.1';

	//prevents potentially sensitive information from being sent
	private $ignore = array(
		'HTTP_COOKIE',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_X_FORWARDED_HOST',
		'HTTP_MAX_FORWARDS',
		'HTTP_X_FORWARDED_SERVER',
		'REDIRECT_STATUS',
		'SERVER_PORT',
		'PATH',
		'DOCUMENT_ROOT',
		'SERVER_ADMIN',
		'QUERY_STRING',
		'PHP_SELF',
	);

	public function __construct()
	{
		require('./config.php');
		$this->config = $config;

		if ($this->config['url_auto'])
		{
			//lets take getting the url far too seriously:
			$this->config['url'] = ($_SERVER['HTTPS'] == 'on' ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : null) . '/';
		}

		//start to populate the comment data
		$this->comment = array(
			'blog'		=> $this->config['url'],
			'user_agent'	=> $_SERVER['HTTP_USER_AGENT'],
			'referrer'	=> $_SERVER['HTTP_REFERER'],
		);

		//check whether api key is valid
		//$this->test();
	}

	private function http_post($request, $url, $path)
	{
		$useragent = $this->config['app'] . '/' . $this->config['app_ver'] . ' callumacrae/Akismet/ . ' . $this->version;

		$ch = curl_init('http://' . $url . $path);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		$var = curl_exec($ch);
		curl_close($ch);
		return $var;
	}

	public function test()
	{
		$test = $this->http_post('key=' . $this->config['api'] . '&blog=' . $this->config['url'], $this->config['akismet_server'], '/' . $this->config['akismet_version'] . '/verify-key');
		return $test == 'valid';
	}
}

$akismet = new Akismet;
var_dump($akismet->test());
