$ = jQuery
(function( $ ){
    let wp_media_post_id
    $(document).on('click', '.upload_image_button, .remove_selected_image', function (event) {
        event.preventDefault();

        let it = $(this),
            data_change = it.attr('data-change'),
            data_upl = it.attr('data-upl'),
            form = it.closest('.seoaic_image_uploader'),
            wp_media_post_id = wp.media.model.settings.post.id,
            file_frame,
            set_to_post_id = form.find('.set_image_id').attr('data-thumb-id'),
            image_preview = form.find('.image-preview-wrapper > .top'),
            image_id = form.find('.set_image_id'); //

        if (it.is('.upload_image_button')) {
            if (file_frame) {
                file_frame.uploader.uploader.param('post_id', set_to_post_id);
                file_frame.open();
                return;
            } else {
                wp.media.model.settings.post.id = set_to_post_id;
            }

            file_frame = wp.media.frames.file_frame = wp.media({
                title: 'Select a image to upload',
                button: {
                    text: 'Use this image',
                },
                multiple: false
            });

            file_frame.on('select', function () {
                // We set multiple to false so only get one image from the uploader
                let attachment = file_frame.state().get('selection').first().toJSON();
                form.addClass('selected')
                image_preview.html('<img src="' + attachment.url + '">');
                image_id.val(attachment.id);
                it.text(data_change)
                // Restore the main post ID
                wp.media.model.settings.post.id = wp_media_post_id;
            });

            file_frame.open();

        } else if (it.is('.remove_selected_image')) {
            form.removeClass('selected')
            image_preview.html('');
            image_id.val('');
            form.find('[data-change]').text(data_upl)
        }

    });
})