<?php
class Authorizer {
	const TOKEN_GRANT = 1;
	const TOKEN_CHECK = 2;
	const TOKEN_CLEAR = 9;
	const WRONG_INPUT = 0;


	const RESULT_TOKEN = 1;
	const RESULT_ACCESS = 2;
	const RESULT_FAIL = 0;

	private $username;
	private $password;

	private $openssl_method;
	private $openssl_key;

	private $salt;
	public $lifetime;
	private $expire;

	public $connection;

	function __construct($method, $key, $salt, $expire)
	{
		$this->username = "";
		$this->password = "";
		$this->openssl_method = $method;
		$this->openssl_key = md5($key);
		$this->salt = md5($salt);
		$this->lifetime = $expire*60;
		$this->expire = 0;
		$this->connection = false;
	}

	function username() { return $this->username; }
	function password()	{ return $this->password; }

	function parseHeader() {
		$headers = array_change_key_case(apache_request_headers());
		$header = $headers['authorization'] ?: '';
		$parts = explode(' ', $header);
		$type = strtolower($parts[0]) ?: '';
		$value = $parts[1] ?: '';
		switch ($type) {
			case 'close':	// close session, clear token
				return self::TOKEN_CLEAR;
			case 'basic':	// authentication with username and password
				$credentials = json_decode(base64_decode($value));
				$this->username = urldecode($credentials[0]);
				$this->password	= urldecode($credentials[1]);
				return self::TOKEN_GRANT;
			case 'bearer':	// authorization with token
				$this->token = base64_decode($value);
				return self::TOKEN_CHECK;
			default:		// all other cases - nothing happens
				return self::WRONG_INPUT;

		}
	}

	function authentication()
	{
		session_start([
			"gc_maxlifetime" => $this->lifetime,
			"use_strict_mode" => true
		]);
		$session_id = session_id();
		$this->expire = time() + $this->lifetime;
		$credentials = $this->encrypt_credentials();
		$_SESSION['credentials'] = $credentials;
		$_SESSION['expire'] = $this->expire;
		$_SESSION['hash'] = $this->hash($credentials, $session_id);
		session_write_close();
		return base64_encode($session_id);
	}

	function authorization( )
	{
		session_id($this->token);
		session_start();
		$credentials = $_SESSION["credentials"];
		$this->expire = $_SESSION['expire'];
		$hash = $this->hash($credentials, $this->token);
		if ($hash != $_SESSION['hash']) return false;
		if ($this->expire < time()) {
			$this->close();
			return false;
		}
		$this->decrypt_credentials($credentials);
		return true;
    }

	function close() {
		session_unset();
		session_destroy();
		session_write_close();
	}

	/*
	function authorization( )
	{
		$incoming = json_decode($this->decrypt($this->token), true);
		$credentials = $incoming["info"];
		$this->expire = $incoming["expire"];
		$hash = $this->hash($credentials);
		if ($hash != $incoming["hash"] || $this->expire < time()) return false;
		$this->decrypt_credentials($credentials);
		return true;
    }
	 *
	 */


	function fingerprints($encrypted_credentials, $session_id) {
		return [
			$session_id,
			$encrypted_credentials,
			$this->salt,
			$this->expire,
			$_SERVER['HTTP_USER_AGENT'],
			$_SERVER['HTTP_X_FORWARDED_FOR'],
			$_SERVER['HTTP_X_REAL_IP'],
			$_SERVER['REMOTE_ADDR'],
			$_SERVER['REMOTE_HOST'],
			$_SERVER['HTTP_ACCEPT_CHARSET'],
			$_SERVER['HTTP_ACCEPT_ENCODING'],
			$_SERVER['HTTP_ACCEPT_LANGUAGE'],
			$_SERVER['HTTP_REFERER'],
		];
	}

	function hash($encrypted_credentials, $session_id)
	{
		return md5(implode('sun',$this->fingerprints($encrypted_credentials, $session_id)));
	}

	function decrypt_credentials($token_id) {
		$str = base64_decode($token_id, true);
		$arr = explode(":::", $str);
		$iv = $this->decrypt($arr[2]);
		$this->password = $this->decrypt($arr[0], $iv, $this->expire);
		$this->username = $this->decrypt($arr[1], $iv, $this->expire);
	}

	function encrypt_credentials() {
		$iv = random_bytes(8);
		$parts = [];
		$parts[] = $this->encrypt($this->password, $iv, $this->expire);
		$parts[] = $this->encrypt($this->username, $iv, $this->expire);
		$parts[] = $this->encrypt($iv);
		$str = base64_encode(implode(":::",$parts));
		return $str;
	}

	function encrypt($data, $iv = '_default', $more_salt = '') {
		return openssl_encrypt ( $data , $this->openssl_method , md5($this->openssl_key.$more_salt),0,$iv);
	}

	function decrypt($data, $iv = '_default', $more_salt = '') {
		return openssl_decrypt ( $data , $this->openssl_method , md5($this->openssl_key.$more_salt),0,$iv);
	}
}
