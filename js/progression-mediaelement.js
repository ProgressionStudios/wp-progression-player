(function($) {
    // add mime-type aliases to MediaElement plugin support
    mejs.plugins.silverlight[0].types.push('video/x-ms-wmv');
    mejs.plugins.silverlight[0].types.push('audio/x-ms-wma');

    $(function() {
    	
    	var options = window.progression,
    		players = $('.wp-audio-shortcode, .wp-video-shortcode');

    	if ( "true" === options.autoplay ) {
    		players.attr( 'autoplay', 'autoplay' );
    	};

    	players.attr( 'preload', options.preload );

        players.mediaelementplayer({
        	startVolume: options.startvolume, // initial volume when the player starts
        	alwaysShowControls: ("true" === options.controls ), // Hide controls when playing and mouse is not over the video
        	loop: ( "true" === options.loop ) // useful for <audio> player loops
        });

        $('.progression-playlist').mediaelementplayer({
        	startVolume: options.startvolume, // initial volume when the player starts
        	loop: ( "true" === options.loop ), // useful for <audio> player loops
        	features: ['playlistfeature', 'prevtrack', 'playpause', 'nexttrack','current', 'progress', 'duration', 'volume', 'playlist'],
        	playlist: ( "true" === options.playlist ), // Playlist Show
        	playlistposition: 'bottom' //Playlist Location
        });
    });

}(jQuery)); 