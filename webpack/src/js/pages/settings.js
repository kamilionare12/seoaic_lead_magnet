import select2 from '../../../node_modules/select2/dist/js/select2';

$(() => {
    const select_id = '#seoaic_visible_posts';
    if ($(select_id).length) {
        $(select_id).select2({
            placeholder: "Select posts",
            minimumResultsForSearch: 2,
            selectionCssClass: 'seoaic-visible-posts-selection form-select mb-5',
            'dropdownCssClass': 'seoaic-visible-posts-dropdown'
        });
    }


    const not_saved_settings_alert = () => {
        let unsaved = false,
            set = $("#seoaic-settings")

        set.on('keyup change input', function(){
            unsaved = true;
        });
        set.bind("DOMSubtreeModified", function(){
            unsaved = true;
        });
        set.on('click', '[type="submit"]', function(){
            unsaved = false;
        });
        function unloadPage(){
            if(unsaved){
                return "You have unsaved changes on this page";
            }
        }
        window.onbeforeunload = unloadPage;
    }
    not_saved_settings_alert();


    const promptTemplateHtml = '' +
        '<div class="prompt-section">' +
            '<div class="item">' +
                '<textarea placeholder="Prompt template" name="post-prompt-teplate" class="form-input light post-prompt-teplate-input" autocomplete="off" rows="3"></textarea>' +
                '<a href="#" class="delete-prompt" title="Remove"></a>' +
            '</div>' +
        '</div>';

    $(document).on('click', '.add-prompt', (e) => {
        e.preventDefault();

        let el = $(e.currentTarget),
            list = el.prev('.prompts-list');

        list.append(promptTemplateHtml);
    });

    $(document).on('click', '.delete-prompt', (e) => {
        e.preventDefault();

        const el = $(e.currentTarget);
        if (el.closest('.prompts-list').children().length > 0) {
            el.closest('.prompt-section').remove();
        }
    })
});