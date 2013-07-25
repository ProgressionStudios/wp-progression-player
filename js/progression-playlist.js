(function($) {

    // supersede the default MediaFrame.Post view
    var oldMediaFrame = wp.media.view.MediaFrame.Post;
    wp.media.view.MediaFrame.Post = wp.media.view.MediaFrame.Post.extend({

        initialize: function() {
            oldMediaFrame.prototype.initialize.apply(this, arguments);

            var strings = window.progression;

            this.states.add([
                new wp.media.controller.Library({
                    id: 'playlist-library',
                    filterable: 'uploaded', // allows the user to show only the files uploaded to the current post
                    multiple: true, // selecting more than one file makes sense for a playlist
                    menu: 'default', // extend the default menu
                    editable: true, // allow selection to be edited via drag & drop
                    toolbar: 'playlist', // select our custom toolbar
                    library: wp.media.query(_.defaults({
                        type: 'audio' // allow audio files only
                    }, {})),
                    title: window.progression.menuitem,
                    priority: 100,
                })]
            );

            // event binding
            this.on('playlist:insert', this.insert, this);
            this.on('toolbar:create:playlist', this.createToolbar, this);
            this.on('toolbar:render:playlist', this.renderToolbar, this);

        },

        renderToolbar: function(view) {
            var controller = this;

            // add the little view where we can edit the selection
            this.selectionStatusToolbar(view);

            view.set('playlist-insert', {
                style: 'primary',
                text: window.progression.button,
                priority: 60,
                requires: {
                    selection: true
                },

                click: function() {
                    var state = controller.state(),
                        selection = state.get('selection');

                    controller.close(); // should close the window after we have selected
                    state.trigger('playlist:insert', selection).reset();
                }
            });
        },

        // triggered when "insert" button is clicked
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
            // used to be send_to_editor()
            wp.media.editor.insert(shortcode);

        }

    });

}(jQuery));