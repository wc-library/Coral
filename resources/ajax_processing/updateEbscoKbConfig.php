<?php

$enabled = filter_input(INPUT_POST, 'enabled', FILTER_VALIDATE_BOOLEAN);
$customerId = filter_input(INPUT_POST, 'customerId', FILTER_SANITIZE_STRING);
$apiKey = filter_input(INPUT_POST, 'apiKey', FILTER_SANITIZE_STRING);
$ini_file = BASE_DIR . "/admin/configuration.ini";

$ini_array = parse_ini_file($ini_file, true);

$ini_array['settings']['ebscoKbEnabled'] = empty($enabled) ? 'N' : 'Y';
$ini_array['settings']['ebscoKbCustomerId'] = $customerId;
$ini_array['settings']['ebscoKbApiKey'] = $apiKey;

try {
    write_php_ini($ini_file, $ini_array);
} catch (Exception $e) {
    http_response_code(500);
    echo $e->getMessage();
}



function write_php_ini($file, $array)
{
	$res = array();
	foreach($array as $key => $val)
	{
		if(is_array($val))
		{
			$res[] = "[$key]";
			foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.addcslashes($sval, '"').'"');
		}
		else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.addcslashes($val, '"').'"');
	}
	safefilerewrite($file, implode("\r\n", $res));
}

function safefilerewrite($fileName, $dataToSave)
{
    if (!is_writable($fileName)) {
        throw new Exception("$fileName is not writeable.");
    }

    if ($fp = fopen($fileName, 'w')) {
        $startTime = microtime(TRUE);

        do {
            $canWrite = flock($fp, LOCK_EX);
            // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
            if (!$canWrite) {
                usleep(round(rand(0, 100) * 1000));
            }
        } while ((!$canWrite) and ((microtime(TRUE) - $startTime) < 5));

        //file was locked so now we can store information
        if ($canWrite) {
            fwrite($fp, $dataToSave);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }
}