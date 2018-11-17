<?php
function __autoload ($class) {require_once("classes/$class.php");}

$config = parse_ini_file("config.ini", true);
$driver = new Driver();

$driver->headers(
	$config["security"]["clients"],
	$config["settings"]["timezone"],
	$config["settings"]["errors"]
);

$authorizer = new Authorizer(
	$config["security"]["method"],
	$config["security"]["key"],
	$config["security"]["salt"],
	$config["security"]["expire"]
);

echo json_encode($driver->execute(
		$config["settings"]["host"],
		$authorizer
	));
