<?php

$start_time = microtime(true);

define('IS_DEVELOPMENT', FALSE);
require 'token.php';

//show_error(503, "503 Service Unavailable", "We are currently under maintenance.");

function show_error($response_code, $status_code, $message) 
{
    http_response_code($response_code);
    header('Content-Type: application/json');
    echo json_encode(array('status_code' => $status_code, 'message' => $message));
//    include("error.php");
    die;
}

function validate_post() 
{
    global $headers;
    
    if ($_SERVER["REQUEST_METHOD"] != 'POST') {
        show_error(200, "405 Method Not Allowed", "Invalid Method");
    }
    if (!isset($headers['Content-Type']) || $headers['Content-Type'] != 'application/json') {
        show_error(200, "400 Bad Request", "Invalid Content Type");
    }
}

function validate_get()
{
    if ($_SERVER["REQUEST_METHOD"] != 'GET') {
        show_error(200, "405 Method Not Allowed", "Invalid Method");
    }
}

if (function_exists("getallheaders")) {
    $headers = getallheaders();
} else {
    $headers['x-api-key'] = isset($_SERVER["HTTP_X_API_KEY"]) ? $_SERVER["HTTP_X_API_KEY"] : "";
    $headers['Content-Type'] = isset($_SERVER["CONTENT_TYPE"]) ? $_SERVER["CONTENT_TYPE"] : "";
}
var_dump($headers);
if (!isset($headers['x-api-key']) || $headers['x-api-key'] != X_API_KEY_TOKEN) {
    show_error(200, "401 Unauthorized", "Invalid Token");
}

//print_r($headers);
//echo "_SERVER[\"REQUEST_METHOD\"]" . $_SERVER["REQUEST_METHOD"] . "\r\n";
//echo "_SERVER[\"QUERY_STRING\"]" . $_SERVER["QUERY_STRING"] . "\r\n";

$query_string = isset($_SERVER["QUERY_STRING"]) ? $_SERVER["QUERY_STRING"] : "";
$params = explode("/", $query_string);

$service = isset($params[0]) ? $params[0] : "";
//var_dump($params);

// validate service ...
switch ($service) {
    case 'leaderboard' :
        validate_get();
        break;
    case 'save' :
        validate_post();
        $input = file_get_contents("php://input");
        break;
    default :
        show_error(200, "503 Service Unavailable", "Invalid Service");
}

// valid service goes here ... then try 'execute service' ...
try {
    $service_result = include($service.'.php');
} catch (Exception $ex) {
    show_error(200, "500 Internal Server Error", $ex->getMessage());
}
        
$end_time = microtime(true);

$service_result['execution_time'] = number_format($end_time - $start_time, 5);
$service_result['memory_usage'] = memory_get_usage(true);

header('Content-Type: application/json');

echo json_encode($service_result);
