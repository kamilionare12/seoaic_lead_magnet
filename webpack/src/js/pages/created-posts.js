import AirDatepicker from 'air-datepicker'
import localeEn from 'air-datepicker/locale/en';

(function($) {
    $(() => {
        let posts_uncheck_all_handler = function() {
            $('#posts_mass_edit_all').prop('checked', false);
            $('.post .col-id input[type="checkbox"]:checked').prop('checked', false);
            $('.seoaic-flip-box').removeClass('seoaic-flip-box-flipped');
            $('.mass-effect-button .additional-form-items').empty();
        };

        $(document).on('change', '.posts-mass-edit, .post-mass-edit', function() {
            let checkbox = $(this);
            let val = checkbox.val();
            let checked_len;

            if (val !== 'all') {
                let input_id = 'post-mass-edit-' + val;
                checked_len = $('.post .col-id input[type=checkbox]:checked').length;

                if (checkbox.prop('checked')) {
                    let title = $('#seoaic_post_' + val).find('.seoaic-post-title').text();
                    let input = '<label data-id="label-' + input_id + '"><input type="checkbox" checked class="seoaic-form-item" name="post-mass-edit" value="' + val + '"> <b>#' + val + '</b> - ' + title + '<label>';
                    $('.mass-effect-button .additional-form-items').append(input);
                } else {
                    $('.mass-effect-button .additional-form-items').find('[data-id="label-' + input_id + '"]').remove();
                }

            } else {
                checked_len = $('.post .col-id input[type=checkbox]').length;

                $('.mass-effect-button .additional-form-items').empty();

                $('.post .col-id input[type=checkbox]').each(function() {
                    let _val = $(this).val();
                    let _input_id = 'post-mass-edit-' + _val;
                    let title = $('#seoaic_post_' + _val).find('.seoaic-post-title').text();
                    let input = '<label data-id="label-' + _input_id + '"><input type="checkbox" checked class="seoaic-form-item" name="post-mass-edit" value="' + _val + '"> <b>#' + _val + '</b> - ' + title + '<label>';
                    $('.mass-effect-button .additional-form-items').append(input);
                });
            }

            $('.seoaic-checked-amount-num').text(checked_len);

            if (checked_len == 0) {
                posts_uncheck_all_handler();
            } else {
                $('.seoaic-flip-box').addClass('seoaic-flip-box-flipped');
            }
        });

        $(document).on('change', '#posts_mass_edit_all', function() {
            $('.post .post-mass-edit').prop('checked', true);
        });

        $(document).on('click', '.posts-mass-edit-uncheck-all', function() {
            posts_uncheck_all_handler();
        });


        // filters
        const publishedFromDateIdentifier = "input[name='filter_post_date_from']";
        const publishedToDateIdentifier = "input[name='filter_post_date_to']";
        const createdFromDateIdentifier = "input[name='filter_post_created_date_from']";
        const createdToDateIdentifier = "input[name='filter_post_created_date_to']";
        const publishedFromDateEl = $(publishedFromDateIdentifier);
        const publishedToDateEl = $(publishedToDateIdentifier);
        const createdFromDateEl = $(createdFromDateIdentifier);
        const createdToDateEl = $(createdToDateIdentifier);
        const titleEl = $("input[name='filter_post_title']");
        const wordsMin = $("#seoaic_words_range_min");
        const wordsMax = $("#seoaic_words_range_max");
        const perPageEl = $("#posts_per_page");

        const publishedMinValue = publishedFromDateEl.length ? publishedFromDateEl.val() : '';
        const publishedMaxValue = publishedToDateEl.length ? publishedToDateEl.val() : '';
        const createdMinValue = createdFromDateEl.length ? createdFromDateEl.val() : '';
        const createdMaxValue = createdToDateEl.length ? createdToDateEl.val() : '';
        const perPage = perPageEl.length ? perPageEl.val() : '10';

        let datePublishedMin, datePublishedMax, dateCreatedMin, dateCreatedMax;

        if (
            publishedFromDateEl.length
            && publishedToDateEl.length
        ) {
            const datePublishedMinArgs = {
                autoClose: true,
                dateFormat: 'MM/dd/yyyy',
                locale: localeEn,
                // minDate: publishedMinValue,
                maxDate: publishedMaxValue,
                buttons: ['clear'],

                onSelect({date}) {
                    datePublishedMax.update({
                        minDate: date
                    });
                },
                // onHide: function(dp) {
                //     if (dp) {
                //         const maxVal = publishedToDateEl.val();
                //         if (maxVal == '') {
                //             datePublishedMax.show();
                //         }
                //     }
                // }
            };
            if ('' != publishedMinValue) {
                datePublishedMinArgs.selectedDates = [publishedMinValue];
            }
            datePublishedMin = new AirDatepicker(publishedFromDateIdentifier, datePublishedMinArgs);

            const datePublishedMaxArgs = {
                autoClose: true,
                dateFormat: 'MM/dd/yyyy',
                minDate: publishedMinValue,
                // maxDate: publishedMaxValue,
                locale: localeEn,
                buttons: ['clear'],
                onSelect({date}) {
                    datePublishedMin.update({
                        maxDate: date
                    });
                },
                // onHide: function(dp) {
                //     if (dp) {
                //         const minVal = publishedfromDateEl.val();
                //         if (minVal == '') {
                //             datePublishedMin.show();
                //         }
                //     }
                // },
            };
            if ('' != publishedMaxValue) {
                datePublishedMaxArgs.selectedDates = [publishedMaxValue];
            }
            datePublishedMax = new AirDatepicker(publishedToDateIdentifier, datePublishedMaxArgs);
        }

        if (
            createdFromDateEl.length
            && createdToDateEl.length
        ) {
            const dateCreatedMinArgs = {
                autoClose: true,
                dateFormat: 'MM/dd/yyyy',
                locale: localeEn,
                maxDate: createdMaxValue,
                buttons: ['clear'],
                onSelect({date}) {
                    dateCreatedMax.update({
                        minDate: date
                    });
                }
            };
            if ('' != createdMinValue) {
                dateCreatedMinArgs.selectedDates = [createdMinValue];
            }
            dateCreatedMin = new AirDatepicker(createdFromDateIdentifier, dateCreatedMinArgs);

            const dateCreatedMaxArgs = {
                autoClose: true,
                dateFormat: 'MM/dd/yyyy',
                minDate: createdMinValue,
                locale: localeEn,
                buttons: ['clear'],
                onSelect({date}) {
                    dateCreatedMin.update({
                        maxDate: date
                    });
                }
            };
            if ('' != createdMaxValue) {
                dateCreatedMaxArgs.selectedDates = [createdMaxValue];
            }
            dateCreatedMax = new AirDatepicker(createdToDateIdentifier, dateCreatedMaxArgs);
        }

        const waiting_animation = function() {
            $('#seoaic-admin-body').addClass('seoaic-loading');
        };

        const postFilterAndPaginationHandler = function() {
            waiting_animation();
            const location = document.location;
            const url = new URL(document.location.href);
            const urlParams = new URLSearchParams(url.search);
            const newParams = {
                seoaic_title: titleEl.length ? titleEl.val() : '',
                seoaic_words_min: wordsMin.length ? wordsMin.val() : 0,
                seoaic_words_max: wordsMax.length ? wordsMax.val() : 1000,
                seoaic_publ_datefrom: publishedFromDateEl.length ? publishedFromDateEl.val() : '',
                seoaic_publ_dateto: publishedToDateEl.length ? publishedToDateEl.val() : '',
                seoaic_create_datefrom: createdFromDateEl.length ? createdFromDateEl.val() : '',
                seoaic_create_dateto: createdToDateEl.length ? createdToDateEl.val() : '',
                per_page: perPageEl.length ? perPageEl.val() : 10,
                paged: 1
            };

            for (var key in newParams) {
                if (newParams.hasOwnProperty(key)) {
                    urlParams.set(key, encodeURIComponent(newParams[key]));

                    if ('' == newParams[key]) {
                        urlParams.delete(key);
                    }
                }
            }

            document.location.href = location.origin + location.pathname + '?' + urlParams.toString();
        };

        $('.posts-search-form').on('submit', (e) => {
            e.preventDefault();
            postFilterAndPaginationHandler();
        });

        perPageEl.on('change', () => {
            postFilterAndPaginationHandler();
        });

        $('.pagination a, .filter-clear-btn, .filter-btn:not(.posts-mass-edit-uncheck-all)').on('click', () => {
            waiting_animation();
        });
    });
})(jQuery);