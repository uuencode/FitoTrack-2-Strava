<?php

$strava_title_prefix = '';             // upload to Strava with workout name prefix 
$strava_title_suffix = '(FitoTrack)';  // upload to Strava with workout name suffix

$keep_uploaded_files = false;          // keep GPX files 
$maxsize_of_gpx_file = 2;              // max size of the GPX file in mB
$timezone_offset_min = 120;            // Timezone offset in minutes when creating the GPX filenames

$curl_upload_logfile = '.curl.log';    // A writeable file to log CURL flow; mind it contains the access_token; choose a secret name

$store_gpx_files_dir = 'data';          // a PHP writeable directory to store GPX files in
$secret_salt_hashing = 'a^tuyREe4W@';   // salt to hash secrets; random letters/symbols


// Strava API settings - You need clientID and ClientSecret in order to get this to work with an OauthToken

$strava_client_id=0;
$strava_client_secret='Replace-Client-Secret';

?>