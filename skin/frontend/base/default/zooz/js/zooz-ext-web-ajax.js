var Zooz = Zooz || {};

Zooz.zoozServerProduction = "https://app.zooz.com";
Zooz.zoozServerSandbox = "https://sandbox.zooz.co";

Zooz.zoozServerProduction_dev = "http://192.168.1.22:8085";
Zooz.zoozServerSandbox_dev = "http://192.168.1.22:8085";

Zooz.serverProduction_111 =   "http://192.168.1.111:8080";
Zooz.serverSandbox_111 =   "http://192.168.1.111:8090";

Zooz.serverProduction_dev =   "http://dev.zooz.co:8585";
Zooz.serverSandbox_dev =   "http://dev.zooz.co:8090";



Zooz.environemtProduction = Zooz.serverProduction_dev;
Zooz.environemtSandbox = Zooz.serverSandbox_dev;
/*
 * The information in this document is proprietary
 *  to TactusMobile and the TactusMobile Product Development.
 *  It may not be used, reproduced or disclosed without
 *  the written approval of the General Manager of
 *  TactusMobile Product Development.
 *
 *  PRIVILEGED AND CONFIDENTIAL
 *  TACTUS MOBILE PROPRIETARY INFORMATION
 *  REGISTRY SENSITIVE INFORMATION
 *
 *  Copyright (c) 2010 TactusMobile, Inc.  All rights reserved.
 */
//var spinner;
var Zooz = Zooz || {};

//Zooz.zoozServerProduction = "https://app.zooz.com";
//Zooz.zoozServerSandbox = "https://sandbox.zooz.co";
//
//Zooz.zoozServerProduction_dev = "http://192.168.1.22:8085";
//Zooz.zoozServerSandbox_dev = "http://192.168.1.22:8085";
//
//Zooz.serverProduction_111 =   "http://192.168.1.111:8080";
//Zooz.serverSandbox_111 =   "http://192.168.1.111:8090";
//
//
//
//Zooz.environemtProduction = Zooz.zoozServerProduction_dev;
//Zooz.environemtSandbox = Zooz.zoozServerSandbox_dev;


Zooz.centerPosition = function(elem, elemWidth, elemHeight) {
    var e = window
            , a = 'inner';
    if (!( 'innerWidth' in window )) {
        a = 'client';
        e = document.documentElement || document.body;
    }

    var width = e[ a + 'Width' ];
    var height = e[ a + 'Height' ];

    if (width > elemWidth) {
        elem.style.left = 0.5 * (width - elemWidth) + "px";
    }

    if (height > elemHeight) {
        elem.style.top = 0.5 * (height - elemHeight) + "px";
    }
};


var zoozStartCheckout = function(zoozParams) {

    var zoozOverlay;
    var zoozIframe;
    var zoozCloseButton;
    var zoozDialogContainer;
    var zoozServer;

    var that = this;

    var isIE = navigator.userAgent.match(/MSIE/i);

    var isZoozLive = true;
    var pingZoozOverlay = function() {
        setTimeout(function() {
            if (isZoozLive) {
                isZoozLive = false;
                try {
                    zoozIframe.contentWindow.postMessage('check-zooz', zoozServer);
                    pingZoozOverlay();
                } catch(ex) {

                }


            } else {
                try {
                    if (zoozCloseButton) {
                        closezooz = closeWindow;
                    }

                    alert('zooz window is dead');
                } catch(ex) {

                }

            }
        }, 1000);

    }

            ;


    var listener = function(event) {
        if (event.origin !== Zooz.environemtProduction) {
            return;
        }
        if (event.data === 'zooz-alive') {
            isZoozLive = true;

        } else if (event.data === 'zooz-started') {
            closezooz = closeByPost;
            pingZoozOverlay();
        } else {
            closeWindow();
        }

    };


    if (window.addEventListener) {

        addEventListener("message", listener, false);
    } else {
        attachEvent("onmessage", listener);
    }

    var closeWindow = function() {
        console.log('close window');
        var elem = document.getElementById('zooz-overlay');
        if (elem) {
            try {


                document.body.removeChild(elem);
                window.removeEventListener('message', listener, false);
                //            if (zoozParams.closeCallbackFunc) {
                //                zoozParams.closeCallbackFunc();
                //            }
                delete that;
            } catch(ex) {

            }
        }
    };

    var closeByPost = function() {
        console.log('close by post');
        zoozIframe.contentWindow.postMessage('close-zooz', zoozServer);
    };

    var showzooz = function () {
        document.getElementById('zooz-overlay').style.visibility = 'visible';
        document.getElementById('zooz-overlay').style.opacity = '1';
    };
    var hidezooz = function() {
        document.getElementById('zooz-overlay').style.visibility = 'hidden';
        document.getElementById('zooz-overlay').style.opacity = '0';
    };
    var closezooz = closeWindow;
    //            closeByPost;
    //            closeWindow;


    if (document.getElementById('zooz-overlay')) {
        return;
    }

    if (!zoozParams.token) {
        alert("Error starting ZooZ checkout: Token is empty");
        return;
    }

    if (!zoozParams.uniqueId) {
        alert("Error starting ZooZ checkout: Unique ID is empty");
        return;
    }


    if (zoozParams.isSandbox) {
        zoozServer = Zooz.environemtSandbox;
    } else {
        zoozServer = Zooz.environemtProduction;
    }


    //    var pixelRatio = 1;
    //    try {
    //        pixelRatio = window.devicePixelRatio;
    //    } catch (e) {
    //        pixelRatio = 1;
    //    }

    //	if (pixelRatio === undefined || pixelRatio == 1) {
    //
    //		try {
    //			if (!zoozParams.useMobileSize && window.matchMedia("(min-device-width: 540px)").matches && window.matchMedia("(min-device-height: 560px)").matches) {
    //				largeViewport = true;
    //			}
    //		} catch (e) {
    //
    //			try {
    //				if (window.matchMedia("(min-width: 540px)").matches && window.matchMedia("(min-height: 560px)").matches) {
    //					largeViewport = true;
    //				}
    //			} catch (e) {
    //				var isIE7 = navigator.userAgent.match(/MSIE 7.0/i);
    //				var isIE8 = navigator.userAgent.match(/MSIE 8.0/i);
    //				var isIE9 = navigator.userAgent.match(/MSIE 9.0/i);
    //				var isIEMobile = navigator.userAgent.match(/IEMobile/i);
    //				if (isIE7 || isIE8 || (isIE9 && !isIEMobile)) {
    //					largeViewport = true;
    //				}
    //			}
    //		}
    //
    //
    //	}


    var isIE8 = navigator.userAgent.match(/MSIE 8.0/i);


    var iframeSrc = zoozServer + "/mobile/mobilewebajax/zooz-checkout.jsp?token=" + zoozParams.token + "&uniqueID=" + zoozParams.uniqueId;


    //	spinner = document.createElement('img');
    //	spinner.src = zoozServer + "/mobile/mobileweb/img/loader_spinner.gif";
    //	spinner.style.position = 'absolute';
    //	spinner.style.zIndex = "99999";
    //	spinner.style.opacity = 0.4;
    //	spinner.style.filter = 'alpha(opacity=40)';
    //	showSpinner();

    if (zoozParams.returnUrl) {
        iframeSrc += "&returnUrl=" +  zoozParams.returnUrl;
    }

    if (zoozParams.cancelUrl) {
        iframeSrc += "&cancelUrl=" +  zoozParams.cancelUrl;
    }

    if (zoozParams.rememberMeDefault == false) {
        iframeSrc += "&uncheckRememberMe=true";
    }

    if (zoozParams.preferredLanguage) {
        iframeSrc += "&preferredLanguage=" + zoozParams.preferredLanguage;
    }

    if (zoozParams.customStylesheet) {
        iframeSrc += "&customStylesheet=" + zoozParams.customStylesheet;
    }

    //    var div = document.createElement('div');


    if (zoozParams.completeCallBackFunc) {
        iframeSrc += "&callbackDomain=" + window.location.protocol + "//" + window.location.host;
        var eventMethod = window.addEventListener ? "addEventListener" : "attachEvent";
        var eventer = window[eventMethod];
        var messageEvent = eventMethod == "attachEvent" ? "onmessage" : "message";

        // Listen to message from child window
        eventer(messageEvent, function(e) {
            if (e.origin === zoozServer) {
                if (zoozOverlay && zoozOverlay.parentNode) {
                    closezooz(zoozOverlay);
                    zoozParams.completeCallBackFunc(e.data);
                }
                //                if (zoozOverlay && zoozOverlay.parentNode) {
                //                    zoozOverlay.parentNode.removeChild(zoozOverlay);
                //                    zoozParams.completeCallBackFunc(e.data);
                //                }
            }
        }, false);

    }
    
    if (zoozParams.shippingMethods) {
        iframeSrc += "&shippingMethods="+  zoozParams.shippingMethods;
//    	for (var shippingMethod in zoozParams.shippingMethods) {
//			if (zoozParams.shippingMethods.hasOwnProperty(shippingMethod)) {
//				iframeSrc += "&shippingMethods=" + shippingMethod + "&shippingPrices=" + zoozParams.shippingMethods[shippingMethod];
//			}
//		}
    }
    
    //                         Zooz.injectedCode.join()

    // html injection
    //    div.innerHTML = Zooz.injectedCode.join("");
    //    document.body.appendChild(div);
    //    var zoozIframe = document.getElementById('zooz-iframe');
    //    var zoozCloseButton = document.getElementById('zooz-close-button');
    //
    //    '<div id="zooz-overlay" style="background:rgba(0,0,0,0.6);position:fixed;z-index:999999;width:100%;height:100%;top:0;bottom:0;left:0;right:0;visibility:hidden;opacity:0;transition:all 0.3s ease-out;-webkit-transition:all 0.3s ease-out;-moz-transition:all 0.3s ease-out;-o-transition:all 0.3s ease-out;">',
    //            '<div id="zooz-dialog-container" style="width:650px;background-color:rgba(255,255,255,1);position:fixed;top:50%;left:50%;margin-left:-325px;margin-top:-350px;padding:0;line-height:0;">',
    //            '<div id="zooz-close-button"    style="position:absolute;background:url(' + zoozServer + '/mobile/mobilewebajax/img/ico-close.png) no-repeat left top;right:5px;top:8px;width:12px;height:12px;cursor:pointer;"></div>',
    //            '<iframe id="zooz-iframe" allowtransparency="true" frameborder="0" width="650px" height="710px" marginheight="0" marginwidth="0" scrolling="no" ></iframe>',
    //            ' </div>',
    //            '</div>',


    zoozOverlay = document.createElement('div');
    zoozOverlay.id = "zooz-overlay";
    zoozOverlay.style.cssText = 'position:fixed;z-index:999999;width:100%;height:100%;top:0;bottom:0;left:0;right:0;visibility:hidden;opacity:0;transition:all 0.3s ease-out;-webkit-transition:all 0.3s ease-out;-moz-transition:all 0.3s ease-out;-o-transition:all 0.3s ease-out;';
    if (!isIE8) {
        zoozOverlay.style.cssText += 'background:rgba(0,0,0,0.6);';
    }
    document.body.appendChild(zoozOverlay);
    //    zoozOverlay.style.background = 'rgba(0,0,0,0.6)';
    //    zoozOverlay.style.position= 'fixed';
    //    zoozOverlay.style.zIndex ='999999' ;
    //    zoozOverlay.style.width='100%';
    //    zoozOverlay.style.height = '100%';
    //    zoozOverlay.style.top = '0';
    //    zoozOverlay.style.bottom = '0';
    //    zoozOverlay.style.left = '0';
    //    zoozOverlay.style.right = '0';
    //    zoozOverlay.style.visibility = 'hidden';
    //    zoozOverlay.style.opacity = '0';
    //    zoozOverlay.style.o -o-transition:all 0.3s ease-out;
    //    zoozOverlay.style.opacity;
    //    zoozOverlay.style.opacity;
    //    zoozOverlay.style.opacity;


    zoozDialogContainer = document.createElement('div');
    zoozDialogContainer.id = 'zooz-dialog-container';
    zoozDialogContainer.style.cssText = 'width:650px;background-color:#FFFFFF;position:fixed;top:50%;left:50%;margin-left:-325px;margin-top:-350px;padding:0;line-height:0;';
    zoozOverlay.appendChild(zoozDialogContainer);

    zoozCloseButton = document.createElement('div');
    zoozCloseButton.id = 'zooz-close-button';
    zoozCloseButton.style.cssText = "position:absolute;background:url(" + zoozServer + "/mobile/mobilewebajax/img/ico-close.png) no-repeat left top;right:5px;top:8px;width:12px;height:12px;cursor:pointer;";
    zoozDialogContainer.appendChild(zoozCloseButton);

    zoozIframe = document.createElement('iframe');
    zoozIframe.id = 'zooz-iframe';
    zoozIframe.setAttribute('allowtransparency', "true");
    zoozIframe.setAttribute('frameborder', "0");
    zoozIframe.setAttribute('width', "650px");
    zoozIframe.setAttribute('height', "710px");
    zoozIframe.setAttribute('marginheight', "0");
    zoozIframe.setAttribute('marginwidth', "0");
    zoozIframe.setAttribute('scrolling', "no");
    zoozDialogContainer.appendChild(zoozIframe);

    zoozIframe.setAttribute('src', iframeSrc);
    zoozCloseButton.onclick = function() {
        closezooz(zoozOverlay)
    };
    //    onMouseOver="document.getElementById(\'zooz-close-button\').style.cssText+=\'background-position:left bottom\'"
    zoozCloseButton.onmouseover = function() {
        var el = document.getElementById('zooz-close-button');
        if (el) {
            el.style.cssText += 'background-position:left bottom';
        }
    };
    zoozCloseButton.onmouseout = function() {
        var el = document.getElementById('zooz-close-button');
        if (el) {
            el.style.cssText += 'background-position:left top';
        }
    };

    showzooz();


    //	if (isIE) {
    //		zoozIframe.attachEvent("onload", onIframeLoad);
    //	} else {
    //		zoozIframe.onload = onIframeLoad();
    //	}


};
