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
			'referrer'		=> $_SERVER['HTTP_REFERER'],
		);

		//check whether api key is valid
		if (!$this->test())
		{
			trigger_error('Could not connect to Akismet');
		}
	}

	private function http_post($request, $url, $path)
	{
		$useragent = $this->config['app'] . '/' . $this->config['app_ver'] . ' callumacrae/Akismet/ . ' . $this->version;

		$ch = curl_init('http://' . $url . $path);
		curl_setopt_array($ch, array(
			CURLOPT_HEADER			=> false,
			CURLOPT_USERAGENT		=> $useragent,
			CURLOPT_TIMEOUT			=> $this->config['timeout'],
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_POST			=> 1,
			CURLOPT_POSTFIELDS		=> $request,
			CURLOPT_HTTP_VERSION	=> CURL_HTTP_VERSION_1_0,
		));
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

	public function test()
	{
		$test = $this->http_post('key=' . $this->config['api'] . '&blog=' . $this->config['url'], $this->config['akismet_server'], '/' . $this->config['akismet_version'] . '/verify-key');
		return $test == 'valid';
	}
}

$akismet = new Akismet;
var_dump($akismet->test());
