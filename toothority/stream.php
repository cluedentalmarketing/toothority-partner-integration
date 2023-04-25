<?php

    /*
    Copyright (c) 2023, Clue Dental Marketing Inc. All rights reserved.
    */

    // We must set the header so browsers recognize this as a video
    header("Content-type: application/x-mpegURL");

    // This is your unique partner ID key
    $partnerKey = "{INSERT PARTNER KEY HERE}";

    // Get the slug from URL variable "v" for the video being requested such as 'gum-disease' or 'dental-implants'
    $videoid = $_GET["v"]; 

    // Check URL variable "l" to determine if this a request for a primary or "master" HLS playlist or a secondary "child" playlists
    $hlslevel = $_GET["l"];

    // Check URL variable "c" for codec - for Safari/iOS users, the toothority.js script will request H.265, everyone else gets H.264
    $codec = $_GET["c"];

    // Check the URL variable for which secondary stream quality is being requested ie 480p, 720p or 1440p etc...
    $object = $_GET["f"];

    // The location of this file
    $streamuri = "stream.php";

    // The location of remote Toothority video assets
    $asseturi = "https://assets.toothority.com/vid/";

    // Initialize the authorization token as a blank string
    $sastoken = "";

    // Name of the file to store the authorization token once it is retrieved
    $cachefile = 'cached-token';

    // How often to request a new token from Clue - Clue tokens are valid for 30 days
    $cachetime = 2419200; // 28 days to give a 2 day buffer

    // Check if a cached token exists and is younger than 28 days
    if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile)) {
        // Cache file is younger than 28 days. Retrive the authorzation token from the cache file.
        $sastoken = file_get_contents($cachefile);
    } else {
        // Cache file is older than 28 days. Rquest a new authorization token from Clue authorization server (this example uses php curl)
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, "https://api.toothority.com/get-token.php?k=" . $partnerKey); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $sastoken = curl_exec($ch); 
        curl_close($ch);
        
        // Don't cache if key is invalid
        if($sastoken == "UNAUTHORIZED") {
            die("Invalid partner key.");
        }
        
        // Save the authorization token to the cache file
        $cached = fopen($cachefile, 'w');
        fwrite($cached, $sastoken);
        fclose($cached);
    }

    if ($hlslevel == 1) {
        
        // A primary/master playlist is being requested. Let's assemble the request and get it from Clue.
        $ch = curl_init(); 
        if ($codec == "hevc") {
            // Authorize H.265/HEVC master playlist for newer Safari and iOS devices.
            // This combines the static location for Toothority assets, the specific video being requested, and the authorization token from Clue, into one request URI.
            // The request will look something like the following example:
            // https://assets.toothority.com/vid/gum-disease/playlist_h265.m3u8?sv=2017-11-09&ss=b&srt=o&sp=r&st=2019-07-11T06:22:31Z&se=2019-08-11T06:22:31Z&spr=https&sig=T9jrlNWxzzKqVMCc1cbQhGX0wPAH8m3oMgXzbuflgCQ%3D&pid=clue
            curl_setopt($ch, CURLOPT_URL, $asseturi . $videoid . "/playlist_h265.m3u8" . $sastoken);
        } else {
            // Authorize H.264 master playlist for everyone else.
            // This combines the static location for Toothority assets, the specific video being requested, and the authorization token from Clue, into one request URI.
            // The request will look something like the following example:
            // https://assets.toothority.com/vid/gum-disease/playlist.m3u8?sv=2017-11-09&ss=b&srt=o&sp=r&st=2019-07-11T06:22:31Z&se=2019-08-11T06:22:31Z&spr=https&sig=T9jrlNWxzzKqVMCc1cbQhGX0wPAH8m3oMgXzbuflgCQ%3D&pid=clue
            curl_setopt($ch, CURLOPT_URL, $asseturi . $videoid . "/playlist.m3u8" . $sastoken);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $output = curl_exec($ch); 
        curl_close($ch);
        
        // This filters through the response to convert the URIs to the secondary playlists into requests to this script (which are the handled by the else if segment that follows below)
        // The resulting URIs will look something like the following example:
        // stream.php?l=2&v=gum-disease&f=1080p.m3u8
        $pattern = '/[^\n]*m3u8[^\n]*/';
        echo preg_replace($pattern, $streamuri . '?l=2&v=' . $videoid . '&f=$0', $output);
        
    } else if ($hlslevel == 2) {
        
        // A secondary/stream playlist is being requested. Let's assemble the request and get it from Clue.
        // This combines the static location for Toothority assets, the specific video being requested, and the authorization token from Clue, into one request URI.
        // The request will look something like the following example:
        // https://assets.toothority.com/vid/gum-disease/1440p_h265.m3u8?sv=2017-11-09&ss=b&srt=o&sp=r&st=2019-07-11T06:22:31Z&se=2019-08-11T06:22:31Z&spr=https&sig=T9jrlNWxzzKqVMCc1cbQhGX0wPAH8m3oMgXzbuflgCQ%3D&pid=clue
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $asseturi . $videoid . "/" . $object . $sastoken); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $output = curl_exec($ch); 
        curl_close($ch);
        
        // Authorize MPEG-DASH init file for H.265/HEVC streams
        // Parse the response and convert relative init URI to absolute and also add authorzation token
        // The resulting URI will look something like the following example:
        // https://assets.toothority.com/vid/gum-disease/1440p_h265_init.mp4?sv=2017-11-09&ss=b&srt=o&sp=r&st=2019-07-11T06:22:31Z&se=2019-08-11T06:22:31Z&spr=https&sig=T9jrlNWxzzKqVMCc1cbQhGX0wPAH8m3oMgXzbuflgCQ%3D&pid=clue
        $output = str_replace('#EXT-X-MAP:URI="', '#EXT-X-MAP:URI="' . $asseturi . $videoid . "/", $output);
        $output = str_replace("init.mp4", "init.mp4" . $sastoken, $output);
        
        // Authorize .ts segments for H.264 streams
        // Parse the response and convert all relative movie segment URIs to absolute and also add authorzation token
        // The resulting URIs will look something like the following example:
        // https://assets.toothority.com/vid/gum-disease/1080p_000.ts?sv=2017-11-09&ss=b&srt=o&sp=r&st=2019-07-11T06:22:31Z&se=2019-08-11T06:22:31Z&spr=https&sig=T9jrlNWxzzKqVMCc1cbQhGX0wPAH8m3oMgXzbuflgCQ%3D&pid=clue
        $pattern = '/[^\n]*\.ts[^\n]*/';
        $output = preg_replace($pattern, $asseturi . $videoid . '/$0' . $sastoken, $output);
        
        // Authorize .m4s segments for H.265/HEVC streams
        // Parse the response and convert all relative movie segment URIs to absolute and also add authorzation token
        // The resulting URIs will look something like the following example:
        // https://assets.toothority.com/vid/gum-disease/1440p_h265_000.m4s?sv=2017-11-09&ss=b&srt=o&sp=r&st=2019-07-11T06:22:31Z&se=2019-08-11T06:22:31Z&spr=https&sig=T9jrlNWxzzKqVMCc1cbQhGX0wPAH8m3oMgXzbuflgCQ%3D&pid=clue
        $pattern = '/[^\n]*\.m4s[^\n]*/';
        $output = preg_replace($pattern, $asseturi . $videoid . '/$0' . $sastoken, $output);
        
        // Send to browser
        echo $output;
        
    }
    
?>
