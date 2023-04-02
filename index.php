<?php

// Define the base path of the API
define('API_BASE_PATH', '/api/v1');

// Parse the request URI and remove the base path
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_uri = substr($request_uri, strlen(API_BASE_PATH));

// Route the request to the appropriate PHP script
if (preg_match('/^\/users/', $request_uri)) {
  require_once 'api.php';
} else {
  http_response_code(404); // Not Found
  exit('Invalid API endpoint');
}