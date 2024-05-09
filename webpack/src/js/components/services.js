$(document).ready(function() {
    $('[name="select_service"]').select2({
        minimumResultsForSearch: -1,
        multiple: false,
        placeholder: 'Select a Service',
        allowClear: true
    });

    $('[name="seoaic_knowledge_base"]').select2({
        minimumResultsForSearch: -1,
        multiple: false,
        placeholder: 'Choose here knowledge base',
        allowClear: true,
        language: {
            noResults: function() {
                return `<a href="${adminPage.adminUrl}?page=seoaic-knowledge-bases&action=create" class="seoaic-idea_create-kb">Create knowledge base</a>`;
            },
        },
        escapeMarkup: function(markup) {
            return markup;
        },
    });
});