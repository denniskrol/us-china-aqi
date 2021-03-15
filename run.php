<?php
$scriptStartTime = microtime(true);
// Files: http://www.stateair.net/web/historical/1/1.html
function AQI($I_high, $I_low, $C_high, $C_low, $C) {
    return round(($I_high - $I_low) * ($C - $C_low) / ($C_high - $C_low) + $I_low);
}

function dustDensityToAQI($density) {
    $d10 = ($density * 10);

    if ($d10 <= 0) {return 0;}
    else if ($d10 <= 120) {return AQI(50, 0, 120, 0, $d10);}
    else if ($d10 <= 354) {return AQI(100, 51, 354, 121, $d10);}
    else if ($d10 <= 554) {return AQI(150, 101, 554, 355, $d10);}
    else if ($d10 <= 1504) {return AQI(200, 151, 1504, 555, $d10);}
    else if ($d10 <= 2504) {return AQI(300, 201, 2504, 1505, $d10);}
    else if ($d10 <= 3504) {return AQI(400, 301, 3504, 2505, $d10);}
    else if ($d10 <= 5004) {return AQI(500, 401, 5004, 3505, $d10);}
    else if ($d10 <= 10000) {return AQI(1000, 501, 10000, 5005, $d10);}
    else {return 1001;}
}

$data = array();
if ($handle = opendir(dirname($_SERVER['PHP_SELF']))) {
	while (false !== ($file = readdir($handle))) {
		$ext = strtolower(substr($file,-3));
		if ($ext == 'csv') {
			if (($fh = fopen($file, "r")) !== FALSE) {
				while (($line = fgetcsv($fh, 1000, ",")) !== FALSE) {
					if ((isset($line[10])) && ($line[10] == "Valid") && ($line[7] != "-999")) {
						if (!isset($data[$line[0]][$line[3]][$line[4]]['divider'])) {
							$data[$line[0]][$line[3]][$line[4]]['data'] = $line[7];
							$data[$line[0]][$line[3]][$line[4]]['divider'] = 0;
						}
						else {
							$data[$line[0]][$line[3]][$line[4]]['data'] = ((($data[$line[0]][$line[3]][$line[4]]['data'] * $data[$line[0]][$line[3]][$line[4]]['divider']) + $line[7]) / ($data[$line[0]][$line[3]][$line[4]]['divider'] + 1));
						}
						$data[$line[0]][$line[3]][$line[4]]['divider']++;
					}
				}
				fclose($fh);
			}
		}
	}
}

$yearTotal = 0;
$allTotal = 0;
foreach ($data as $cityKey => $city) {
	echo "Average PM2.5 for ".$cityKey."\n";
	foreach ($city as $yearKey => $year) {
		echo "----------------------------\n";
		echo $yearKey."\n";
		echo "----------------------------\n";
		foreach ($year as $monthKey => $month) {
			$yearTotal += $month['data'];
			$dateObj   = DateTime::createFromFormat('!m', $monthKey);
			echo $dateObj->format('F').": ".round($month['data'], 1)."ug, AQI: ".dustDensityToAQI($month['data'])."\n";
		}
		echo "Average over ".$yearKey.": ".round(($yearTotal / count($city[$yearKey])), 1)."ug, AQI: ".dustDensityToAQI(($yearTotal / count($city[$yearKey])))."\n";
		$allTotal += ($yearTotal / count($city[$yearKey]));
		$yearTotal = 0;
	}
	echo "\n";
	echo "Average over all ".$cityKey." data: ".round(($allTotal / count($data[$cityKey])), 1)."ug, AQI: ".dustDensityToAQI(($allTotal / count($data[$cityKey])))."\n";
	echo "\n\n";
	$allTotal = 0;
}
echo round((microtime(true) - $scriptStartTime))."ms";
//print_r($data);