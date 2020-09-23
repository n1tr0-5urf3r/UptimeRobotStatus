<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="60">
    <title>Uptime Status</title>
    <link rel="stylesheet" href="status.css">
    <link rel="icon" type="image/png" href="img/logo.png">

</head>

<body>
    <div id="content">
        <a href="https://me.ihlecloud.de"><img id="logo" src="img/logo.png" alt="ihlecloud.de"></a><br>
        <?php
        $monitors = callApi();

        $status = array(
            "0" => "paused",
            "1" => "not checked yet",
            "2" => "up",
            "8" => "seems down",
            "9" => "down"
        );
        $uptimes_strings = array("(Last 24 hours)", "(Last 7 days)", "(Last 30 days)", "(All time)");
        $uptimes_sum = array(0, 0, 0, 0);
        $up = 0;
        $down = 0;
        $paused = 0;
        $all_logs = array();


        createTable($monitors);
        echo "<hr>";
        createUptimesSummaryTable($uptimes_sum, $uptimes_strings, $monitors);
        createQuickStatsTable($up, $down, $paused);
        createLastDownTimeTable($all_logs);


        function callApi()
        {
            try {
                // API URL
                $url = 'https://api.uptimerobot.com/v2/getMonitors';
                // Create a new cURL resource
                $ch = curl_init($url);
                // Setup request to send json via POST
                $apiKey = file_get_contents("api_key.json");
                $apiKey_arr = json_decode($apiKey, true);

                // Get timestamps for the 7 last days
                $last_7_days = getLast7Days();

                $parameter = array(
                    "response_times" => "1",
                    "all_time_uptime_ratio" => "1",
                    "custom_uptime_ratios" => "1-7-30",
                    "custom_uptime_ranges" => $last_7_days,
                    "logs" => "1"
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
            return $jsonData["monitors"];
        }

        function formatData($monitors)
        {
            $types = array(
                "1" => "http(s)",
                "2" => "keyword",
                "3" => "ping",
                "4" => "port",
                "5" => "heartbeat"
            );
            global $uptimes_sum;
            global $up;
            global $down;
            global $paused;
            global $all_logs;

            foreach ($monitors as $monitor) {
                $uptimes = explode("-", $monitor["custom_uptime_ratio"]);
                $uptime_7 = $uptimes[1];

                $uptimes_sum[0] += $uptimes[0];
                $uptimes_sum[1] += $uptimes[1];
                $uptimes_sum[2] += $uptimes[2];
                $uptimes_sum[3] += $monitor["all_time_uptime_ratio"];

                array_push($all_logs, $monitor["logs"]);

                if ($monitor["status"] == "2") {
                    $up += 1;
                } elseif ($monitor["status"] == "0") {
                    $paused += 1;
                } else {
                    $down += 1;
                }

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
                    echo "<td><div class='daily " . $bulletClass . "'>" . round($day, 2) . "%</div></td>";
                }
                echo "</tr>";
            }
        }

        function createTable($monitors)
        {
            echo "<h2>Monitoring Status</h2>";
            echo "<hr>";
            echo "<div id='monitoring'>";
            echo "<table class='status'>";
            echo "<tr><th></th><th>Last 7 days</th><th>Avg Response Time</th><th>Service</th><th></th><th>Port</th></tr>";
            echo "<tr><td></td><td></td><td></td><td></td><td></td><td></td>";
            for ($i = 0; $i < 7; $i++) {
                $dateString = date("d. M", time() - 60 * 60 * 24 * $i);
                echo "<td>" . $dateString . "</td>";
            }
            echo "</tr>";

            formatData($monitors);

            echo "<tr></tr>";
            echo "</table><br>";
            echo "</div>";
        }

        function getLast7Days()
        {
            // Get timestamps for the 7 last days
            $last_7_days = "";
            for ($i = 0; $i < 7; $i++) {
                if ($i == 6) {
                    $last_7_days = $last_7_days . date(time() - 60 * 60 * 24 * ($i + 1)) . "_" . date(time() - 60 * 60 * 24 * ($i));
                } else {
                    $last_7_days = $last_7_days . date(time() - 60 * 60 * 24 * ($i + 1)) . "_" . date(time() - 60 * 60 * 24 * $i) . "-";
                }
            }
            return $last_7_days;
        }

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

        function createUptimesSummaryTable($uptimes, $uptimes_strings, $monitors)
        {
            echo "<div id=leftbox><h3>Overall Uptime</h3>";
            echo "<table id='sums'>";
            $i = 0;
            foreach ($uptimes as $uptime) {
                echo "<tr>";
                $up_round = round($uptime / sizeof($monitors), 2);
                $bulletClass = checkColor($up_round);
                echo "<td><span class='bullet " . $bulletClass . "'></span></td>";
                echo "<td><p>" . $up_round . "% " .  $uptimes_strings[$i] . "</p></td>";
                echo "</tr>";
                $i++;
            }
            echo "</table></div>";
        }

        function createLastDownTimeTable($logs)
        {

            function secondsToString($seconds)
            {
                $minutes = 0;
                $hours = 0;
                $days = 0;
                # ugly af
                if ($seconds >= 60) {
                    $minutes = floor($seconds / 60);
                    $seconds = $seconds - $minutes * 60;
                }
                if ($minutes >= 60) {
                    $hours = floor($minutes / 60);
                    $minutes = $minutes - $hours * 60;
                }
                if ($hours >= 24) {
                    $days = floor($hours / 24);
                    $hours = $hours - $days * 24;
                }
                return array($days, $hours, $minutes, $seconds);
            }

            $maxDownTime = 0;
            $downString = "";
            # Probably will affect performance with big logs?
            foreach ($logs as $log) {
                foreach ($log as $logentry) {
                    if ($logentry["type"] == "1") {
                        if ($logentry["datetime"] > $maxDownTime) {
                            $maxDownTime = $logentry["datetime"];
                            $duration = $logentry["duration"];
                        }
                    }
                }
            }
            if ($maxDownTime == 0) {
                $downString = "No downtime recorded!";
            } else {
                $seconds = time() - $maxDownTime;
                $timeArr = secondsToString($seconds);
                $durationArr = secondsToString($duration);

                $dateString =  date("d.m.Y H:i:s", substr($maxDownTime, 0, 10));
                $downString = "The latest downtime was at <span id='latestDowntime'>" . $dateString . "</span> which was " . $timeArr[0] . " days, " . $timeArr[1] . " hours and " . $timeArr[2] . " minutes ago and lasted " . $durationArr[0] . " days, " . $durationArr[1] . " hours, " . $durationArr[2] . " minutes and " . $durationArr[3] . " seconds.";
            }
            echo "<div id=middlebox>";
            echo "<h3>Latest Downtime</h3>";
            echo "<p>" . $downString . "</p>";
            echo "</div>";
        }

        function createQuickStatsTable($up, $down, $paused)
        {
            echo "<div id=rightbox><h3>Quick Stats</h3>";
            echo "<table>";
            echo "<tr><td><span class='bullet success'></span></td><td>Up</td><td>" . $up . "</td></tr>";
            echo "<tr><td><span class='bullet error'></span></td><td>Down</td><td>" . $down . "</td></tr>";
            echo "<tr><td><span class='bullet warning'></span></td><td>Paused</td><td>" . $paused . "</td></tr>";
            echo "</table>";
            echo "<p id='refresh'></p>";
            echo "</div>";
        }
        ?>

    </div>

    <script>
        var field = document.getElementById("refresh");
        var counter = 60;

        function countDown() {
            setTimeout("countDown()", 1000);
            field.innerHTML = "Page refreshes in " + counter + " seconds.";
            counter = counter - 1;
            if (counter < 0) {
                counter = 0;
            }
        }

        countDown();
    </script>
</body>

</html>