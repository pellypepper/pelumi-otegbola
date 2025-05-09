<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

// Initialize response array
$output = array();
$output['status'] = array();

// Get  API type request
$apiType = isset($_REQUEST['apiType']) ? $_REQUEST['apiType'] : '';
$executionStartTime = microtime(true);
$username = 'ppeliance'; 

// Set base URL based on API type
switch ($apiType) {
    case 'findNearbyPlaceName':
        if (!isset($_REQUEST['lat']) || !isset($_REQUEST['lng'])) {
            sendErrorResponse(400, 'Missing required parameters: lat, lng');
            exit;
        }
        $url = 'http://api.geonames.org/findNearbyPlaceNameJSON?formatted=true&lat=' . 
               $_REQUEST['lat'] . '&lng=' . $_REQUEST['lng'] . 
               '&username=' . $username . '&style=full';
        break;

    case 'countryInfo':
        if (!isset($_REQUEST['country'])) {
            sendErrorResponse(400, 'Missing required parameter: country');
            exit;
        }
        $lang = isset($_REQUEST['lang']) ? $_REQUEST['lang'] : 'en';
        $url = 'http://api.geonames.org/countryInfoJSON?formatted=true&lang=' . 
               $lang . '&country=' . $_REQUEST['country'] . 
               '&username=' . $username . '&style=full';
        break;

    case 'search':
        if (!isset($_REQUEST['q'])) {
            sendErrorResponse(400, 'Missing required parameter: q');
            exit;
        }
        $maxRows = isset($_REQUEST['maxRows']) ? $_REQUEST['maxRows'] : 10;
        $url = 'http://api.geonames.org/searchJSON?formatted=true&q=' . 
               urlencode($_REQUEST['q']) . '&maxRows=' . $maxRows . 
               '&username=' . $username . '&style=full';
        break;

    default:
        sendErrorResponse(400, 'Invalid API type');
        exit;
}

// Make API request
$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, $url);

$result = curl_exec($ch);

if ($result === false) {
    sendErrorResponse(500, 'cURL error: ' . curl_error($ch));
    curl_close($ch);
    exit;
}
curl_close($ch);

// Parse the JSON response
$decode = json_decode($result, true);

if ($decode === null) {
    sendErrorResponse(500, 'JSON parse error: ' . json_last_error_msg());
    exit;
}

if (isset($decode['status']) && isset($decode['status']['message'])) {
    sendErrorResponse(400, 'API error: ' . $decode['status']['message']);
    exit;
}

// Prepare output
$output['status']['code'] = 200;
$output['status']['name'] = "ok";
$output['status']['description'] = "success";
$output['status']['returnedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";

// Extract only the data block
$output['data'] = isset($decode['geonames']) ? array_slice($decode['geonames'], 0, 4) : [];

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($output);
exit;

// Error response helper
function sendErrorResponse($code, $message) {
    global $executionStartTime;
    $response = array(
        'status' => array(
            'code' => $code,
            'name' => 'error',
            'description' => $message,
            'returnedIn' => intval((microtime(true) - $executionStartTime) * 1000) . " ms"
        )
    );
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($response);
}
