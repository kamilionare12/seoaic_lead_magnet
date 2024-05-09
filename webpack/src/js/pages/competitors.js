import {ajaxValidation, delay} from "./rank-tracker";
import {posting_idea_date} from "./idea"

$(document).ready(function () {
    dragScroll()
    competitorsActiveClick()
    competitorsRemoveClick()
    competitorsClickAjax()
    termsClickAjax()
    rankingPositions()
    termsSorting()
    filterMyRankedTerms()
    selectCompetitors()
    bulkRemoveClickAjax()
    stickyHeadingColor()
    postedPostsModal()
    runUpdateMyRanking()
    addToRankTracking()
    updateCompetitor()
    filterableColumns()
    filterTerms()
    setMassGenerateModalArgs()
    PaginateTerms()
    validatePositions()
    competitors_compare_terms_filter()
    competitor_compare_content()
    per_page_terms_select_url()
    activateActionButton('.top button.competitor-compare', 'data-competitor', 'data-compare', '.competitor-check-key', '.compare', 2, false)
    add_terms_to_keywords()
    select_all_types()
    migrate_competitors_from_options_to_terms()
})

const actions = {
    terms_html: 'seoaic_Competitors_Search_Terms_HTML',
    add_terms: 'seoaicAddCompetitorsTerms',
    progress_values: 'seoaic_Progress_Values',
    check_terms: 'seoaic_Check_Terms_Update_Progress',
    update_competitor: 'seoaic_update_competitor_data',
    validate_positions: 'seoaic_validate_positions_real_terms_count',
    compare_competitors: 'seoaic_compare_competitors',
    other_positions: 'seoaic_other_top_5_positions',
    migrate_competitors: 'seoaic_migrate_competitors_from_options',
}

const main = {
    admin_wrap: $('.competitors-page'),
    top: $('.competitors-page > .inner > .top'),
    generate_content_btn: $('.competitors-page > .inner > .top .generate-competitor-content-btn'),
    compare: $('.competitors-page > .inner > .bottom > .compare'),
    terms: $('.competitors-page > .inner > .bottom > .search-terms-table'),
    bottom: $('.competitors-page > .inner > .bottom-section'),
    pagination_section: $('.competitors-page > .inner > .bottom-section > .pagination'),
    per_page_section: $('.competitors-page > .inner > .bottom-section > .per-page'),
}

const select_all_types = () => {
    activateActionButton('.serp-selected.modal-button', 'data-term', 'data-terms', '.select-compare', '.table-competitors-compare', 1, false)
    select_all('.select-all-compare', '.select-compare')
    select_all('[name="seoaic-select-all-keywords"]', '[name="seoaic-check-key"]')
    on_check_buttons('[name="seoaic-select-all-keywords"],[name="seoaic-check-key"]', '.seoaic-remove-main, .generate-competitor-content-btn')
    on_check_buttons('.select-compare,.select-all-compare', '.serp-selected')
}

export const select_all = (select_all, children) => {
    $(document).on('change', select_all, function () {
        let isChecked = $(this).prop('checked')
        $(children).prop('checked', isChecked)
        $(this).closest('.table').find(children + ':first').change()
    })
}

export const on_check_buttons = (checkboxes, buttons_to_active = '') => {
    $(document).on('change', checkboxes, function () {
        let isChecked = $(checkboxes).prop('checked')
        let buttons = buttons_to_active.split(',').map(function (item) {
            return item.trim()
        })
        let checked_class = $('.' + this.classList[0] + ':checked')
        let checked_length = checked_class.length ? checked_class.length : isChecked

        if (checked_length) {
            buttons.forEach(function (button) {
                $(button).prop('disabled', false)
            })
        } else {
            buttons.forEach(function (button) {
                $(button).prop('disabled', true)
            })
        }
    })
}

const per_page_terms_select_url = () => {
    main.per_page_section.on('change', 'select', function () {
        let index = main.generate_content_btn.attr('data-index'),
            per_page_num = $(':selected', this).val()
        set_url_params(index, per_page_num)
    })
}

export const activateActionButton = (btn, data_el, data_btn, checkbox, table, length, remove = false) => {
    const checkers = (check) => {
        let checked = document.querySelectorAll(check),
            competitors = [];
        checked.forEach(function (checkbox) {
            let dataIndex = checkbox.getAttribute(data_el);
            competitors.push(dataIndex);
        });
        if (remove) {
            btn = btn + ',.seoaic-remove-main'
        }
        $(btn).attr(data_btn, competitors)
        if (competitors.length >= length) {
            $(btn).prop('disabled', false)
        } else {
            $(btn).prop('disabled', true)
        }
    }
    $(document).on('change', checkbox, function () {
        checkers(checkbox + ':checked')
    })
    if ($(checkbox)[0]) {
        $(document).ajaxComplete(function () {
            checkers(checkbox + ':checked')
        })
    }
}

const migrate_competitors_from_options_to_terms = () => {
    let modal = $('#search-terms-update-modal')
    let old_competitors = parseInt($('[data-migrate-competitors-options]').attr('data-migrate-competitors-options'))
    let admin_body = $('#seoaic-admin-body'),
        content = admin_body.attr('data-migrate-modal-content'),
        submit = modal.find('.seoaic-popup__btn-right'),
        loading_progress = modal.find('.lds-indication .loader'),
        from_num = modal.find('.status-update .from'),
        to_num = modal.find('.status-update .to'),
        body = $('body'),
        status_ul = modal.find('.status-update + ul')

    if (old_competitors === 0 || old_competitors === undefined) {
        return;
    }

    let count_loop = Math.ceil(old_competitors)

    if (old_competitors) {
        modal.find('.seoaic-popup__content .modal-content').html(content);
        from_num.html(0)
        to_num.html(old_competitors)
        submit.attr('action', actions.migrate_competitors)
        modal.fadeIn(200);
        admin_body.addClass('seoaic-blur');
        body.addClass('modal-show');
    }

    submit.on('click', () => {
        for (let i = 0; i < old_competitors; i++) {
            let action = actions.migrate_competitors,
                data = {
                    action: action,
                };
            ajaxValidation(data)
            $.ajaxQueue({
                url: ajaxurl,
                method: 'post',
                data: data,
                beforeSend: function (data) {
                    setTimeout(() => {
                        admin_body.addClass('seoaic-blur');
                        body.addClass('modal-show');
                    }, 200);
                },
                success: function (data) {
                    let to_num_val = i + 1,
                        percent = to_num_val * 100 / old_competitors
                    loading_progress.css({
                        'max-width': percent + '%'
                    })
                    from_num.text(to_num_val)
                    status_ul.append(data)
                    if (to_num_val === old_competitors) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    }
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        }
    })
    //submit.click()
}

const activate_terms_actions_button = () => {
    activateActionButton(
        '.top button.generate-competitor-content-btn',
        'data-keyword',
        'data-post-id',
        '.seoaic-check-key',
        '.search-terms-table',
        1,
        true)
}

const go_to_top_modal = () => {
    let elementToShow = $('.go-to-top');
    $('#competitors-compare').scroll(function() {
        let scrollPosition = $(this).scrollTop();
        let threshold = 200;
        if (scrollPosition > threshold) {
            elementToShow.show();
        } else {
            elementToShow.hide();
        }
    });

    elementToShow.click(function() {
        $('#competitors-compare').animate({scrollTop: 0}, 'slow');
    });
};

const competitor_compare_content = () => {
    main.top.on('click', '.competitor-compare', function () {
        let it = $(this),
            competitors = it.attr('data-compare'),
            modal = $(it.attr('data-modal')),
            modal_body = modal.find('.seoaic-popup__content .table .body'),
            data = {
                action: actions.compare_competitors,
                competitors: competitors
            }

        modal.next('.go-to-top')
        modal.after('<div class="go-to-top"></div>')
        modal_body.html('<div class="loader-ellipsis medium"></div>')
        modal.find('.seoaic-popup__content .table #chart_term_positions').hide()
        modal.find('.seoaic-popup__content .table .body').show()

        ajaxValidation(data)
        $.ajax({
            url: ajaxurl,
            method: 'post',
            data: data,
            success: function (data) {
                modal.find('.table-competitors-compare').remove()
                modal_body.html(data.html)
            },
            complete: function () {
                dragScroll()
                competitors_compare_terms_sorting()
                filterableColumns()
                loadLocationsAndLanguges($('#add-keyword-modal'))
                go_to_top_modal()
            }
        })
    })
}

const add_terms_to_keywords = () => {
    $(document).on('click', '.serp .serp-selected', function () {
        let terms = $(this).attr('data-terms')
        let modal_input = $('#add-keyword-modal').find('[value="seoaic_add_keyword"]').next('.seoaic-popup__field').find('[type="text"]')
        modal_input.val(terms)
    })
}

const loadLocationsAndLanguges = (modal) => {
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

    ajaxValidation(data)

    $.ajax({
        url: ajaxurl,
        method: 'post',
        dataType: 'json',
        data: data,
        success: function (data) {
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

const validatePositions = () => {
    let data = {
        action: actions.validate_positions
    }
    $('#seoaic-settings').on('click', '#seoaic_submit', function () {
        ajaxValidation(data)
        $.ajax({
            url: ajaxurl,
            method: 'post',
            data: data,
            success: function (data) {

            }
        });
    })
}

const competitors_compare_terms_sorting = () => {
    const table = $(document).find('.table-competitors-compare');
    let body = table.find('> div:not(.heading)');

    table.on('click', '.heading > .search-volume[data-column] .inner, .heading > .website[data-column] .inner, .heading > .keyword[data-column] .inner', (e) => {
        e.preventDefault();
        const column = $(e.currentTarget).closest('.col')
        const columnName = column.data('column');
        let order = column.attr('data-order');
        order = (order && order === 'ASC') ? 'DESC' : 'ASC';
        table.find('.heading > div').removeAttr('data-order');
        column.attr('data-order', order);

        const sortStrings = (a, b) => {
            if (order === 'DESC') {
                [b, a] = [a, b]
            }

            let aText = $(a).find('.' + columnName).text().toLowerCase();
            let bText = $(b).find('.' + columnName).text().toLowerCase();
            return aText <= bText ? -1 : 1;
        }

        const sortNums = (a, b) => {
            if (order === 'DESC') {
                [b, a] = [a, b]
            }

            let aNum = Number($(a).find('.' + columnName).text().replace(/[^0-9.-]+/g, ""));
            let bNum = Number($(b).find('.' + columnName).text().replace(/[^0-9.-]+/g, ""));

            if (isNaN(aNum) || aNum === 0) {
                return 1;
            }

            if (isNaN(bNum) || bNum === 0) {
                return -1;
            }

            return aNum - bNum;
        }

        const sortingMethod = columnName === 'keyword' || columnName === 'location' ? sortStrings : sortNums
        body.sort(sortingMethod);
        table.children().not('.heading').remove();
        table.append(body);
    })
}

const competitors_compare_terms_filter = () => {
    $(document).on('change input keyup', '.heading > .search-volume [name="search-volume-compare-min"], .heading > .search-volume [name="search-volume-compare-max"]',
        delay(function (e) {
            e.preventDefault();
            let tableRows = $('.table-competitors-compare .table-row:not(.heading)'),
                length_rows = tableRows.length

            tableRows.each(function (i) {
                let cell = parseInt($(this).find('.search-volume .inner').text()),
                    min = parseInt($('.heading > .search-volume [name="search-volume-compare-min"]').val()),
                    max = parseInt($('.heading > .search-volume [name="search-volume-compare-max"]').val()),
                    table = $(document).find('.table-competitors-compare')

                if (cell >= min && cell <= max) {
                    $(this).show()
                } else {
                    $(this).hide()
                }

                if (i === (length_rows - 1)) {
                    table.removeClass('blur')
                }
            })
        }, 1000))
}

const addToRankTracking = () => {
    $(document).on('click', '[data-action="seoaicAddToRankTracking"]', function (e) {
        let ths = $(e.currentTarget),
            selected_keywords = ths.attr('data-terms'),
            seoaic_location = ths.attr('data-location'),
            data = {
                action: actions.add_terms,
                selected_keywords: selected_keywords,
                seoaic_location: seoaic_location,
            }
        e.preventDefault()
        ajaxValidation(data)
        $.ajax({
            url: ajaxurl,
            method: 'post',
            data: data,
            beforeSend: function () {
                $('#seoaic-admin-body').addClass('seoaic-loading')
            },
            success: function (data) {
                alertModal(data.status, data.message, '', false)
                $('.seoaic-check-key').prop('checked', false)
                $('[data-action="seoaicAddToRankTracking"]').removeClass('active')
            },
            complete: function (data) {
                $('#seoaic-admin-body').removeClass('seoaic-loading')
                window.location.reload();
            }
        });
    })
}

const showAddToRankButton = () => {
    $('.search-terms-table').on('change', '.seoaic-check-key, [name="seoaic-select-all-keywords"]', function () {

        let button = $('[data-action="seoaicAddToRankTracking"]'),
            location = $(document).find('.competitors-watch span.active').closest('a').attr('data-location')

        if ($(this).is('.seoaic-check-key, [name="seoaic-select-all-keywords"]')) {

            if ($('.search-terms-table > .row-line:not(.heading) .seoaic-check-key:checked')[0]) {
                button.addClass('active')
                let selected = new Set();
                ($(document).find('[name="seoaic-check-key"]:checked').each((i, e) => {
                    selected.add($(e).attr('data-term'));
                }));

                button.attr('data-terms', Array.from(selected).join(','))
                button.attr('data-location', location)

            } else {
                button.removeClass('active')
                button.attr('data-terms', '')
                button.attr('data-location', '')
            }

        }
    })
}

const alertModal = (responseStatus, responseMessage, alert_modal = '', reload = true) => {
    let modal = !alert_modal ? $('#seoaic-alert-modal') : $(alert_modal);
    if (responseStatus === 'error' || responseStatus === 'alert') {
        if (false !== responseMessage) {
            modal.find('.seoaic-popup__content .modal-content').html(responseMessage);
        }
        $('#seoaic-admin-body').addClass('seoaic-blur');
        $('body').addClass('modal-show');
        modal.fadeIn(200);
    }

    if (reload === true) {
        $(document).on('click', '#seoaic-alert-modal .seoaic-modal-close', function () {
            if (main.admin_wrap[0]) {
                window.location.reload();
            }
        })
    }
}

const Processing = () => {
    //return !!$('.progress .terms')[0];
}

const competitorsRemoveClick = () => {
    $('.competitors-watch').on('click', '.remove', (e) => {
        if (Processing()) {
            alert('Please, wait or stop processing before delete Competitor data')
            return false;
        }
        let ths = $(e.currentTarget),
            ID = ths.attr('data-post-id'),
            confirm_modal_id = $('#seoaic-confirm-modal').find('#confirm_item_id')
        confirm_modal_id.val(ID)
    })
}

const competitorsActiveClick = () => {
    $('.competitors-watch').on('click', 'a .competitor', (e) => {
        let ths = $(e.currentTarget),
            a = ths.closest('.competitors-watch').find('a .competitor'),
            index = ths.closest('a').attr('data-index'),
            removeBulk = main.admin_wrap.find('.seoaic-remove-main')
        e.preventDefault()
        removeBulk.attr('data-index', index)
        main.generate_content_btn.attr('data-index', index)
        a.removeClass('active')
        ths.addClass('active')
    })
}

const set_url_params = (index, per_page_num = 0) => {
    const params = new Proxy(new URLSearchParams(window.location.search), {
        get: (searchParams, prop) => searchParams.get(prop),
    })
    let per_page = per_page_num ? '&per_page=' + per_page_num : params['per_page'] ? '&per_page=' + params['per_page'] : '',
        refresh = window.location.protocol + "//" + window.location.host + window.location.pathname + '?page=seoaic-competitors&competitor=' + index + per_page
    window.history.pushState({path: refresh}, '', refresh)
}

const get_url_param = (parameter) => {
    const params = new Proxy(new URLSearchParams(window.location.search), {
        get: (searchParams, prop) => searchParams.get(prop),
    })
    return params[parameter] ? params[parameter] : 0
}

const competitorsClickAjax = () => {
    const activateProcessingTerms = () => {

    }
    main.top.on('click', '.add-new-competitor-button, .seoaic-remove-main', (e) => {
        if (Processing()) {
            alert('Please, wait or stop processing to add new Competitor')
            return false;
        }
    })
    $('.competitors-watch').on('click', 'a .competitor:not(.active)', (e) => {

        $('[action="seoaicAddToRankTracking"]').removeClass('active')

        $('.generate-competitor-content-btn, .seoaic-remove-main').attr('disabled', true)

        let ths = $(e.currentTarget),
            index = ths.closest('a').attr('data-index'),
            action = ths.closest('a').attr('data-action'),
            data = {
                action: action,
                index: index,
            }

        set_url_params(index)
        e.preventDefault()
        ajaxValidation(data)
        $.ajax({
            url: ajaxurl,
            method: 'post',
            data: data,
            beforeSend: function () {
                $('#seoaic-admin-body').addClass('seoaic-loading')
            },
            success: function (data) {
                if (data.status === '') {
                } else {
                    main.compare.html(data)
                }
            },
            complete: function (data) {
                $('#seoaic-admin-body').removeClass('seoaic-loading')
                stickyHeadingColor()
                postedPostsModal()
                activateProcessingTerms()
                showAddToRankButton()

                $('.check:not(.competitor-check-key) .seoaic-check-key').each(function () {
                    $(this).prop('checked', false)
                }).change()
            }
        });
    })
}

const bulkRemoveClickAjax = () => {
    main.admin_wrap.on('click', '.seoaic-remove-main', (e) => {
        let ths = $(e.currentTarget),
            index = ths.attr('data-index'),
            modalForm = $('#seoaic-confirm-modal').find('form'),
            competitor_index_input = modalForm.find('[name="competitor_index"]'),
            item_id_input = modalForm.find('[name="item_id"]')

        item_id_input.val(index)
        if (!competitor_index_input.length) {
            modalForm.append('<input id="confirm_competitor_index" class="seoaic-form-item" type="hidden" name="competitor_index" value="' + index + '">')
        }
    })
}

const clear_input = () => {
    $(document).on('click', '.clear', function () {
        let t = $(this)
        t.prev('input').val('')
        t.prev('input').keyup()
    })
}

const filterableColumns = () => {
    let filterable = $('.seoai-filterable')
    filterable.each(function (i, e) {
        let ths = $(e),
            filter_name = ths.attr('data-sort'),
            id = 'seoai-slider-filtering-' + i,
            id_handle = 'seoai-slider-handle-' + i,
            id_handle_end = 'seoai-slider-handle_end-' + i,
            id_hidden_min = 'seoai-' + filter_name + '-min-' + i,
            id_hidden_max = 'seoai-' + filter_name + '-max-' + i,
            min_val = ths.attr('data-min'),
            max_val = ths.attr('data-max'),
            slider_wrap =
                '<label class="text-label">Choose Range</label>' +
                '<div id="' + id + '" class="seoai-ui-slider-range" nochilddrag>' +
                '<div id="' + id_handle + '" class="ui-slider-handle"></div>' +
                '<div id="' + id_handle_end + '" class="ui-slider-handle"></div>' +
                '<input name="' + filter_name + '-min" type="hidden" id="' + id_hidden_min + '">' +
                '<input name="' + filter_name + '-max" type="hidden" id="' + id_hidden_max + '">' +
                '</div>',
            input_wrap =
                '<div class="search">' +
                '<label class="text-label">Filter by keyword</label>' +
                '<input autocomplete="none" name="' + filter_name + '" data-default="" class="form-input" type="text" data-placeholder="Type keyword" placeholder="Type keyword">' +
                '<div class="clear"></div>' +
                '</div>',
            filter = ths.is('.seoai-filter-num') ? slider_wrap : input_wrap
        ths.prepend('<div class="seoai-slider-opener"></div><div class="seoai-slider-filter">' + filter + '</div>')
        clear_input()
        min_val = Math.floor(min_val)
        max_val = Math.floor(max_val)
        min_val = min_val ? min_val : 0
        max_val = max_val ? max_val : 0

        if (ths.is('.seoai-filter-num')) {
            $("#" + id).slider({
                range: true,
                min: min_val ? min_val : 0,
                max: max_val ? max_val : 0,
                values: [min_val, max_val],
                animate: "slow",
                create: function () {
                    $("#" + id_handle).html('<span>' + ($(this).slider("values", 0) + '</span>'))
                    $("#" + id_handle_end).html('<span>' + ($(this).slider("values", 1) + '</span>'))
                    $("#" + id_hidden_min).val($(this).slider("values", 0))
                    $("#" + id_hidden_max).val($(this).slider("values", 1))
                },
                slide: function (event, ui) {
                    let min = $("#" + id_hidden_min),
                        max = $("#" + id_hidden_max)
                    $("#" + id_handle).html('<span>' + ui.values[0] + '</span>')
                    $("#" + id_handle_end).html('<span>' + ui.values[1] + '</span>')
                    min.val(ui.values[0])
                    max.val(ui.values[1])
                    min.keyup()
                    max.keyup()
                }
            }).on("slidechange", function (event, ui) {
                $(this).closest('body').find('.table-competitors-compare').addClass('blur')
            });
        }
    })

    let slider_opener = $('.seoai-slider-opener')
    $(document).on('mousedown ontouchstart', function (e) {
        if (!$(e.target).is(".seoai-slider-opener, .seoai-slider-filter, .seoai-slider-opener *, .seoai-slider-filter *")) {
            $(document).on('click', function (e) {
                if (!$(e.target).is(".seoai-slider-opener, .seoai-slider-filter, .seoai-slider-opener *, .seoai-slider-filter *")) {
                    slider_opener.removeClass('active')
                }
            })
        }
    })

    filterable.on('click', '.seoai-slider-opener', function (e) {
        let clicked = $(this)
        slider_opener.removeClass('active')
        if (!$(e).is('active')) {
            clicked.addClass('active')
        } else {
            clicked.removeClass('active')
        }
    })
}

const filterTerms = (e) => {
    let filterable = $('.seoai-filterable input')
    filterable.keyup(delay(function (e) {
        let ths = $(e.currentTarget),
            index = ths.closest('.seoai-filterable').attr('data-competitor-index'),
            action = actions.terms_html,
            table_heading = main.admin_wrap.find('.flex-table:not(.compare) .row-line.heading'),
            table_row_line = main.admin_wrap.find('.flex-table:not(.compare) > *:not(.heading)'),
            blur = 'seoaic-blur seoaic-loading',
            admin_body = ths.closest('#seoaic-admin-body'),
            order = table_heading.find('[data-order]').attr('data-order'),
            order_revert = order === 'ASC' ? 'DESC' : 'ASC',
            order_name = table_heading.find('[data-order]').attr('data-sort'),
            value = table_heading.find('.rank select').val(),
            data = {
                index: index,
                action: action,
                page: 1,
                order: order_revert,
                order_name: order_name,
                my_rank: value
            }

        let per_page = get_url_param('per_page')
        if (per_page) {
            data['per_page'] = per_page
        }

        filterable.each(function () {
            let _ths = $(this),
                _name = _ths.attr('name') ? _ths.attr('name') : 'name'
            data[_name] = _ths.val()
        })
        ajaxValidation(data)
        $.ajax({
            url: ajaxurl,
            method: 'post',
            data: data,
            beforeSend: function () {
                admin_body.addClass(blur)
            },
            success: function (data) {
                table_row_line.remove()
                $('.search-terms-table .row-line:not(.heading)').remove()
                table_heading.after(data.html)
                main.pagination_section.html('').html(data.pagination)
                main.per_page_section.html('').html(data.per_page)
            },
            complete: function () {
                admin_body.removeClass(blur)
                //termsSorting()
                runTermsUpdate()
                PaginateTerms()
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    }, 1000))

}

const termsSorting = () => {
    let terms_table = main.admin_wrap.find('.search-terms-table'),
        table_heading = $('.search-terms-table').find('.row-line.heading'),
        table_row_line = main.admin_wrap.find('.flex-table:not(.compare) > *:not(.heading)'),
        bottom = main.admin_wrap.find('.inner > .bottom')

    main.admin_wrap.on('click', '.heading > div:not(.search-intent):not(.serp):not(.check)', function (e) {
        if ($(e.target).is(".seoai-slider-opener, .seoai-slider-filter, .seoai-slider-opener *, .seoai-slider-filter *, select")) {
            return
        }
        e.preventDefault();
        let column = $(this),
            index = column.attr('data-competitor-index'),
            filterable = $('.seoai-filterable input'),
            order_name = column.attr('data-sort'),
            order = column.attr('data-order'),
            value = column.closest('.heading').find('.rank select').val(),
            data = {
                action: actions.terms_html,
                index: index,
                order: order ? order : 'DESC',
                page: 1,
                order_name: order_name,
                my_rank: value
            }

        let per_page = get_url_param('per_page')
        if (per_page) {
            data['per_page'] = per_page
        }

        filterable.each(function () {
            let _ths = $(this),
                _name = _ths.attr('name') ? _ths.attr('name') : 'name'
            data[_name] = _ths.val()
        })

        order = (order && order === 'ASC') ? 'DESC' : 'ASC';
        terms_table.find('.heading > div').removeAttr('data-order');
        column.attr('data-order', order);

        ajaxValidation(data)
        $.ajax({
            url: ajaxurl,
            method: 'post',
            data: data,
            async: true,
            beforeSend: function () {
                main.admin_wrap.addClass('seoaic-loading')
            },
            success: function (data) {
                table_row_line.remove()
                $('.search-terms-table .row-line:not(.heading)').remove()
                $('.search-terms-table .row-line.heading').after(data.html)
                main.pagination_section.html('').html(data.pagination)
                main.per_page_section.html('').html(data.per_page)
            },
            complete: function (data) {
                main.admin_wrap.removeClass('seoaic-loading')
                PaginateTerms()
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
            }
        });

    })
}

const filterMyRankedTerms = () => {
    let terms_table = main.admin_wrap.find('.search-terms-table'),
        table_heading = $('.search-terms-table').find('.row-line.heading'),
        table_row_line = main.admin_wrap.find('.flex-table:not(.compare) > *:not(.heading)'),
        bottom = main.admin_wrap.find('.inner > .bottom')

    main.admin_wrap.on('change', '.rank select', function (e) {

        let ths = $(this),
            value = ths.val(),
            rank = ths.closest('.rank'),
            index = rank.attr('data-competitor-index'),
            filterable = $('.seoai-filterable input'),
            order_name = rank.attr('data-sort'),
            order = rank.attr('data-order'),
            data = {
                action: actions.terms_html,
                index: index,
                order: order ? order : 'DESC',
                page: 1,
                order_name: order_name,
                my_rank: value
            }

        let per_page = get_url_param('per_page')
        if (per_page) {
            data['per_page'] = per_page
        }

        filterable.each(function () {
            let _ths = $(this),
                _name = _ths.attr('name') ? _ths.attr('name') : 'name'
            data[_name] = _ths.val()
        })

        order = (order && order === 'ASC') ? 'DESC' : 'ASC';
        terms_table.find('.heading > div').removeAttr('data-order');
        ths.attr('data-order', order);

        ajaxValidation(data)
        $.ajax({
            url: ajaxurl,
            method: 'post',
            data: data,
            async: true,
            beforeSend: function () {
                main.admin_wrap.addClass('seoaic-loading')
            },
            success: function (data) {
                table_row_line.remove()
                $('.search-terms-table .row-line:not(.heading)').remove()
                $('.search-terms-table .row-line.heading').after(data.html)
                main.pagination_section.html('').html(data.pagination)
                main.per_page_section.html('').html(data.per_page)
            },
            complete: function (data) {
                main.admin_wrap.removeClass('seoaic-loading')
                PaginateTerms()
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    })
}

const Scroll_to_element = (el) => {
    $([document.documentElement, document.body]).animate({
        scrollTop: $(el).offset().top
    }, 800);
}

const run_terms = (e, filterable) => {
    let ths = $(e.currentTarget),
        page = ths.attr('data-page'),
        per_page = main.per_page_section.find('select :selected').val(),
        index = main.generate_content_btn.attr('data-index'),
        action = actions.terms_html,
        table_heading = main.admin_wrap.find('.flex-table:not(.compare) .row-line.heading'),
        table_row_line = main.admin_wrap.find('.flex-table:not(.compare) > *:not(.heading)'),
        blur = 'seoaic-blur seoaic-loading',
        order = table_heading.find('[data-order]').attr('data-order'),
        order_revert = order === 'ASC' ? 'DESC' : 'ASC',
        order_name = table_heading.find('[data-order]').attr('data-sort'),
        value = table_heading.find('.rank select').val(),
        data = {
            index: index,
            action: action,
            page: page,
            order: order_revert,
            order_name: order_name,
            my_rank: value
        }

    if (per_page) {
        data['per_page'] = per_page
    }

    if (filterable) {
        filterable.each(function () {
            let _ths = $(this),
                _name = _ths.attr('name') ? _ths.attr('name') : 'name'
            data[_name] = _ths.val()
        })
    }
    ajaxValidation(data)
    $.ajax({
        url: ajaxurl,
        method: 'post',
        data: data,
        async: true,
        beforeSend: function () {
            main.admin_wrap.addClass(blur)
        },
        success: function (data) {
            table_row_line.remove()
            $('.search-terms-table .row-line:not(.heading)').remove()
            table_heading.after(data.html)
            main.pagination_section.html('').html(data.pagination)
            main.per_page_section.html('').html(data.per_page)
        },
        complete: function () {
            main.admin_wrap.removeClass(blur)
            //termsSorting()
            runTermsUpdate()
            filterTerms()
            PaginateTerms()
            Scroll_to_element('.top')
        },
        error: function (xhr, status, error) {
            console.error(xhr.responseText)
        }
    })
}

const PaginateTerms = (e) => {
    let pagination = main.pagination_section.find('.seoaic-pagination'),
        filterable = $('.seoai-filterable input')
    pagination.on('click', 'a', function (e) {
        e.preventDefault()
        run_terms(e, filterable)

        return false;
    })
    main.per_page_section.on('change', 'select', function (e) {
        e.preventDefault()
        run_terms(e, filterable)

    })
}

const termsClickAjax = () => {
    let competitors = $('.competitors-watch')

    competitors.on('click', 'a .competitor', (e) => {
        e.preventDefault()
        let ths = $(e.currentTarget),
            index = ths.closest('a').attr('data-index'),
            action = actions.terms_html,
            table = main.admin_wrap.find('.flex-table:not(.compare)'),
            data = {
                action: action,
                index: index,
            },
            body = $('#seoaic-admin-body')

        let per_page = get_url_param('per_page')
        if (per_page) {
            data['per_page'] = per_page
        }

        e.preventDefault()
        ajaxValidation(data)

        function runAjax() {
            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: data,
                beforeSend: function () {
                    //body.addClass('seoaic-loading')
                },
                success: function (data) {
                    table.html(data.html)
                    main.pagination_section.html('').html(data.pagination)
                    main.per_page_section.html('').html(data.per_page)
                },
                complete: function (data) {
                    body.removeClass('seoaic-loading')
                    stickyHeadingColor()
                    runTermsUpdate()
                    filterableColumns()
                    filterTerms()
                    PaginateTerms()
                    check_all_checked_props()
                    activate_terms_actions_button()
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        }

        runAjax()
    })
}

function check_all_checked_props() {
    $('[checked="checked"]').prop('checked', true)
}

const runUpdateMyRanking = () => {
    let table = $('.search-terms-table')
    table.on('change', '.check input', function () {
        $(this).closest('.search-terms-table').find('.check input').each(function () {
            let rank = $(this).closest('.row-line').find('.rank:not(.updated)')
            if ($(this).is(':checked')) {
                rank.addClass('term-ready-to-update')
                rank.find('[class="empty"]').addClass('view')
            } else {
                rank.removeClass('term-ready-to-update')
                rank.find('[class="empty view"]').removeClass('view')
            }
        })
    })
    table.on('click', '.empty', (e) => {
        let term_ready_to_update = $('.term-ready-to-update')
        term_ready_to_update.find('.empty').addClass('loading')
        term_ready_to_update.addClass('running').addClass('added')
        term_ready_to_update.find('.empty span').html('')
        add_checked_items_progress('terms')
    })
}

const add_checked_items_progress = (item) => {
    let action = actions.progress_values
    let index = main.generate_content_btn.attr('data-index')
    let ready_to_update = $('.term-ready-to-update.running:not(.ready):not(.heading)')
    let ready_to_update_length = ready_to_update.length
    let i = 0
    ready_to_update.each(function () {

        i = i + 1
        let term = $(this).attr('data-term')
        let data = {
            action: action,
            term: term,
            index: index
        }
        ajaxValidation(data)
        $.ajaxQueue({
            url: ajaxurl,
            method: 'post',
            data: data,
            complete: function () {
                if (i === ready_to_update_length) {
                    runTermsUpdate()
                }
            }
        });
    })

    console.log(ready_to_update_length)
    console.log(i)
}

const runTermsUpdate = (ths) => {
    let action = actions.check_terms
    let running_terms_elements = $('.rank [class="empty view loading"]')
    let competitor = main.generate_content_btn.attr('data-index')

    if (running_terms_elements.length === 0) {
        return
    }

    running_terms_elements.each(function () {

        let term_index = $(this).closest('[data-term]').attr('data-term')
        let _this = $(this)

        let data = {
            action: action,
            index: competitor,
            term: term_index,
        }

        ajaxValidation(data)

        $.ajaxQueue({
            url: ajaxurl,
            method: 'post',
            data: data,
            async: true,
            success: function (data) {
                _this.after(data.html.html)
                _this.remove()

                console.log(data.html.html)
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
            }
        })

    })
}

const stickyHeadingColor = (e) => {

}

const rankingPositions = () => {
    $(document).on('click', 'a[data-modal="#ranking-modal"], a[data-modal="#rank-history"]', function (e) {
        e.preventDefault()
        let html = '',
            table = $('#ranking-modal, #rank-history').find('.table .body'),
            url = $(this).closest('.row-line').find('.company-name span').html(),
            keyword = $(this).closest('.row-line').find('.keyword span').html(),
            modalTitle = $('#ranking-modal, #rank-history').find('.seoaic-popup__header h3')

        modalTitle.next('span').remove()
        url ? modalTitle.after('<span>' + url + '</span>') : ''
        keyword ? modalTitle.after('<span>' + keyword + '</span>') : ''
        table.html(html)
    })
}

const postedPostsModal = (e) => {
    let link = $('.search-terms-table')
    link.on('click', '[data-modal="#search-terms-posts"]', (e) => {
        e.preventDefault()
        let it = $(e.currentTarget),
            data = JSON.parse(it.attr('data-content')),
            html = '',
            modal_content = $('#search-terms-posts #confirm-modal-content')
        data.forEach(function (element) {
            html += '<li><a target="_blank" href="' + element.link + '">' + element.title + '</a></li>';
        });
        modal_content.html('<ul class="created-posts">' + html + '</ul>')
    })
}

const setMassGenerateModalArgs = () => {
    main.top.on('click', '.generate-competitor-content-btn', (e) => {
        e.preventDefault()
        let ths = $(e.currentTarget),
            term_ids = ths.attr('data-post-id').split(','),
            competitor_index = ths.attr('data-index'),
            modal = $('#seoaic-post-mass-creation-modal .seoaic-popup'),
            form = modal.find('form'),
            competitor_input = form.find('[name="competitor"]'),
            set_post_status_input = form.find('[name="set_post_status"]'),
            set_post_date_input = form.find('[name="set_post_date"]'),
            posts_terms_array = form.find('[name="posts_terms_array"]'),
            submit = modal.find('button[type="submit"]'),
            check_box_area = modal.find('.additional-items'),
            next_to_ranges = modal.find('.flex-justify.modal-ranges ~ .flex-justify'),
            date_picker_post = modal.find('.idea-date-picker input')
        competitor_input.val(competitor_index)
        next_to_ranges.addClass('custom_date_layout')
        date_picker_post.prop("readonly", true)
        date_picker_post.attr('type', 'datetime')
        if (!modal.find('.picker-call').length) {
            date_picker_post.after('<div class="picker-call"></div>')
        }
        posting_idea_date('.seoaic-mass-idea-date')
        let term = '',
            terms = [];
        $('.search-terms-table .seoaic-check-key:checked').each(function () {
            let ths = $(this),
                data_term = ths.attr('data-keyword'),
                terms_keyword = ths.closest('.row-line').find('.keyword').text()
            term +=
                '<label data-id="label-idea-mass-create-' + data_term + '" class="w-100">' +
                '<input type="checkbox" checked="" class="seoaic-form-item" name="idea-mass-create" value="' + data_term + '">' +
                '<b>' + terms_keyword + '</b>' +
                '</label>'
            terms.push(terms_keyword)
        })

        if (!competitor_input.length) {
            form.prepend('<input class="seoaic-form-item" type="hidden" name="competitor" value="' + competitor_index + '">')
        }
        if (!set_post_date_input.length) {
            form.prepend('<input class="seoaic-form-item" type="hidden" name="set_post_date" value="' + date_picker_post.val() + '">')
        }
        if (!posts_terms_array.length) {
            form.prepend('<input class="seoaic-form-item" type="hidden" name="posts_terms_array" value="' + terms + '">')
        }
        let set_post_status = $('select[form="post-mass-creation-form"]'),
            post_status = set_post_status.val(),
            date_picker = modal.find('.idea-date-picker')
        date_picker.addClass('visible')
        date_picker.attr('style', '')
        set_post_status.addClass('form-select').on('change', function () {
            post_status = $(this).find(':selected').val()
            $(this).closest('.seoaic-popup').find('[name="set_post_status"]').val(post_status)
            if (post_status === 'delay' || post_status === '') {
                date_picker.addClass('visible')
                date_picker.attr('style', '')
            } else {
                date_picker.hide()
            }
        })
        if (!set_post_status_input.length) {
            form.prepend('<input class="seoaic-form-item" type="hidden" name="set_post_status" value="' + post_status + '">')
        }
        check_box_area.html(term)
        submit.attr('id', 'posts-mass-generate-competitors-button')
        submit.attr('data-index', competitor_index)
        submit.attr('data-post-id', term_ids)
    })
}

const dragScroll = () => {
    (function (root, factory) {
        if (typeof define === 'function' && define.amd) {
            define(['exports'], factory);
        } else if (typeof exports !== 'undefined') {
            factory(exports);
        } else {
            factory((root.dragscroll = {}));
        }
    }(this, function (exports) {
        var _window = window;
        var _document = document;
        var mousemove = 'mousemove';
        var mouseup = 'mouseup';
        var mousedown = 'mousedown';
        var EventListener = 'EventListener';
        var addEventListener = 'add' + EventListener;
        var removeEventListener = 'remove' + EventListener;
        var newScrollX, newScrollY;
        var dragged = [];
        var reset = function (i, el) {
            for (i = 0; i < dragged.length;) {
                el = dragged[i++];
                el = el.container || el;
                el[removeEventListener](mousedown, el.md, 0);
                _window[removeEventListener](mouseup, el.mu, 0);
                _window[removeEventListener](mousemove, el.mm, 0);
            }
            // cloning into array since HTMLCollection is updated dynamically
            dragged = [].slice.call(_document.getElementsByClassName('dragscroll'));
            for (i = 0; i < dragged.length;) {
                (function (el, lastClientX, lastClientY, pushed, scroller, cont) {
                    (cont = el.container || el)[addEventListener](
                        mousedown,
                        cont.md = function (e) {
                            if (!el.hasAttribute('nochilddrag') ||
                                _document.elementFromPoint(
                                    e.pageX, e.pageY
                                ) == cont
                            ) {
                                pushed = 1;
                                lastClientX = e.clientX;
                                lastClientY = e.clientY;

                                e.preventDefault();
                            }
                        }, 0
                    );
                    _window[addEventListener](
                        mouseup, cont.mu = function () {
                            pushed = 0;
                        }, 0
                    );
                    _window[addEventListener](
                        mousemove,
                        cont.mm = function (e) {
                            if (pushed) {
                                (scroller = el.scroller || el).scrollLeft -=
                                    newScrollX = (-lastClientX + (lastClientX = e.clientX));
                                scroller.scrollTop -=
                                    newScrollY = (-lastClientY + (lastClientY = e.clientY));
                                if (el == _document.body) {
                                    (scroller = _document.documentElement).scrollLeft -= newScrollX;
                                    scroller.scrollTop -= newScrollY;
                                }
                            }
                        }, 0
                    );
                })(dragged[i++]);
            }
        }
        if (_document.readyState == 'complete') {
            reset();
        } else {
            _window[addEventListener]('load', reset, 0);
        }
        exports.reset = reset;
    }));
}

const updateCompetitor = () => {
    let modal = $('#search-terms-update-modal'),
        admin_body = $('#seoaic-admin-body'),
        content = admin_body.attr('data-update-modal-content'),
        submit = modal.find('.seoaic-popup__btn-right'),
        loading_progress = modal.find('.lds-indication .loader'),
        from_num = modal.find('.status-update .from'),
        to_num = modal.find('.status-update .to'),
        terms = admin_body.attr('data-ready-to-update'),
        ready_to_update_terms = terms ? JSON.parse(admin_body.attr('data-ready-to-update')) : '',
        update_terms_count = terms ? Object.keys(ready_to_update_terms).length : 0,
        body = $('body'),
        status_ul = modal.find('.status-update + ul')

    if (update_terms_count) {
        modal.find('.seoaic-popup__content .modal-content').html(content);
        from_num.html(0)
        to_num.html(update_terms_count)
        submit.attr('action', actions.update_competitor)
        modal.fadeIn(200);
        admin_body.addClass('seoaic-blur');
        body.addClass('modal-show');
    }

    submit.on('click', (e) => {
        e.preventDefault()
        modal.addClass('loading')
        if (!ready_to_update_terms) {
            return false
        }
        ready_to_update_terms.forEach(function (element, i) {
            let num_val = i + 1,
                data = {
                    action: actions.update_competitor,
                    index: element.index,
                    location: element.location,
                    domain: element.domain,
                }
            ajaxValidation(data)
            jQuery.ajaxQueue({
                url: ajaxurl,
                method: 'post',
                data: data,
                beforeSend: function () {
                    setTimeout(() => {
                        admin_body.addClass('seoaic-blur');
                        body.addClass('modal-show');
                    }, 200);
                },
                success: function (data) {
                    let to_num_val = parseInt(to_num.html()),
                        percent = num_val * 100 / to_num_val
                    loading_progress.css({
                        'max-width': percent + '%'
                    })
                    from_num.text(num_val)
                    status_ul.append(data)
                    if (to_num_val === num_val) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    }
                },
                complete: function (responseText) {
                    from_num.text(num_val)
                    status_ul.scrollTop(function () {
                        return this.scrollHeight;
                    })
                }
            })
        })
    })
}

main.compare.on('click', '.row-line .company-name [data-i]', function () {
    let selected_val = $(this).attr('data-i'),
        select_box = $('.select2-competitors')

    select_box.val(selected_val)
    select_box.change()
})

function selectCompetitors() {
    let select_box = $('.select2-competitors'),
        watch = $('.competitors-watch')
    select_box.select2({
        minimumResultsForSearch: -1
    })

    select_box.on('change', function () {
        let selected_val = $(this).find('option:selected').val()
        watch.find('[data-index="' + selected_val + '"] span').trigger('click')
    })

    let competitor_index = get_url_param('competitor')

    if (competitor_index) {
        select_box.val(competitor_index)
        select_box.change()
    }
}