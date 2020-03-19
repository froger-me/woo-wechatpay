/* global WooWechatpay */
jQuery( function( $ ) {

    var beats          = 500,
        beat_interval  = 1000;

     function nextHeartbeat() {
        
        if ( 0 < beats-- ) {
            setTimeout( heartbeat, beat_interval );
        } else {
            $( '.woowechatpay-qr-error' ).text( WooWechatpay.expired_error ).show();
            $( '.woowechatpay-qr-placeholder' ).show();
            $( '#woowechatpay_qr_code' ).hide();
        }
    }

    function heartbeat() {
        var data    = {
                orderId : $( '#woowechatpay_qr_code' ).data( 'oid' ),
                action  : 'woowechatpay_payment_heartbeat',
                nonce   : WooWechatpay.nonce
            };

        $.post( WooWechatpay.ajax_url, data, function( response ) {

            if ( response.success ) {
                $( '.woowechatpay-qr-error' ).hide();

                if ( 'paid' === response.data.status ) {
                    location.href = response.data.message;
                } else if ( 'nPaid' === response.data.status ) {
                    nextHeartbeat();
                }
            } else {
                $( '.woowechatpay-qr-error' ).text( WooWechatpay.order_error ).show();
                $( '.woowechatpay-qr-placeholder' ).show();
                $( '#woowechatpay_qr_code' ).hide();
            }
        } ).fail( function() {
            $( '.woowechatpay-qr-error' ).text( WooWechatpay.default_error ).show();
            nextHeartbeat();
        } );
    }

    if ( ! window.isMobile ) {
        heartbeat();
    }
} );