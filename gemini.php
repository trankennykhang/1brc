<?php

// Configuration
const FILENAME = 'data/weather100.csv';
const TEST_SIZE = 100000000; // Lines to generate for testing (Set higher for stress test)

/**
 * 1. Memory-Efficient File Reader (Generator)
 * This function uses 'yield' to process the file line by line without loading the
 * entire content into memory, which is essential for massive files.
 * @param string $filename The path to the measurement file.
 * @return Generator<string>
 */
function file_line_reader(string $filename): Generator
{
    if (!is_readable($filename)) {
        throw new RuntimeException("File not found or not readable: $filename");
    }

    $handle = fopen($filename, 'rb');
    if ($handle === false) {
        throw new RuntimeException("Could not open file handle for: $filename");
    }

    while (!feof($handle)) {
        // Read a single line. The memory consumption remains constant.
        $line = fgets($handle);
        if ($line !== false) {
            // Trim whitespace/newlines immediately
            yield trim($line);
        }
    }
    fclose($handle);
}

/**
 * 2. Main High-Performance Processing Logic
 * Uses highly optimized string functions (strpos, substr) for parsing.
 * @param string $filename The path to the measurement file.
 * @return array<string, array{min: float, max: float, sum: float, count: int}>
 */
function process_measurements(string $filename): array
{
    // Array to store the aggregated data for each station:
    // [station_name => [min, max, sum, count]]
    $data = [];

    $line_count = 0;

    // Use the generator to iterate memory-efficiently
    foreach (file_line_reader($filename) as $line) {
        $line_count++;

        // Fast delimiter finding (much faster than explode or regex)
        $pos = strpos($line, ';');

        if ($pos !== false) {
            // Extract station name
            $stationName = substr($line, 0, $pos);
            // Extract and cast temperature as a float (fastest way to get the number)
            $temperature = (float)substr($line, $pos + 1);

            // Check if station has been seen before
            if (!isset($data[$stationName])) {
                // Initialize min, max, sum, and count
                $data[$stationName] = [
                    'min' => $temperature,
                    'max' => $temperature,
                    'sum' => $temperature,
                    'count' => 1
                ];
            } else {
                // Update stats using native min/max and direct arithmetic
                $data[$stationName]['min'] = min($data[$stationName]['min'], $temperature);
                $data[$stationName]['max'] = max($data[$stationName]['max'], $temperature);
                $data[$stationName]['sum'] += $temperature;
                $data[$stationName]['count']++;
            }
        }
    }

    echo "Processed $line_count lines.\n";
    return $data;
}

/**
 * Utility: Generates a large test file for simulation.
 * Use this to simulate a 1BRC-style file.
 * @param string $filename The output file path.
 * @param int $lines The number of lines to generate.
 */
function generate_test_file(string $filename, int $lines): void
{
    $stations = ['Station A', 'Station B', 'Station C', 'Station D', 'Station E', 'Fulton', 'Denver', 'Miami', 'Quebec', 'Tokyo'];

    // Use an array of 50 common stations for better simulation realism
    $common_stations = array_merge($stations, array_map(fn($i) => "City $i", range(1, 40)));

    echo "Generating test file ($lines lines)... This may take a moment.\n";

    $handle = fopen($filename, 'w');
    if ($handle === false) {
        throw new RuntimeException("Could not open file for writing: $filename");
    }

    $time_start = microtime(true);

    for ($i = 0; $i < $lines; $i++) {
        // Randomly pick one of the common stations
        $station = $common_stations[array_rand($common_stations)];
        // Generate a random temperature between -99.9 and 99.9 (1 decimal place)
        $temperature = number_format(mt_rand(-999, 999) / 10, 1);

        $line = $station . ';' . $temperature . "\n";
        fwrite($handle, $line);

        if ($i % 100000 === 0 && $i > 0) {
            echo "."; // Progress indicator
        }
    }

    fclose($handle);
    $time_end = microtime(true);
    $execution_time = $time_end - $time_start;

    echo "\nFile generation complete in " . number_format($execution_time, 2) . " seconds.\n\n";
}

// --- Main Execution ---

try {
    // 1. Generate the test file if it doesn't exist
    if (!file_exists(FILENAME) || filesize(FILENAME) === 0) {
        generate_test_file(FILENAME, TEST_SIZE);
    }

    $start_time = microtime(true);

    // 2. Process the file
    $results = process_measurements(FILENAME);

    $end_time = microtime(true);
    $total_time = $end_time - $start_time;

    // 3. Finalize and Output
    $final_output = [];
    foreach ($results as $station => $stats) {
        $avg = $stats['sum'] / $stats['count'];
        $final_output[$station] = [
            'min' => number_format($stats['min'], 1),
            'avg' => number_format($avg, 1),
            'max' => number_format($stats['max'], 1),
            'count' => $stats['count']
        ];
    }

    // Sort the results by station name
    ksort($final_output);

    echo "--- Results ---\n";
    //foreach ($final_output as $station => $data) {
      //  echo "{$station}: Min={$data['min']} | Avg={$data['avg']} | Max={$data['max']} ({$data['count']} readings)\n";
    //}

    echo "\nTotal execution time (Processing): " . number_format($total_time, 2) . " seconds.\n";

} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage() . "\n";
}