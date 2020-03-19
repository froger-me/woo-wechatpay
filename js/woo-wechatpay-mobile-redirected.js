/* global wechatData, WeixinJSBridge */
jQuery( function( $ ) {

    function onBridgeReady() {
        var api    = 'getBrandWCPayRequest',
            params = {
                'appId'     : '' + wechatData.appId,
                'timeStamp' : '' + wechatData.timeStamp,
                'nonceStr'  : '' + wechatData.nonceStr,
                'package'   : '' + wechatData['package'],
                'signType'  : 'MD5',
                'paySign'   : '' + wechatData.paySign
            };

        $( '.woowechatpay-loader' ).hide();

        WeixinJSBridge.invoke( api, params, function( res ) {
            wechatData.debug && window.alert( 'Debug mode active - redirection may fail.' );

            if ( 'get_brand_wcpay_request:ok' === res.err_msg ) {
                var data = {
                    action: 'woowechatpay_hold',
                    key: wechatData.key,
                    orderId: wechatData.orderId,
                    blogId: wechatData.blogId
                };

                $.ajax( {
                    url: wechatData.ajax_url,
                    data: data,
                    type: 'POST',
                    success: function( response ) {

                        if ( ! response.success ) {
                            window.alert( response.data[0].message );
                        }
                    },
                    error: function ( jqXHR, textStatus ) {
                        wechatData.debug && window.console.log( textStatus );
                    },
                    complete: function() {
                        window.location = wechatData.SuccessPayUrl;
                    }
                } );

                 window.setTimeout( function() {
                    window.location = wechatData.SuccessPayUrl;
                }, 2000 );
            } else if ( 'get_brand_wcpay_request:cancel' === res.err_msg ) {
                window.location = wechatData.FailedPayUrl;
            } else if ( 'get_brand_wcpay_request:fail' === res.err_msg ) {
                window.alert( wechatData.gatewayFail );
                history.back();
            } else {
                 window.setTimeout( function() {
                    history.back();
                }, 3000 );
            }
        } );
    }

    function callWXPay( wechatData ) {

        if ( 'failure' === wechatData.result ) {
            
            if ( 'failed' !== wechatData.message ) {
                window.alert( wechatData.message );
                history.back();
            }

            return;
        }
        
        if ( 'undefined' === typeof WeixinJSBridge ) {

            if ( document.addEventListener ) {
                 document.addEventListener( 'WeixinJSBridgeReady', onBridgeReady, false );
            } else if ( document.attachEvent ) {
                 document.attachEvent( 'WeixinJSBridgeReady', onBridgeReady) ;
                 document.attachEvent( 'onWeixinJSBridgeReady', onBridgeReady );
            }
        } else {
             onBridgeReady( wechatData );
        }
    }

    $( function() {
        callWXPay( wechatData );
    } );

} );