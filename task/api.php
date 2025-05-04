<?php
// Remove for production
ini_set('display_errors', 'On');
error_reporting(E_ALL);

// Initialize response array
$output = array();
$output['status'] = array();


$logFile = fopen('api_log.txt', 'a');
fwrite($logFile, date('Y-m-d H:i:s') . " - Request: " . print_r($_REQUEST, true) . "\n");

// Get the API type from the request
$apiType = isset($_REQUEST['apiType']) ? $_REQUEST['apiType'] : '';
$executionStartTime = microtime(true);
$username = 'ppeliance'; 


fwrite($logFile, "API Type: $apiType\n");

// Set base URL based on API type
switch ($apiType) {
    case 'findNearbyPlaceName':
        // Validate parameters
        if (!isset($_REQUEST['lat']) || !isset($_REQUEST['lng'])) {
            sendErrorResponse(400, 'Missing required parameters: lat, lng');
            fwrite($logFile, "Error: Missing lat/lng parameters\n");
            fclose($logFile);
            exit;
        }
        
        $url = 'http://api.geonames.org/findNearbyPlaceNameJSON?formatted=true&lat=' . 
               $_REQUEST['lat'] . '&lng=' . $_REQUEST['lng'] . 
               '&username=' . $username . '&style=full';
        break;
    
    case 'countryInfo':
        // Validate parameters
        if (!isset($_REQUEST['country'])) {
            sendErrorResponse(400, 'Missing required parameter: country');
            fwrite($logFile, "Error: Missing country parameter\n");
            fclose($logFile);
            exit;
        }
        
        $lang = isset($_REQUEST['lang']) ? $_REQUEST['lang'] : 'en';
        
        $url = 'http://api.geonames.org/countryInfoJSON?formatted=true&lang=' . 
               $lang . '&country=' . $_REQUEST['country'] . 
               '&username=' . $username . '&style=full';
        break;
    
    case 'search':
        // Validate parameters
        if (!isset($_REQUEST['q'])) {
            sendErrorResponse(400, 'Missing required parameter: q');
            fwrite($logFile, "Error: Missing q parameter\n");
            fclose($logFile);
            exit;
        }
        
        $maxRows = isset($_REQUEST['maxRows']) ? $_REQUEST['maxRows'] : 10;
        
        $url = 'http://api.geonames.org/searchJSON?formatted=true&q=' . 
               urlencode($_REQUEST['q']) . '&maxRows=' . $maxRows . 
               '&username=' . $username . '&style=full';
        break;
    
    default:
        // Invalid API type
        sendErrorResponse(400, 'Invalid API type');
        fwrite($logFile, "Error: Invalid API type\n");
        fclose($logFile);
        exit;
}


fwrite($logFile, "URL: $url\n");

// Make API request
$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, $url);

$result = curl_exec($ch);

// Check for cURL errors
if ($result === false) {
    $error = curl_error($ch);
    sendErrorResponse(500, 'cURL error: ' . $error);
    fwrite($logFile, "cURL Error: $error\n");
    curl_close($ch);
    fclose($logFile);
    exit;
}

curl_close($ch);


fwrite($logFile, "Raw API Response: " . substr($result, 0, 500) . "...\n");

// Parse the JSON response
$decode = json_decode($result, true);

// Check for JSON parsing errors
if ($decode === null) {
    $jsonError = json_last_error_msg();
    sendErrorResponse(500, 'JSON parse error: ' . $jsonError);
    fwrite($logFile, "JSON Parse Error: $jsonError\n");
    fclose($logFile);
    exit;
}

// Check for API errors
if (isset($decode['status']) && isset($decode['status']['message'])) {
    sendErrorResponse(400, 'API error: ' . $decode['status']['message']);
    fwrite($logFile, "API Error: " . $decode['status']['message'] . "\n");
    fclose($logFile);
    exit;
}

// Process the API response
$output['status']['code'] = 200;
$output['status']['name'] = "ok";
$output['status']['description'] = "success";
$output['status']['returnedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";

// Different APIs return data in different formats
if ($apiType == 'countryInfo') {
    $output['data'] = isset($decode['geonames']) ? $decode['geonames'] : array();
} else if ($apiType == 'findNearbyPlaceName') {
    $output['data'] = isset($decode['geonames']) ? $decode['geonames'] : array();
} else if ($apiType == 'search') {
    $output['data'] = isset($decode['geonames']) ? $decode['geonames'] : array();
}
else {
    $output['data'] = array();
}


fwrite($logFile, "Final Output: " . substr(json_encode($output), 0, 500) . "...\n\n");
fclose($logFile);

// Send the response
header('Content-Type: application/json; charset=UTF-8');
echo json_encode($output);
exit;

// Function to send error response
function sendErrorResponse($code, $message) {
    global $executionStartTime;
    
    $response = array();
    $response['status'] = array();
    $response['status']['code'] = $code;
    $response['status']['name'] = "error";
    $response['status']['description'] = $message;
    $response['status']['returnedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($response);
}