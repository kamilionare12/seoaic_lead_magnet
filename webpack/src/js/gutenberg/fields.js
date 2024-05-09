const {__} = wp.i18n;
const {compose, withState} = wp.compose;
const {withSelect, withDispatch} = wp.data;
const {useState} = wp.element;
const {PluginDocumentSettingPanel} = wp.editPost;
const {
    ToggleControl,
    TextControl,
    PanelRow,
    Button,
    TextareaControl,
    SelectControl,
    FormTokenField,
    MenuGroup,
    MenuItemsChoice,
    RadioGroup,
    Modal
} = wp.components;
const Create = wp.element.createElement;

let // improve
    improveInstructionsPrompt,

    // Menu tab
    menuTab,

    // Frame
    frameHeading, frameToggle, framePostTitle, frameBTN,

    // Thumb
    titleImages, thumbToggle, gen, imagePrompt,

    // Article
    titleArticle, articleDescription, subTitles, keyWords,

    // Submit
    checkTitle, submit, checkArticle,

    // rollback
    rollback,

    // modal
    modalInfo

// Get post id from editor
const params = new Proxy(new URLSearchParams(window.location.search), {
    get: (searchParams, prop) => searchParams.get(prop),
});

// Start component
const Component = ({postType, postMeta, setPostMeta, postTitle, loadFrame, generatePost, improvePost, postContent}) => {
    //if ('post' !== postType) return null;
    //if (true !== postMeta._featured_seoaic)

    // Menu tab
    menuTab = Create(
        PanelRow,
        //null,
        //{label: 'Select improvement type'},
        {className: 'select_an_improvement_type'},
        Create(SelectControl, {
            label: __('Select an improvement type', 'seoaic'),
            value: postMeta._improvement_type_select ? postMeta._improvement_type_select : 'generate_new',
            options: [{label: 'Generate new content', value: 'generate_new'}, {label: 'Improve an existing article', value: 'improve_an_existing'}],
            onChange: value => setPostMeta({_improvement_type_select: value})
        })
    );

    const improveType = postMeta._improvement_type_select



    // Frame
    frameHeading =
        improveType === 'generate_new' ? Create(
            'div', {
                dangerouslySetInnerHTML: {
                    __html: '' +
                        "<div>" + __('Here you can automatically generate alternative content for this article based on AI', 'seoaic') + "</div>" +
                        "<h4 class='subsection-title'>" + __('FRAME', 'seoaic') + "</h4>"
                }
            }
        ) :
        improveType === 'improve_an_existing' ? Create(
            'div', {
                dangerouslySetInnerHTML: {
                    __html: '' +
                        "<div>" + __('Here you can automatically improve an existing article based on AI', 'seoaic') + "</div>" +
                        "<h4 class='subsection-title'>" + __('PROMPT', 'seoaic') + "</h4>"
                }
            }
        )
            : ''

    improveInstructionsPrompt = improveType === 'improve_an_existing' ? Create(
        PanelRow,
        null,
        Create(TextareaControl, {
            label: __('Improvement instructions', 'seoaic'),
            value: postMeta.seoaic_improve_instructions_prompt,
            className: 'seoaic_improve_instructions_prompt',
            id: 'seoaic_improve_instructions_prompt',
            placeholder: 'Give some instructions on how this article can be improved. Describe the following in a free text here',
            onChange: value => setPostMeta({seoaic_improve_instructions_prompt: value})
        })
    ) : ''

    frameToggle = improveType === 'generate_new' ? Create(
        PanelRow,
        null,
        Create(ToggleControl, {
            label: __('Generate frame', 'seoaic'),
            onChange: value => setPostMeta({_frame_yes_seoaic: value}),
            checked: postMeta._frame_yes_seoaic
        })
    ) : ''
    framePostTitle = postMeta._frame_yes_seoaic && improveType === 'generate_new' ? Create(
        PanelRow,
        null,
        Create(TextControl, {
            label: __('Article title', 'seoaic'),
            help: __('Frame will be generated on the basis of the article title.', 'seoaic'),
            value: postTitle,
            onChange: value => wp.data.dispatch('core/editor').editPost({title: value})
        })
    ) : ''

    frameBTN = postMeta._frame_yes_seoaic && improveType === 'generate_new' ? Create(
        PanelRow,
        null,
        Create(Button, {
            variant: 'secondary',
            text: __('Generate frame', 'pseoaic'),
            label: __('This option automatically generates an article outline.', 'pseoaic'),
            size: 'small',
            disabled: !postTitle,
            id: 'generate-frame-editor',
            'data-action': 'seoaic_generate_skeleton',
            'data-editor': true,
            onClick: loadFrame,
        })
    ) : ''

    // Thumbnail
    titleImages = improveType === 'generate_new' ? Create(
        'div', {
            dangerouslySetInnerHTML: {
                __html: '' +
                    "<h4 class='subsection-title'>" + __('THUMBNAIL', 'seoaic') + "</h4>"
            }
        }
    ) : ''
    thumbToggle = improveType === 'generate_new' ? Create(
        PanelRow,
        null,
        Create(ToggleControl, {
            label: __('Generate thumbnail as well', 'seoaic'),
            onChange: checked => setPostMeta({_thumb_yes_seoaic: checked}),
            checked: postMeta._thumb_yes_seoaic
        })
    ) : ''
    gen = postMeta._thumb_yes_seoaic && improveType === 'generate_new' ? Create(
        PanelRow,
        null,
        Create(SelectControl, {
            label: __('Select image generator', 'seoaic'),
            value: postMeta.seoaic_idea_thumbnail_generator,
            options: [{label: 'ChatGPT', value: 'gpt'}, {label: 'Clipdrop', value: 'clipdrop'}],
            onChange: value => setPostMeta({seoaic_idea_thumbnail_generator: value})
        })
    ) : ''
    imagePrompt = postMeta._thumb_yes_seoaic && improveType === 'generate_new' ? Create(
        PanelRow,
        null,
        Create(TextareaControl, {
            label: __('Image Prompt', 'seoaic'),
            value: postMeta.seoaic_generate_description,
            onChange: value => setPostMeta({seoaic_generate_description: value})
        })
    ) : ''

    // Article
    titleArticle = improveType === 'generate_new' ? Create(
        'div', {
            dangerouslySetInnerHTML: {
                __html: '' +
                    "<h4 class='subsection-title'>" + __('ARTICLE', 'seoaic') + "</h4>"
            }
        }
    ) : ''
    articleDescription = improveType === 'generate_new' ? Create(
        PanelRow,
        null,
        Create(TextareaControl, {
            label: __('Article description', 'seoaic'),
            value: postMeta.seoaic_article_description,
            placeholder: __('Short description of what the article should be about', 'seoaic'),
            onChange: value => setPostMeta({seoaic_article_description: value})
        })
    ) : ''
    subTitles = improveType === 'generate_new' ? Create(
        PanelRow,
        null,
        Create(FormTokenField, {
            label: __('ARTICLE Subtitles', 'seoaic'),
            value: postMeta.seoaic_article_subtitles,
            suggestions: postMeta.seoaic_article_subtitles,
            className: 'subtitles-tagged',
            onChange: value => setPostMeta({seoaic_article_subtitles: value})
        })
    ) : ''
    keyWords = improveType === 'generate_new' ? Create(
        PanelRow,
        null,
        Create(FormTokenField, {
            label: __('ARTICLE keywords', 'seoaic'),
            value: postMeta.seoaic_article_keywords,
            suggestions: postMeta.seoaic_article_keywords,
            className: 'keywords-tagged',
            onChange: value => setPostMeta({seoaic_article_keywords: value})
        })
    ) : ''

    const userInstructions = $("#seoaic_improve_instructions_prompt")
    //userInstructions.value === '' ? alert("debug") : ''


    // Submit
    submit = improveType === 'generate_new' ? Create(
            PanelRow,
            null,
            Create(Button, {
                variant: 'primary',
                text: __('Generate article', 'seoaic'),
                size: 'default',
                disabled: !postTitle,
                'data-action': 'seoaic_generate_post',
                'data-editor': 'true',
                onClick: generatePost
            })
        ) :
        improveType === 'improve_an_existing' ? Create(
            PanelRow,
           // null,
            {className: 'submit-improve'},
            Create(Button, {
                variant: 'primary',
                text: __('Improve an article', 'seoaic'),
                size: 'default',
                disabled: !postContent ? true : !postMeta.seoaic_improve_instructions_prompt ? true : userInstructions.val() === '' ? true : !postTitle,
                'data-action': 'seoaic_improve_post',
                'data-editor': 'true',
                'data-rollback': '',
                onClick: improvePost
            })
        ) : ''

    // rollback content
    rollback = improveType === 'improve_an_existing' && postMeta.seoaic_rollback_content_improvement ? Create(
        PanelRow,
            null,
            Create(Button, {
                variant: 'secondary',
                text: __('Rollback an article', 'seoaic'),
                label: __('This will restore previous article state before the last improvement run', 'pseoaic'),
                size: 'small',
                'data-action': 'seoaic_improve_post',
                'data-rollback': 'true',
                'data-editor': 'true',
                onClick: improvePost
            })
        ) : ''

    // modal
    modalInfo = Create(
        PanelRow,
            null,
            Create(Button, {
                variant: 'secondary',
                text: __('Open modal', 'seoaic'),
                onClick: Create(
                    Modal, {
                        variant: 'secondary',
                        title: __('Rollback an article', 'seoaic'),
                        //onClick: () => setState( { isOpen: true } ),
                        onRequestClose: '',
                    }
                )
            })
        )

    const [isOpen, setOpen] = useState(false);
    const openModal = () => setOpen(true);
    const closeModal = () => setOpen(false);

    const close = Create(Button, {
        variant: 'secondary',
        text: __('Close', 'pseoaic'),
        label: __('This option automatically generates an article outline.', 'pseoaic'),
        size: 'small',
        onClick: closeModal,
    })

    const openModalInfo = Create(
        PanelRow,
        null,
        Create(Button, {
                variant: 'link',
                text: __('Help', 'pseoaic'),
                label: __('Click for Help', 'pseoaic'),
                size: 'small',
                onClick: openModal,
            },
            isOpen ?
                Create(Modal, {
                        variant: 'secondary',
                        title: __('Generate frame', 'pseoaic'),
                        size: 'medium',
                        shouldCloseOnEsc: true,
                        shouldCloseOnClickOutside: true,
                        __experimentalHideHeader: true,
                        className: 'modal-help',
                        close,
                        onRequestClose: closeModal
                    },
                    Create(
                        'div', {
                            dangerouslySetInnerHTML: {
                                __html: '' +
                                    "<h3 class='subsection-title'>" + __('HELP', 'seoaic') + "</h3>" +
                                    "<p>" + __('To make the results of your request more effective, please use clear and simple sentences as possible. Avoid using personal pronouns such as "my article or "my text".', 'seoaic') + "</p>" +
                                    "<p>" + __('The AI understands that it is editing the current document and you can provide any relevant information without self-referencing.', 'seoaic') + "</p>" +

                                    "<h4 class='subsection-title'>" + __('Examples of some prompts may used:', 'seoaic') + "</h4>" +

                                    "<ul>" +
                                    "<li><i>" + __('- add subtitles for each 2 pharagrafs. Wrab subtitles in &#60;h3&#62; tag', 'seoaic') + "</i></li>" +
                                    "<li><i>" + __('- insert the keywords "blue taxi, yellow car, new road" in article.', 'seoaic') + "</i></li>" +
                                    "<li><i>" + __('- Incorporate a simile into the following text.', 'seoaic') + "</i></li>" +
                                    "<li><i>" + __('- proofread and edit this text for errors', 'seoaic') + "</i></li>" +
                                    "<li><i>" + __('- Summarize this entire text into 10-15 sentences. wrap the main ideas in to &#60;strong&#62; tag', 'seoaic') + "</i></li>" +
                                    "<li><i>" + __('- Add FAQ in to the text and wrap questions in &#60;strong&#62; tag', 'seoaic') + "</i></li>" +
                                    "</ul>"
                            }
                        }
                    ),
                ) : ''
        )
    )


    checkTitle = improveType === 'generate_new' ? postTitle ? '' : Create(
        'div', {
            className: 'components-panel__row',
            dangerouslySetInnerHTML: {
                __html: "<div class='red small'>" + __('The post title is required', 'seoaic') + "</div>"
            }
        }
    ) : ''

    checkArticle = improveType === 'improve_an_existing' ? postContent ? '' : Create(
        'div', {
            className: 'components-panel__row',
            dangerouslySetInnerHTML: {
                __html: "<div class='red small'>" + __('The Post Content is Required', 'seoaic') + "</div>"
            }
        }
    ) : ''

    return Create(
        PluginDocumentSettingPanel,
        {title: __('Improve with SEO AI', 'seoaic'), icon: ' ', initialOpen: 'false', className: 'improve-with-ai'},

        // Menu tab
        menuTab,

        // Frame
        frameHeading,
        // improve
        improveInstructionsPrompt,

        frameToggle,
        framePostTitle,
        frameBTN,

        // Thumb
        titleImages,
        thumbToggle,
        gen,
        imagePrompt,

        // Article
        titleArticle,
        articleDescription,
        subTitles,
        keyWords,

        // Submit
        checkTitle,
        openModalInfo,
        rollback,
        checkArticle,
        submit,
    );
};

export default compose([withSelect(select => {
    return {
        postTitle: select('core/editor').getEditedPostAttribute('title'),
        postContent: select( 'core/editor' ).getEditedPostAttribute( 'content' ),
        postMeta: select('core/editor').getEditedPostAttribute('meta'),
        postType: select('core/editor').getCurrentPostType(),
        notSaved: select('core/editor').isEditedPostDirty()
    };
}),
    withDispatch(dispatch => {
        return {
            setPostMeta(newMeta) {
                dispatch('core/editor').editPost({meta: newMeta});
            },
            loadFrame: function (e) {
                let its = $(e.currentTarget),
                    title = wp.data.select('core/editor').getEditedPostAttribute('title'),
                    id = wp.data.select('core/editor').getEditedPostAttribute('id'),
                    container = its.closest('.improve-with-ai'),
                    action = its.attr('data-action'),
                    data = {
                        'action': action,
                        'data_editor': its.attr('data-editor'),
                        'item_id': id ? id : params.post,
                        'get_title': title,
                    }

                let seoaicNonceValue = wp_nonce_params[data.action];

                if (seoaicNonceValue !== '') {
                    data._wpnonce = seoaicNonceValue;
                }

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: data,
                    beforeSend: function () {
                        container.addClass('loading')
                        its.find('select').each(function () {
                            $(this).change()
                            $(this).addClass('changed')
                        })
                        console.log(id)
                        wp.data.dispatch('core/notices').createNotice(
                            'warning',
                            'Frame generation in progress',
                            {
                                id: 'seoaic-greetings-notice',
                                isDismissible: true,
                                type: 'snackbar'
                            }
                        );
                        wp.data.dispatch('core/editor').savePost();
                    },
                    success: function (data) {
                        //container.removeClass('ajax-loading')
                        console.log(data)
                        wp.data.dispatch('core/editor').editPost({meta: {seoaic_generate_description: data.content.idea_content.idea_thumbnail}});
                        wp.data.dispatch('core/editor').editPost({meta: {seoaic_idea_thumbnail_generator: data.content.idea_content.idea_thumbnail_generator}});
                        wp.data.dispatch('core/editor').editPost({meta: {seoaic_article_description: data.content.idea_content.idea_description}});
                        wp.data.dispatch('core/editor').editPost({meta: {seoaic_article_subtitles: data.content.idea_content.idea_skeleton}});
                        wp.data.dispatch('core/editor').editPost({meta: {seoaic_article_keywords: data.content.idea_content.idea_keywords}});
                        wp.data.dispatch('core/notices').createNotice(
                            'warning',
                            'Frame generation is complete',
                            {
                                id: 'seoaic-greetings-notice',
                                isDismissible: true,
                                type: 'snackbar'
                            }
                        );
                        wp.data.dispatch('core/editor').savePost();
                    },

                    error: function (xhr) {
                        console.log(xhr)
                    },

                    complete: function (data) {
                        container.removeClass('loading')
                    }
                })
            },
            generatePost: function (e) {

                let its = $(e.currentTarget),
                    title = wp.data.select('core/editor').getEditedPostAttribute('title'),
                    id = wp.data.select('core/editor').getEditedPostAttribute('id'),
                    container = its.closest('.improve-with-ai'),
                    action = its.attr('data-action'),
                    data = {
                        'action': action,
                        'data_editor': its.attr('data-editor'),
                        'item_id': id ? id : params.post,
                        'get_title': title,
                    }

                let seoaicNonceValue = wp_nonce_params[data.action];

                if (seoaicNonceValue !== '') {
                    data._wpnonce = seoaicNonceValue;
                }

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: data,
                    beforeSend: function () {
                        container.addClass('loading')
                        its.find('select').each(function () {
                            $(this).change()
                        })
                        wp.data.dispatch('core/editor').savePost();
                        wp.data.dispatch('core/notices').createNotice(
                            'warning',
                            'Post generation in progress',
                            {
                                id: 'seoaic-greetings-notice',
                                isDismissible: true,
                                type: 'snackbar'
                            }
                        );
                    },
                    success: function (data) {
                        //container.removeClass('ajax-loading')
                        console.log(data.thumbnail)
                        console.log(data)

                        wp.data.dispatch('core/block-editor').resetBlocks(wp.blocks.parse(data.editor_content));
                        wp.data.dispatch('core/editor').editPost({featured_media: data.thumbnail});
                        wp.data.dispatch('core/editor').editPost({meta: {seoaic_rollback_content_improvement: ''}})
                        wp.data.dispatch('core/notices').createNotice(
                            'warning',
                            'Article generation is complete',
                            {
                                id: 'seoaic-greetings-notice',
                                isDismissible: true,
                                type: 'snackbar'
                            }
                        );
                        wp.data.dispatch('core/editor').savePost();

                    },
                    error: function (xhr) {
                        console.log(xhr)
                    },
                    complete: function (data) {
                        container.removeClass('loading')
                    }
                })
            },
            improvePost: function (e) {
                let its = $(e.currentTarget),
                    action = its.attr('data-action'),
                    rollback = its.attr('data-rollback'),
                    container = its.closest('.improve-with-ai'),
                    id = wp.data.select('core/editor').getEditedPostAttribute('id'),
                    title = wp.data.select('core/editor').getEditedPostAttribute('title'),
                    content = wp.data.select('core/editor').getEditedPostAttribute('content'),
                    prompt = wp.data.select('core/editor').getEditedPostAttribute('meta').seoaic_improve_instructions_prompt,
                    data = {
                        'action': action,
                        'data_editor': its.attr('data-editor'),
                        'item_id': id ? id : params.post,
                        'title': title ? title : '',
                        'content': content ? content : '',
                        'improve_prompt': prompt ? prompt : '',
                        'rollback': rollback,
                    }

                let seoaicNonceValue = wp_nonce_params[data.action];

                if (seoaicNonceValue !== '') {
                    data._wpnonce = seoaicNonceValue;
                }

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: data,
                    beforeSend: function () {
                        container.addClass('loading')
                        $('body').addClass('improvement-loading')

                        console.log(rollback)
                        wp.data.dispatch('core/notices').createNotice(
                            'warning',
                            'Article improvement in progress',
                            {
                                //id: 'seoaic-greetings-notice',
                                isDismissible: true,
                                type: 'snackbar'
                            }
                        );

                        wp.data.dispatch('core/editor').savePost();
                    },
                    success: function (data) {
                        console.log(data)
                        wp.data.dispatch('core/notices').createNotice(
                            'warning',
                            rollback ? 'Article rollback complete' : 'Article improvement complete',
                            {
                                //id: 'seoaic-greetings-notice',
                                isDismissible: true,
                                type: 'snackbar'
                            }
                        );

                        rollback ?
                            wp.data.dispatch('core/editor').editPost({meta: {seoaic_rollback_content_improvement: ''}})
                            :
                            wp.data.dispatch('core/editor').editPost({meta: {seoaic_rollback_content_improvement: content}});

                        if (data.content.content) {
                            wp.data.dispatch('core/block-editor').resetBlocks(wp.blocks.parse(data.content.content))
                        } else {
                            wp.data.dispatch('core/notices').createNotice(
                                'warning',
                                'Empty response, try again',
                                {
                                    //id: 'seoaic-greetings-notice',
                                    isDismissible: true,
                                    type: 'snackbar'
                                }
                            );
                        }
                    },
                    error: function (xhr) {
                        console.log(xhr)
                    },
                    complete: function (data) {
                        //console.log(data)
                        container.removeClass('loading')
                        $('body').removeClass('improvement-loading')
                        wp.data.dispatch('core/editor').savePost();
                    }
                })
            }
        };
    })])(Component);