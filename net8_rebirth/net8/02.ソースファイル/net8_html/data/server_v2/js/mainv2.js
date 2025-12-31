/*
 *  Copyright (c) 2015 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */

'use strict';

var videoID = undefined; // undefined = use default camera
var audioID = $.cookie('audioid');
var videoMode = layoutOption['video_mode'];
var videoSize = '';
if( $.cookie('videoid') && $.cookie('videoid') !== 'default' ) videoID = $.cookie('videoid');
if( $.cookie('audioid') && $.cookie('audioid') !== 'default' ) audioID = $.cookie('audioid');

//除外コーデック指定
// VP8:全て可能 VP9:chrome系 H264:全て可能
var removeCodecList = [];

// Put variables in global scope to make them available to the browser console.
var video = document.querySelector('video');
//default
var constraints = {
	video: {
		deviceId: videoID,
		width:  { min: 640, ideal: 1024, max: 1280 },
		height: { min: 480, ideal: 768,  max: 960 },
		frameRate: {
			ideal: 30,
			min: 5
		}
	},
	audio: {
		deviceId: audioID
	}
};
//category setting
if ( machineMode == 1 ){
	constraints = {
		video: {
			deviceId: videoID,
			width:  { min: 640, ideal: 1024, max: 1280 },
			height: { min: 480, ideal: 768,  max: 960 },
			frameRate: {
				ideal: 30,
				min: 5
			}
		},
		audio: {
			deviceId: audioID
		}
	};
} else {
	constraints = {
		video: {
			deviceId: videoID,
			width:  { min: 640, ideal: 1024, max: 1280 },
			height: { min: 480, ideal: 768,  max: 960 },
			frameRate: {
				ideal: 30,
				min: 5
			}
		},
		audio: {
			deviceId: audioID
		}
	};
}
//videoMode Setting
if ( videoMode ){
	if( videoMode == 1 ){
		constraints = {
			video: {
				deviceId: videoID,
				width:  { min: 320, ideal: 640, max: 800 },
				height: { min: 240, ideal: 480, max: 600 },
				frameRate: {
					ideal: 30,
					min: 5
				}
			},
			audio: {
				deviceId: audioID
			}
		};
	}
	if( videoMode == 2 ){
		//removeCodecList = ['VP8'];
		constraints = {
			video: {
				deviceId: videoID,
				width:  { min: 640, ideal: 760 },
				height: { min: 480, ideal: 570 },
				frameRate: {
					ideal: 30,
					min: 5
				}
			},
			audio: {
				deviceId: audioID
			}
		};
/*
		constraints = {
			video: {
				deviceId: videoID,
				width:  { min: 640, ideal: 800, max: 800 },
				height: { min: 480, ideal: 600, max: 600 },
				frameRate: {
					ideal: 15,
					min: 5
				}
			},
			audio: {
				deviceId: audioID
			}
		};
*/
	}
	if( videoMode == 3 ){
		constraints = {
			video: {
				deviceId: videoID,
				width:  { min: 640, ideal: 800, max: 1024 },
				height: { min: 480, ideal: 600, max: 768 },
				frameRate: {
					ideal: 30,
					min: 5
				}
			},
			audio: {
				deviceId: audioID
			}
		};
	}
	if( videoMode == 4 ){
		constraints = {
			video: {
				deviceId: videoID,
				width:  { min: 640, ideal: 1024, max: 1280 },
				height: { min: 480, ideal: 768,  max: 960 },
				frameRate: {
					ideal: 30,
					min: 5
				}
			},
			audio: {
				deviceId: audioID
			}
		};
	}
	if( videoMode == 5 ){
		constraints = {
			video: {
				deviceId: videoID,
				width:  { min: 800, ideal: 1280,  max: 1920 },
				height: { min: 600, ideal: 1024,  max: 1080 },
				frameRate: {
					ideal: 30,
					min: 5
				}
			},
			audio: {
				deviceId: audioID
			}
		};
	}
}

// Remove undefined deviceId constraints to avoid SyntaxError
if (constraints.video && constraints.video.deviceId === undefined) {
    delete constraints.video.deviceId;
}
if (constraints.audio && constraints.audio.deviceId === undefined) {
    delete constraints.audio.deviceId;
}

window.constraints = constraints;
videoSize = '['+videoMode+'] '+constraints['video']['width']['ideal']+'x'+constraints['video']['height']['ideal']+
			' f:'+constraints['video']['frameRate']['ideal']+'(min:'+constraints['video']['frameRate']['min']+')';
/*
var constraints = window.constraints = {
//  ==== 2019-04-23 iPad H.264mode OK version ==== 
//  video: {
//    deviceId: videoID,
//    width:  { min: 640, ideal: 800, max: 1024 },
//    height: { min: 480, ideal: 600, max: 768 },
//    frameRate: {
//        ideal: 30,
//        min: 10
//    }
//  },
//
//  video: {
//    deviceId: videoID,
//    width:  { min: 1024, ideal: 1024, max: 1280 },
//    height: { min: 768,  ideal: 768,  max: 960 },
//    frameRate: {
//        ideal: 30,
//        min: 10
//    }
//  },
//  ==== 2019-05-16 Slot OK version ====
  video: {
    deviceId: videoID,
    width:  { min: 640, ideal: 1024, max: 1280 },
    height: { min: 480, ideal: 768, max: 960 },
   frameRate: {
        ideal: 30,
        min: 5
    }
  },
  
//  video: {
//    width: { min: 1024, ideal: 1280, max: 1920 },
//    height: { min: 776, ideal: 720, max: 1080 }
//  }
  audio: {
    deviceId: audioID
  }
};
*/
var errorElement = document.querySelector('#errorMsg');

var onSuccess = function(stream) {
  var videoTracks = stream.getVideoTracks();
  console.log('Got stream with constraints:', constraints);
  console.log('Using video device: ' + videoTracks[0].label);
  stream.onended = function() {
    console.log('Stream ended');
  };
  window.stream = stream; // make variable available to browser console
  //video = attachMediaStream(video, stream);
  
  cameraReady(stream);
  
};

var onFailure = function(error) {
  if (error.name === 'ConstraintNotSatisfiedError') {
    errorMsg('The resolution ' + constraints.video.width.exact + 'x' +
        constraints.video.width.exact + ' px is not supported by your device.');
  } else if (error.name === 'PermissionDeniedError') {
    errorMsg('Permissions have not been granted to use your camera and ' +
      'microphone, you need to allow the page access to your devices in ' +
      'order for the demo to work.');
  }
  errorMsg('getUserMedia error: ' + error.name, error);
};

window.AdapterJsStart = function(){
	AdapterJS.webRTCReady(function(isUsingPlugin) {
	  if (typeof Promise === 'undefined') {
	    navigator.getUserMedia(constraints, onSuccess, onFailure);
	  } else {
	    navigator.mediaDevices.getUserMedia(constraints)
	      .then(onSuccess).catch(onFailure);
	  }
	});
}

function errorMsg(msg, error) {
  errorElement.innerHTML += '<p>' + msg + '</p>';
  if (typeof error !== 'undefined') {
    console.error(error);
  }
}

//SDPから特定のコーデックを外す
function removeCodec(orgsdp, codec) {
	var internalFunc = function(sdp) {
	    var codecre = new RegExp('(a=rtpmap:(\\d*) ' + codec + '\/90000\\r\\n)');
	    var rtpmaps = sdp.match(codecre);
	    if (rtpmaps == null || rtpmaps.length <= 2) {
	        return sdp;
	    }
	    var rtpmap = rtpmaps[2];
	    // var modsdp = sdp.replace(codecre, "");​
	    var modsdp = sdp.replace(codecre, "");
	    var rtcpre = new RegExp('(a=rtcp-fb:' + rtpmap + '.*\r\n)', 'g');
	    //  modsdp = modsdp.replace(rtcpre, "");​
	    modsdp = modsdp.replace(rtcpre, "");
	    var fmtpre = new RegExp('(a=fmtp:' + rtpmap + '.*\r\n)', 'g');
	    //    modsdp = modsdp.replace(fmtpre, "");​
	    modsdp = modsdp.replace(fmtpre, "");
	    var aptpre = new RegExp('(a=fmtp:(\\d*) apt=' + rtpmap + '\\r\\n)');
	    var aptmaps = modsdp.match(aptpre);
	    var fmtpmap = "";
	    if (aptmaps != null && aptmaps.length >= 3) {
	        fmtpmap = aptmaps[2];
	        // modsdp = modsdp.replace(aptpre, "");​
	        modsdp = modsdp.replace(aptpre, "");
	        var rtppre = new RegExp('(a=rtpmap:' + fmtpmap + '.*\r\n)', 'g');
	        modsdp = modsdp.replace(rtppre, "");
	    }
	    var videore = /(m=video.*\r\n)/;
	    var videolines = modsdp.match(videore);
	    if (videolines != null) {
	        //If many m=video are found in SDP, this program doesn't work.
	        var videoline = videolines[0].substring(0, videolines[0].length - 2);
	        var videoelem = videoline.split(" ");
	        var modvideoline = videoelem[0];
	        for (var i = 1; i < videoelem.length; i++) {
	            if (videoelem[i] == rtpmap || videoelem[i] == fmtpmap) {
	                continue;
	            }
	            modvideoline += " " + videoelem[i];
	        }
	        modvideoline += "\r\n";
	        modsdp = modsdp.replace(videore, modvideoline);
	    }
	    return internalFunc(modsdp);
	};
	return internalFunc(orgsdp);
}
