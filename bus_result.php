<?php
// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error_log.txt'); // Log errors to error_log.txt
error_reporting(E_ALL); // Report all errors and warnings
?>
<!DOCTYPE html>
<html>

<head>
    <title>Bus Station Search</title>
    <style>
        table th {
            background-color: #f2f2f2;
        }

        p.not_found {
            text-align: center;
            font-size: 20px;
        }

        .form-row {
            display: inline-grid;
            align-items: center;
            margin-bottom: 10px;
        }

        .form-row label {
            margin-right: 10px;
            min-width: 100px;
        }

        .form-row select,
        .form-row input[type="text"] {
            flex: 1;
            padding: 5px;
        }

        .container_fromcity, .container_tocity {
            width: 47.5%;
            margin-top: 50px;
            padding: 18px;
            border-radius: 5px;
            background: #f2f2ff8a !important;
            margin-bottom: 40px;
            display: inline-grid;
        }

        .container_fromcity {
            margin-left: 25px;
            margin-right: 6px;
        }

        .container_tocity {
            margin-right: 25px;
            margin-left: 6px;
        }

        button {
            padding: 5px 15px;
            border: 1px solid #aaaaaa;
            background: #efefef;
        }

        table {
            border-collapse: collapse;
            width: 90%;
            margin: 0px auto;
        }

        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        th.fix.sorting {
            width: 84px !important;
        }

        td {
            font-weight: 400;
        }

        .from_station, .to_station {
            padding-top: 5px;
            padding-bottom: 5px;
            margin-bottom: 40px;
            background-color: #333333;
            text-align: center;
            font-size: 22px;
            color: white;
            border-radius: 4px;
        }

        .arrow {
            cursor: pointer;
        }
    </style>
    <?php 
    include 'fromcity_bus.php'; 
    include 'tocity_bus.php';
    error_log("Included fromcity_Bus.php and tocity_Bus.php successfully.");
    ?>
</head>

<body>

    <!-- From City Bus Station Table -->
    <div class="container_fromcity">
        <h2 class="from_station">From Bus Stations of
            <span>(<?php echo htmlspecialchars($dataArray['fromCity'] . ', ' . $dataArray['fromState']); ?>)</span>
        </h2>

        <?php
        // Log filtered data check
        if (empty($filteredData)) {
            error_log("No data found for From Bus Stations within 50 miles.");
            echo "<p>No data found for the given city and state within 50 miles.</p>";
        } else {
            error_log("Displaying From Bus Stations table.");
        ?>
            <table id="Bus-from-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th data-sortable="true" data-field="station_name">
                            Station Name <span class="arrow"></span>
                        </th>
                        <th data-sortable="true" data-field="station_type">
                            Station Type <span class="arrow"></span>
                        </th>
                        <th data-sortable="true" data-field="address">
                            Address <span class="arrow"></span>
                        </th>
                        <th data-sortable="true" data-field="code">
                            Code <span class="arrow"></span>
                        </th>
                        <th data-sortable="true" data-field="distance">
                            Distance (mi) <span class="arrow"></span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filteredData as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['station_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['station_type']); ?></td>
                            <td><?php echo htmlspecialchars($item['address']); ?></td> <!-- Added address column -->
                            <td><?php echo htmlspecialchars($item['code']); ?></td>
                            <td><?php echo round($item['distance_to_city_center'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php } ?>
    </div>

    <!-- To City Bus Station Table -->
    <div class="container_tocity">
        <h2 class="to_station">To Bus Stations of
            <span>(<?php echo htmlspecialchars($dataArray['toCity'] . ', ' . $dataArray['toState']); ?>)</span>
        </h2>

        <?php
        // Log filtered data check for To City Bus stations
        if (empty($tofilteredData)) {
            error_log("No data found for To Bus Stations within 50 miles.");
            echo "<p>No data found for the given city and state within 50 miles.</p>";
        } else {
            error_log("Displaying To Bus Stations table.");
        ?>
            <table id="Bus-to-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th data-sortable="true" data-field="station_name">
                            Station Name <span class="arrow"></span>
                        </th>
                        <th data-sortable="true" data-field="station_type">
                            Station Type <span class="arrow"></span>
                        </th>
                        <th data-sortable="true" data-field="address">
                            Address <span class="arrow"></span>
                        </th>
                        <th data-sortable="true" data-field="code">
                            Code <span class="arrow"></span>
                        </th>
                        <th data-sortable="true" data-field="distance">
                            Distance (mi) <span class="arrow"></span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tofilteredData as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['station_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['station_type']); ?></td>
                            <td><?php echo htmlspecialchars($item['address']); ?></td> <!-- Added address column -->
                            <td><?php echo htmlspecialchars($item['code']); ?></td>
                            <td><?php echo round($item['distance_to_city_center'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php } ?>
    </div>

    <!-- Scripts for DataTables -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
    <script>
        // Log the initialization of DataTables
        $(document).ready(function () {
            console.log("Initializing DataTable for From Bus Stations.");
            $('#Bus-from-table').DataTable({
                "paging": false,
                "searching": false,
                "info": false,
                "order": [[4, "asc"]] // Sort by the 5th column (Distance) in ascending order
            });

            console.log("Initializing DataTable for To Bus Stations.");
            $('#Bus-to-table').DataTable({
                "paging": false,
                "searching": false,
                "info": false,
                "order": [[4, "asc"]] // Sort by the 5th column (Distance) in ascending order
            });
        });
    </script>

</body>

</html>
