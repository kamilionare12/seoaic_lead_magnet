(($) => {
    $(() => {
        $('#wizard_generate_keywords.seoaic-modal [name="location"], #wizard_generate_keywords.seoaic-modal [name="language"]').select2({
            minimumResultsForSearch: 1
        });

        $('#wizard_generate_ideas select[name="idea_template_type"]').select2({
            minimumResultsForSearch: 1
        });

        const wizard_page = $('#seoaic-admin-body.wizard');
        const is_wizard_page = wizard_page.length == 1;

        const checkboxes_handler = (table, btn) => {
            const checked_els = table.find('input:checked');
            const btn_exists = btn.length == 1;

            if (
                checked_els.length
                && btn_exists
            ) {
                btn.removeAttr('disabled');
            } else {
                btn.attr('disabled', 'disabled');
            }
        };

        if (is_wizard_page) {
            const step_container = wizard_page.find('.step-container');
            const step = step_container.data('step');

            if (1 == step) {
                // Location and Language dropdown handlers
                $('#wizard_generate_keywords.seoaic-modal [name="location"]').on('change', (e, state) => {
                    if ("undefined" != typeof state && state) {
                        // console.log('is triggered from code');
                        return false;
                    }

                    const el = $(e.currentTarget);
                    const val = el.val();
                    const modal = el.closest('.seoaic-modal');
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
                        }
                    });
                });


                wizard_page.find('.seoaic-generate-keywords-button').on('click', (e) => {
                    const el = $(e.currentTarget);
                    const modal = $(el.data('modal'));

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
                });

            } else if (2 == step) {
                const generated_keywords_table = $('.generated-keywords-table');
                const generated_keywords_table_exists = generated_keywords_table.length == 1;
                const apply_btn = $('#apply_selected_keywords');

                if (generated_keywords_table_exists) {
                    const flipbox_flip = function () {
                        $('.keywords .seoaic-flip-box').addClass('seoaic-flip-box-flipped');
                    }

                    const flipbox_unflip = function () {
                        $('.keywords .seoaic-flip-box').removeClass('seoaic-flip-box-flipped');
                    }


                    checkboxes_handler(generated_keywords_table, apply_btn);

                    const init_checked_length = $('.keywords .row-line .seoaic-check-key:checked').length;
                    if (0 != init_checked_length) {
                        flipbox_flip();
                    }

                    generated_keywords_table.find('input[type="checkbox"]').each((i, checkbox) => {
                        $(checkbox).on('change', () => {
                            checkboxes_handler(generated_keywords_table, apply_btn);
                        });
                    });


                    const keywords_counter_handler = function(count) {
                        const counter = $('.keywords .seoaic-checked-amount-num');

                        counter.text(count);
                    }

                    $(document).on('change', '.seoaic-check-key', function() {
                        const checkbox = $(this);
                        let checked_length = 0;

                        if ('all' == checkbox.val()) {
                            if (checkbox.is(':checked')) {
                                const all_items = $('.keywords .row-line .seoaic-check-key');
                                all_items.prop('checked', true);
                                checked_length = all_items.length;

                                flipbox_flip();
                            }
                        } else {
                            checked_length = $('.keywords .row-line .seoaic-check-key:checked').length;

                            if (checkbox.is(':checked')) {
                                flipbox_flip();
                            } else {
                                if (0 == checked_length) {
                                    $('#wizard_keywords_check_all').prop('checked', false);
                                    flipbox_unflip();
                                }
                            }
                        }

                        keywords_counter_handler(checked_length);
                        checkboxes_handler(generated_keywords_table, apply_btn);
                    });

                    $(document).on('click', '#wizard_keywords_uncheck_all', function() {
                        $('.keywords .row-line .seoaic-check-key, #wizard_keywords_check_all').prop('checked', false);
                        flipbox_unflip();
                        checkboxes_handler(generated_keywords_table, apply_btn);
                    });
                }

            } else if (3 == step) {
                const btn = $('#wizard_generate_ideas_button');

                if ("undefined" !== typeof btn.data('selected-keywords')) {
                    btn.removeAttr('disabled');
                }

            } else if (4 == step) {
                const generate_btn = $('#wizard_generate_posts_button');
                const generated_ideas_table = $('.seoaic-ideas-posts');
                const generated_ideas_table_exists = generated_ideas_table.length == 1;

                if (generated_ideas_table_exists) {
                    checkboxes_handler(generated_ideas_table, generate_btn);

                    generated_ideas_table.find('input.idea-mass-create[type="checkbox"]').each((i, checkbox) => {
                        $(checkbox).on('change', (e) => {
                            checkboxes_handler(generated_ideas_table, generate_btn);

                            // ability to select only 1 idea
                            // const clicked_checkbox = e.currentTarget;
                            // const all_checkboxes = generated_ideas_table.find('input.idea-mass-create[type="checkbox"]');
                            // if ($(clicked_checkbox).is(':checked')) {
                            //     all_checkboxes.each((i, checkbox) => {
                            //         if (!$(checkbox).is(':checked')) {
                            //             $(checkbox).attr('disabled', 'disabled');
                            //         }
                            //     });
                            // } else {
                            //     all_checkboxes.each((i, checkbox) => {
                            //         $(checkbox).removeAttr('disabled');
                            //     });
                            // }
                        });
                    });
                }

                // popup window section
                const popup_generate_btn = $('#wizard_generate_posts #btn-generate-posts');
                const popup_selected_ideas_table = $('#wizard_generate_posts .additional-items');
                const popup_selected_ideas_table_exists = popup_selected_ideas_table.length == 1;

                if (popup_selected_ideas_table_exists) {
                    // check popup inputs on popup appear
                    generate_btn.on('click', function() {
                        setTimeout(() => {
                            checkboxes_handler(popup_selected_ideas_table, popup_generate_btn);
                        }, 1);
                    });

                    $(popup_selected_ideas_table).on('click', 'input[type="checkbox"]', function(){
                        checkboxes_handler(popup_selected_ideas_table, popup_generate_btn);
                    });
                }
            }
        }
    });
})(jQuery);