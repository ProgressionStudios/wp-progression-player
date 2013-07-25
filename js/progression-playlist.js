(function($) {

    // ---------------------------------
    wp.media.controller.PlaylistAdd = wp.media.controller.Library.extend({
        defaults: _.defaults({
            id: 'playlist-library',
            filterable: 'uploaded',
            multiple: 'add',
            menu: 'default',
            editable: true,
            toolbar: 'playlist',
            library: wp.media.query(_.defaults({
                type: 'audio'
            }, {})),
            title: window.progression.menuitem,
            priority: 100,
        }, wp.media.controller.Library.prototype.defaults),

        initialize: function() {
            wp.media.controller.Library.prototype.initialize.apply(this, arguments);
        }
    });

    // supersede the default MediaFrame.Post view
    var oldMediaFrame = wp.media.view.MediaFrame.Post;
    wp.media.view.MediaFrame.Post = wp.media.view.MediaFrame.Post.extend({

        initialize: function() {
            oldMediaFrame.prototype.initialize.apply(this, arguments);

            var strings = window.progression;

            this.states.add([
            new wp.media.controller.PlaylistAdd()]);

            this.bindHandlers();

            this.on('playlist', this.insert, this);

        },

        bindHandlers: function() {
            wp.media.view.MediaFrame.Select.prototype.bindHandlers.apply(this, arguments);
            this.on('toolbar:create:playlist', this.createToolbar, this);
            this.on('toolbar:render:playlist', this.renderToolbar, this);
        },

        createToolbar: function(toolbar) {
            toolbar.view = new wp.media.view.Toolbar({
                controller: this
            });
        },

        renderToolbar: function(view) {
            var controller = this;

            this.selectionStatusToolbar(view);

            view.set('playlist', {
                style: 'primary',
                text: window.progression.button,
                priority: 60,
                requires: {
                    selection: true
                },

                click: function() {
                    var state = controller.state(),
                        selection = state.get('selection');

                    controller.close();
                    state.trigger('playlist', selection).reset();
                }
            });
        },

        insert: function() {

            var attrs = {},
                shortcode = '';

            // get the ids of all selected audio files
            attrs.ids = this.state().get('selection').pluck('id');

            // construct playlist shortcode
            shortcode = wp.shortcode.string({
                tag: 'playlist',
                attrs: attrs,
                type: 'self-closing'
            });

            // send shortcode back to editor
            wp.media.editor.insert(shortcode);

        }

    });

}(jQuery));