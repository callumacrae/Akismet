<?php

/**
 * @package Akismet Library
 * @author Callum Macrae
 * @version 1.0.0-RC1
 * @license http://www.gnu.org/licenses/gpl2.html GNU General Public License
 */

class Akismet
{
	private $config = array();
	private $version = '1.0.0-RC1';

	/**
	 * Gets the configuration, attempts to find the URL if it isn't defined in
	 * the configuration, and test whether API is valid and a connection can
	 * be made. If it cannot, call the callback.
	 *
	 * @param array $config Configuration
	 * @param string $fallback The name of the fallback to be called
	 * @param array $fallback_args The params to be passed to the fallback
	 * 	If not an array, it will be converted to one
	 */
	public function __construct($config = false, $fallback = false, $fallback_args = false)
	{
		if (!$config)
		{
			require('./config.php');
		}
		$this->config = $config;

		if ($this->config['url_auto'])
		{
			//lets take getting the url far too seriously:
			$this->config['url'] = ($_SERVER['HTTPS'] == 'on' ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : null) . '/';
		}

		//check whether api key is valid
		if (!$this->test())
		{
			if ($fallback)
			{
				//convert arguments to an array if they're not already
				if (!is_array($fallback_args))
				{
					$fallback_args = array($fallback_args);
				}
				
				//call the fallback
				call_user_func_array($fallback, $fallback_args);
			}
			return false;
		}
		return true;
	}

	/**
	 * Sends HTTP request to Akismet. Uses HTTP/1.0 because Akismet
	 * told me to.
	 *
	 * @param string $request String to be passed via POST to Akismet
	 * @param string $url URL for the request to be sent to
	 * @param string $path Path for the request to be sent to
	 */
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

	/**
	 * Test whether connection can be made
	 */
	public function test()
	{
		$test = $this->http_post('key=' . $this->config['api'] . '&blog=' . $this->config['url'], $this->config['akismet_server'], '/' . $this->config['akismet_version'] . '/verify-key');
		return $test == 'valid';
	}
	
	/**
	 * Gets info from the $user array and formats it into something that
	 * can be sent to Akismet. See the following page for info about what
	 * to put in the array (all required info is done for you):
	 * http://akismet.com/development/api/#comment-check
	 *
	 * @param array $user Array to be formatted
	 */
	private function get_info($user)
	{
		$info = array(
			'blog=' . urlencode($this->config['url']),
			'user_ip=' . $_SERVER['REMOTE_ADDR'],
			'user_agent=' . urlencode($_SERVER['HTTP_USER_AGENT']),
			'referrer=' . urlencode($_SERVER['HTTP_REFERER']),
		);
		
		if (!empty($user['comment_type']))
		{
			$info[] = urlencode('comment_type=' . $user['comment_type']);
		}
		
		if (!empty($user['comment_author']))
		{
			$info[] = urlencode('comment_author=' . $user['comment_author']);
		}
		
		if (!empty($user['comment_author_email']))
		{
			$info[] = urlencode('comment_author_email=' . $user['comment_author_email']);
		}
		
		if (!empty($user['comment_content']))
		{
			$info[] = urlencode('comment_content=' . $user['comment_content']);
		}
		
		$info = implode('&', $info);
		return $info;
	}
	
	/**
	 * Checks whether comment is spam.
	 *
	 * @param array $user Array containing details of what should be sent
	 * 	to Akismet
	 */
	public function check_spam($user)
	{
		$info = $this->get_info($user);
		$spam = $this->http_post($info, $this->config['api'] . '.' . $this->config['akismet_server'], '/' . $this->config['akismet_version'] . '/comment-check');
		return $spam == 'true';
	}

	/**
	 * Submit spam to Akismet
	 *
	 * @param array $user Array containing details of what should be sent
	 * 	to Akismet
	 */
	public function submit_spam($user)
	{
		$info = $this->get_info($user);
		$spam = $this->http_post($info, $this->config['api'] . '.' . $this->config['akismet_server'], '/' . $this->config['akismet_version'] . '/submit-spam');
		return $spam == 'true';
	}

	/**
	 * Submit ham to Akismet
	 *
	 * @param array $user Array containing details of what should be sent
	 * 	to Akismet
	 */
	public function submit_ham($user)
	{
		$info = $this->get_info($user);
		$spam = $this->http_post($info, $this->config['api'] . '.' . $this->config['akismet_server'], '/' . $this->config['akismet_version'] . '/submit-ham');
		return $spam == 'true';
	}
}
