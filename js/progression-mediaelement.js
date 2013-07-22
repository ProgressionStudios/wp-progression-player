(function($) {
    // add mime-type aliases to MediaElement plugin support
    mejs.plugins.silverlight[0].types.push('video/x-ms-wmv');
    mejs.plugins.silverlight[0].types.push('audio/x-ms-wma');

    $(function() {
    	
    	var options = window.progression;

        $('.wp-audio-shortcode, .wp-video-shortcode').mediaelementplayer({
        	startVolume: options.startvolume, // initial volume when the player starts
        	success: function(player, node) {
        		if ( options.autoplay === '1' ) {
        			$('.mejs-overlay-button').trigger('click');
        		};
        		
        	}
        });
    });

}(jQuery)); 