<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uptime Status</title>
    <link rel="stylesheet" href="status.css">

</head>

<body>

    <?php
    try {
        // API URL
        $url = 'https://api.uptimerobot.com/v2/getMonitors';
        // Create a new cURL resource
        $ch = curl_init($url);
        // Setup request to send json via POST
        $apiKey = file_get_contents("api_key.json");
        $apiKey_arr = json_decode($apiKey, true);

        // Get timestamps for the 7 last days
        $last_7_days = "";
        for ($i = 0; $i < 7; $i++) {
            if ($i == 6) {
                $last_7_days = $last_7_days . date(time() - 60 * 60 * 24 * ($i+1)) . "_" . date(time() - 60 * 60 * 24 * ($i));
            } else {
                $last_7_days = $last_7_days . date(time() - 60 * 60 * 24 * ($i+1)) . "_" . date(time() - 60 * 60 * 24 * $i) . "-";
            }
        }

        $parameter = array(
            "response_times" => "1",
            "all_time_uptime_ratio" => "1",
            "custom_uptime_ratios" => "1-7-30",
            "custom_uptime_ranges" => $last_7_days
        );

        $data = array_merge($apiKey_arr, $parameter);
        $data = json_encode($data);

        // Attach encoded JSON string to the POST fields
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        // Set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        // Return response instead of outputting
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the POST request
        $result = curl_exec($ch);

        // Close cURL resource
        curl_close($ch);
    } catch (Exception $e) {
        echo "Error! Is php-curl installed?";
    }


    $jsonData = json_decode($result, true);
    $monitors = $jsonData["monitors"];

    $types = array(
        "1" => "http(s)",
        "2" => "keyword",
        "3" => "ping",
        "4" => "port"
    );

    echo "<h2>Monitoring Status</h2>";
    echo "<hr>";
    echo "<table class='status'>";
    echo "<tr><th>Status</th><th>Last 7 days</th><th>Avg Response Time</th><th>Service</th><th>Type</th><th>Port</th></tr>";
    echo "<tr><td></td><td></td><td></td><td></td><td></td><td></td>";
    for ($i = 0; $i < 7; $i++){
        $dateString = date("d. M", time() - 60 * 60 * 24 * $i);
        echo "<td>" . $dateString . "</td>";
    }
    echo "</tr>";

    $uptime_1_all = 0;
    $uptime_7_all = 0;
    $uptime_30_all = 0;
    $uptime_all_all = 0;

    $uptimes_sum = array(0,0,0,0);
    for ($i = 0; $i < sizeof($monitors); $i++) {
        $monitor = $monitors[$i];

        $uptimes = explode("-", $monitor["custom_uptime_ratio"]);
        $uptime_1 = $uptimes[0];
        $uptime_7 = $uptimes[1];
        $uptime_30 = $uptimes[2];

        $uptimes_sum[0] += $uptimes[0];
        $uptimes_sum[1] += $uptimes[1];
        $uptimes_sum[2] += $uptimes[2];
        $uptimes_sum[3] += $monitor["all_time_uptime_ratio"];
        $uptimes_strings= array("(Last 24 hours)", "(Last 7 days)", "(Last 30 days)", "(All time)");

        $bulletClass = checkColor($uptime_7);

        echo "<tr>";
        echo "<td><span class='bullet " . $bulletClass . "'></span></td>";
        echo "<td>" . $uptime_7 . "%</td>";
        echo "<td>" . $monitor["average_response_time"] . "ms </td>";
        echo "<td>" . $monitor["friendly_name"] . "</td>";
        echo "<td>" . $types[$monitor["type"]] . "</td>";
        echo "<td>" . $monitor["port"] . "</td>";
        $uptimes_7_days =  explode("-", $monitor["custom_uptime_ranges"]);

        foreach ($uptimes_7_days as $day) {
            $bulletClass = checkColor($day);
            echo "<td class=" . $bulletClass . ">" . $day . "%</td>";
        }
        echo "</tr>";
    }
    echo "<tr></tr>";
    echo "</table>";
    echo "<hr>";


    echo "<div id=leftbox><h3>Overall Uptime</h3>";
    echo "<table id='sums'>";
    $i = 0;
    foreach ($uptimes_sum as $uptime) {
        echo "<tr>";
        $up_round = round($uptimes_sum[0] / sizeof($monitors), 2);
        $bulletClass = checkColor($up_round);
        echo "<td><span class='bullet " . $bulletClass . "'></span></td>";
        echo "<td><p>" . $up_round . "% " .  $uptimes_strings[$i] . "</p></td>";
        echo "</tr>";
        $i++;
    }
    echo "</table>";

    echo "</div>";
    echo "<div id=middlebox></div>";
    echo "<div id=rightbox><h3>Quick Stats</h3></div>";

    function checkColor($value)
    {
        if ($value == 100) {
            $bulletClass = "success";
        } elseif ($value >= 90) {
            $bulletClass = "warning";
        } else {
            $bulletClass = "error";
        }
        return $bulletClass;
    }
    ?>


</body>

</html>