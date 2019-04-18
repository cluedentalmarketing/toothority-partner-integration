/*
Copyright (c) 2018, Clue Dental Marketing Inc. All rights reserved.
*/

// Dynamically load video and subitles

var pageScripts = document.getElementsByTagName("script"),
    myLocation = pageScripts[pageScripts.length-1].src,
    streamPHP = myLocation.replace(/[^\\/]+$/g, "stream.php"),
    muviplayers = document.getElementsByClassName("muvi-player");

var config = {
    initialLiveManifestSize: 1,
    maxLoadingDelay: 2,
    maxBufferLength: 15,
    maxMaxBufferLength: 30
  };





var i;
for (i = 0; i < muviplayers.length; i++) {
    
    var videotopic = muviplayers[i].dataset.topic;
    
    // muviplayers[i].setAttribute("playsinline", "playsinline"); // Uncommnet to disable default full screen on mobile devices
    muviplayers[i].setAttribute("preload", "metadata");
    muviplayers[i].setAttribute("crossorigin", "anonymous");
    muviplayers[i].setAttribute("controlsList", "nodownload");
    muviplayers[i].setAttribute("oncontextmenu", "return false;");
    muviplayers[i].setAttribute("onClick", 'if (!this.hasAttribute("controls")) {this.setAttribute("controls", "controls"); this.play();}');
    muviplayers[i].setAttribute("ontouchstart", 'if (!this.hasAttribute("controls")) {this.setAttribute("controls", "controls"); this.play();}');
    
    muviplayers[i].setAttribute("poster", "https://assets.muvidental.com/img/posters/" + videotopic + ".jpg");     // Not protected, no token needed
    
    
    
    var uAgent = navigator.userAgent;
    var isiOS = false;
    var isMac = false;
    var osVersion = "";

    // Check if iOS
    if (/iPad|iPhone|iPod/.test(uAgent) == true) {
        isiOS = true;
        osVersion = uAgent.match(/OS (\d+)/);
        console.log(osVersion[1]);
    }
    // Check if Mac OS
    if (/OS X 10[_.](\d+)/.test(uAgent) == true) {
        isMac = true;
        osVersion = uAgent.match(/OS X 10[_.](\d+)/);
        console.log(osVersion[1]);
    }
                
    
    
    
    if(Hls.isSupported()) {
        var hls = new Hls(config);
            
        // Check if browser is Safari
        if (navigator.userAgent.indexOf('Safari') != -1 && navigator.userAgent.indexOf('Chrome') == -1 && !window.MSStream) {
            
            if (isiOS == true && osVersion[1] >= 11) {
                // Send H265 master playlist to iOS 11+ Safari
                hls.loadSource(streamPHP + '?l=1&c=hevc&v=' + videotopic);
                // document.getElementById("infodiv").innerHTML = "hls.js H.265";
            } else if (isMac == true && osVersion[1] >= 13) {
                // Send H265 master playlist to Mac OS X 13+ Safari
                hls.loadSource(streamPHP + '?l=1&c=hevc&v=' + videotopic);
                // document.getElementById("infodiv").innerHTML = "hls.js H.265";
            } else {
                // Send H264 master playlist to older iDevices
                hls.loadSource(streamPHP + '?l=1&&v=' + videotopic);
                // document.getElementById("infodiv").innerHTML = "hls.js H.265";
            }
            
        } else {
            // Send H264 master playlist to everyone else
            hls.loadSource(streamPHP + '?l=1&v=' + videotopic);
            // document.getElementById("infodiv").innerHTML = "hls.js H.264";
        }
        hls.attachMedia(muviplayers[i]);
        hls.on(Hls.Events.MANIFEST_PARSED,function() {
            // video.play();
        });
    } else if (muviplayers[i].canPlayType('application/vnd.apple.mpegurl')) {
        
       
            if (isiOS == true && osVersion[1] >= 11) {
                // Send H265 master playlist to iOS 11+ Safari
                muviplayers[i].src = streamPHP + '?l=1&c=hevc&v=' + videotopic; 
                muviplayers[i].addEventListener('canplay', function() {
                    muviplayers[i].play();
                });
                // document.getElementById("infodiv").innerHTML = "Native H.265";
            } else if (isMac == true && osVersion[1] >= 13) {
                // Send H265 master playlist to Mac OS X 13+ Safari
                muviplayers[i].src = streamPHP + '?l=1&c=hevc&v=' + videotopic; 
                muviplayers[i].addEventListener('canplay', function() {
                    muviplayers[i].play();
                });
                // document.getElementById("infodiv").innerHTML = "Native H.265";
            } else {
                // Send H264 master playlist to older iDevices
                muviplayers[i].src = streamPHP + '?l=1&v=' + videotopic; 
                muviplayers[i].addEventListener('canplay', function() {
                    muviplayers[i].play();
                });
                // document.getElementById("infodiv").innerHTML = "Native H.264";
            }
            
    }
    
    
    
    // English Captions
    var enTrack = document.createElement("track");
    enTrack.kind = "captions"; // captions are for hearing impaired/accessibility
    enTrack.label = "English";
    enTrack.srclang = "en";
    enTrack.id = "EnCaptionsTrack";
    enTrack.src = "https://assets.muvidental.com/vtt/" + videotopic + "/en.vtt";   // Not protected, no token needed
    muviplayers[i].appendChild(enTrack);
    
    // Spanish Subtitles (for future implementation)
    // var esTrack = document.createElement("track");
    // esTrack.kind = "subtitles"; // subtitles are for foreign language speakers
    // esTrack.label = "Español";
    // esTrack.srclang = "es";
    // esTrack.id = "EsSubTitleTrack";
    // esTrack.src = "https://assets.muvidental.com/vtt/" + videotopic + "/es.vtt";   // Not protected, no token needed
    // muviplayers[i].appendChild(esTrack);
    
    // French Subtitles (for future implementation)
    // var frTrack = document.createElement("track");
    // frTrack.kind = "subtitles";
    // frTrack.label = "Français";
    // frTrack.srclang = "fr";
    // frTrack.id = "FrSubTitleTrack";
    // frTrack.src = "https://assets.muvidental.com/vtt/" + videotopic + "/fr.vtt";   // Not protected, no token needed
    // muviplayers[i].appendChild(frTrack);
    
    // German Subtitles (for future implementation)
    // var deTrack = document.createElement("track");
    // deTrack.kind = "subtitles";
    // deTrack.label = "Deutsch";
    // deTrack.srclang = "es";
    // deTrack.id = "DeSubTitleTrack";
    // deTrack.src = "https://assets.muvidental.com/vtt/" + videotopic + "/de.vtt";   // Not protected, no token needed
    // muviplayers[i].appendChild(deTrack);
    
    // Portuguese Subtitles (for future implementation)
    // var ptTrack = document.createElement("track");
    // ptTrack.kind = "subtitles";
    // ptTrack.label = "Português";
    // ptTrack.srclang = "pt";
    // ptTrack.id = "PtSubTitleTrack";
    // ptTrack.src = "https://assets.muvidental.com/vtt/" + videotopic + "/pt.vtt";   // Not protected, no token needed
    // muviplayers[i].appendChild(ptTrack);
    
    // Hungarian Subtitles (for future implementation)
    // var huTrack = document.createElement("track");
    // huTrack.kind = "subtitles";
    // huTrack.label = "Magyar";
    // huTrack.srclang = "hu";
    // huTrack.id = "HuSubTitleTrack";
    // huTrack.src = "https://assets.muvidental.com/vtt/" + videotopic + "/hu.vtt";   // Not protected, no token needed
    // muviplayers[i].appendChild(huTrack);
    
    // Polish Subtitles (for future implementation)
    // var plTrack = document.createElement("track");
    // plTrack.kind = "subtitles";
    // plTrack.label = "Polski";
    // plTrack.srclang = "pl";
    // plTrack.id = "HuSubTitleTrack";
    // plTrack.src = "https://assets.muvidental.com/vtt/" + videotopic + "/pl.vtt";   // Not protected, no token needed
    // muviplayers[i].appendChild(plTrack);
    
    // Russian Subtitles (for future implementation)
    // var ruTrack = document.createElement("track");
    // ruTrack.kind = "subtitles";
    // ruTrack.label = "Русский";
    // ruTrack.srclang = "ru";
    // ruTrack.id = "RuSubTitleTrack";
    // ruTrack.src = "https://assets.muvidental.com/vtt/" + videotopic + "/ru.vtt";   // Not protected, no token needed
    // muviplayers[i].appendChild(ruTrack);

}







