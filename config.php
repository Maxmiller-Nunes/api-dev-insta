<?php
require "./environment.php";

global $config;

$config = array();
if (ENVIRONMENT == "development") {
  define("BASE_URL", "http://localhost/phpdo0aoproficional/Webservice/api-dev-instagram/");

  $config['dbname'] = '';
  $config['host'] = '';
  $config['dbuser'] = '';
  $config['dbpass'] = '';
  $config['jwt_secret_key'] = "abC123!";
} else {
  define("BASE_URL", "http://localhost/phpdo0aoproficional/Webservice/api-dev-instagram/");

  $config['dbname'] = '';
  $config['host'] = '';
  $config['dbuser'] = '';
  $config['dbpass'] = '';
  $config['jwt_secret_key'] = "abC123!";
}

global $pdo;
try {
  $pdo = new PDO("mysql:dbname=" . $config['dbname'] . ";host=" . $config['host'], $config['dbuser'], $config['dbpass']);
} catch (PDOException $e) {
  echo "Error " . $e->getMessage();
  die();
}
