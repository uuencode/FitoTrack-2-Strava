# FitoTrack-2-Strava
A Strava app to upload FitoTrack workouts to Strava automatically.

FitoTrack is a free and open-source Android sport app for logging and viewing workouts.   
Fitotrack URL: https://codeberg.org/jannis/FitoTrack

Strava is a website for tracking physical exercise with social network features used for cycling and running using Global Positioning System data.  
Strava URL: https://www.strava.com

## Requirements

Any web host with PHP, `php_sqlite3` extension and PHP Curl support enabled.

## Installation

1. Login to Strava
2. Register a Strava app at https://www.strava.com/settings/api
3. Upload the files to your host and open config.php with a text editor:
  - Paste Strava ClientID and Strava ClientSecret
  - Replace `$secret_salt_hashing` with a random string
  - Create a new folder with a random name e.g. `ABC123` and set `$store_gpx_files_dir = 'ABC123';` instead of 'data'
  - CHMOD `ABC123` to 777

## How it works

When users visit the URL where your app is deployed, they will be automatically redirected to the Strava website to authorize the app to upload workouts to their profile. After a successful authoriztion, users will receive a personalized URL in a specific format. This URL needs to be copy/pasted to the following location:

FitoTrack » Settings » Database » Workout GPX Exports » **+** » **HTTP POSTS request**

## License
MIT
