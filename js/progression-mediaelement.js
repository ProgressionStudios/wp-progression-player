/* global mejs, _wpmejsSettings */
(function ($) {
	// add mime-type aliases to MediaElement plugin support
	mejs.plugins.silverlight[0].types.push('video/x-ms-wmv');
	mejs.plugins.silverlight[0].types.push('audio/x-ms-wma');

	$(function () {
		var settings = _wpmejsProgressionSettings || {},
			players = $('.wp-audio-shortcode, .wp-video-shortcode');

		settings.startVolume = parseFloat(settings.startvolume, 10);

		settings.alwaysShowControls = "true" === settings.controls;

		if ( $( document.body ).hasClass( 'mce-content-body' ) ) {
			return;
		}

		if ( typeof _wpmejsSettings !== 'undefined' ) {
			settings.pluginPath = _wpmejsSettings.pluginPath;
		}

		settings.success = function (mejs) {
			var autoplay = mejs.attributes.autoplay && 'false' !== mejs.attributes.autoplay;
			if ( 'flash' === mejs.pluginType && autoplay ) {
				mejs.addEventListener( 'canplay', function () {
					mejs.play();
				}, false );
			}
		};

		players.mediaelementplayer( settings );

		if ( "true" === settings.autoplay ) {
    		players.attr( 'autoplay', 'autoplay' );
    	};

	});

}(jQuery));