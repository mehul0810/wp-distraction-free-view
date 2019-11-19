jQuery( document ).ready( function( $ ) {

	$( '.wpdfv-fullscreen-container .wpdfv-fullscreen-btn' ).on( 'click', function( e ) {

		var post_id = jQuery(this).data('post-id');
		var data = {
			'action': 'display_post_details',
			'id': post_id
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		$.post( wpdfv.ajaxurl, data, function(response) {
			$('.wpdfv-overlay-wrap').css('overflow-y','scroll');
			$('body').css('overflow-y','hidden');
			$('.wpdfv-fullscreen-overlay-container .wpdfv-overlay-wrap').html(response);
			$('.wpdfv-fullscreen-overlay-container').fadeIn('slow');
		});
		e.preventDefault();
	});

	$( '.wpdfv-overlay-close' ).on( 'click', function( e ) {
		$('.wpdfv-overlay-wrap').css('overflow-y','scroll');
		$('body').css('overflow-y','scroll');
		$('.wpdfv-fullscreen-overlay-container').fadeOut('slow');
		e.preventDefault();
	});

	// Dual Fullscreen Mode.
	$( '.wpdfv-fullscreen-overlay-container .wpdfv-dual-fullscreen-btn' ).on( 'click', function( e ) {

		if (
			! document.fullscreenElement &&
			! document.mozFullScreenElement &&
			! document.webkitFullscreenElement &&
			! document.msFullscreenElement
		) {

			if ( document.documentElement.requestFullscreen ) {
				document.documentElement.requestFullscreen();
			} else if ( document.documentElement.msRequestFullscreen ) {
				document.documentElement.msRequestFullscreen();
			} else if ( document.documentElement.mozRequestFullScreen ) {
				document.documentElement.mozRequestFullScreen();
			} else if ( document.documentElement.webkitRequestFullscreen ) {
				document.documentElement.webkitRequestFullscreen( Element.ALLOW_KEYBOARD_INPUT );
			}
			$( '.wpdfv-overlay-close' ).hide();
		} else {
			if ( document.exitFullscreen ) {
				document.exitFullscreen();
			} else if ( document.msExitFullscreen ) {
				document.msExitFullscreen();
			} else if ( document.mozCancelFullScreen ) {
				document.mozCancelFullScreen();
			} else if ( document.webkitExitFullscreen ) {
				document.webkitExitFullscreen();
			}
			$( '.wpdfv-overlay-close' ).show();
		}
	});

	// Display action for Print.
	$( '.wpdfv-fullscreen-overlay-container .wpdfv-overlay-print' ).on( 'click', function( e ) {

		// Get the HTML of div.
		var divElements = document.getElementById( divID ).innerHTML;

		// Get the HTML of whole page.
		var oldPage = document.body.innerHTML;

		// Reset the page's HTML with div's HTML only.
		document.body.innerHTML =
			"<html><head><title></title></head><body>" +
			divElements + "</body>";

		// Print Page.
		window.print();

		// Restore orignal HTML.
		document.body.innerHTML = oldPage;
	});
});
