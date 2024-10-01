<?php
// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error_log.txt'); // Log errors to error_log.txt
error_reporting(E_ALL); // Report all errors and warnings

$csvFile = dirname(__FILE__) . '/bus_search.csv'; // Updated CSV file name

// Log CSV file access attempt
if (!file_exists($csvFile)) {
    error_log("CSV file not found: $csvFile");
    die("CSV file not found.");
}

error_log("CSV file found: $csvFile");

?>
<?php if ($_SERVER['REQUEST_METHOD'] === 'GET'): ?>
<?php

    // Capture the distance from the form (assuming it was submitted via GET)
    $userDistance = isset($_POST['distance']) ? intval($_POST['distance']) : 0;
    $defaultDistance = $userDistance > 0 ? $userDistance : 50; // Default to 50 miles if no distance is provided

    error_log("User-provided distance: $defaultDistance");

    // Assuming city and state are stored in dataArray, validate if these variables exist
    if (isset($dataArray['toCity']) && isset($dataArray['toState'])) {
        $city = $dataArray['toCity'];
        $state = $dataArray['toState'];
        error_log("To City: $city, To State: $state");
    } else {
        error_log("Missing city or state in dataArray.");
        die("City and state are required.");
    }

    // Read CSV file and log the reading process
    error_log("Reading CSV data from: $csvFile");
    $csvData = array_map('str_getcsv', file($csvFile));

    $tofilteredData = array();

    // Geocoding API
    $apiKey = ''; // Replace with your actual API key
    $cityState = urlencode($city . ',' . $state);
    $geocodeApiUrl = "https://maps.googleapis.com/maps/api/geocode/json?address={$cityState}&key={$apiKey}";

    error_log("Geocoding API request URL: $geocodeApiUrl");

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

    // Log the obtained latitude and longitude from the API
    $userCityCenterLatitude = $data['results'][0]['geometry']['location']['lat'];
    $userCityCenterLongitude = $data['results'][0]['geometry']['location']['lng'];
    error_log("Geocoding API success - Latitude: $userCityCenterLatitude, Longitude: $userCityCenterLongitude");

    // Haversine formula to calculate the distance in miles
    function haversineDistanceInMiles($lat1, $lon1, $lat2, $lon2)
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

    // Log the distance calculations and filtering process
    foreach ($csvData as $index => $row) {
        if ($index === 0) {
            continue; // Skip header row
        }

        // Ensure latitude and longitude columns exist and are numeric (column 9 for longitude, 10 for latitude)
        if (!isset($row[8]) || !isset($row[9]) || !is_numeric($row[8]) || !is_numeric($row[9])) {
            error_log("Invalid latitude or longitude in row $index. Latitude: {$row[9]}, Longitude: {$row[8]}");
            continue;
        }

        $stationLatitude = floatval($row[9]); // Convert Latitude column index to float (column 10)
        $stationLongitude = floatval($row[8]); // Convert Longitude column index to float (column 9)

        // Log the latitude and longitude values
        error_log("Row $index - Latitude: $stationLatitude, Longitude: $stationLongitude");

        $distanceToCityCenter = haversineDistanceInMiles($userCityCenterLatitude, $userCityCenterLongitude, $stationLatitude, $stationLongitude);

        // Log the calculated distance
        error_log("Row $index - Calculated distance: $distanceToCityCenter miles");

        if ($distanceToCityCenter <= $defaultDistance) {
            $tofilteredData[] = array(
                'station_name' => $row[7],  // Station Name from your file
                'station_type' => $row[1],  // Station Name from your file
                'address' => $row[6],       // Address
                'code' => $row[10],       // Code
                'distance_to_city_center' => round($distanceToCityCenter, 2) // Distance rounded to 2 decimal places
            );
            error_log("Row $index - Station within distance: {$row[7]}, Distance: $distanceToCityCenter miles");
        }
    }

    // Log filtered data count
    error_log("Filtered data count: " . count($tofilteredData));

?>
<?php endif; ?>
