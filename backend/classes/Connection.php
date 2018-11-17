<?php
class Connection extends mysqli
{
	const QUERY_RETURN_NONE = 0;
	const QUERY_RETURN_RESOURCE = 1;
	const QUERY_RETURN_ROW = 2;
	const QUERY_RETURN_ASSOC = 3;
	const QUERY_RETURN_ARRAY = 4;
	const QUERY_RETURN_LIST = 5;
	const QUERY_RETURN_ONEVAL = 6;
	const QUERY_RETURN_ID = 7;
	const QUERY_RETURN_NUM = 101;
	const QUERY_RETURN_AFFECTED = 103;
	const QUERY_RETURN_STATUS = 1001;
	const QUERY_RETURN_AUTO = 9000;
	public $connected;
	public $message;
	function __construct($host, $username, $password, $database = '')
	{
		parent::__construct($host, $username, $password, $database);

		if (mysqli_connect_error()) {
            $this->connected = false;
			$this->message = mysqli_connect_error();
		} else {
			$this->connected = true;
			$this->start();
		}
	}

	function q($sql_in,$mode = self::QUERY_RETURN_RESOURCE)
	{
		$resource = parent::query($sql_in) or die (json_encode([
			"status" => "error",
			"data" => mysqli_error($this),
			"query" => $sql_in]));
		switch ($mode)
		{
			case self::QUERY_RETURN_ASSOC:		return $this->build_assoc($resource);
			case self::QUERY_RETURN_ARRAY:		return $this->build_array($resource);
			case self::QUERY_RETURN_LIST:		return $this->build_list($resource);
			case self::QUERY_RETURN_ROW:		return is_object($resource) ? $resource->fetch_assoc() : false;
			case self::QUERY_RETURN_ONEVAL:		return is_object($resource) ? $resource->fetch_array()[0] : false;
			case self::QUERY_RETURN_NUM:		return is_object($resource) ? $resource->num_rows : false;
			case self::QUERY_RETURN_ID:			return $this->insert_id;
			case self::QUERY_RETURN_AFFECTED:	return $this->affected_rows;
			case self::QUERY_RETURN_STATUS:		return true;
			case self::QUERY_RETURN_NONE:		return null;
			case self::QUERY_RETURN_AUTO:
				if (is_object($resource)) return $this->build_assoc($resource);
				if ($this->insert_id > 0) return $this->insert_id;
				if ($this->affected_rows > 0) return $this->affected_rows;
				return null;
			default: return $resource;
		}


	}

	function build_assoc($r)
	{
		$array = [];
		while ($row = $r->fetch_assoc())
		{
			$array[] = $row;
		}
		return $array;
	}

	function build_list($r)
	{
		$array = [];
		while ($row = $r->fetch_array())
		{
			$array[] = $row[0];
		}
		return $array;
	}

	function build_array($r)
	{
		$array = [];
		while ($row = $r->fetch_array())
		{
			$array[] = $row;
		}
		return $array;
	}

	function start()		{ parent::query("SET NAMES utf8;"); }
	function connected()	{ return $this->connected; }

	function fields($table) { return $this->q("DESCRIBE `$table`", self::QUERY_RETURN_ASSOC);	}
	function total($table, $conditions = "")	{
		$where_conditions = $conditions !== "" ? " WHERE $conditions" : "";
		return $this->q("SELECT COUNT(*) FROM `$table` $where_conditions", self::QUERY_RETURN_ONEVAL);
	}

	function collect_fields($fields, $delimeter)
	{
		$collected = [];
		foreach ($fields as $key => $value)
		{
			$value = $this->real_escape_string($value);
			$collected[] = "`$key`='$value'";
		}
		return implode($collected,$delimeter);
	}

	function collect_filters($fields)
	{
		$delimeter = ' AND ';
		$collected = [];
		foreach ($fields as $key => $value)
		{
			$value = $this->real_escape_string($value);
			$collected[] = "LOWER(`$key`) LIKE LOWER('%$value%')";
		}
		return implode($collected,$delimeter);
	}

	function update($table, $fields, $condition)
	{
		if (count($fields) == 0) return 0;
		$set = $this->collect_fields($fields, ",");
		$where = $this->collect_fields($condition, " AND ");
		$sql = "UPDATE $table SET $set WHERE $where";
		$this->q($sql);
		return count($fields);
	}

	function entry($table, $filters)
	{
		$conditions = $this->collect_fields($filters, ' AND ');
		$sql = "SELECT * FROM `$table` WHERE ($conditions) LIMIT 1";
		return $this->q($sql, self::QUERY_RETURN_ROW);
	}

	function content($table, $params = []) {
		$limit = isset($params["limit"]) ? $params["limit"] : 1;
		$start = isset($params["start"]) ? $params["start"] : 0;
		$order = isset($params["order"]) && $params["order"]!='' ? $params["order"] : false;
		$order_desc = $params["orderDesc"];
		if ($order !== false)
		{
			$order = " ORDER BY $order ";
			$order .= $order_desc == "true" ? "DESC" : "ASC";
		}

		$conditions = "";
		if (isset($params["filters"]))
		{
			$filters = json_decode($params["filters"], true);
			$conditions = $this->collect_filters($filters);
		}

		$where_conditions = $conditions !== "" ? " WHERE $conditions" : "";
		$total = $this->total($table, $conditions);
		$start = max(0,$start);
		if ($start >$total) $start = 0;
		$limit = max(1,$limit);
		$limit = min(100,$limit);

		$sql = "SELECT * FROM $table $where_conditions $order LIMIT $start,$limit";
		$r = $this->q($sql);
		while ($db = $r->fetch_assoc())
		{
			$data[] = $db;
		}

		$answer["rows"] = $data;
		$answer["total"] = $total;

		return $answer;
	}
}