<?php
	/** WEBSERVICE PARA Satellital Patrol. */
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	header('Content-Type: text/html; charset=UTF-8');
  	header("Cache-Control: no-store, no-cache, must-revalidate");
  	header("Cache-Control: post-check=0, pre-check=0", false);
  	header("Pragma: no-cache");
  	date_default_timezone_set('UTC');
    error_reporting(E_ALL);
	
    require("./config.php");

    $conexion 	= @new mysqli($db_server, $db_user, $db_pass, $db_name, $db_port);

	if ($conexion->connect_error){
		die('Error de conectando a la base de datos: ' . $conexion->connect_error);
	}

	$sqlQuery 	= "SELECT `id`, `event`, `plate`, `speed`, `latitude`, `longitude`, `gpsDate`, `odometer`, `sent`, `accountID` FROM `Osinergmin` WHERE `sent`=0 ORDER BY id DESC LIMIT 1;";
	
	$resultado 	= $conexion->query($sqlQuery);
	
	$post  	= array();
	$devicesCount	= 0;

	$firstRowID	= 0;
	$lastRowID	= 0;
	$company	= "";
	$tramaUDP	= "";
    
    
	if ($resultado->num_rows > 0){
        while($row = $resultado->fetch_array(MYSQLI_ASSOC)){
			
			if ($firstRowID == 0){ $firstRowID = $row['id'];}

			$devicesCount++;

            $position['latitude']   = number_format($row['latitude'],5);
            $position['longitude']  = number_format($row['longitude'],5);
            $position['altitude']   = rand(117,228);

            $epoch = $row['gpsDate'];
            $dt = new DateTime("@$epoch");  // convert UNIX timestamp to PHP DateTime
            $newDt = $dt->format('Y-m-d\TH:i:s.v\Z'); // output = 2017-01-01 00:00:00

            $event = $row['event'];
            $code = "none";

            switch($event) {
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
            $resp['speed'] = (int)$row['speed'];
            $resp['position'] = $position;
            $resp['gpsDate'] = $newDt;
            $resp['tokenTrama'] = $token;
            $resp['odometer'] = (int)$row['odometer'];
            
            array_push($post, $resp);
            
            $lastRowID = $row['id'];
    	}
	}else{
		die("Todos los registros han sido enviados! No hay data nueva que enviar...");
	}

    $sqlUpdate 		= "UPDATE `Osinergmin` SET `sent`=1 WHERE `sent`=0 AND id BETWEEN ".$lastRowID." AND ".$firstRowID.";";
    $mensajeUpdate	= "";
	
	if ($conexion->query($sqlUpdate) === TRUE) {
		$mensajeUpdate	= "Tablas actualizadas!  ";
	} else {
		$mensajeUpdate	= "Error actualizando la tabla ".$conexion->error;
	}
	
	mysqli_close($conexion);

    $max = sizeof($post);

    for ($i = 0; $i < $max; $i++) {

        print_r("<br/>");
        print_r(json_encode($post[$i]));
        print_r("<br/>");
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL,            $endpoint );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($curl, CURLOPT_POST,           1 );
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt($curl, CURLOPT_POSTFIELDS,     json_encode($post[$i])); 
        curl_setopt($curl, CURLOPT_HTTPHEADER,     array('Content-Type: aplication/json')); 

        $response 	= curl_exec($curl);

        if($response){ // ?? - if request and data are completely received
            print_r("<br/>");
            print_r($response);
            print_r("<br/>");
            continue; // ?? - go to the next loop
        }

        $err 		= curl_error($curl);
        if ($err) {
            die("cURL Error #:" . $err);
        }

        // DONT go to the next loop until the above data is complete or returns true
      }
?>