<?php

    /*
    Copyright (c) 2018, Clue Dental Marketing Inc. All rights reserved.
    */

    header("Content-type: application/x-mpegURL");

    $partnerKey = "{INSERT PARTNER KEY HERE}";

    $videoid = $_GET["v"]; 
    $hlslevel = $_GET["l"];
    $codec = $_GET["c"];
    $object = $_GET["f"];
    $streamuri = "stream.php";
    $asseturi = "https://assets.muvidental.com/vid/";
    $sastoken = "";

    $cachefile = 'cached-token';
    $cachetime = 2419200; // 28 days

    // Serve from the cache if it is younger than $cachetime
    if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile)) {
        $sastoken = file_get_contents($cachefile);
    } else {
        // Get SAS token from Clue authorization server
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, "https://api.muvidental.com/get-token.php?k=" . $partnerKey); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $sastoken = curl_exec($ch); 
        curl_close($ch);
        
        // Don't cache if key is invalid
        if($sastoken == "UNAUTHORIZED") {
            die("Invalid partner key.");
        }
        
        // Cache the SAS token to a file
        $cached = fopen($cachefile, 'w');
        fwrite($cached, $sastoken);
        fclose($cached);
    }

    if ($hlslevel == 1) {
        
        // Get master playlist
        $ch = curl_init(); 
        if ($codec == "hevc") {
            // Authorize HEVC master playlist
            curl_setopt($ch, CURLOPT_URL, $asseturi . $videoid . "/playlist_h265.m3u8" . $sastoken);
        } else {
            // Authorize H264 master playlist
            curl_setopt($ch, CURLOPT_URL, $asseturi . $videoid . "/playlist.m3u8" . $sastoken);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $output = curl_exec($ch); 
        curl_close($ch);
        
        // Authorize stream playlists
        $pattern = '/[^\n]*m3u8[^\n]*/';
        echo preg_replace($pattern, $streamuri . '?l=2&v=' . $videoid . '&f=$0', $output);
        
    } else if ($hlslevel == 2) {
        
        // Get stream playlist
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $asseturi . $videoid . "/" . $object . $sastoken); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $output = curl_exec($ch); 
        curl_close($ch);
        
        // Authorize MPEG-DASH initialization segments
        $output = str_replace('#EXT-X-MAP:URI="', '#EXT-X-MAP:URI="' . $asseturi . $videoid . "/", $output);
        $output = str_replace("init.mp4", "init.mp4" . $sastoken, $output);
        
        // Authorize .ts resources
        $pattern = '/[^\n]*\.ts[^\n]*/';
        $output = preg_replace($pattern, $asseturi . $videoid . '/$0' . $sastoken, $output);
        
        // Authorize .m4s resources
        $pattern = '/[^\n]*\.m4s[^\n]*/';
        $output = preg_replace($pattern, $asseturi . $videoid . '/$0' . $sastoken, $output);
        
        // Send to browser
        echo $output;
        
    }
    

?>
