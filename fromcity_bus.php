<?php
// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error_log.txt'); // Log errors to error_log.txt
error_reporting(E_ALL); // Report all errors and warnings

$csvFile = dirname(__FILE__) . '/bus_search.csv'; // Using your provided CSV file

// Log CSV file access attempt
if (!file_exists($csvFile)) {
    error_log("CSV file not found: $csvFile");
    die("CSV file not found.");
}

error_log("CSV file found: $csvFile");

?>

<?php if ($_SERVER['REQUEST_METHOD'] === 'GET') : ?>
<?php

 // Capture the distance from the form (assuming it was submitted via GET)
$userDistance = isset($_POST['distance']) ? intval($_POST['distance']) : 0;
$defaultDistance = $userDistance > 0 ? $userDistance : 50; // Default to 50 miles if no distance is provided

error_log("User-provided distance: $defaultDistance");

// Assuming city and state are stored in dataArray, validate if these variables exist
if (isset($dataArray['fromCity']) && isset($dataArray['fromState'])) {
    $city = $dataArray['fromCity'];
    $state = $dataArray['fromState'];
    error_log("from City: $city, from State: $state");
} else {
    error_log("Missing city or state in dataArray.");
    die("City and state are required.");
}

// Read CSV file and log the reading process
error_log("Reading CSV data from: $csvFile");
$csvData = array_map('str_getcsv', file($csvFile));

$tofilteredData = array();

// Geocoding API
$apiKey = ''; // Replace with your real API key
$cityState = urlencode($city . ',' . $state);
$geocodeApiUrl = "https://maps.googleapis.com/maps/api/geocode/json?address={$cityState}&key={$apiKey}";

error_log("Geocoding API request URL: $geocodeApiUrl");

// Get the response from the API and log any potential errors
$response = file_get_contents($geocodeApiUrl);
if ($response === FALSE) {
    error_log("Geocoding API request failed for URL: $geocodeApiUrl");
    die('Unable to get location data for the specified city and state.');
}

$data = json_decode($response, true);

if ($data['status'] !== 'OK') {
    error_log("Geocoding API error: " . $data['status']);
    die('Unable to get location data for the specified city and state.');
}

// Log geocoded coordinates
$userCityCenterLatitude = $data['results'][0]['geometry']['location']['lat'];
$userCityCenterLongitude = $data['results'][0]['geometry']['location']['lng'];
error_log("Geocoding API success - Latitude: $userCityCenterLatitude, Longitude: $userCityCenterLongitude");

// Haversine formula to calculate the distance in miles
function haversineDistance($lat1, $lon1, $lat2, $lon2)
{
    $earthRadius = 3959; // Earth's radius in miles
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

// Filter data based on user input city name and distance
$filteredData = [];
foreach ($csvData as $index => $row) {
    if ($index === 0) {
        continue; // Skip header row
    }

    // Ensure latitude and longitude columns exist and are numeric (column 9 for longitude, 10 for latitude)
        if (!isset($row[8]) || !isset($row[9]) || !is_numeric($row[8]) || !is_numeric($row[9])) {
            error_log("Invalid latitude or longitude in row $index. Latitude: {$row[9]}, Longitude: {$row[8]}");
            continue;
        }

    $stationLatitude = $row[9]; // Latitude column index (set to column 9)
    $stationLongitude = $row[8]; // Longitude column index (set to column 8)

    // Check if lat/lon are numeric and valid
    if (!is_numeric($stationLatitude) || !is_numeric($stationLongitude)) {
        error_log("Invalid latitude or longitude for row $index. Lat: $stationLatitude, Lon: $stationLongitude");
        continue;
    }

    $distanceToCityCenter = haversineDistance($userCityCenterLatitude, $userCityCenterLongitude, $stationLatitude, $stationLongitude);

    // Log the distance calculation
    error_log("Row $index - Station: " . $row[7] . " - Distance: $distanceToCityCenter miles");

    if ($distanceToCityCenter <= $defaultDistance) {
        $filteredData[] = array(
            'station_name' => $row[7],  // Station Name from your file
            'station_type' => $row[1],  // Station Name from your file
            'address' => $row[6],       // Address
            'code' => $row[10],       // Code
            'distance_to_city_center' => round($distanceToCityCenter, 2) // Rounded distance
        );
    }
}

// Log filtered results
error_log("Filtered data count: " . count($filteredData));

?>
<?php endif; ?>
