/*globals window, document, jQuery, _, Backbone, _wpmejsSettings */

(function ($, _, Backbone) {
	"use strict";

	var WPPlaylistView = Backbone.View.extend({
		initialize : function (options) {
			this.index = 0;
			this.settings = _wpmejsProgressionSettings || {};
			this.compatMode = $( 'body' ).hasClass( 'wp-admin' ) && $( '#content_ifr' ).length;
			this.data = options.metadata || $.parseJSON( this.$('script').html() );
			this.playerNode = this.$( this.data.type );

			this.tracks = new Backbone.Collection( this.data.tracks );
			this.current = this.tracks.first();

			if ( 'audio' === this.data.type ) {
				this.currentTemplate = wp.template( 'wp-playlist-current-item' );
				this.currentNode = this.$( '.wp-playlist-current-item' );
			}

			this.renderCurrent();

			this.itemTemplate = wp.template( 'wp-playlist-item' );
			this.playingClass = 'wp-playlist-playing';
			this.renderTracks();

			if ( this.isCompatibleSrc() ) {
				this.playerNode.attr( 'src', this.current.get( 'src' ) );
			}

			_.bindAll( this, 'bindPlayer', 'bindResetPlayer', 'setPlayer', 'ended', 'clickTrack' );

			if ( ! _.isUndefined( window._wpmejsSettings ) ) {
				this.settings.pluginPath = _wpmejsSettings.pluginPath;
			}
			this.settings.enableAutosize = false;
			this.settings.success = this.bindPlayer;
			this.setPlayer();
		},

		bindPlayer : function (mejs) {
			this.mejs = mejs;
			this.mejs.addEventListener( 'ended', this.ended );
		},

		bindResetPlayer : function (mejs) {
			this.bindPlayer( mejs );
			if ( this.isCompatibleSrc() ) {
				this.playCurrentSrc();
			}
		},

		isCompatibleSrc: function () {
			var testNode;

			if ( this.compatMode ) {
				testNode = $( '<span><source type="' + this.current.get( 'type' ) + '" /></span>' );

				if ( ! wp.media.mixin.isCompatible( testNode ) ) {
					this.playerNode.removeAttr( 'src' );
					this.playerNode.removeAttr( 'poster' );
					return;
				}
			}

			return true;
		},

		setPlayer: function (force) {
			if ( this.player ) {
				this.player.pause();
				this.player.remove();
				this.playerNode = this.$( this.data.type );
			}

			if (force) {
				if ( this.isCompatibleSrc() ) {
					this.playerNode.attr( 'src', this.current.get( 'src' ) );
				}
				this.settings.success = this.bindResetPlayer;
			}

			/**
			 * This is also our bridge to the outside world
			 */
			this.player = new MediaElementPlayer( this.playerNode.get(0), this.settings );
		},

		playCurrentSrc : function () {
			this.renderCurrent();
			this.mejs.setSrc( this.playerNode.attr( 'src' ) );
			this.mejs.load();
			this.mejs.play();
		},

		renderCurrent : function () {
			var dimensions, defaultImage = 'wp-includes/images/media/video.png';
			if ( 'video' === this.data.type ) {
				if ( this.data.images && this.current.get( 'image' ) && -1 === this.current.get( 'image' ).src.indexOf( defaultImage ) ) {
					this.playerNode.attr( 'poster', this.current.get( 'image' ).src );
				}
				dimensions = this.current.get( 'dimensions' ).resized;
				this.playerNode.attr( dimensions );
			} else {
				if ( ! this.data.images ) {
					this.current.set( 'image', false );
				}
				this.currentNode.html( this.currentTemplate( this.current.toJSON() ) );
			}
		},

		renderTracks : function () {
			var self = this, i = 1, tracklist = $( '<div class="wp-playlist-tracks"></div>' );
			this.tracks.each(function (model) {
				if ( ! self.data.images ) {
					model.set( 'image', false );
				}
				model.set( 'artists', self.data.artists );
				model.set( 'index', self.data.tracknumbers ? i : false );
				tracklist.append( self.itemTemplate( model.toJSON() ) );
				i += 1;
			});
			this.$el.append( tracklist );

			this.$( '.wp-playlist-item' ).eq(0).addClass( this.playingClass );
		},

		events : {
			'click .wp-playlist-item' : 'clickTrack',
			'click .wp-playlist-next' : 'next',
			'click .wp-playlist-prev' : 'prev'
		},

		clickTrack : function (e) {
			e.preventDefault();

			this.index = this.$( '.wp-playlist-item' ).index( e.currentTarget );
			this.setCurrent();
		},

		ended : function () {
			if ( this.index + 1 < this.tracks.length ) {
				this.next();
			} else {
				this.index = 0;
				this.current = this.tracks.at( this.index );
				this.loadCurrent();
			}
		},

		next : function () {
			this.index = this.index + 1 >= this.tracks.length ? 0 : this.index + 1;
			this.setCurrent();
		},

		prev : function () {
			this.index = this.index - 1 < 0 ? this.tracks.length - 1 : this.index - 1;
			this.setCurrent();
		},

		loadCurrent : function () {
			var last = this.playerNode.attr( 'src' ) && this.playerNode.attr( 'src' ).split('.').pop(),
				current = this.current.get( 'src' ).split('.').pop();

			this.mejs && this.mejs.pause();

			if ( last !== current ) {
				this.setPlayer( true );
			} else if ( this.isCompatibleSrc() ) {
				this.playerNode.attr( 'src', this.current.get( 'src' ) );
				this.playCurrentSrc();
			}
		},

		setCurrent : function () {
			this.current = this.tracks.at( this.index );

			this.$( '.wp-playlist-item' )
					.removeClass( this.playingClass )
					.eq( this.index )
						.addClass( this.playingClass );

			this.loadCurrent();
		}
	});

    $(document).ready(function () {
		if ( ! $( 'body' ).hasClass( 'wp-admin' ) || $( 'body' ).hasClass( 'about-php' ) ) {
			$('.wp-playlist').each(function () {
				return new WPPlaylistView({ el: this });
			});
		}
    });

	window.WPPlaylistView = WPPlaylistView;

}(jQuery, _, Backbone));

(function($) {
    // loop toggle
    MediaElementPlayer.prototype.buildtogglePlaylist = function(player, controls, layers, media) {
        var wpPlaylist = controls.closest('.wp-playlist'),
        	playlist = wpPlaylist.find('.wp-playlist-tracks'),
        	data = $.parseJSON( wpPlaylist.find('script').html() );

            if (playlist.length) {
            	// create the playlist button
            	var playlistButton =  
	            $('<div class="mejs-button mejs-playlist mejs-playlist-button"><button type="button" aria-controls="mep_0" title="Show/Hide Playlist" aria-label="Show/Hide Playlist"></button></div>' +
	            '</div>')
            	// append it to the toolbar
	            .appendTo(controls)
	            // add a click toggle event
	            .click(function() {
	            	playlist.toggle();
	            	$(this).toggleClass('progression-selected');
	            });

	            if ( false == data.tracklist || typeof _wpmejsProgressionSettings !== 'undefined' && _wpmejsProgressionSettings.playlist !== "true"  ) {
					playlist.hide();
				} else {
					playlistButton.addClass('progression-selected')
				}


            };
            
            
    }
})(jQuery);