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

// define number of thread
$threads = 8;
$handle = fopen($file, 'r');
// loadd all station names
$stations = [];
$trunks = [];
$from = 0;
if ($handle != false) {

    // Search the filesize
    $size = filesize($file);
    $trunk = round($size / $threads);
    $pos = $trunk;
    for($i=0;$i<$threads;$i++) {
        // move cursor to the position
        fseek($handle, $pos);
        // read the left over of the current line
        fgets($handle, 1000);
        // now we got the correct current of then next line
        $next_line = ftell($handle);
        
        // put everything in array
        $trunks[$i] = [$from, $next_line];
        
        // set the next pos
        $pos += $trunk;
        if ($pos > $size) {
            $pos = $size;
        }
        $from = $next_line;
        
    }
    for($i=0;$i<$threads;$i++) {
        /// step to create new fork for get from/to 
        $pid = pcntl_fork();
        if ($pid == -1) {
            die("could not fork"); 
        } else if ($pid) {
            pcntl_wait($status); //Protect against Zombie children
            //print date('c', time()).PHP_EOL;
//print "Execute time is: ". (time() - $time) . PHP_EOL;
//print "Memory usage in MB: ".(memory_get_usage() / (1024 * 1024)).PHP_EOL;
        } else {
            // we are the child
            $last = $trunks[$i][1];
            $current = $trunks[$i][0];
            fseek($handle, $current);
            while($current < $last) {
                $data = fgets($handle, 1000);
                $arr = explode(';', $data);
                if (count($arr) == 2) {
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
                $current = ftell($handle);
            }
            foreach ($stations as $name=>$values) {
                $med = round(($values[0] + $values[1]) / 2, 2);
                file_put_contents('data/'.$i.'.txt', $name. ': '. $values[0] . '/'. $med . '/' . $values[1]. ';', FILE_APPEND);
            }
        }
    }
}