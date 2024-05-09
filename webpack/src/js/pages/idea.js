import AirDatepicker from 'air-datepicker'
import localeEn from 'air-datepicker/locale/en';
//locale: localeEn,

$('.info-btn').on('click', function (e) {
    e.preventDefault()
    let it = $(this)
    it.toggleClass('active')
})

export const posting_idea_date = (el) => new AirDatepicker(el, {
    autoClose: true, //dateFormat: 'dd.MM.yyyy',
    dateFormat: 'yyyy-MM-dd',
    timepicker: true,
    timeFormat: 'hh:mm',
    locale: localeEn,
    maxHours: 24,
    minDate: new Date(),
    onSelect: function(formattedDate, date, inst) {
      $('#post-mass-creation-form [name="set_post_date"]').val($(el).val())
    },
    position({$datepicker,
                 $target,
                 $pointer}) {
        let coords = $target.getBoundingClientRect(), dpHeight = $datepicker.clientHeight,
            dpWidth = $datepicker.clientWidth;
        let top = coords.y + coords.height / 2 + window.scrollY - dpHeight / 2;
        let left = coords.x + coords.width / 2 - dpWidth / 2;
        $datepicker.style.left = `${left}px`;
        $datepicker.style.top = `${top}px`;
        $pointer.style.display = 'none';
    }
})

export const input_date_picker = (el) => new AirDatepicker(el, {
    autoClose: true, //dateFormat: 'dd.MM.yyyy',
    dateFormat: 'yyyy-MM-dd', timepicker: false, /*timeFormat: 'hh:mm',*/
    locale: localeEn, /*maxHours: 24,*/
    minDate: new Date(),
    position({$datepicker,
                 $target,
                 $pointer}) {
        let coords = $target.getBoundingClientRect(), dpHeight = $datepicker.clientHeight,
            dpWidth = $datepicker.clientWidth;
        let top = coords.y + coords.height / 2 + window.scrollY - dpHeight / 2;
        let left = coords.x + coords.width / 2 - dpWidth / 2;
        $datepicker.style.left = `${left}px`;
        $datepicker.style.top = `${top}px`;
        $pointer.style.display = 'none';
    }
})

posting_idea_date('.seoaic-posting-idea-date')
input_date_picker('.seoaic-date-picker-input')

$(document).on('click', '.picker-call', function () {
    $(this).prev('input').focus()
})

$(document).on('change input click', 'select[name="select_location"],[name="ideas_count"],select[name="select-keywords"],#generate_keywords_separately', function () {
    const form = $(this).closest('form');
    const select_location = form.find('[name="select_location"] > :selected');
    const select_keyword = form.find('[name="select-keywords"] > :selected');
    const label_locations = form.find('[name="select_location"]').siblings('.label-location').find('label');
    const keyword_label = form.find('[name="select-keywords"]').siblings('.label-keyword').find('label');
    const count = parseInt(form.find('[name="ideas_count"]').val());
    const generate_keywords_separately = form.find('[name="generate_keywords_separately"]');
    const locations = [];

    select_location.each(function () {
        locations.push(...$(this).val().split(","));
    });

    if (generate_keywords_separately.prop('checked') && select_keyword.length) {
        const count_keyword = select_keyword.length * count;
        const infoKeyword = 'Important! Will be created ' + count_keyword + ' ideas, ' + count + ' on each keyword';
        if (select_keyword.length > 1) {
            addInfoLabel(keyword_label, infoKeyword);
        } else {
            keyword_label.find('span').remove();
        }
    } else {
        keyword_label.find('span').remove();
    }

    if (locations.length > 1) {
        const location_num = locations.length;
        const info = 'Important! Will be created ' + count * location_num + ' ideas, ' + count + ' on each location';
        addInfoLabel(label_locations, info);
    } else {
        label_locations.find('span').remove();
    }
});

function addInfoLabel(label, message) {
    label.find('span').remove();
    label.append('<span class="sub-label-note">' + message + '</span>');
}

const selectCatSettings = (selectPostType) => {

    let it = $(selectPostType), data = {
            action: it.attr('data-action'),
            post_type: it.val(),
            idea_post_id: it.closest('.full-width').find('.post.active .idea-content').attr('data-post-id')
        }, postTypeSection = it.closest('.seoaic-select-post-type, .seoaic-idea-content-section'),
        catSection = postTypeSection.next('.seoaic-select-post-type-cat, #seoaic-idea-content-category').find('.terms-select');

    let seoaicNonceValue = wp_nonce_params[data.action];

    if (seoaicNonceValue !== '') {
        data._wpnonce = seoaicNonceValue;
    }

    $.ajax({
        url: ajaxurl, method: 'post', data: data, beforeSend: function () {
        }, success: function (data) {
            catSection.html(data.select)
        }
    });
}


// terms count select
const ideasCountSearchTermsSelected = () => {
    $(document).on('change input', '[name="ideas_count"], [name="select-keywords"]', (e) => {
        let ths = $(e.currentTarget),
            ideasInputVal = ths.closest('#generate-idea-form').find('[name="ideas_count"]').val(),
            selectedTerms = ths.closest('#generate-idea-form').find('select[name="select-keywords"] option'),
            labelTerms = ths.closest('#generate-idea-form').find('select[name="select-keywords"]').closest('.seoaic-popup__field').find('.top label'),
            ideasNum = ths.closest('#generate-idea-form').find('select[name="select-keywords"]').closest('.seoaic-popup__field').find('.top label .ideas-num')

        //var options = document.getElementById('foo').options,
        let countTerms = 0;
        for (let i = 0; i < selectedTerms.length; i++) {
            if (selectedTerms[i].selected) countTerms++;
        }

        ideasNum.remove()

        if (countTerms > 1 && ideasInputVal > 0 && $('#seoaic-admin-body.rank-tracker')[0]) {
            labelTerms.append('<span class="ideas-num red">Important! Will be created ' + countTerms * ideasInputVal + ' ideas, ' + ideasInputVal + ' on each selected search term</span>')
        }
    })
}

const openSubTermsManuallyAddon = () => {
    $('.open-button').on('click', 'a', (e) => {
        let ths = $(e.currentTarget),
            openLabel = ths.attr('data-open'),
            closeLabel = ths.attr('data-close'),
            container = ths.closest('.add-additional-terms')

        container.toggleClass('opened')

        if (container.is('.opened')) {
            ths.html(closeLabel)
        } else {
            ths.html(openLabel)
        }
    })
}

$(document).on('change', '#seoaic_post_type, [data-action="seoaic_selectCategoriesIdea"]', function () {
    //console.log('changed')
    $(this).addClass('change')
    selectCatSettings(this)
})

const promptTemplatesApplyHandler = () => {
    const generatePostsModal = $('#seoaic-post-mass-creation-modal');
    if (!generatePostsModal.length) {
        return;
    }

    $(document).on('click', '.prompt-templates-section .template-text', (e) => {
        const el = $(e.currentTarget);
        const promptTemplateText = el.text().trim();

        const promptEl = generatePostsModal.find('[name="mass_prompt"]');
        if (promptEl.length) {
            promptEl.val(promptTemplateText);
        }
    });
};

const promptTemplateEditHandler = () => {
    $(document).on('click', '.prompt-templates-section .seoaic-edit-prompt-template', (e) => {
        const el = $(e.currentTarget);
        const templateEl = el.closest('.template');

        if (!templateEl.length) {
            return;
        }

        const templateTextEl = templateEl.find('.template-text');
        const editorWrapper = templateEl.find('.prompt-editor-wrapper');
        const editor = templateEl.find('.prompt-template-editor');
        const templateButtons = templateEl.find('.buttons');

        templateEl.toggleClass('editing');

        if (templateTextEl.length) {
            templateTextEl.toggle(0);
        }

        if (editorWrapper.length) {
            editorWrapper.toggle(0, () => {
                const height = (editor[0].scrollHeight);
                const value = editor.val();
                editor
                .css({'height': height})
                .focus()
                .val('')
                .val(value); // cursor to the end
            });
            templateButtons.toggle();
        }
    });
};

const promptTemplateEditButtonsHandler = () => {
    $(document).on('click', '.prompt-templates-section .seoaic-editor-btn', (e) => {
        const btn = $(e.currentTarget);
        const templateEl = btn.closest('.template');

        if (!templateEl.length) {
            return;
        }

        const templateTextEl = templateEl.find('.template-text');
        const editorWrapper = templateEl.find('.prompt-editor-wrapper');
        const editor = templateEl.find('.prompt-template-editor');
        const templateButtons = templateEl.find('.buttons');

        templateEl.toggleClass('editing');

        if (templateTextEl.length) {
            templateTextEl.toggle(0);
        }

        if (editorWrapper.length) {
            editorWrapper.toggle(0);
            templateButtons.toggle();
        }

        if (btn.hasClass('seoaic-editor-save')) {
            const text = editor.val();
            templateTextEl.text(text);

            const data = {
                action: btn.data('action'),
                id: btn.data('id'),
                text: text
            };
            const seoaicNonceValue = wp_nonce_params[data.action];

            if (seoaicNonceValue !== '') {
                data._wpnonce = seoaicNonceValue;
            }

            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: data,
                success: function(resp) {
                    // console.log(resp);
                }
            });
        }
    });
};

const promptTemplateDeleteHandler = () => {
    $(document).on('click', '.prompt-templates-section .seoaic-delete-prompt-template, .prompt-templates-section .seoaic-prompt-confirm-delete-no', (e) => {
        const btn = $(e.currentTarget);
        const templateEl = btn.closest('.template');

        if (!templateEl.length) {
            return;
        }

        templateEl.toggleClass('deleting');

        const templateButtons = templateEl.find('.buttons');
        const deleteConfirmSectionEl = templateEl.find('.delete-confirm-section');

        templateButtons.toggle(0);
        deleteConfirmSectionEl.toggle(0);
    });
};

const promptTemplateDeleteConfirmHandler = () => {
    $(document).on('click', '.prompt-templates-section .seoaic-prompt-confirm-delete-yes', (e) => {
        const btn = $(e.currentTarget);
        const templateEl = btn.closest('.template');

        if (!templateEl.length) {
            return;
        }

        const deleteConfirmSectionEl = templateEl.find('.delete-confirm-section');
        deleteConfirmSectionEl.find('.prompt-deletion').remove();

        const data = {
            action: btn.data('action'),
            id: btn.data('id')
        };
        const seoaicNonceValue = wp_nonce_params[data.action];

        if (seoaicNonceValue !== '') {
            data._wpnonce = seoaicNonceValue;
        }

        $.ajax({
            url: ajaxurl,
            method: 'post',
            data: data,
            success: function(resp) {
                if (
                    "undefined" !== resp.status
                    && "success" == resp.status
                ) {
                    templateEl.remove();
                }
            }
        });

    });
};

$(document).ready(function () {
    openSubTermsManuallyAddon()
    ideasCountSearchTermsSelected()
    /*if ($('#seoaic_post_type:not([data-action="seoaic_selectCategoriesIdea"])')[0])
        selectCatSettings('#seoaic_post_type:not([data-action="seoaic_selectCategoriesIdea"])')*/

    promptTemplatesApplyHandler();
    promptTemplateEditHandler();
    promptTemplateEditButtonsHandler();
    promptTemplateDeleteHandler();
    promptTemplateDeleteConfirmHandler();
})

$(document).on('click', '.alert-added-ideas .idea-btn', (e) => {
    const clickedEl = $(e.target);
    const row = clickedEl.closest('.alert-added-ideas');
    const saveBtn = row.find('.save');
    const titleOrig = row.find('.idea-orig-title');
    const titleInput = row.find('.idea-updated-title');
    let sendAjax = false;
    let successFunc = function() {};

    const data = {
        action: clickedEl.data('action'),
        item_id: clickedEl.data('post-id')
    };
    const seoaicNonceValue = wp_nonce_params[data.action];

    if (seoaicNonceValue !== '') {
        data._wpnonce = seoaicNonceValue;
    }

    if (clickedEl.hasClass('edit')) {
        saveBtn.addClass('active');
        titleInput.addClass('active');

    } else if (clickedEl.hasClass('save')) {
        const newVal = titleInput.val();
        const titleChanged = newVal != titleOrig.text();

        if (titleChanged) {
            sendAjax = true;
            data.item_name = newVal;
            successFunc = function(resp) {
                if (resp.success != "error") {
                    saveBtn.removeClass('active');
                    titleOrig.text(newVal);
                    titleInput.removeClass('active');
                }
            };

        } else {
            saveBtn.removeClass('active');
            titleInput.removeClass('active');
        }

    } else if (clickedEl.hasClass('delete')) {
        sendAjax = true;
        successFunc = function(data) {
            if (data.success != "error") {
                row.remove();
            }
        };
    }

    if (sendAjax) {
        $.ajax({
            url: ajaxurl,
            method: 'post',
            data: data,
            beforeSend: function() {
                clickedEl.addClass('in-progress');
            },
            success: function(resp) {
                successFunc(resp);
            },
            complete: function() {
                clickedEl.removeClass('in-progress');
            }
        });
    }
});