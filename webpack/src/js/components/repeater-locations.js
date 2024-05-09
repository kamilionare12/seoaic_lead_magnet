var form_callbacks = {
    window_reload: function (data) {
        window.location.reload();
    },
    getLocationGroup: function (data) {

        $('#seoaic-idea-title').html(data.name)

        let form = $('#seoaicLocationsForm')

        if ( data.data_html ) {
            form.html(data.data_html)
        } else {
            form.find('.location-section').each(function() {
                $(this).remove()
            })
        }

        setTimeout(() => {

            form.find('[data-select2-id]').each(function () {
                $(this).removeAttr('data-select2-id')
            })
            form.find('select').each(function () {
                $(this).next('.select2-container').remove()
                $(this).removeClass('select2-hidden-accessible')

                $(this).select2({
                    placeholder: $(this).attr('name'),
                    minimumResultsForSearch: 2,
                    allowClear: true,
                    language: {
                        noResults: function () {
                            return "No locations found in this area";
                        }
                    },
                })
            })
        }, 1000);

    },
    SaveLocationGroup: function (data) {

        //console.log(data)

    },
    window_href_seoaic: function (data) {
        window.location.href = window.location.href.split('?')[0] + '?page=seoaic';
    },
    window_href_setinfo: function (data) {
        window.location.href = window.location.href.split('?')[0] + '?page=seoaic&subpage=setinfo';
    },
    skeleton_saved: function (data) {
        $('#seoaic-admin-body').addClass('seoaic-loading-success');
        $('#seoaic-admin-body').removeClass('seoaic-loading');
        $('.seoaic-save-content-idea-button').attr('data-changed', 'false');
        $('#idea-post-' + data.idea_id).find('.seoaic-idea-icons').html(data.idea_icons);
    },
    window_generate_post: function (data) {

        let seoaic_admin_body = $('#seoaic-admin-body');
        let body = $('body');
        body.addClass('ajax-work');
        seoaic_admin_body.addClass('seoaic-loading');

        $('#seoaic-admin-generate-loader').remove();
        $('#seoaic-admin-title').after(data.loader);

        $('#idea-post-' + data.post_id).addClass('post-is-generating');

        seoaic_is_simple_generation = data.post_id;
        init_loading_process();
    },
    regenerate_image_modal: function (data) {
        let holder = $('#generated-post').find('.holder')
        seoaic_open_modal(holder, data.content);
        holder.html(data.content)
        //console.log(data)
    },
    update_keywords_manual: function (data) {
        let up = $('.flex-table').find('.heading')
        up.siblings('.row-line').remove()
        up.after(data.content)
        //console.log(data.content)
        //console.log(data.notify)

        $(document).on("ajaxSuccess", function () {
            $('.seoaic_update_keywords').addClass('disabled')
            let modal = $('#seoaic-alert-modal');
            seoaic_open_modal(modal, data.notify);
        });
    },
    regenerate_image_editor: function (data) {

        wp.data.select('core/editor').getEditedPostAttribute('featured_media');
        wp.data.dispatch('core/editor').editPost({featured_media: data.featured_media});

        //console.log(editedContent)

        //console.log(data.featured_media)
    },
    forgot_callback: function (data) {
        let form = $('#seoaic-forgot');
        let btn = form.find('#seoaic_submit');
        let step = btn.attr('data-step') * 1;

        if (step === 2) {
            form_callbacks.window_href_seoaic();
            return;
        }

        form.find('.step-' + step).find('.form-input').attr('disabled', 'disabled');

        step++;

        btn.attr('data-step', step);
        btn.text(btn.attr('data-step-' + step));
        form.find('.step-' + step).show(0).find('.form-input').addClass('seoaic-form-item').attr('required', 'required');

        $('#seoaic-admin-body').removeClass('seoaic-loading');
    },
    before_generate_post: function (button) {
        let changed = $('.seoaic-save-content-idea-button').attr('data-changed');
        if (changed === 'true') {
            let alert_modal = $('#seoaic-alert-modal');
            alert_modal.removeAttr('data-callback');
            seoaic_open_modal(alert_modal, button.attr('data-callback-before-content'));
            return true;
        }
    },
    before_get_idea_content: function (button) {
        let admin_body = $('#seoaic-admin-body');

        $('.seoaic-ideas-posts .post').removeClass('active');
        $('.seoaic-save-content-idea-button').attr('data-changed', 'false');
        button.parents('.post').addClass('active');
        admin_body.addClass('seoaic-slide-opened');
        admin_body.addClass('seoaic-loading');
        admin_body.removeClass('seoaic-loading-success');
        let post_id = button.attr('data-post-id');
        $('.seoaic-content-idea-box [data-post-id]').attr('data-post-id', post_id);
    },
    view_idea_content: function (data) {

        if ('' !== data.idea_postdate) {
            $('.seoaic-content-idea-box-slide .seoaic-posting-idea-date').val(data.idea_postdate);
        }

        $('.seoaic-idea-content-thumbnail-textarea').val('');
        $('.seoaic-idea-content-description-textarea').val('');
        $('#seoaic-idea-skeleton-sortable').empty();
        $('#seoaic-idea-keywords').empty();

        let default_generator_val = $('#seoaic-image-generator').attr('default-value');
        $('#seoaic-image-generator').val(default_generator_val);
        idea_content_generator_check(default_generator_val);

        let default_category_val = $('#seoaic-category').attr('default-value');
        $('#seoaic-category').val(default_category_val);

        let default_post_type_val = $('#seoaic-post-type').attr('default-value');
        $('#seoaic-post-type').val(default_post_type_val);

        $('#seoaic-idea-content-category').find('.terms-select').html(data.idea_categories);

        if ('' !== data.idea_content) {
            let idea_content = data.idea_content;
            $('.seoaic-idea-content-thumbnail-textarea').val(idea_content.idea_thumbnail);

            /*if (idea_content.idea_category !== undefined && idea_content.idea_category !== '') {
                $('#seoaic-category').val(idea_content.idea_category);
            }*/

            if (idea_content.idea_post_type !== undefined && idea_content.idea_post_type !== '') {
                $('#seoaic-post-type').val(idea_content.idea_post_type);
            }

            if (idea_content.idea_thumbnail_generator !== undefined && idea_content.idea_thumbnail_generator !== '') {
                $('#seoaic-image-generator').val(idea_content.idea_thumbnail_generator);
                idea_content_generator_check(idea_content.idea_thumbnail_generator);
            }

            $('.seoaic-idea-content-description-textarea').val(idea_content.idea_description);

            for (let k in idea_content.idea_skeleton) {
                form_callbacks.add_item({action: 'subtitle', item_name: idea_content.idea_skeleton[k]}, false);
            }

            for (let k in idea_content.idea_keywords) {
                form_callbacks.add_item({action: 'keyword', item_name: idea_content.idea_keywords[k]}, false);
            }
        }

        if ('' !== data.seoaic_credits && undefined !== data.seoaic_credits) {
            let cred_box = $('.seoaic-credits-panel');

            if (undefined !== data.seoaic_credits.posts) {
                cred_box.find('.posts .num').text(data.seoaic_credits.posts);
                $('#posts-credit').val(data.seoaic_credits.posts);
            }

            if (undefined !== data.seoaic_credits.ideas) {
                cred_box.find('.ideas .num').text(data.seoaic_credits.ideas);
            }

            if (undefined !== data.seoaic_credits.frames) {
                cred_box.find('.frames .num').text(data.seoaic_credits.frames);
            }
        }

        if ('' !== data.idea_icons && undefined !== data.idea_icons) {
            $('#idea-post-' + data.idea_id).find('.seoaic-idea-icons').html(data.idea_icons);
        }

        if ('' !== data.idea_name && undefined !== data.idea_name) {
            let idea_name = document.getElementById("seoaic-idea-title");
            idea_name.innerHTML = data.idea_name;
        }
    },
    add_item: function (data, change = true) {

        let item_name = data.item_name;
        let item_type = data.action;

        if (null !== item_name && '' !== item_name) {

            item_name = item_name.split("\n");

            for ( let key in item_name ) {

                if ( item_name[key] === '' ) {
                    continue;
                }

                let hash_id = 'item-' + item_type + '-' + makeid(10);

                let li = '<li id="' + hash_id + '">';

                if ( item_type === 'location' ) {
                    li += '<input form="seoaicLocationsForm" class="seoaic-form-item" type="hidden" name="locations-manual[]" value="' + item_name[key] + '">';
                }

                li += '<span>' + item_name[key] + '</span><button type="button" title="Edit ' + item_type + '" class="modal-button seoaic-edit-subtitle" data-modal="#edit-idea" data-action="' + item_type + '" data-mode="edit" data-title="Add ' + item_type + '" data-form-before-callback="edit_item" data-action="' + item_type + '"><span class="dashicons dashicons-edit"></span><div class="dn edit-form-items"><input type="hidden" name="item_id" value="' + hash_id + '"><input type="hidden" name="item_name" value="' + item_name[key] + '"></div></button><button type="button" title="Remove ' + item_type + '" class="seoaic-remove-subtitle modal-button confirm-modal-button" data-modal="#seoaic-confirm-modal" data-content="Do you want to remove this ' + item_type + '?" data-form-before-callback="remove_item" data-post-id="' + hash_id + '"><span class="dashicons dashicons-dismiss"></span></button></li>';

                $('.seoaic-idea-content-section-' + item_type).append(li);

            }

            if ( item_type === 'location' ) {
                $('.seoaic-loaction-form-container').addClass('seoaic-locations-manual');
                $('.seoaic-loaction-form-container [name="mode"]').val('manual');
            }

            if (change) {
                $('.seoaic-save-content-idea-button').attr('data-changed', 'true');
            }
        }

        return true;
    },
    edit_item: function (data) {

        let item_name = data.item_name;
        let hash_id = data.item_id;

        $('#' + hash_id).find('> span').text(item_name);

        $('.seoaic-save-content-idea-button').attr('data-changed', 'true');

        return true;
    },
    remove_item: function (data) {

        $('#' + data.item_id).remove();

        $('.seoaic-save-content-idea-button').attr('data-changed', 'true');

        return true;
    },

    keyword_update_link_icon: function(data) {
        $('#seoaic-admin-body').removeClass('seoaic-loading');

        const str = '[data-link-post-id="' + data.id + '"]';
        const linkEl = $('.seoaic-keywords-table').find(str);

        if (
            linkEl.length
            && "undefined" !== typeof data.content
        ) {
            linkEl.parent().html(data.content);
        }

        return true;
    }
}

function makeid(length) {
    let result = '';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const charactersLength = characters.length;
    let counter = 0;
    while (counter < length) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
        counter += 1;
    }
    return result;
}

$(document).on('click', '.select-all', function () {
    let it = $(this),
        select = it.siblings('select')

    //it.toggleClass('checked')

    $(" > option", select).prop("selected", "selected");
    $(select).trigger("change");
});


// $(document).one('change', '.select-cities', function () {
//
//     var vals = $(this).val();
//     if (vals.length > 1 && vals.includes("1")) {
//         $(this).val("1");
//         $(this).trigger('change');
//     }
//
//     $(this).on('select2:select', function(e) {
//         let data = e.params.data;
//         console.log(data.id);
//
//         if ( data.id === '1' ) {
//             //$(this).val(1).trigger('change.select2');
//             $(" > option:not([value=\"1\"])", this).prop("selected", "selected").trigger("change");
//             $(this).find('[value="1"]').prop("selected", false ).trigger("change");
//         } else {
//             $(this).find('[value="1"]').remove()
//             $(this).prepend('<option value="1">Select All</option>').change()
//         }
//     });
// })


function checkSelect(select) {
    select.each(function () {
        let it = $(this),
            options = it.find('option')

        if (options.length < 1) {
            it.closest('.item').addClass('not-active')
        } else {
            it.closest('.item').removeClass('not-active')
        }

        //console.log(options.length)
    })
}


$(document).on('change', '.select-countries, .select-states, .select-cities', function () {
    let it = $(this),
        section = it.closest('.location-section'),
        options = section.find('select').find('option'),
        country = section.find('.select-countries'),
        state = section.find('.select-states'),
        city = section.find('.select-cities'),
        id = it.find(':selected').attr('data-id'),
        select = $('select'),
        data = {
            'action': 'seoaicGetCountries',
        }
    
    let seoaicNonceValue = wp_nonce_params[data.action];

    if (seoaicNonceValue !== '') {
        data._wpnonce = seoaicNonceValue;
    }

    it.find('option').each(function () {
        $(this).removeAttr('selected')
        if ($(this).is(':selected')) {
            $(this).attr('selected', 'selected')
        }
    })

    checkSelect(select)

    if (it.is('.select-countries')) {
        it.addClass('country_id')
        data['country_id'] = country.find(':selected').attr('data-id')

        //console.log(data['country_id'])

    } else if (it.is('.select-states')) {
        it.addClass('state_id')
        data['country_id'] = country.find(':selected').attr('data-id')
        data['state_id'] = state.find(':selected').attr('data-id')

        //console.log(data['country_id'])
        //console.log(data['state_id'])

    } else if (it.is('.select-cities')) {
        // let v = city.find(':selected[value="1"]:first-child'),
        //     w = $(' > option', this)
        //
        // if ( v.length ) {
        //     city.val('').trigger('change.select2');
        //
        //     $(this).find('option').each(function() {
        //         $(this).prop("selected","selected")
        //     })
        //
        //     city.change()
        // }

        //console.log(w.length)

    }

    $.ajax({
        url: ajaxurl,
        method: 'post',
        data: data,
        beforeSend: function () {

        },
        success: function (data) {

            let ops = data.content.content


            if (it.is('.select-countries')) {

                $(city).val(null).trigger("change");
                $(city).siblings('.select-all').removeClass("checked");

                if (ops) {
                    $(state).attr('disabled', false)
                    $(state).closest('.item').addClass('loaded')
                }

                $(state).html(ops).change()

            } else if (it.is('.select-states')) {

                $(city).val(null).trigger("change");
                $(city).siblings('.select-all').removeClass("checked");

                if (!ops === false) {
                    $(city).attr('disabled', false).html(ops)
                    $(city).closest('.item').removeClass('not-active').addClass('loaded')
                } else {
                    $(city).attr('disabled', true).html(ops)
                    $(city).closest('.item').addClass('not-active').removeClass('loaded')
                    city.next('.select2').find('textarea').attr('placeholder', 'No cities available here')
                }

                //console.log(ops)

            }

        },
        error: function (xhr) {

        }
    })

})

function initSelectJS() {
    $('.location-section').each(function () {

        $(this).find('select').each(function () {

            let val = $(this).find(":selected").val()

            if (val === '') {

                $(this).select2({
                    placeholder: $(this).attr('name'),
                    minimumResultsForSearch: 2,
                    allowClear: true,
                    language: {
                        noResults: function () {
                            return "No locations found in this area";
                        }
                    },
                    //closeOnSelect:false
                });

            }

            //$(this).change()
        })

    })
}

function loadCountries() {
    let it = $('.select-countries'),
        data = {
            'action': 'seoaicGetCountries',
        }

    let seoaicNonceValue = wp_nonce_params[data.action];

    if (seoaicNonceValue !== '') {
        data._wpnonce = seoaicNonceValue;
    }

    $.ajax({
        url: ajaxurl,
        method: 'post',
        data: data,
        beforeSend: function () {

        },
        success: function (data) {

            //console.log(data.content.content)
            it.each(function () {

                let val = $(this).find(":selected").val()

                if (val === '') {

                    $(this).append(data.content.content)
                    $(this).closest('.item').addClass('loaded')

                }

            })

        },
        error: function (xhr) {
            alert(xhr.responseText);
        }
    })
}

(function ($) {
    let a = '' +
            '<div class="location-section"><div class="item">' +
            '<select type="text" class="form-select select-countries" data-placeholder="Select country" autocomplete="off" required><option value="" disabled selected>Select country</option></select>' +
            '</div>' +
            '<div class="item not-active">' +
            '<select type="text" class="form-select select-states" data-placeholder="Select state" autocomplete="off" required disabled><option value="" disabled selected>Select state/region</option></select>' +
            '</div>' +
            '<div class="item not-active">' +
            '<select type="text" class="form-select select-cities" data-placeholder="Select cities" autocomplete="off" required disabled multiple><option value="" disabled selected>Select city(s)</option></select>' +
            '<div class="select-all"><span class="check">Select all cities</span></div>' +
            '</div>' +
            '<a href="#" class="delete" title="Remove service"></a>' +
            '</div>',
        b = $('.list'),
        c = $('.add')

    $(document).on('click', '.add-loction', function (e) {
        let it = $(this),
            list = it.prev('.list'),
            cl = it.attr('data-add'),
            read = 0

        e.preventDefault()
        list.append(a)
        list.find('input').addClass(cl).attr('name', cl).attr('readonly', false)
        loadCountries()
        initSelectJS()


        $('.seoaic-loaction-form-container').addClass('seoaic-locations-api');
        $('.seoaic-loaction-form-container [name="mode"]').val('api');
    })

    $(document).on('click', '.delete', function (e) {
        e.preventDefault()
        if ($(this).closest('.list').children().length > 0)
            $(this).parent().remove()

        if ( $('.seoaic-loaction-form-container').find('.location-section').length === 0 ) {
            $('.seoaic-loaction-form-container').removeClass('seoaic-locations-api');
        }
    })

})($)


$(document).on("ajaxStop", function () {
    $("#seoaicLocationsForm").addClass('dome');
});

$(document).on('click', '[data-action="seoaicGetGroupLocation"]', function () {

    let it = $(this),
        item_id = it.attr('data-post-id'),
        data = {
            'action': 'seoaicGetGroupLocation',
            'item_id': item_id,
        }
    
    let seoaicNonceValue = wp_nonce_params[data.action];

    if (seoaicNonceValue !== '') {
        data._wpnonce = seoaicNonceValue;
    }

    form_callbacks[it.attr('data-callback-before')](it);

    //$('#location_list').empty();
    $('#seoaic-idea-locations').empty();

    let form = $('#seoaicLocationsForm');
    form.find('#location_list').empty();

    $.ajax({
        url: ajaxurl,
        method: 'post',
        data: data,
        beforeSend: function () {

        },
        success: function (data) {

            $('#seoaic-idea-title').html(data.content.name)

            $('.seoaic-loaction-form-container').removeClass('seoaic-locations-api').removeClass('seoaic-locations-manual');

            switch ( data.content.mode ) {
                case 'api':
                    $('.seoaic-loaction-form-container').addClass('seoaic-locations-api');
                    break;
                case 'manual':
                    $('.seoaic-loaction-form-container').addClass('seoaic-locations-manual');
                    break;
            }

            $('.seoaic-loaction-form-container [name="mode"]').val(data.content.mode);

            if ( data.content.mode === 'api' ) {
                if (data.content.data_html) {
                    form.html(data.content.data_html)
                } else {
                    form.find('.location-section').each(function() {
                        $(this).remove()
                    })
                }
            } else if ( data.content.mode === 'manual' ) {
                let item_data = {
                    'item_name' : data.content.locations.join("\n"),
                    'action' : 'location',
                }

                $('#seoaic-idea-locations').empty();
                form_callbacks.add_item(item_data, 'location', false);
            }


            setTimeout(() => {

                form.find('[data-select2-id]').each(function () {
                    $(this).removeAttr('data-select2-id')
                })
                form.find('select').each(function () {
                    $(this).next('.select2-container').remove()
                    $(this).removeClass('select2-hidden-accessible')

                    $(this).select2({
                        placeholder: $(this).attr('name'),
                        minimumResultsForSearch: 2,
                        allowClear: true,
                        language: {
                            noResults: function () {
                                return "No locations found in this area";
                            }
                        },
                    })
                })

                $('#seoaic-admin-body').removeClass('seoaic-loading');
                $('body').removeClass('ajax-work');
            }, 1000);

            //console.log(data.content.data_html)

        },
        error: function (xhr) {
            alert(xhr.responseText);
        }
    })
})