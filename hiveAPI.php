<?php 
		
	//database details
	$dbhost = "localhost";
	$dbuser = "username";
	$dbpass = "securepassword";
	$dbname = "name of the database";
	$dbtable = "name of your database table";
        //I have a MySQL DB setup on local host with a DB named "Hive" a table for the "ThermostatTemps" with;
        // | ID (auto inc int)  | datetime (datetime)   | temp (int)|
        // |        10989       | 2020-10-13 08:00:00   |     17    |
	// |        10988       | 2020-10-13 07:00:00   |    16.5   |
	
	//Hive details
	$username= "username@gmail.com"; 
	$password= "secure password";		
	
	//read the response in the first CURL to get this ID from the @devices section returned
	$thermostat = "put your thermostat ID here it will look like this: 061daf89-67db-4aec-bc8d-c35038dc4f6d";
	
	
	//Get Authorisation token
	$curl = curl_init();
	curl_setopt_array($curl, array(
	  CURLOPT_URL => "https://beekeeper.hivehome.com/1.0/cognito/login",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS =>"{\"username\":\"${username}\",\"password\":\"${password}\",\"devices\":true,\"products\":true,\"actions\":true,\"homes\":true}",
	  CURLOPT_HTTPHEADER => array(
		"Content-Type: application/json"
	  )
	));
	$response = curl_exec($curl);
    // read this to get the device ID of your Hive Thermostat	
	echo ${response};
	//there is a bunch of other data about you in here, have a read of it and play around

	
	
	$session = json_decode($response, true);	
	//this is out Authentication Token
	$token = $session["token"];


	//todays date
	$date = date("Y-m-d H:i:s");	
	//round to the last hour
	$lastHour = date("Y-m-d H:00:00",strtotime($date)); 
	//the start hour will be 2 hours ago if the time is 13:02 then the start time will be 12:00
	$start = ((strtotime($lastHour)-(60*60)) * 1000); 
	//end hour is the last hour to pass, e.g.  if the time is 13:02 then the end time will be 13:00
	$end = (strtotime($lastHour)* 1000); 
	
	$curl = curl_init();
	curl_setopt_array($curl, array(
	  CURLOPT_URL => "https://beekeeper-uk.hivehome.com/1.0/history/heating/${thermostat}?start=${start}&end=${end}&timeUnit=MINUTES&rate=60&operation=AVG",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "GET",
	  CURLOPT_HTTPHEADER => array(
		"accept: */*",
		"authorization: ${token}",
		"cache-control: no-cache",
		"content-type: application/json",
		"sec-fetch-dest: empty",
		"sec-fetch-mode: cors",
		"sec-fetch-site: same-site",
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);
	curl_close($curl);
	if ($err) {
	  echo "cURL Error #:" . $err;
	} else {
      //don't need to see the reponse
	  echo $response;	
	}
	
	//the data will be a single entry JSON array for the selected device, which will contain the average temperature over that hour'
	$data = json_decode($response, true);
	//grab the temperature
	$temperature = $data["data"]['0']['temperature'];
	

	$link = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
	 
	// Check connection
	if($link === false){
		die("ERROR: Could not connect. " . mysqli_connect_error());
	}
	 
	// Attempt insert query execution
	$sql = "INSERT INTO ${dbtable} (datetime, temp) VALUES ('". $lastHour ."', '".$temperature."')";
	if(mysqli_query($link, $sql)){
		echo "Records inserted successfully.";
	} else{
		echo "ERROR: Could not able to execute ${sql}. " . mysqli_error($link);
	}	 
	// Close connection
	mysqli_close($link);


?>
