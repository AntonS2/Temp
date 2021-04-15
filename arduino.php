<?PHP

header('Content-type: text/plain; charset=utf8', true);

function check_header($name, $value = false) {
    if(!isset($_SERVER[$name])) {
        return false;
    }
    if($value && $_SERVER[$name] != $value) {
        return false;
    }
    return true;
}

function sendFile($path) {
    header($_SERVER["SERVER_PROTOCOL"].' 200 OK', true, 200);
    header('Content-Type: application/octet-stream', true);
    header('Content-Disposition: attachment; filename='.basename($path));
    header('Content-Length: '.filesize($path), true);
    header('x-MD5: '.md5_file($path), true);
    readfile($path);
}

if(!check_header('HTTP_USER_AGENT', 'ESP32-http-Update')) {
    header($_SERVER["SERVER_PROTOCOL"].' 403 Forbidden', true, 403);
    echo "only for ESP32 updater!\n";
    exit();
}

if(
    !check_header('x-ESP32-STA-MAC') ||
    !check_header('x-ESP32-AP-MAC') ||
    !check_header('x-ESP32-free-space') ||
    !check_header('x-ESP32-sketch-size') ||
    !check_header('x-ESP32-sketch-md5') ||
    !check_header('x-ESP32-chip-size') ||
    !check_header('x-ESP32-sdk-version')
) {
    header($_SERVER["SERVER_PROTOCOL"].' 403 Forbidden', true, 403);
    echo "only for ESP32 updater! (header)\n";
    exit();
}

 $db = array(
    "7C:9E:BD:E3:14:24" => "v1"
    
 );

 if(!isset($db[$_SERVER['x-ESP32-STA-MAC']])) {
    header($_SERVER["SERVER_PROTOCOL"].' 500 ESP MAC not configured for updates', true, 500);
 }

 $localBinary = "./bin/".$db[$_SERVER['x-ESP32-STA-MAC']].".bin";

// проверяем, прислал ли ESP8266 версию прошивки;
// если она не соответствует, проверяем соответствие MD5-хэшэй между 
// бинарным файлом на сервере и бинарным файлом на ESP8266;
// если они не соответствуют, то апдейта выполнено не будет:
if((!check_header('x-ESP32-sdk-version') && $db[$_SERVER['x-ESP32-STA-MAC']] != $_SERVER['x-ESP32-sdk-version'])
    || $_SERVER["x-ESP32-sketch-md5"] != md5_file($localBinary)) {
    sendFile($localBinary);
} else {
    header($_SERVER["SERVER_PROTOCOL"].' 304 Not Modified', true, 304);
}

 header($_SERVER["SERVER_PROTOCOL"].' 500 no version for ESP MAC', true, 500);