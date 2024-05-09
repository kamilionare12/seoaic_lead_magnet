import { ajax } from 'jquery';
import select2 from '../../../node_modules/select2/dist/js/select2'
import {seoaic_open_modal} from '../imported/seoaic-admin'

$(function() {
    $('[name="select-keywords"]').select2({
        minimumResultsForSearch: -1,
        closeOnSelect:false
    });


    $('#add-keyword-modal [name="location"], #add-keyword-modal [name="language"]').select2({
        minimumResultsForSearch: 1
    });

    $('#generate-keywords [name="location"], #generate-keywords [name="language"]').select2({
        minimumResultsForSearch: 1
    });

    $('#add-keyword-modal [name="head_term_id"], #add-keyword-modal [name="mid_tail_id"]').select2({
        minimumResultsForSearch: 1
    });

    $('#generate-keywords.seoaic-modal [name="head_term_id"], #generate-keywords.seoaic-modal [name="mid_tail_id"]').select2({
        minimumResultsForSearch: 1
    });

    function linksFormat (page) {
        let html = '';
        let $result = $("<span></span>");
        $result.text(page.text);

        if (page.newTag) {
            $result.append(" (New)");
            html += $result.html();
        } else {
            const originalOption = page.element;
            const permalink = $(originalOption).data('permalink');

            html += '<span class="fs-small"><b>' + page.text + '</b></span>';
            if (permalink) {
                html += '<br><span>' + permalink + '</span>';
            }
        }

        return html;
    };
    $('#add-keyword-link-modal .seoaic-keyword-page-link').each((i, el) => {
        $(el).select2({
            placeholder: 'Select Page',
            allowClear: true,
            minimumResultsForSearch: 3,
            tags: true,
            createTag: function (params) {
                const term = $.trim(params.term);

                if (term === '') {
                    return null;
                }

                return {
                    id: term,
                    text: term,
                    newTag: true
                };
            },
            // templateResult: function (data) {
            //     let $result = $("<span></span>");

            //     $result.text(data.text);

            //     if (data.newTag) {
            //         $result.append(" (New)");
            //     }

            //     return $result;
            // },
            dropdownCssClass: 'seoaic-keyword-link-dd',
            selectionCssClass: 'seoaic-keyword-link-selection',
            templateResult: linksFormat,
            templateSelection: linksFormat,
            escapeMarkup: function(m) { return m; },
            matcher: (params, data) => {
                if (
                    params.term == null
                    || params.term.trim() === ''
                ) {
                    return data;
                }

                const original = data.text.toUpperCase();
                const term = params.term.toUpperCase();
                const el = $(data.element);
                const permalink = el.data('permalink');

                if (
                    original.indexOf(term) > -1 // Check if the text contains the term
                    || ( // or permalink attr contains the term
                        permalink
                        && permalink.toUpperCase().indexOf(term) > -1
                    )
                ) {
                    return data;
                }

                // If it doesn't contain the term, don't return anything
                return null;
            }
        });
    });

    $('#keywords-set-category-modal #seoaic_keywords_categories').select2({
        minimumResultsForSearch: 3,
        dropdownCssClass: 'seoaic-keywords-categories-dd',
        selectionCssClass: 'seoaic-keywords-categories-selection',
        placeholder: 'Select Cluster',
        allowClear: true,
        tags: true,
        createTag: function (params) {
            const term = params.term.trim();

            if (term === '') {
                return null;
            }

            return {
                id: term,
                text: term,
                newTag: true
            };
        },
        templateResult: function (data) {
            let $result = $("<span></span>");

            $result.text(data.text);

            if (data.newTag) {
                $result.append(" (New)");
            }

            return $result;
        }
    });

    $('#seoaic-remove-and-reassign-confirm-modal form #seoaic_reassign_to_keyword').select2({
        minimumResultsForSearch: 3
    });

    const addFormID = 'add-keywords-form';
    const generateFormID = 'generate-keywords-form';
    const addGenerateKeywordsForms = $('#' + addFormID + ', #' +  generateFormID);

    if (addGenerateKeywordsForms.length) {
        addGenerateKeywordsForms.each((i, _formEl) => {
            const formEl = $(_formEl);
            const keywordTypeEl = formEl.find('[name="keyword_type"]');
            const headTermEl = formEl.find('[name="head_term_id"]');
            const midTailTermsEl = formEl.find('[name="mid_tail_id"]');
            const headTermsWrapper = formEl.find('.seoaic-head-terms-wrapper');
            const midTailTermsWrapper = formEl.find('.seoaic-mid-tail-terms-wrapper');
            const termsSelector = formEl.find('.seoaic-terms-selector');
            const promptWrapper = formEl.find('.seoaic-keywords-custom-prompt-wrapper');

            keywordTypeEl.on('change', (e) => {
                const keywordTypeValue = $(e.currentTarget).val();

                if ('mid_tail_term' == keywordTypeValue) {
                    if (
                        formEl[0].id == generateFormID
                        && promptWrapper.length
                    ) {
                        promptWrapper.fadeOut();
                    }

                    headTermsWrapper.fadeIn();
                    midTailTermsWrapper.fadeOut();
                    headTermEl.prop('required', true);
                    midTailTermsEl.prop('required', false);

                } else if ('long_tail_term' == keywordTypeValue) {
                    if (
                        formEl[0].id == generateFormID
                        && promptWrapper.length
                    ) {
                        promptWrapper.fadeOut();
                    }

                    headTermsWrapper.fadeIn();
                    midTailTermsWrapper.fadeIn();
                    headTermEl.prop('required', true);
                    midTailTermsEl.prop('required', true);

                } else { // head term
                    if (
                        formEl[0].id == generateFormID
                        && promptWrapper.length
                    ) {
                        promptWrapper.fadeIn();
                    }

                    termsSelector.each((index, el) => {
                        $(el).fadeOut();
                    });
                    headTermEl.prop('required', false);
                    midTailTermsEl.prop('required', false);
                }
            });

            headTermEl.on('change', (e) => {
                const headTermValue = $(e.currentTarget).val();
                const data = {
                    action: 'seoaic_get_child_keywords',
                    back_action: 'seoaic_get_child_keywords',
                    id: headTermValue,
                    options_html: 1
                };
                const seoaicNonceValue = wp_nonce_params[data.action];

                if (seoaicNonceValue !== '') {
                    data._wpnonce = seoaicNonceValue;
                }

                $.ajax({
                    url: ajaxurl,
                    method: 'post',
                    dataType: 'json',
                    data: data,
                    beforeSend: function() {
                        midTailTermsEl.prop("disabled", true);
                    },
                    success: function(data) {
                        if ("undefined" !== typeof data.options_html) {
                            midTailTermsEl.html(data.options_html).trigger("change");
                        }
                    },
                    complete: function() {
                        midTailTermsEl.prop("disabled", false);
                    }
                });
            });
        });
    }


    $(document).on('click', '.seoaic-has-children > .row-line-container > .keyword', (e) => {
        const keywordEl = $(e.currentTarget);
        const parentEl = keywordEl.closest('.seoaic-has-children');
        const keywordID = parentEl.data('id');

        if ('' == keywordID) {
            return;
        }

        const childrenSection = $('#seoaic_kw_children_' + keywordID);

        if ("undefined" == typeof childrenSection) {
            return;
        }

        keywordEl.toggleClass('seoaic-closed');
        childrenSection.toggleClass('d-none');


        let keywordsTableOpenState = getKeywordsTableOpenState();

        if (keywordEl.hasClass('seoaic-closed')) {
            keywordsTableOpenState.set(keywordID, 0);
        } else {
            keywordsTableOpenState.set(keywordID, 1);
        }

        setKeywordsTableOpenState(keywordsTableOpenState);
    });

    const getKeywordsTableOpenState = () => {
        let keywordsTableOpenState = localStorage.getItem("keywordsTableOpenState");
        keywordsTableOpenState = null !== keywordsTableOpenState ? new Map(JSON.parse(keywordsTableOpenState)) : new Map();

        return keywordsTableOpenState;
    };

    const setKeywordsTableOpenState = (keywordsTableOpenState) => {
        localStorage.setItem(
            "keywordsTableOpenState",
            JSON.stringify(Array.from(keywordsTableOpenState.entries()))
        );
    };

    const runKeywordsTableOpenState = () => {
        let keywordsTableOpenState = localStorage.getItem("keywordsTableOpenState");

        if (null !== keywordsTableOpenState) {
            keywordsTableOpenState = new Map(JSON.parse(keywordsTableOpenState));
            keywordsTableOpenState.forEach((value, key, map) => {
                $('.seoaic-has-children > .row-line-container > .keyword').each((i, el) => {
                    const keywordEl = $(el);
                    const parentEl = keywordEl.closest('.seoaic-has-children');
                    const keywordID = parentEl.data('id');
                    const childrenSection = $('#seoaic_kw_children_' + keywordID);

                    if (keywordID == key) {
                        if (1 == value) {
                            keywordEl.removeClass('seoaic-closed');

                            if ("undefined" !== typeof childrenSection) {
                                childrenSection.removeClass('d-none');
                            }

                        } else {
                            keywordEl.addClass('seoaic-closed');

                            if ("undefined" !== typeof childrenSection) {
                                childrenSection.addClass('d-none');
                            }
                        }
                    }
                });
            });
        }
    };
    runKeywordsTableOpenState();


    $('.keyword > span').on('click', function (e) {
        e.stopPropagation();
        let it = $(this),
            checkbox = it.closest('.row-line-container').find('.check input[type="checkbox"]');

        checkbox.trigger("click");

        return false;
    })


    $('.seoaic-keywords-table').on('click', '.rank-view-more', (e) => {
        const clickedEl = $(e.currentTarget);
        const modal = $('#rank-keyword-modal');

        if (modal.length) {
            const rankContent = clickedEl.next().html();
            const body = modal.find('.table .body');

            if (body.length) {
                body.html(rankContent);
            }
        }
    });

    const loadLocationsAndLanguges = function(modal)
    {
        if (!modal.length) {
            return;
        }

        const locationEl = modal.find('[name="location"]');

        if ('' != locationEl.html().trim()) { // locations already loaded
            return;
        }

        const data = {
            action: 'seoaic_get_locatios',
            options_html: 1
        };
        const seoaicNonceValue = wp_nonce_params[data.action];

        if (seoaicNonceValue !== '') {
            data._wpnonce = seoaicNonceValue;
        }

        $.ajax({
            url: ajaxurl,
            method: 'post',
            dataType: 'json',
            data: data,
            success: function(data) {
                let html = '';

                if (
                    "undefined" !== typeof data.status
                    && "success" == data.status
                    && "undefined" !== typeof data.options_html
                ) {
                    html = data.options_html;
                }

                locationEl.html(html).trigger('change');
            }
        });
    }

    $('.seoaic-keywords-table').on('click', '.create-more-keywords', (e) => {
        e.stopPropagation();

        const el = $(e.currentTarget);
        const modal = $('#generate-keywords');

        if (!modal.length) {
            return;
        }

        loadLocationsAndLanguges(modal);

        const headTermEl = modal.find('[name="head_term_id"]');
        const midTailTermEl = modal.find('[name="mid_tail_id"]');
        const type = el.data('type');
        const str = '.seoaic-keyword-type-wrapper-generate input.' + type + '-radio';
        const optionEl = modal.find(str);

        if (optionEl.length) {
            optionEl.trigger('click').trigger('change');
        }

        if ('mid_tail_term' == type) {
            let id = el.data('for-id');
            headTermEl.val(id).trigger('change');

        } else if ('long_tail_term' == type) {
            let id = el.data('for-id');
            let parentID = el.data('for-parent');

            headTermEl.val(parentID).trigger('change');
            $(document).on("ajaxSuccess", function(event, xhr, ajaxOptions, data) {
                // change mid-tail term value after we have pulled child keywords
                if ('seoaic_get_child_keywords' == data.back_action) {
                    midTailTermEl.val(id).trigger('change');
                }
            });
        }

        seoaic_open_modal(modal);
    });

    const categoriesForm = $('#keywords_add_category_form');
    if (categoriesForm.length) {
        categoriesForm.on('submit', (e) => {
            e.preventDefault();

            const categoryNameEl = categoriesForm.find('[name="keywords_category_name"]');

            if (categoryNameEl.length) {
                const categoryVal = categoryNameEl.val().trim();

                if ('' == categoryVal) {
                    return;
                }

                const data = {
                    action: 'seoaic_keywords_category_add',
                    category_name: categoryVal
                };
                const seoaicNonceValue = wp_nonce_params[data.action];

                if (seoaicNonceValue !== '') {
                    data._wpnonce = seoaicNonceValue;
                }

                $.ajax({
                    url: ajaxurl,
                    method: 'post',
                    dataType: 'json',
                    data: data,
                    success: function(data) {
                        if ("success" == data.status) {
                            const table = $('#keywords-manage-categories-modal .table .body');
                            if (table.length) {
                                // table.append('<div class="table-row"><div>' + categoryVal + '</div><div></div></div>');
                                table.append(data.html);
                                categoryNameEl.val('');
                            }
                        }
                    }
                });
            }
        });
    }

    const setCategoryForm = $('#keywords_set_category_form');
    if (setCategoryForm.length) {
        setCategoryForm.on('submit', (e) => {
            e.preventDefault();

            const categoriesSelect = setCategoryForm.find('#seoaic_keywords_categories');
            const keywordIDEl = setCategoryForm.find('[name="keyword_id"]');
            const data = {
                action: 'seoaic_keywords_set_category',
                keyword_id: keywordIDEl.val(),
                category_id: categoriesSelect.val()
            };
            const seoaicNonceValue = wp_nonce_params[data.action];

            if (seoaicNonceValue !== '') {
                data._wpnonce = seoaicNonceValue;
            }

            $.ajax({
                url: ajaxurl,
                method: 'post',
                dataType: 'json',
                data: data,
                success: function(data) {
                    if ("success" == data.status) {
                        window.location.reload();
                    }
                }
            });
        });
    }

    $('.add-keyword-category.modal-button, .update-keyword-category,modal-button').on('click', (e) => {
        const data = {
            action: 'seoaic_keywords_get_categories',
            options_html: 1
        };
        const seoaicNonceValue = wp_nonce_params[data.action];

        if (seoaicNonceValue !== '') {
            data._wpnonce = seoaicNonceValue;
        }

        $.ajax({
            url: ajaxurl,
            method: 'post',
            dataType: 'json',
            data: data,
            success: function(data) {
                if ("success" == data.status) {
                    const el = $(e.currentTarget);
                    const catID = el.data('category-id');
                    const categoriesSelect = $('#keywords-set-category-modal').find('#seoaic_keywords_categories');

                    if (categoriesSelect.length) {
                        categoriesSelect.html(data.options_html);
                        categoriesSelect.val(catID).trigger('change');
                    }
                }
            }
        });
    });


    // category buttons show/hide
    $(document).on('click', '.edit-remove-buttons .seoaic-remove-category-button, .remove-confirm-buttons .seoaic-remove-category-button-cancel', (e) => {
        const btnsColumn = $(e.currentTarget).closest('.buttons-col');
        const btnsWrapper= btnsColumn.find('.edit-remove-buttons');
        const confirmBtns = btnsColumn.find('.remove-confirm-buttons');

        btnsWrapper.toggle();
        confirmBtns.toggle();
    });

    $(document).on('click', '.edit-remove-buttons .seoaic-edit-category-button, .update-confirm-buttons .seoaic-update-category-button-cancel', (e) => {
        const el = $(e.currentTarget);
        const btnsColumn = el.closest('.buttons-col');
        const btnsWrapper = btnsColumn.find('.edit-remove-buttons');
        const confirmBtns = btnsColumn.find('.update-confirm-buttons');

        const tableRow = el.closest('.table-row');
        const titlesCol = tableRow.find('.titles-col');
        const span = titlesCol.find('span');
        const input = titlesCol.find('input');

        btnsWrapper.toggle();
        confirmBtns.toggle();

        if (btnsWrapper.is(':visible')) {
            span.show();
            input.hide();
        } else {
            span.hide();
            input.show();
        }
    });

    // category remove handler
    $('#keywords-manage-categories-modal').on('click', '.seoaic-remove-category-button-confirm', (e) => {
        const el = $(e.currentTarget);
        const categoryID = el.data('cat-id');
        const data = {
            action: 'seoaic_keywords_delete_category',
            category_id: categoryID
        };
        const seoaicNonceValue = wp_nonce_params[data.action];

        if (seoaicNonceValue !== '') {
            data._wpnonce = seoaicNonceValue;
        }

        $.ajax({
            url: ajaxurl,
            method: 'post',
            dataType: 'json',
            data: data,
            success: function(data) {
                if ("success" == data.status) {
                    const row = el.closest('.table-row');

                    row.fadeOut('slow', () => {
                        row.remove();
                    });
                }
            }
        });
    });

    // category update handler
    $('#keywords-manage-categories-modal').on('click', '.seoaic-update-category-button-confirm', (e) => {
        const el = $(e.currentTarget);
        const categoryID = el.data('cat-id');
        const tableRow = el.closest('.table-row');
        const btnsColumn = el.closest('.buttons-col');
        const btnsWrapper = btnsColumn.find('.edit-remove-buttons');
        const confirmBtns = btnsColumn.find('.update-confirm-buttons');
        const categoryNameSpanEl = tableRow.find('.titles-col span');
        const categoryNameInputEl = tableRow.find('.titles-col input[name="category_name"]');
        const categoryName = categoryNameInputEl.val();
        const data = {
            action: 'seoaic_keywords_update_category',
            category_id: categoryID,
            category_name: categoryName
        };
        const seoaicNonceValue = wp_nonce_params[data.action];

        if (seoaicNonceValue !== '') {
            data._wpnonce = seoaicNonceValue;
        }

        $.ajax({
            url: ajaxurl,
            method: 'post',
            dataType: 'json',
            data: data,
            success: function(data) {
                if ("success" == data.status) {
                    categoryNameSpanEl.text(data.category_name).show();
                    categoryNameInputEl.hide();
                    $('button[data-category-id="' + categoryID + '"]').each((i, el) => {
                        $(el).find('span').text(data.category_name);
                    });

                    btnsWrapper.toggle();
                    confirmBtns.toggle();
                }
            }
        });
    });

    $('.seoaic-keywords-table').on('click', '.confirm-remove-and-reassign-modal-button', (e) => {
        const el = $(e.currentTarget);
        const keywordID = el.data('keyword-id');

        const data = {
            action: 'seoaic_keywords_get_siblings_keywords',
            keyword_id: keywordID,
            options_html: 1
        };
        const seoaicNonceValue = wp_nonce_params[data.action];

        if (seoaicNonceValue !== '') {
            data._wpnonce = seoaicNonceValue;
        }

        $.ajax({
            url: ajaxurl,
            method: 'post',
            dataType: 'json',
            data: data,
            success: function(data) {
                if ("success" == data.status) {
                    const modal = $('#seoaic-remove-and-reassign-confirm-modal');
                    if (modal.length) {
                        const keywordsSelectEl = modal.find('form select[name="reassign_keyword_id"]');

                        if (keywordsSelectEl.length) {
                            keywordsSelectEl.html(data.options_html);
                        }
                    }
                }
            }
        });
    });


    // Location and Language dropdown handlers
    $('#add-keyword-modal.seoaic-modal [name="location"], #generate-keywords.seoaic-modal [name="location"]').on('change', (e, state) => {
        if ("undefined" != typeof state && state) {
            // console.log('is triggered from code');
            return false;
        }

        const el = $(e.currentTarget);
        const val = el.val();
        const modal = el.closest('.seoaic-modal');
        const crossModalId = modal.data('cross-modal-id');
        const crossModal = $('#' + crossModalId);
        const data = {
            action: 'seoaic_get_location_languages',
            location: val,
            options_html: 1
        };
        const seoaicNonceValue = wp_nonce_params[data.action];

        if (seoaicNonceValue !== '') {
            data._wpnonce = seoaicNonceValue;
        }

        $.ajax({
            url: ajaxurl,
            method: 'post',
            dataType: 'json',
            data: data,
            success: function(data) {
                let html = '';
                if (!modal.length) {
                    return;
                }
                const changeLangInModal = function(modal) {
                    const languagesSelectEl = modal.find('[name="language"]');

                    if (!languagesSelectEl.length) {
                        return;
                    }

                    if (
                        "undefined" !== typeof data.status
                        && "success" == data.status
                        && "undefined" !== typeof data.options_html
                    ) {
                        html = data.options_html;
                    }

                    languagesSelectEl.html(html);
                };

                changeLangInModal(modal);

                const crossLocationEl = crossModal.find('[name="location"]');
                crossLocationEl.val(val).trigger('change', [true]);
                changeLangInModal(crossModal);
            }
        });
    });

    $('.keywords .add-keyword-manual, .keywords .generate-keywords').on('click', (e) => {
        const el = $(e.currentTarget);
        const modal = $(el.data('modal'));

        if (!modal.length) {
            return;
        }

        const crossModalId = modal.data('cross-modal-id');
        const crossModal = $('#' + crossModalId);

        loadLocationsAndLanguges(modal);

        if (crossModal.length) {
            loadLocationsAndLanguges(crossModal);
        }
    });


    // Created posts/ideas
    $('.created-posts .modal-button[data-action]').on('click', (e) => {
        const el = $(e.currentTarget);
        const id = el.data('id');
        const modal = $(el.data('modal'));

        if (
            id
            && modal.length
        ) {
            const titleEl = modal.find('h3');
            const bodyEl = modal.find('.table .body');
            const title = el.data('modal-title');
            const data = {
                action: el.data('action'),
                id: id
            };
            const seoaicNonceValue = wp_nonce_params[data.action];

            if (seoaicNonceValue !== '') {
                data._wpnonce = seoaicNonceValue;
            }
            $.ajax({
                url: ajaxurl,
                method: 'post',
                dataType: 'json',
                data: data,
                beforeSend: () => {
                    if (
                        titleEl.length
                        && "undefined" !== typeof title
                    ) {
                        titleEl.text(title);
                    }

                    if (bodyEl.length) {
                        bodyEl.html('<div class="waiting position-relative"></div>');
                    }
                },
                success: function(data) {
                    if (
                        bodyEl.length
                        && "undefined" !== typeof data.html
                    ) {
                        bodyEl.html(data.html);
                    }
                }
            });
        }
    });

});

$(".all-selector").on('click', 'input', function(){

    let field = $(this).closest('.seoaic-popup__field');

    if($(this).is(':checked') ) {
        field.find('[name="select-keywords"] > option').prop('selected','selected');
        field.find('[name="select-keywords"]').trigger("change");
    } else {
        field.find('[name="select-keywords"] > option').prop("selected", false);
        field.find('[name="select-keywords"]').trigger("change");
    }
});

// $(document).on('click', '.keyword > span', function (e) {
//     e.stopPropagation();
//     let it = $(this),
//         checkbox = it.closest('.row-line').find('.check input[type="checkbox"]');

//     checkbox.trigger("click");

//     return false;
// })

/*$(document).on('click', '.delete > button', function () {
    let it = $(this),
        row = it.closest('.row-line')

    $(row).slideUp(200, function () {
        $(this).remove();
    });
})*/

let currentAction = '';
const modalGenerateIdeas = $('#generate-ideas');
$(document).on('click', '#seoaic-admin-body:not(.keywords):not(.idea-page) .seoaic-generate-ideas-button', function (e) {
    let btn = $(this);

    if ( btn.hasClass('generate-keyword-based')) {
        let keywords = [];
        $('.seoaic-check-key:checked').each(function () {
            let keyword = $(this).attr('data-keyword');
            keywords.push(keyword);
        });

        $('[name="select-keywords"]').val(keywords);
    }
    modalGenerateIdeas.fadeIn(200, function () {
        const select = $('[name="select-keywords"]');
        select.trigger('change');
    });
    currentAction = $(this).attr('data-action');
});


// fix that pre-selects keywords in modal form at Keywords page
const modalGenerateIdeasK = $('#generate-ideas-new-keywords');
$(document).on('click', '#seoaic-admin-body.keywords .seoaic-generate-ideas-button', function(e) {
    const btn = $(this);

    if (btn.hasClass('generate-keyword-based')) {
        let keywords = [];

        $('.seoaic-check-key:checked').each(function() {
            keywords.push($(this).attr('data-id'));
        });

        $(modalGenerateIdeasK).find('[name="select-keywords"]').val(keywords);
    }

    const select = $(modalGenerateIdeasK).find('[name="select-keywords"]');
    select.trigger('change');
});

// $('.seoaic-popup__field').on('change', '[name="select-keywords"]', function (e){
//
//     let li = $(this).closest('.seoaic-popup__field').find('.select2-selection__rendered li'),
//         list = li.map(function(){
//             return $('> span', this).text();
//         }).get().join(',');
//
//     $('input[name="selected_keywords"]').val(list)
// });

const keywordsSorting = () => {
    const table = $(document).find('#seoaic-admin-body.keywords .seoaic-keywords-table');
    let body = table.find('> div:not(.heading)');

    table.on('click', '.heading .row-line-container > div:not(.search-intent):not(.serp):not(.check):not(.created):not(.location):not(.link)', (e) => {
        e.preventDefault();

        const column = $(e.currentTarget);
        const columnName = column.data('column');
        let order = column.attr('data-order');
        order = (order && order === 'ASC') ? 'DESC' : 'ASC';
        table.find('.heading .row-line-container > div').removeAttr('data-order');
        column.attr('data-order', order);

        const sortStrings = (a, b) => {
            if (order === 'DESC') {
                [b, a] = [a, b];
            }

            const aRowLineContainer = $(a).find('.row-line-container')[0];
            const bRowLineContainer = $(b).find('.row-line-container')[0];
            let aText = $(aRowLineContainer).find('.' + columnName).text().toLowerCase();
            let bText = $(bRowLineContainer).find('.' + columnName).text().toLowerCase();

            return aText <= bText ? -1 : 1;
        }

        const sortNums = (a, b) => {
            if (order === 'DESC') {
                [b, a] = [a, b];
            }

            const aRowLineContainer = $(a).find('.row-line-container')[0];
            const bRowLineContainer = $(b).find('.row-line-container')[0];
            let aNum = Number($(aRowLineContainer).find('.' + columnName).text().replace(/[^0-9.-]+/g,""));
            aNum = aNum ? aNum : 0;
            let bNum = Number($(bRowLineContainer).find('.' + columnName).text().replace(/[^0-9.-]+/g,""));
            bNum = bNum ? bNum : 0;

            return aNum - bNum;
        }

        const sortingMethod = columnName === 'keyword' || columnName === 'location' || columnName === 'difficulty' ? sortStrings : sortNums;

        body.sort(sortingMethod);

        table.children().not('.heading').remove();
        table.append(body);
    });
}
keywordsSorting();

const FilterIntentData = () => {
    const table = $(document).find('#seoaic-admin-body.keywords .seoaic-keywords-table');
    let body = table.find('.row-line:not(.heading) .row-line-container');

    table.on('change', '#intent-filter', (e) => {
        const value = $(e.currentTarget).val();
        if (value) {
            body.each((i, e) => {
                const el = $(e).find('.search-intent');
                if (el.text() === value) {
                    $(e).stop().show();
                    el.parentsUntil('.seoaic-keywords-table').each((i, e) => {
                        $(e).show();
                    });
                } else {
                    $(e).stop().hide();
                    $(e).parent().hide();
                }
            });
        } else {
            body.each((i, e) => {
                $(e).show();
                $(e).parent().show();
            });
        }
    });
    // table.on('change', '#intent-filter', (e) => {
    //     const value = $(e.currentTarget).val();
    //     if (value) {
    //         body.each((i, e) => {
    //             if ($(e).find('.search-intent').text() === value) {
    //                 $(e).stop().show();
    //             } else {
    //                 $(e).stop().hide();
    //             }
    //         });
    //     } else {
    //         body.stop().show();
    //     }
    // });
}
FilterIntentData();

const SEOAICFilterCategoryData = () => {
    const table = $(document).find('#seoaic-admin-body.keywords .seoaic-keywords-table');
    const body = table.find('> div:not(.heading)');

    table.on('change', '#category-filter', (e) => {
        const filterValue = $(e.currentTarget).val();
        if ('_all' === filterValue) {
            body.show();

        } else if ('_without_cluster' === filterValue) {
            body.each((i, e) => {
                const row = $(e);
                const button = row.find('.category button');
                if (button.hasClass('add-keyword-category')) {
                    row.stop().show();
                } else {
                    row.stop().hide();
                }
            });

        } else {
            body.each((i, e) => {
                const row = $(e);
                const button = row.find('.category button');
                if (button.data('category-id') == filterValue) {
                    row.stop().show();
                } else {
                    row.stop().hide();
                }
            });
        }
    });
}
SEOAICFilterCategoryData();

const getSERPData = () => {
    $(document).on('click', 'button[data-modal="#serp-keyword"]', e => {
        const btn = $(e.currentTarget);
        const table = $('#serp-keyword').find('.table');
        const keywordSlug = btn.attr('data-keyword');
        const keywordID = btn.attr('data-id');
        const action = btn.attr('data-action');
        const data = {
          'action': action,
          'id': keywordID,
          'keyword': keywordSlug,
        };

        let seoaicNonceValue = wp_nonce_params[data.action];

        if (seoaicNonceValue !== '') {
            data._wpnonce = seoaicNonceValue;
        }

        $.ajax({
            url: ajaxurl,
            method: 'post',
            data: data,
            beforeSend: function () {
                table.find('.body').html('<div class="table-row"><div>Loading Data...</div></div>');
            },
            success: function (data) {
                if (data.status === 'error') {
                    table.find('.body').html('<div class="table-row"><div>' + data.message + '</div></div>');
                    return;
                }
                let body = '';
                data.serp.forEach(e => {
                    const isCurrentDomain = e.domain === window.location.hostname ? ' highlighted' : '';
                    let row = '<div class="table-row' + isCurrentDomain + '">';
                    row += '<div class="domain">' + e.domain + '</div>';
                    row += '<div class="avg_position">' + e.avg_position + '</div>';
                    row += '<div class="visibility">' + e.visibility + '</div>';
                    row += '</div>';
                    body += row;
                })
                if (body) {
                    btn.find('.dashicons').removeClass('dashicons-plus').addClass('dashicons-yes');
                }
                table.find('.body').html(body);
            }
        });
    })
}
getSERPData();

const selectAllKeywords = () => {
    $(document).on('click', '[name="seoaic-select-all-keywords"]', (e) => {
        const currentState = e.currentTarget.checked;
        $('[name="seoaic-check-key"]').prop('checked', currentState);
    })
}
selectAllKeywords()

const deleteKeywordsBulk = () => {
    $(document).on('change', '.keywords [name="seoaic-check-key"], .keywords [name="seoaic-select-all-keywords"]', (e) => {
        const button = $(document).find('.keywords .seoaic-remove-keywords-bulk');
        let selected = new Set();
        let noChildrenAll = true;

        ($(document).find('.keywords [name="seoaic-check-key"]:checked').each((i, e) => {
            const hasChildren = $(e).attr('data-has-children');

            if (
                noChildrenAll
                && 1 == hasChildren
            ) {
                noChildrenAll = false;
            }

            selected.add($(e).attr('data-id'));
        }));

        if (
            selected.size
            && noChildrenAll
        ) {
            button.attr('data-post-id', Array.from(selected).join(','));
            button.removeAttr('disabled');
        } else {
            button.attr('data-post-id', '');
            button.attr('disabled', true);
        }
    })
}
deleteKeywordsBulk();

// add Keyword link
$(document).on('click', '.seoaic-keywords-table .seoaic-keyword-add-link, .seoaic-keywords-table .seoaic-keyword-link', (e) => {
    const modal = $('#add-keyword-link-modal');
    if (modal.length) {
        const postIDEl = modal.find('[name="post_id"]');
        const pageLinkEl = modal.find('[name="page_link"]');

        if (postIDEl.length) {
            const postID = $(e.currentTarget).data('link-post-id');
            postIDEl.val(postID);
        }

        if (pageLinkEl.length) {
            const postLink = $(e.currentTarget).data('post-link') || '';
            pageLinkEl.val(postLink).trigger('change');
        }
    }
});

const SearchKeywordsFilter = () => {
    const table = $(document).find('#seoaic-admin-body.keywords .seoaic-keywords-table');
    const body = table.find('.row-line:not(.heading)');
    const searchEl = $('#keywords_search_input');
    const searchBtn = $('#keywords_search_do_search');
    let timer;

    const runKeywordsSearch = () => {
        clearTimeout(timer);

        timer = setTimeout(() => {
            const value = searchEl.val().toLowerCase();

            if (value) {
                body.each((i, e) => {
                    const keywordEl = $(e).find('.keyword span');

                    if (-1 !== keywordEl.text().toLowerCase().indexOf(value)) {
                        $(e).stop().show();
                    } else {
                        $(e).stop().hide();
                    }
                })
            } else {
                body.stop().show();
            }
        }, 400);
    };
    searchEl.on('keyup keydown', () => {
        runKeywordsSearch();
    });
    searchBtn.on('click', () => {
        runKeywordsSearch();
    });
}
SearchKeywordsFilter();

const RankDataPoll = () => {
    const isRankInProgress = $('#is_rank_in_progress');
    if (
        isRankInProgress.length
        && isRankInProgress.val() == 1
    ) {
        const data = {
            'action': 'seoaic_keywords_poll_rank_data',
        };
        let seoaicNonceValue = wp_nonce_params[data.action];

        if (seoaicNonceValue !== '') {
            data._wpnonce = seoaicNonceValue;
        }

        const runAjaxFunc = () => {
            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: data,
                success: function (resp) {
                    if (true == resp.completed) {
                        clearInterval(timer);

                    } else {
                        if ("undefined" !== typeof resp.data) {
                            resp.data.forEach(item => {
                                const el = $('.keyword-' + item.id + '-rank');
                                if (el.length) {
                                    el.html(item.html);
                                }
                            });
                        }
                    }
                }
            });
        };
        runAjaxFunc();

        const timer = setInterval(() => {
            runAjaxFunc();
        }, 10000);
    }
};
RankDataPoll();

const ScrollbarsHandler = () => {
    $(() => {
        let timerId = null;
        $(".bottom").on('scroll', (e) => {
            if (null !== timerId) {
                clearInterval(timerId);
            }
            timerId = setTimeout(() => {
                $(".top-scrollbar-wrapper").scrollLeft($(e.currentTarget).scrollLeft());
            }, 100);
        });
        $(".top-scrollbar-wrapper").on('scroll', (e) => {
            $(".bottom").scrollLeft($(e.currentTarget).scrollLeft());
        });
    });
};
ScrollbarsHandler();

const SetCategoriesDropDownListHeight = function() {
    $(document).on('click', '.seoaic-keywords-categories-selection', (e) => {
        const el = $(e.currentTarget);
        setTimeout(() => {
            const listEl = $('.seoaic-keywords-categories-dd .select2-results > .select2-results__options');

            if (listEl.length) {
                const boundingClientRect = el[0].getBoundingClientRect();
                const height = window.innerHeight - boundingClientRect.bottom - 60;
                listEl.css({'max-height': height + 'px'});
            }
        }, 1);
    });
};
SetCategoriesDropDownListHeight();

const SetLinkDropDownListHeight = function() {
    $(document).on('click', '.seoaic-keyword-link-selection', (e) => {
        const el = $(e.currentTarget);
        setTimeout(() => {
            const listEl = $('.seoaic-keyword-link-dd .select2-results > .select2-results__options');

            if (listEl.length) {
                const boundingClientRect = el[0].getBoundingClientRect();
                const height = window.innerHeight - boundingClientRect.bottom - 60;
                listEl.css({'max-height': height + 'px'});
            }
        }, 1);
    });
};
SetLinkDropDownListHeight();