if ( wp.element) {
    var el = wp.element.createElement;
    var promtElement = document.getElementById('seoaic-promt-key');
    var promt = '';
    if (promtElement) {
        promt = promtElement.getAttribute('data-key');
    }

    // get post id from editor
    const params = new Proxy(new URLSearchParams(window.location.search), {
        get: (searchParams, prop) => searchParams.get(prop),
    });

    function wrapPostFeaturedImage( OriginalComponent ) {
        return function ( props ) {
            return el(
                wp.element.Fragment,
                {},

                el( OriginalComponent, props ),
                el( 'div', {
                    dangerouslySetInnerHTML: {
                        __html: '' +
                            '<div class="seoaic-module">' +
                            '<input type="checkbox" name="regenerate_image" id="regenerate_image">\n' +
                            '<label for="regenerate_image">Generate new image' +
                            '<div class="info">\n' +
                            '<span class="info-btn">?</span>\n' +
                            '<div class="info-content">\n' +
                            '<h4>Generate new image</h4>\n' +
                            '<p>You can try regenerating image with another service if you are not satisfied with it.\n' +
                            '</p>\n' +
                            '</div>\n' +
                            '</div>' +
                            '</label>' +
                            '<div class="selections">' +
                            '<textarea class="promt-key">' + promt + '</textarea>' +
                            '<select class="seoaic-form-item form-select regenerate-select-modal" name="seoaic_regenerate-select-modal" required="">\n' +
                            '<option value="gpt" selected="">GPT</option>\n' +
                            '<option value="clipdrop">Clipdrop</option>\n' +
                            '</select>' +
                            '<div class="btn-sc">\n' +
                            '<div class="info">\n' +
                            '<span class="info-btn">?</span>\n' +
                            '<div class="info-content">\n' +
                            '<h4>Generate new image</h4>\n' +
                            '<p>You can try regenerating image with another service if you are not satisfied with it.\n' +
                            '</p>\n' +
                            '</div>\n' +
                            '</div>\n' +
                            '<button data-callback="regenerate_image_editor" data-action="seoaic_regenerate_image" data-type="modal" data-post="' + params.post + '" title="Regenerate image" type="button" class="button-primary seoaic-generate-image-button" data-action="seoaic_generate_ideas">New image</button>\n' +
                            '</div>' +
                            '</div>' +
                            '</div>'
                    }
                } )
            );
        };
    }

    //wp.hooks.addFilter(
    //    'editor.PostFeaturedImage',
    //    'my-plugin/wrap-post-featured-image',
    //    wrapPostFeaturedImage
    //);
}