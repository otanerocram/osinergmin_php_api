<?php

/** WEBSERVICE PARA SatellFital Patrol. */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
header('Content-Type: text/html; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
date_default_timezone_set('UTC');
error_reporting(E_ALL);

require("./config.php");

$conexion = @new mysqli($db_server, $db_user, $db_pass, $db_name, $db_port);

if ($conexion->connect_error) {
    die('Error de conectando a la base de datos: ' . $conexion->connect_error);
}

$fields = "`id`, `event`, `plate`, `speed`, `latitude`, `longitude`, `gpsDate`, `odometer`, `sent`, `accountID`";

$sqlQuery = "SELECT $fields FROM `Osinergmin` WHERE `sent`=0 ORDER BY `id` DESC LIMIT 50;";

$resultado = $conexion->query($sqlQuery);
$posts = array();
$devicesCount = 0;
$firstRowID = 0;
$lastRowID = 0;
$company = "";
$tramaUDP = "";


if ($resultado->num_rows > 0) {
    while ($row = $resultado->fetch_array(MYSQLI_ASSOC)) {

        if ($firstRowID == 0) {
            $firstRowID = $row['id'];
        }

        $devicesCount++;
        $position['latitude'] = number_format($row['latitude'], 5);
        $position['longitude'] = number_format($row['longitude'], 5);
        $position['altitude'] = rand(117, 228);
        $epoch = $row['gpsDate'];
        $newDt = gmdate("Y-m-d\TH:i:s", $epoch);
        $event = $row['event'];
        $code = "none";

        switch ($event) {
            case 63553:
                $code = "sos";
                break;

            case 62476:
                $code = "acc_on";
                break;

            case 62477:
                $code = "acc_off";
                break;

            case 64787:
                $code = "battery_dc";
                break;

            case 64789:
                $code = "battery_ct";
                break;

            default:
                $code = "none";
        }

        $resp['event'] = $code;
        $resp['plate'] = $row['plate'];
        $resp['speed'] = (int) $row['speed'];
        $resp['position'] = $position;
        $resp['gpsDate'] = $newDt . '.000Z';
        $resp['tokenTrama'] = $token;
        $resp['odometer'] = (int) $row['odometer'];

        array_push($posts, $resp);

        $lastRowID = $row['id'];
    }
} else {
    die("Todos los registros han sido enviados! No hay data nueva que enviar...");
}

$condition = "`sent`=0 AND `id` BETWEEN $lastRowID AND $firstRowID;";
$sqlUpdate = "UPDATE `Osinergmin` SET `sent`=1 WHERE $condition";

$mensajeUpdate = "";

if ($conexion->query($sqlUpdate) === true) {
    $mensajeUpdate = "Tablas actualizadas!  ";
} else {
    $mensajeUpdate = "Error actualizando la tabla " . $conexion->error;
}

mysqli_close($conexion);

$max = sizeof($posts);

$multiCurl = array();
$result = array();
$mh = curl_multi_init();

foreach ($posts as $i => $post) {
    $item = json_encode($post);
    // print_r("<pre>item: $item</pre>");

    $multiCurl[$i] = curl_init();
    curl_setopt($multiCurl[$i], CURLOPT_URL, $endpoint);
    curl_setopt($multiCurl[$i], CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($multiCurl[$i], CURLOPT_HTTPHEADER, array('Content-Type: aplication/json'));
    curl_setopt($multiCurl[$i], CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($multiCurl[$i], CURLOPT_POST, 1);
    curl_setopt($multiCurl[$i], CURLOPT_POSTFIELDS, $item);
    curl_multi_add_handle($mh, $multiCurl[$i]);
}

$index = null;
do {
    curl_multi_exec($mh, $index);
} while ($index > 0);

// get content and remove handles
foreach ($multiCurl as $k => $ch) {
    $result[$k] = curl_multi_getcontent($ch);
    $ite = json_encode($posts[$k]);
    print_r("<pre>item: $ite</pre>");
    print_r("<pre>result: $result[$k]</pre>");
    curl_multi_remove_handle($mh, $ch);
}

curl_multi_close($mh);