<?php

require 'config.php';

// Strava ClientID and Strava ClientSecret not set. Die with explanation.
if(!isset($strava_client_id) || !isset($strava_client_secret) || !is_integer($strava_client_id) || strlen($strava_client_secret)!==40){
	print '<html><body>In order to continue this app requires you to enter Strava ClientID & Strava ClientSecret in config.php.<br>Sign up at <b>http://www.strava.com/</b> and register your own Strava client at <b>https://www.strava.com/settings/api</b><br>Enter Strava ClientID & Strava ClientSecret in config.php and reload this page...</body></html>';
	die();
}

// ---

function abc50chars($n){
	$n=preg_replace('/[^\p{L}\p{N} -]/u',' ',$n);
	$n=preg_replace('/([\s])\1+/',' ',$n);
	$n=substr($n,0,50);
	return trim($n);
}

function debug_die($x){
	// @file_put_contents('debug.log',$x);
	die();
}

// ---

// redirect and die if no athlete_token 
if(count($_GET)<1){
	header('location:getoauth.php');
	die();
}

// get athlete_token from URL
foreach($_GET as $key => $val){
	$athlete_token=trim(preg_replace("/[^a-z0-9]+/i", '', $key));
}

// just die if missing or not a valid athlete_token
if(!isset($athlete_token)){debug_die(1);}
$athlete_array = explode('z',$athlete_token); 
$athlete_id = (int)$athlete_array[0];
if(!isset($athlete_array[1]) || $athlete_array[1] !== sha1($athlete_id.$secret_salt_hashing)){debug_die(2);}

// die if no POST data
if(count($_POST)<1){debug_die(3);}

// GETTING GPX FROM FITOTRACK AND UPLOADING TO STRAVA

// get name/type from FitoTrack headers
$workout_name = ''; $workout_type = '';
foreach($_SERVER as $key => $val) {
	if($key == 'HTTP_FITOTRACK_COMMENT'){$workout_name = abc50chars($val);}
	if($key == 'HTTP_FITOTRACK_WORKOUT_TYPE'){$workout_type = abc50chars($val);}
}

$workout_name=explode('_',$workout_name);
$workout_name=implode(' ',$workout_name);
$workout_name=$strava_title_prefix.' '.$workout_name.' '.$strava_title_suffix;

// getting actual data
$gpx=file_get_contents('php://input');
$maxsize_of_gpx_file=$maxsize_of_gpx_file*1024*1024;
if(strlen($gpx)>$maxsize_of_gpx_file){debug_die(4);}

$gpxfilename=$store_gpx_files_dir.'/'.$athlete_id.'_'.gmdate('Ymd_His',time()+$timezone_offset_min*60).'.gpx';
@file_put_contents($gpxfilename,$gpx);
$prepare_curl_file = new CURLFile($gpxfilename);

if(is_writable($curl_upload_logfile)){
	$fp = fopen($curl_upload_logfile, 'w');
}


// get access_token

$db = new SQLite3($store_gpx_files_dir.'/db.sqlite');
$res=$db->query('SELECT * FROM tokens WHERE athlete = '.$athlete_id);
$ath=$res->fetchArray();

if(!isset($ath['expires'])){debug_die(5);}

$exp=(int)$ath['expires'];
if($exp>time()){
	$access_token = $ath['access'];
}

// refresh access_token if expired

if(!isset($access_token)){

	$ch=curl_init();
	curl_setopt_array($ch,[
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_URL => "https://www.strava.com/api/v3/oauth/token",
	CURLOPT_POST => 1,
	CURLOPT_POSTFIELDS => http_build_query([
	    "client_id" => $strava_client_id,
	    "client_secret" => $strava_client_secret,
		"refresh_token" => $ath['refresh'],
		"grant_type" => "refresh_token"])
	]);

	$res=curl_exec($ch);
	curl_close($ch);

	$res=@json_decode($res,true);

	if(!isset($res['access_token']) || !isset($res['refresh_token'])){debug_die(6);}

		$expires_at=(int)$res['expires_at'];
		$access_token=preg_replace("/[^a-z0-9]+/i", '', $res['access_token']);
		$refresh_token=preg_replace("/[^a-z0-9]+/i", '', $res['refresh_token']);

		$db->query('BEGIN');
		$res=$db->query('DELETE FROM tokens WHERE athlete = '.$athlete_id);
		$res=$db->query("INSERT INTO tokens VALUES($athlete_id,'$access_token','$refresh_token',$expires_at)");
		$db->query('COMMIT'); 
		$db->query('VACUUM');

}

if(!isset($access_token)){debug_die(7);}

$ch=curl_init();

if(is_writable($curl_upload_logfile)){
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_STDERR, $fp);
}

curl_setopt($ch, CURLOPT_URL, "https://www.strava.com/api/v3/uploads");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $access_token]);
curl_setopt($ch, CURLOPT_POSTFIELDS, ["file" => $prepare_curl_file, "data_type" => 'gpx', "name" => $workout_name, "description" => $workout_type]);

$res=curl_exec($ch);
curl_close($ch);

// print result
// $res = json_decode($res);
// print '<pre>'; 
// print_r($res);

if($keep_uploaded_files!==true){
	@unlink($gpxfilename);
}

?>