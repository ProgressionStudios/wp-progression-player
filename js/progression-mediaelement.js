(function($) {
    // add mime-type aliases to MediaElement plugin support
    mejs.plugins.silverlight[0].types.push('video/x-ms-wmv');
    mejs.plugins.silverlight[0].types.push('audio/x-ms-wma');

    $(function() {
    	
    	var options = window.progression;

        $('.wp-audio-shortcode, .wp-video-shortcode').mediaelementplayer({
        	startVolume: options.startvolume, // initial volume when the player starts
        	alwaysShowControls: (options.controls === "true"), // Hide controls when playing and mouse is not over the video
        	loop: (options.loop === "true"), // useful for <audio> player loops
        	success: function(player, node) {
        		if ( options.autoplay === "true" ) {
        			$('.mejs-overlay-button').trigger('click');
        		};
        		
        	}
        });
    });

}(jQuery)); 