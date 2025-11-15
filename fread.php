<?php
/**
 * Requirements:
 * - Read weather from file
 * - Find the max and min for each station
 * - Order by alphabet
 * - Output the result
 */

/*
 * // weather100 6e91f3dea642f543baa86aed2c9c2924
 * Improvement: not issue with read the file => 0 s
 *  => Need to improve array handling
 */
// Check CLI
if (PHP_SAPI != "cli") {
    die('Not a command');
}
// Get args
if (count($argv) != 2) {
    die('Invalid command');
}
$file = 'data/'.$argv[1];
if (!file_exists($file)){
    die('Can\'t locate the file');
}

// Pass all basic checks, continue...
$time = time();
print date('c', time()).PHP_EOL;
print "Memory usage in MB: ".(memory_get_usage() / (1024 * 1024)).PHP_EOL;

$handle = fopen($file, 'r');
// loadd all station names
$stations = [];
if ($handle != false) {
    $contents = "";
    $flag = false;
    while (!feof($handle)) {
        $contents = fread($handle, 8192);

        // multi byte string mb4 and \t or \n
        if (substr($contents, -1) == "\n") {
            $flag = true;
        }
        $lines = explode("\n", $contents);
        $total = count($lines);
        $i = 0;
        foreach ($lines as $line) {
            if (!$flag && $i == $total - 1) {
                $contents = $line;
            } else {
                /*
                $arr = explode(';', $line);
                if (count($arr) == 2) {
                    $arr[1] = (float)$arr[1];
                    if (!isset($stations[$arr[0]])) {
                        $stations[$arr[0]] = [$arr[1], $arr[1]];
                    } else {
                        if ($stations[$arr[0]][0] > $arr[1])
                            $stations[$arr[0]][0] = $arr[1];
                        elseif ($stations[$arr[0]][1] < $arr[1])
                            $stations[$arr[0]][1] = $arr[1];
                    }
                }
                */
                $pos = strpos($line, ';');
                if ($pos !== false) {
                    $stationName = substr($line, 0, $pos);
                    // $temperatureStr = substr($line, $pos + 1);
                    // We can skip getting the string and just cast the number directly:
                    $temperature = (float)substr($line, $pos + 1);
                    // NOTE: Use (float) for temperature data to avoid integer truncation.

                    // 3. OPTIMIZED MIN/MAX LOGIC
                    if (!isset($stations[$stationName])) {
                        // Initialize min/max
                        $stations[$stationName] = [$temperature, $temperature];
                    } else {
                        // Use native min/max functions for performance
                        $stations[$stationName][0] = min($stations[$stationName][0], $temperature);
                        $stations[$stationName][1] = max($stations[$stationName][1], $temperature);
                    }
                }
            }
            $i++;
        }
        $flag = false;

    }
    print "Execute time to read data: ". (time() - $time) . PHP_EOL;
    $str = "";
    foreach ($stations as $name=>$values) {
        $med = round(($values[0] + $values[1]) / 2, 2);
        $str .= $name. ': '. $values[0] . '/'. $med . '/' . $values[1]. ';';
    }
    //print $str . PHP_EOL;
    print "Hash string: ". md5($str). PHP_EOL;
}
print "Execute time is: ". (time() - $time) . PHP_EOL;
print "Memory usage in MB: ".(memory_get_usage() / (1024 * 1024)).PHP_EOL;
print date('c', time()).PHP_EOL;

