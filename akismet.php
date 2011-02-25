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
		$length = strlen($request);
		$http_request = <<<EOT
POST $path HTTP/1.1
Host: $this->akismet_server
Content-Type: application/x-www-form-urlencoded; charset=utf-8
Content-Length: $length
User-Agent: {$this->config['app']}/{$this->config['app_ver']} callumacrae/Akismet/$this->version

$request
EOT;
		if (!$fs = fsockopen($this->config['akismet_server'], $this->config['akismet_port'], $errno, $errstr, $this->config['timeout']))
		{
			trigger_error('Failed to make a connection to Akismet server, aborting.', E_USER_ERROR);
		}

		$response = null;
		fwrite($fs, $http_request);
		while(!feof($fs))
		{
			$response .= fgets($fs, 1160);
		}

		fclose($fs);
		return explode("\r\n\r\n", $response, 2);
	}

	public function test()
	{
		print_r($this->http_post('key=' . $this->config['api'] . '&blog=' . $this->config['url'], $this->config['akismet_server'], '/1.1/verify_key'));
	}
}

$akismet = new Akismet;
$akismet->test();
