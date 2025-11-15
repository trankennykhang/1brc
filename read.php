<?php
/**
 * Requirements:
 * - Read weather from file
 * - Find the max and min for each station
 * - Order by alphabet
 * - Output the result
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
    while(($data = fgets($handle, 1000)) != false) {
        $arr = explode(';', $data);
        if (count($arr) == 2) {
            $arr[1] = (int)$arr[1];
            if (!isset($stations[$arr[0]])) {
                $stations[$arr[0]] = [];
                $stations[$arr[0]][0] = $arr[1];
                $stations[$arr[0]][1] = $arr[1];
            } else {
                if ($stations[$arr[0]][0] > $arr[1])
                    $stations[$arr[0]][0] = $arr[1];
                elseif ($stations[$arr[0]][1] < $arr[1])
                    $stations[$arr[0]][1] = $arr[1];
            }
        }
    }
    $str = "";
    ksort($stations);
    foreach ($stations as $name=>$values) {
        $med = round(($values[0] + $values[1]) / 2, 2); 
        $str = $name. ': '. $values[0] . '/'. $med . '/' . $values[1]. ';';
        print $str."\n";
    }
    //print $str . PHP_EOL;
    //print "Hash string: ". md5($str). PHP_EOL;
}
print "Execute time is: ". (time() - $time) . PHP_EOL;
print "Memory usage in MB: ".(memory_get_usage() / (1024 * 1024)).PHP_EOL;
print date('c', time()).PHP_EOL;
