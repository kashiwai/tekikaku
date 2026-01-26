/*
 *  Copyright (c) 2015 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */

'use strict';

var videoID = "default";;
var audioID = $.cookie('audioid');
var videoMode = layoutOption['video_mode'];
var videoSize = '';
if( $.cookie('videoid') ) videoID = $.cookie('videoid');
if( $.cookie('audioid') ) audioID = $.cookie('audioid');

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
			width:  { min: 640, ideal: 800, max: 1024 },
			height: { min: 480, ideal: 600, max: 768 },
			frameRate: {
				ideal: 30,
				min: 10
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
		constraints = {
			video: {
				deviceId: videoID,
				width:  { min: 640, ideal: 800, max: 800 },
				height: { min: 480, ideal: 600, max: 600 },
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
	if( videoMode == 3 ){
		constraints = {
			video: {
				deviceId: videoID,
				width:  { min: 640, ideal: 800, max: 1024 },
				height: { min: 480, ideal: 600, max: 768 },
				frameRate: {
					ideal: 24,
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
				width:  { min: 800, ideal: 1280,  max: 1280 },
				height: { min: 600, ideal: 1024,  max: 1024 },
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
