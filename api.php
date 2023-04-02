<?php
// Include the configuration and database files
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';
define('API_BASE_PATH', '/api/v1');

// Get the HTTP method and the API endpoint from the request
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = parse_url($_SERVER['REDIRECT_SCRIPT_URL'], PHP_URL_PATH);
$endpoint = substr($request_uri, strlen(API_BASE_PATH));


// Get the API key from the request headers or parameters
$api_key = isset($_SERVER['HTTP_API_KEY']) ? $_SERVER['HTTP_API_KEY'] : $_REQUEST['api_key'];

if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    // If the request is not made over HTTPS, return a 403 Forbidden response
    http_response_code(403);
    echo 'HTTPS is required';
    exit();
}

if (!$api_key) {
    http_response_code(401); // Unauthorized
    exit('Api key missing');
}

// Authenticate the API key
if (!Auth::authenticateApiKey($api_key)) {
    http_response_code(401); // Unauthorized
    exit('Invalid API key');
}

// Throttle Control implementation
$timestamp = time(); // Current timestamp
$last_accessed_timestamp = Auth::lastRequest($api_key);
$last_authorised_access = $last_accessed_timestamp[0]['request_timestamp']; // Last accessed timestamp from database within allowed limit
$api_request_counter = $last_accessed_timestamp[0]['request_counter'];


$time_elapsed = $timestamp - $last_authorised_access;

if ($time_elapsed > THROTTLE_INTERVAL) {
    // Access is allowed
    // Reset counter & update timestamp in database	
    DB::query('UPDATE api_keys SET request_counter = ?, request_timestamp = ? WHERE api_key = ?', array(1, time(), $api_key));
} else {
    if ($api_request_counter < REQUEST_LIMIT) {
        // Allow access & increment counter
        $api_request_counter++;
        DB::query('UPDATE api_keys SET request_counter = ? WHERE api_key = ?', array($api_request_counter, $api_key));
    } else {
        //Prevent access	
        $next_access_in = (THROTTLE_INTERVAL - $time_elapsed);
        exit("You are allowed to access the api for a maximum of " . REQUEST_LIMIT . " times in " . THROTTLE_INTERVAL . " seconds. Try again after " . $next_access_in . " seconds");
    }
}


// Handle GET requests for the /users endpoint
if ($method == 'GET' && $endpoint == '/users') {
    // Get all the users from the database	
    $stmt = DB::query('SELECT * FROM users');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the users as a JSON response
    header('Content-Type: application/json');
    echo json_encode($users);
}

// Handle GET requests for the /users/{id} endpoint
if ($method == 'GET' && preg_match('/^\/users\/(\d+)$/', $endpoint, $matches)) {
    // Get the user with the specified ID from the database	
    $stmt = DB::query('SELECT * FROM users WHERE id = ?', array($matches[1]));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if the user exists
    if (!$user) {
        http_response_code(404); // Not Found
        exit('User not found');
    }

    // Return the user as a JSON response
    header('Content-Type: application/json');
    echo json_encode($user);
}

// Handle POST requests for the /users endpoint
if ($method == 'POST' && $endpoint == '/users') {
    // Get the username and password from the request body		
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Insert the user into the database
    DB::query('INSERT INTO users (username, password) VALUES (?, ?)', array($username, $password));

    // Return a success message as a JSON response
    header('Content-Type: application/json');
    echo json_encode(array('message' => 'User created successfully'));
}


if ($method == 'PUT' && preg_match('/^\/users\/(\d+)$/', $endpoint, $matches)) {
    // Get the user ID from the endpoint
    $user_id = $matches[1];

    // Get the user with the specified ID from the database
    $stmt = DB::query('SELECT * FROM users WHERE id = ?', array($user_id));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if the user exists
    if (!$user) {
        http_response_code(404); // Not Found
        exit('User not found');
    }

    // Get the new username and password from the request body
    $new_username = $_POST['username'];
    $new_password = $_POST['password'];

    // Update the user in the database
    DB::query('UPDATE users SET username = ?, password = ? WHERE id = ?', array($new_username, $new_password, $user_id));

    // Return a success message as a JSON response
    header('Content-Type: application/json');
    echo json_encode(array('message' => 'User updated successfully'));
}


// Handle DELETE requests for the /users/{id} endpoint
if ($method == 'DELETE' && preg_match('/^\/users\/(\d+)$/', $endpoint, $matches)) {

    // Check if the user exists
    $user_id = $matches[1];
    $stmt = DB::query('SELECT * FROM users WHERE id = ?', array($user_id));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        http_response_code(404); // Not Found
        exit('User not found');
    }

    // Get the user with the specified ID from the database	
    $stmt = DB::query('DELETE FROM users WHERE id = ?', array($matches[1]));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);



    // Return the user as a JSON response
    header('Content-Type: application/json');
    echo json_encode(array('message' => 'User deleted successfully'));
}