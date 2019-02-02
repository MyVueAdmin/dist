<?php
function __autoload ($class) {require_once("classes/$class.php");}

$ini = "config.ini"; // path to .ini-file

$config = parse_ini_file($ini, true);

$authorizer = new Authorizer(
	$config["security"]["method"],
	$config["security"]["key"],
	$config["security"]["salt"],
	$config["security"]["expire"],
	$config["access"]
);

$driver = new Driver();
$driver->headers(
	$config["security"]["clients"],
	$config["settings"]["timezone"],
	$config["settings"]["errors"]
);

echo json_encode($driver->execute(
	$config["settings"]["host"],
	$authorizer
));
