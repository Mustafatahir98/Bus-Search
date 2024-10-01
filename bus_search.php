<?php
// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error_log.txt'); // Log errors to error_log.txt
error_reporting(E_ALL); // Report all errors and warnings

/* Template Name: Nearby Bus Search */

if (!is_user_logged_in()) {
    // Check if dealId is set in the URL
    $deal_id = isset($_GET['dealId']) ? $_GET['dealId'] : '';

    // Ensure that dealId is appended to the current page URL
    if (!empty($deal_id)) {
        $current_page_with_deal_id = add_query_arg('dealId', $deal_id, get_permalink());
    } else {
        // If no dealId, just use the current page URL
        $current_page_with_deal_id = get_permalink();
    }

    // Pass the current page URL (with dealId if present) as the redirect_to parameter in the login URL
    $login_url = wp_login_url($current_page_with_deal_id);

    // Redirect to the login URL
    wp_redirect($login_url);
    exit;
}
?>

<?php
// Default distance value
$defaultDistance = 100;
error_log("Default distance set to: $defaultDistance");
?>
<!DOCTYPE html>
<html>

<head>
    <title>Bus Station Search</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">

    <style>
      .top-barr {
            background-color: #027CBA; /* Background color */
            color: #fff; /* Text color */
            padding: 5px 0; 
            text-align: center; 
            margin-bottom: 40px;
        }
        .top-barr h1 {
            font-size: 24px;
            margin: 0;
            padding: 5px;
        }
    </style>
</head>

<body>
    <div style="padding:20px 0px 20px 20px; display:none">
        <h3>Search HubSpot Deal:</h3>
        <form method="POST">
            Enter HubSpot Deal ID: <input type="text" name="dealId" placeholder="Enter HubSpot ID">
            Enter radius Value: <input type="number" id="distance" name="distance" value="" placeholder="<?php echo $defaultDistance; ?>">
            <button type="submit">Fetch Deal</button>
        </form>
    </div>
</body>

</html>

<?php
global $wpdb;

$result = $wpdb->get_results("SELECT token FROM hubspot_access_tokens LIMIT 1");
if (!empty($result)) {
    $accessToken = $result[0]->token;
    error_log("Access token retrieved from the database.");
} else {
    error_log("Failed to retrieve access token from the database.");
}

// Function to fetch deals from HubSpot API
function fetchDeals($url, $headers) {
    $response = @file_get_contents(
        $url,
        false,
        stream_context_create(
            array(
                'http' => array(
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers)
                )
            )
        )
    );

    if ($response === FALSE) {
        error_log("Failed to fetch data from HubSpot API. URL: $url");
        return false;
    }

    error_log("Successfully fetched data from HubSpot API. URL: $url");

    return array(
        'response' => $response,
        'http_code' => isset($http_response_header[0]) ? $http_response_header[0] : 'No HTTP Code'
    );
}

if (isset($_GET['dealId'])) {
    $dealId = intval($_GET['dealId']);
    error_log("Deal ID provided: $dealId");

    // API endpoint to retrieve a specific deal
    $dealUrl = "https://api.hubapi.com/crm/v3/objects/deals/$dealId?properties=amount,closedate,dealname,dealstage,from_city,from_state,to_city,to_state";
    error_log("Fetching deal from HubSpot. Deal URL: $dealUrl");

    $headers = array(
        "Authorization: Bearer $accessToken"
    );

    $dealResponse = fetchDeals($dealUrl, $headers);

    echo '<div class="top-barr">';
    echo "<h2>HubSpot Deal ID: <span>$dealId</span></h2>"; 
    echo '</div>';

    if ($dealResponse === false) {
        error_log("Failed to fetch deal details from HubSpot.");
        echo "API request failed: " . print_r(error_get_last(), true);

    } else {
        // Check if the HTTP response code is 200 (OK)
        if ($dealResponse['http_code'] == 'HTTP/1.1 200 OK') {
            $dealData = json_decode($dealResponse['response'], true);
            error_log("Successfully retrieved deal data for Deal ID: $dealId");

            // Display the deal data if found
            if (isset($dealData['properties'])) {
                echo "<table border='1'>";
                echo "<tr><th>Deal Name</th><th>From City</th><th>From State</th><th>To City</th><th>To State</th><th>Amount</th><th>HubSpot ID</th><th>Close Date</th></tr>";

                $deal = $dealData['properties'];
                $dealName = isset($deal['dealname']) ? $deal['dealname'] : 'N/A';
                $amount = isset($deal['amount']) ? $deal['amount'] : 'N/A';
                $closeDate = isset($deal['closedate']) ? $deal['closedate'] : 'N/A';

                $Hubspot_ID = isset($deal['hs_object_id']) ? $deal['hs_object_id'] : 'N/A';
                $fromCity = isset($deal['from_city']) ? $deal['from_city'] : 'N/A';
                $fromState = isset($deal['from_state']) ? $deal['from_state'] : 'N/A';
                $toCity = isset($deal['to_city']) ? $deal['to_city'] : 'N/A';
                $toState = isset($deal['to_state']) ? $deal['to_state'] : 'N/A';

                $dataArray = array(
                    'fromCity' => $fromCity,
                    'fromState' => $fromState,
                    'toCity' => $toCity,
                    'toState' => $toState
                );

                error_log("Deal data: DealName: $dealName, FromCity: $fromCity, ToCity: $toCity");

                echo "<tr><td>$dealName</td><td>$fromCity</td><td>$fromState</td><td>$toCity</td><td>$toState</td><td>$amount</td><td>$Hubspot_ID</td><td>$closeDate</td></tr>";
                echo "</table>";

                // Include file to perform the Bus search
                include 'bus_result.php';
                error_log("Included Bus_result.php for Bus search.");

            } else {
                error_log("No properties found for the deal with Deal ID: $dealId.");
                echo '<p class="not_found">';
                echo "No deal found with the provided HubSpot ID: $dealId";
            }
        } else if ($dealResponse['http_code'] == 'HTTP/1.1 404 Not Found') {
            error_log("Deal not found for Deal ID: $dealId.");
            echo '<p class="not_found">';
            echo "No deal found with the provided HubSpot ID: $dealId";
        } else {
            error_log("Unexpected HTTP response code for Deal ID: $dealId. Response: " . $dealResponse['http_code']);
            echo '<p class="not_found">';
            echo "An error occurred while fetching the deal data.";
        }
    }
} else {
    error_log("No HubSpot ID provided in the URL.");
    echo '<p class="not_found">';
    echo "No HubSpot ID available in the URL requested";
}
?>