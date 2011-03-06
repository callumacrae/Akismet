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

	public function __construct($fallback = false, $fallback_args = false)
	{
		require('./config.php');
		$this->config = $config;

		if ($this->config['url_auto'])
		{
			//lets take getting the url far too seriously:
			$this->config['url'] = ($_SERVER['HTTPS'] == 'on' ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : null) . '/';
		}

		//check whether api key is valid
		if (!$this->test() && $fallback)
		{
			if (!is_array($fallback_args))
			{
				$fallback_args = array($fallback_args);
			}
			call_user_func_array($fallback, $fallback_args);
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
	
	public function check_spam($user)
	{
		$info = array(
			'blog=' . $this->config['url'],
			'user_ip=' . $_SERVER['REMOTE_ADDR'],
			'user_agent=' . $_SERVER['HTTP_USER_AGENT'],
			'referrer=' . $_SERVER['HTTP_REFERER'],
		);
		
		if (!empty($user['comment_type']))
		{
			$info[] = 'comment_type=' . $user['comment_type'];
		}
		
		if (!empty($user['comment_author']))
		{
			$info[] = 'comment_author=' . $user['comment_author'];
		}
		
		if (!empty($user['comment_author_email']))
		{
			$info[] = 'comment_author_email=' . $user['comment_author_email'];
		}
		
		if (!empty($user['comment_content']))
		{
			$info[] = 'comment_content=' . $user['comment_content'];
		}
		
		$info = implode('&', $info);
		
		$spam = $this->http_post($info, $this->config['api'] . '.' . $this->config['akismet_server'], '/' . $this->config['akismet_version'] . '/comment-check');
		return $spam == 'true';
	}
}

$akismet = new Akismet('trigger_error', 'Could not connect to Akismet');
$array = array(
	'comment_author'	=> 'test-viagra-123',
);
var_dump($akismet->check_spam($array));
