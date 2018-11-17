<?php
class Driver {
	public $connection;
	function execute($host, $authorizer) {
		$answer = ["status" => "fail"];
		switch ($authorizer->parseHeader())
		{
			case Authorizer::TOKEN_GRANT:
				$this->connection = new Connection($host, $authorizer->username(), $authorizer->password());
				if ($this->connection->connected) {
					$answer["status"] = "success";
					$answer["token"] = $authorizer->authentication();
					$answer["expire"] = $authorizer->lifetime;
				} else {
					$answer["status"] = "fail";
					$answer["data"] = $this->connection->message;
				}
			break;
			case Authorizer::TOKEN_CHECK:
				if ($authorizer->authorization() === true) {
					$rawInput = file_get_contents('php://input');
					$input = json_decode($rawInput, true);
					if (!isset($input['database'])) $input['database'] = '';
					$this->connection = new Connection($host, $authorizer->username(), $authorizer->password(), $input['database']);
					if ($this->connection->connected) {
						$answer["status"] = "success";
						$answer["data"] = $this->query($input);
						$answer["total"] = $this->total($input);
					} else {
						$answer["status"] = "fail";
						$answer["data"] = $this->connection->message;
					}
				}
			break;
			case Authorizer::TOKEN_CLEAR:
				$authorizer->close();
				$answer["status"] = "success";
				$answer["data"] = "Bye-bye";
			break;
			default:
				http_response_code (200);
				exit;
			break;
		}

		// $r = setcookie ("test", "lalala",time()+60*60*24*3000,'/','mva.tcrm.online');
		// $answer["test"] = "test = ". $_COOKIE["test"] ." / ".$r;

		return $answer;
	}

	function total($input) {

		$parts = array_filter(explode(' ',trim($input['query'])));
		$type = strtolower($parts[0]);
		$directive = strtolower($parts[1]);

		if ($type === 'select' && $directive === 'sql_calc_found_rows') {
			return  $this->connection->q('SELECT FOUND_ROWS()', Connection::QUERY_RETURN_ONEVAL);
		} else {
			return 0;
		}
	}

	function query($input) {
		if (!isset($input['query']) || $input['query'] == '') return 'no query';
		$answer = $this->connection->q($input['query'], $this->mode($input['return']));
		return $answer;
	}

	function mode($in) {
		switch ($in) {
			case 'array': return Connection::QUERY_RETURN_ASSOC;
			case 'list': return Connection::QUERY_RETURN_LIST;
			case 'row': return Connection::QUERY_RETURN_ROW;
			case 'value': return Connection::QUERY_RETURN_ONEVAL;
			case 'id': return Connection::QUERY_RETURN_ID;
			case 'num': return Connection::QUERY_RETURN_NUM;
			case 'affected': return Connection::QUERY_RETURN_AFFECTED;
			case 'none': return Connection::QUERY_RETURN_NONE;
			case 'status': return Connection::QUERY_RETURN_STATUS;
			case 'auto': return Connection::QUERY_RETURN_AUTO;
			default: return Connection::QUERY_RETURN_ARRAY;
		}
	}

	function headers($clients = [], $timezone = 'GMT+0', $errors = false) {
		$origin = $_SERVER['HTTP_ORIGIN'];
		$all = "*";
		if (in_array($origin, $clients) || in_array($all, $clients)) {
			header('Access-Control-Allow-Origin: *');
		}
		header("Access-Control-Allow-Headers: Authorization, Content-Type, Token, Connection, Keep-Alive");
		header("Access-Control-Allow-Methods: PUT, GET");
		header('Content-Type: application/json; charset=UTF-8');
		date_default_timezone_set($timezone);
		if ($errors) {
			error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
			ini_set('display_errors', 'On');
		} else {
			error_reporting(E_NONE);
			ini_set('display_errors', 'Off');
		}
	}
}
