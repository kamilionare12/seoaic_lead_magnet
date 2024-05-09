var form_callbacks;

(function ($) {
    $(document).ready(function () {
        let seoaiAuditStorage = null
        const create_seo_audit = (title) => {
            let data = {action: 'seoaic_create_seo_audit'}
            let seoaicNonceValue = wp_nonce_params[data.action];
            if (seoaicNonceValue !== '') {
                data._wpnonce = seoaicNonceValue;
            }
            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: data,
                success: function (data) {
                    if (data.status === 'blocked') {
                        let alert_modal = $('#seoaic-alert-modal');
                        alert_modal.find('.modal-title').html(title);
                        seoaic_open_modal(alert_modal, '<p>'+data.message+'</p>');
                    } else {
                        location.reload();
                    }

                    $('#seoaic-admin-body').removeClass('seoaic-loading')
                }
            });
        }

        $(document).on('click', '.seoaic_create_audit', function (e) {
            e.preventDefault();
            let button = $(this);
            create_seo_audit(button.attr('data-title'))
        });

        const get_seo_audit_data = () => {
            let data = {action: 'seoaic_get_seo_audit_data'}

            let seoaicNonceValue = wp_nonce_params[data.action];

            if (seoaicNonceValue !== '') {
                data._wpnonce = seoaicNonceValue;
            }

            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: data,
                beforeSend: function () {
                    $('#seoaic-admin-body').addClass('seoaic-loading')
                },
                success: function (data) {
                    if (data) {
                        if (typeof data.status !== 'undefined' && data.status != 'finished' && data.status != 'queue' && data.status != 'alert') {
                            $('.audit-container').css('display', 'none')
                            $('.progress-container').css('display', 'block')

                            const progressBar = document.getElementById('audit-progress-bar');
                            const crawlStatus = data.crawl_status;
                            const progressValue = calculatePercent(crawlStatus.max_crawl_pages, crawlStatus.pages_crawled);

                            progressBar.style.width = progressValue.toFixed(2) + '%';

                            setSeoaicText(document.getElementById('progress-max-crawl-pages'), crawlStatus.max_crawl_pages);
                            setSeoaicText(document.getElementById('progress-pages-in-queue'), crawlStatus.pages_in_queue);
                            setSeoaicText(document.getElementById('progress-pages-crawled'), crawlStatus.pages_crawled);

                            setTimeout(function() {
                                get_seo_audit_data();
                            }, 15000);
                        }

                        if (data.status === 'queue') {
                            $('.audit-container').css('display', 'none')
                            $('.progress-container').css('display', 'block')

                            setSeoaicText(document.getElementById('progress-status-message'), data.message);

                            setTimeout(function() {
                                get_seo_audit_data();
                            }, 15000);
                        }

                        if (data.status === 'error') {
                            let alert_modal = $('#seoaic-alert-modal');
                            seoaic_open_modal(alert_modal, '<p>'+data.message+'</p>');
                        }

                        if (data.audit && data.audit.status === 'finished') {
                            $('.audit-container').css('display', 'block')
                            $('.progress-container').css('display', 'none')

                            seoaiAuditStorage = data
                            const pages = seoaiAuditStorage.auditInfoPages.length
                            $('.seoaic-error-count').text(seoaiAuditStorage.auditData.errors.qty)
                            $('.seoaic-warning-count').text(seoaiAuditStorage.auditData.warnings.qty)
                            $('.seoaic-notice-count').text(seoaiAuditStorage.auditData.notice.qty)

                            setSeoAuditData('#errors-container', seoaiAuditStorage.auditData.errors, 'errors');
                            setSeoAuditData('#warnings-container', seoaiAuditStorage.auditData.warnings, 'warnings');
                            setSeoAuditData('#notices-container', seoaiAuditStorage.auditData.notice, 'notice');

                            setThematic('seoai-https', pages, seoaiAuditStorage.auditData.thematic.is_https.qty)
                            if (seoaiAuditStorage.auditData.errors.is_orphan_page.pages.length) {
                                setSeoaicText(document.getElementById('seoaic-orphan-pages'), seoaiAuditStorage.auditData.errors.is_orphan_page.pages.length);
                                $('.overview-section .number-value .title button').css('display', 'inline-block')
                            }

                            setStatusPage(seoaiAuditStorage.auditInfoPages)

                            setCrawledPagesHtml(seoaiAuditStorage.auditInfoPages)
                        }

                        if (data.audit) {
                            $('.seoaic_create_audit').attr('disabled', false).removeClass('seoaic_create_audit_disabled');
                        }
                    } else {
                        get_seo_audit_data();
                        console.log('Data is null or undefined');
                    }

                    $('#seoaic-admin-body').removeClass('seoaic-loading')
                }
            });
        }

        if($('#seoaic-admin-container').hasClass('seoaic-audit-page')){
            get_seo_audit_data();
        }

        function setStatusPage(pages) {
            let is_2xx_code = 0,
                is_3xx_code = 0,
                is_4xx_code = 0,
                is_5xx_code = 0,
                has_micromarkup = 0;

            pages.forEach(item => {
                const statusCode = item.status_code;
                if (statusCode >= 200 && statusCode < 300) {
                    is_2xx_code++;
                } else if (statusCode >= 300 && statusCode < 400) {
                    is_3xx_code++;
                } else if (statusCode >= 400 && statusCode < 500) {
                    is_4xx_code++;
                } else if (statusCode >= 500 && statusCode < 600) {
                    is_5xx_code++;
                }

                if (item.checks.has_micromarkup == true && item.checks.has_micromarkup_errors == false) {
                    has_micromarkup++;
                }
            });

            setThematic('seoai-markup', pages.length, has_micromarkup, true)

            setCrawledPages('thematic-2xx', is_2xx_code)
            setCrawledPages('thematic-3xx', is_3xx_code)
            setCrawledPages('thematic-4xx', is_4xx_code)
            setCrawledPages('thematic-5xx', is_5xx_code)

            setCrawledPages('thematic-crawl', pages.length)

            const blocks = [
                { value: is_2xx_code, owner: 'graph-2xx' },
                { value: is_3xx_code, owner: 'graph-3xx' },
                { value: is_4xx_code, owner: 'graph-4xx' },
                { value: is_5xx_code, owner: 'graph-5xx' },
              ];

            getCrawledPagesPercent(blocks)
        }

        function setCrawledPages(element, value) {
            const elem = document.getElementById(element);
            setSeoaicText(elem, value)
        }

        function setThematic(element, pages, value, fixVal = false) {
            const elem = document.getElementById(element);
            const p = calculatePercent(pages, value)

            if (pages) {
                setSeoaicText(elem, p, fixVal)
                elem.querySelector('.pie').style.setProperty('--p', p);
            } else {
                setSeoaicText(elem, value, fixVal)
                elem.querySelector('.pie').style.setProperty('--p', value);
            }
        }

        function setSeoaicText(element, value, fixNum = false) {
            const v = fixNum ? value.toFixed(2) : value
            element.querySelector('span').textContent = v
        }

        function setSeoAuditData(container, messages, ancor) {
            for (const key in messages) {
                const message = messages[key];

                if (message === 0) {
                    $(container).parent().css('display', 'none');
                }

                if (message.title && message.pages && message.pages.length > 0) {
                    let title = message.title
                    let html = '<div class="message-wrap">'
                    html += '<div class="message-item">'
                    html +=    '<h3>'+title+'</h3>'
                    html +=    '<div class="button-wrap">'
                    //html +=        '<a href="#" class="button submit-add-to-menu button_underline">Why and how to fix it</a>'
                    //html +=        '<a href="#" class="button submit-add-to-menu button_border"><span class="dashicons dashicons-arrow"></span>Send to...</a>'
                    html +=        '<button type="button" class="outline button_border modal-button button_view dashicons dashicons-eye" data-message="'+ancor+'" data-key="'+key+'" data-title="'+title+'" data-modal="#seoaic-alert-modal" data-action="seoaic_get_seo_audit_data"></button>'
                    html +=     '</div>'
                    html +=   '</div>'
                    html += '</div>'
                    $(container).append(html);
                }
            }
        }

        function calculatePercent(pages, value) {
            return (value / pages) * 100
        }

        function getCrawledPagesPercent(blocks) {
            const total = blocks.reduce((acc, block) => acc + block.value, 0);

            const percentages = blocks.map(block => ({
                owner: block.owner,
                percentage: Math.round((block.value / total) * 100)
            }));

            percentages.forEach(block => {
                const blockElement = document.getElementById(block.owner.toLowerCase().replace(' ', ''));
                blockElement.style.width = `${block.percentage}%`;
            });
        }

        function setCrawledPagesHtml(pages) {
            let pagesContainer = $('.crawled-pages-section .row-line.heading');
            let performance = 0

            pages.forEach(function (page) {
                let pageHTML = createPageHTML(page);
                pagesContainer.after(pageHTML);

                performance += Number(page.onpage_score)
            });

            const siteScore = performance / pages.length

            setThematic('seoai-performance', false, siteScore.toFixed(0))

            const setSiteHealthScore = () => {
                const elem = document.getElementById('seoai-site-health-score');
                const progressChart = elem.getElementsByClassName('progress-chart')[0];
                const progressLabel = elem.getElementsByClassName('value')[0];
                const chartScore = siteScore / 100 * 180;
                progressChart.style.cssText = `--progress: ${chartScore}deg;`;
                progressLabel.innerHTML = siteScore.toFixed(0) + '%';
            }
            setSiteHealthScore();
        }

        function createPageHTML(page) {
            let issueCount = 0,
                issuesList = [];

            Object.entries(page.issues).forEach(([key, issue]) => {
                issueCount += issue.length;
                issuesList = issuesList.concat(issue);
            });

            const issuesString = issuesList.map(issue => `<li>${issue}</li>`).join('');

            return `
            <div class="row-line">
                <div class="url"><a href="${page.url}" target="_blank">${page.url}</a></div>
                <div class="crawl-depth">${page.click_depth}</div>
                <div class="issues">
                    <button type="button" class="outline modal-button" data-title="Issues" data-content="<ol>${issuesString}</ol>" data-modal="#seoaic-alert-modal">${issueCount}</button>
                </div>
                <div class="status-code">${page.status_code}</div>
                <div class="performance"><button type="button" class="outline modal-button button_view dashicons dashicons-eye" data-title="${page.url}" data-modal="#seoaic-performance-modal" data-score="${page.onpage_score}" data-interactive="${page.page_timing.time_to_interactive}ms"></button></div>
            </div>
            `;
        }

        $(document).on('click', '.issues-section .button_view, .orphan-pages-wrap .button_view', function () {
            let button = $(this);
            let $html = '<ol>';
            const messages = seoaiAuditStorage.auditData[button.attr('data-message')][button.attr('data-key')]

            if (messages.pages && messages.pages.length > 0) {
                messages.pages.forEach(page => {
                    $html += `<li><a href="${page.url}" target="_blank" class="issues-item">${page.url}</a></li>`
                });
            }
            $html += '<ol>';

            $('#seoaic-alert-modal #confirm-modal-content').html($html);
        });

        $(document).on('click', '.crawled-pages-section .button_view', function () {
            const button = $(this),
                  score = Math.round(button.attr('data-score')),
                  interactive = button.attr('data-interactive'),
                  block = document.querySelector(button.attr('data-modal')),
                  scoreBlock = block.querySelector('.pie'),
                  interactiveBlock = block.querySelector('.time-to-interactive');

            scoreBlock.style.setProperty('--p', score);
            scoreBlock.querySelector('span').textContent = score;
            interactiveBlock.querySelector('span').textContent = interactive;

            scoreBlock.classList.remove('low', 'middle', 'top');

            if (score >= 0 && score <= 49) {
                scoreBlock.classList.add('low');
            } else if (score >= 50 && score <= 89) {
                scoreBlock.classList.add('middle');
            } else if (score >= 90 && score <= 100) {
                scoreBlock.classList.add('top');
            }
        });

        const currentUrl  = window.location.href;
        const searchParams = new URLSearchParams(currentUrl);

        if (searchParams.has("tab") && searchParams.has("tab") !== 'overview') {
            activateTab(searchParams.get("tab"));
        } else {
            activateTab('overview');
        }

        $(document).on('click', '.menu-section .tab', function (event) {
            event.preventDefault();
            let button = $(this);

            const url = new URL(location);
            url.searchParams.set("tab", button.attr('data-tab'));
            history.pushState({}, "", url);

            activateTab(button.attr('data-tab'));
        });

        function activateTab(tabName) {
            $('.tab, .tab-content').removeClass('active');
            $('.tab-content[data-tab-list="' + tabName + '"], .tab[data-tab="' + tabName + '"]').addClass('active');
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

        let ajax_response;
        let generating_timer;

        function post_ajax(data, callback = false, async = true) {
            let seoaic_admin_body = $('#seoaic-admin-body');

            let body = $('body');
            body.addClass('ajax-work');
            switch (data.action) {
                case 'seoaic_scan_site':
                    seoaic_admin_body.addClass('seoaic-scanning');
                    break;
                default:
                    seoaic_admin_body.addClass('seoaic-loading');
            }

            let seoaicNonceValue = wp_nonce_params[data.action];

            if (seoaicNonceValue !== '') {
                data._wpnonce = seoaicNonceValue;
            }

            seoaic_admin_body.removeClass('seoaic-loading-success');
            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: data,
                async: async,
                success: function (response) {
                    if (0 !== data.action.indexOf('seoaic_wizard_')) {
                        seoaic_admin_body.removeClass('seoaic-loading');
                        body.removeClass('ajax-work');
                    }
                    let alert_modal = $('#seoaic-alert-modal');

                    let content = false;
                    if (response['content'] !== undefined) {
                        content = response['content'];
                    }

                    if (response.status !== undefined) {
                        switch (response.status) {
                            case 'success':
                                if (false !== callback) {
                                    form_callbacks[callback](content);
                                }
                                break;
                            case 'alert':
                                ajax_response = response;

                                if (false !== callback) {
                                    alert_modal.attr('data-on-close-callback', callback);
                                }

                                seoaic_open_modal(alert_modal, response['message']);
                                break;
                            case 'error':
                                ajax_response = false;
                                alert_modal.removeAttr('data-on-close-callback');
                                seoaic_open_modal(alert_modal, response['message']);
                                break;
                            case 'scanning':
                                $('.loader').css({'max-width': ((response.step / response.steps) * 100) + '%'});
                                if (response.step === response.steps) {
                                    alert_modal.attr('data-on-close-callback', callback);
                                    seoaic_open_modal(alert_modal, response['message']);
                                } else {
                                    data.step = response.step + 1;
                                    data.step_content = response.step_content;
                                    post_ajax(data, callback);
                                }
                                break;
                            case 'generating':

                                $('label[data-id="label-idea-mass-create-' + response.content.post_id + '"]').addClass('line-through').find('.seoaic-form-item').removeClass('seoaic-form-item');

                                let modal = $('#seoaic-post-mass-creation-modal');
                                let items_len = modal.find('.generating-num-total').text() * 1;
                                let pos = modal.find('.generating-num-pos').text() * 1;


                                data.item_id = modal.find('form .seoaic-form-item[name="idea-mass-create"]:checked').first().val();
                                modal.find('.loader').css({'max-width': '100%'});

                                let max_width = (pos / items_len) * 100
                                modal.find('.loader').css({'max-width': max_width + '%'});

                                if (pos < items_len) {
                                    pos++;
                                    modal.find('.generating-num-pos').text(pos);
                                    generating_process();
                                    post_ajax(data);
                                } else {
                                    clearTimeout(generating_timer);
                                    modal.removeClass('generating').addClass('generated');
                                    modal.attr('data-on-close-callback', 'window_reload');
                                }

                                break;
                        }

                    }
                },
                complete: function() {
                    $('[data-action="seoaic_selectCategoriesIdea"]').trigger('click');
                }
            });
        }

        const Update_credits_real_time = () => {

            let data = {
                    action: 'seoaic_Update_credits_real_time',
                },
                posts = $('.seoaic-credits-panel .posts .num'),
                ideas = $('.seoaic-credits-panel .ideas .num'),
                frames = $('.seoaic-credits-panel .frames .num')

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

                    if ( data !== null ) {
                        posts.html(parseInt(data.posts_limit) - parseInt(data.posts_used))
                        ideas.html(parseInt(data.ideas_limit) - parseInt(data.ideas_used))
                        frames.html(parseInt(data.frames_limit) - parseInt(data.frames_used))
                    }

                },
                done: function(data) {
                    //console.log(data)
                }
            });

        }

        if ($('.idea-page')[0]) {
            Update_credits_real_time();
        }

        let modal_callbacks = {
            before_open_mass_create: function (button) {
                let posts_credit = $('#posts-credit').val();
                let checked_len = $('.post .idea-mass-create:checked').length;

                if (posts_credit < checked_len) {
                    let alert_title = $('#alert-posts-credit').val();
                    seoaic_open_modal($('#seoaic-alert-modal'), alert_title);
                    return true;
                }

                return false;
            }
        }

        form_callbacks = {
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
            },

            settings_update_description: function(data) {
                const textarea = $('#seoaic-settings #seoaic_business_description');
                if (
                    textarea.length
                    && "undefined" !== typeof data.description
                    && "" != data.description
                ) {
                    textarea.val(data.description);
                }
            }
        }

        function generating_process() {
            clearTimeout(generating_timer);
            $('.generating-process > span').hide(0);
            let k = 0;

            generating_timer = setInterval(function () {
                k++;

                if (k > $('.generating-process > span').length) {

                    clearTimeout(generating_timer);
                } else {
                    $('.generating-process > span').hide(0);
                    $('.generating-process > span:nth-child(' + k + ')').show(0);
                }

            }, 10000);
        }

        // can be closed
        $(document).on('mousedown ontouchstart', function (e) {
            if (!$(e.target).is(".media-modal,.media-modal *, .seoaic-popup,.seoaic-popup *, [data-modal], [data-modal] *, .select2-selection__choice__remove, .select2-container, .select2-container *, .air-datepicker-global-container, .air-datepicker-global-container *, .go-to-top")) {
                let modal = $('.seoaic-modal')
                if(modal.is('#search-terms-update-modal.loading')) {
                    window.onbeforeunload = function() {
                        return "Process will be stopped, are you sure?";
                    };
                    window.location.reload()
                } else if(!modal.is('.second-inner-modal')) {
                    seoaic_close_modal(modal)
                } else {
                    $('.second-inner-modal')
                        .find('.seoaic-modal-close').click()
                        .removeClass('second-inner-modal')
                }

                if(modal.is('#competitors-compare')) {
                    modal.next('.go-to-top').remove()
                }
            }
        });

        function seoaic_close_modal(modal) {

            modal.fadeOut(200, function () {

                if(!modal.is('.second-inner-modal')) {
                    $('body').removeClass('modal-show');
                    $('#seoaic-admin-body').removeClass('seoaic-blur');
                }

                let callback = modal.attr('data-on-close-callback');

                if (callback !== undefined && callback !== '') {
                    $('#seoaic-admin-body').addClass('seoaic-loading');

                    let content = false;
                    if (ajax_response !== undefined && ajax_response['content'] !== undefined && ajax_response['content'] !== '') {
                        content = ajax_response['content'];
                    }
                    form_callbacks[callback](content);
                }
            });
        }

        function idea_content_slide_close() {
            $('#seoaic-admin-body').removeClass('seoaic-loading-success');
            $('#seoaic-admin-body').removeClass('seoaic-slide-opened');
            $('.seoaic-ideas-posts .post').removeClass('active');
        }

        function idea_content_generator_check(val) {
            let image_description = $('#seoaic-idea-content-thumbnail');

            if (val === 'no_image') {
                image_description.slideUp(300);
            } else {
                image_description.slideDown(300);
            }
        }

        $(document).on('click', '.modal-button', function () {
            let button = $(this);
            let modal = $(button.attr('data-modal'));
            let form = modal.find('form');
            let title = button.attr('data-title');
            let action = button.attr('data-action');
            let content = button.attr('data-content');
            let form_callback = button.attr('data-form-callback');
            let form_before_callback = button.attr('data-form-before-callback');
            let language_parent = button.attr('data-language-parent-id');
            let language = button.attr('data-language');

            if ( button.attr('data-languages') === "true" ) {
                form.find('.seoaic-language').show(0);
            } else {
                form.find('.seoaic-language').hide(0);
            }

            if (language_parent !== undefined ) {
                form.find('.seoaic-multilanguage-parent-id').val(language_parent);
            }

            if (language !== undefined ) {
                form.find('.seoaic-language').val(language);
            }

            if (action !== undefined && action !== '') {
                modal.find('[name="action"]').val(action);
            }

            let callback_before = button.attr('data-callback-before');
            if (callback_before !== undefined && callback_before !== '') {
                if (modal_callbacks[callback_before](button)) {
                    return;
                }
            }

            if (form_callback !== undefined && form_callback !== '') {
                form.attr('data-callback', form_callback);
            } else {
                form.removeAttr('data-callback');
            }

            if (form_before_callback !== undefined && form_before_callback !== '') {
                form.attr('data-before-callback', form_before_callback);
            } else {
                form.removeAttr('data-before-callback');
            }

            if (title === undefined) {
                title = modal.find('.modal-title').attr('data-title');
            }
            modal.find('.modal-title').text(title);

            if (content === undefined) {
                content = false;
            }

            switch (button.attr('data-mode')) {
                case 'add':
                case 'edit':
                case 'set-category':
                    button.find('.edit-form-items input').each(function () {
                        let input = $(this);
                        let value = input.val();
                        let name = input.attr('name');
                        let target_input = form.find('[name="' + name + '"]');
                        let target_tagname = target_input.prop("tagName");
                        let tagname = 'INPUT';

                        if (button.attr('data-single') === 'no' && name === 'item_name' ) {
                            tagname = 'TEXTAREA';
                        }

                        let id = target_input.attr('id');
                        $('[for="' + id + '"]').text(input.attr('data-label'));

                        if (target_tagname !== tagname) {

                            let new_input = $(document.createElement(tagname.toLowerCase()));

                            target_input.replaceWith(new_input);
                            new_input.val(value);

                            new_input.attr('class', 'seoaic-form-item');
                            new_input.attr('id', id);
                            new_input.attr('type', target_input.attr('type'));
                            new_input.attr('name', target_input.attr('name'));
                            new_input.attr('required', 'required');
                        } else {

                            target_input.val(value);
                        }


                    });
                    break;
            }

            seoaic_open_modal(modal, content);
        });

        $(document).on('click', '.seoaic-button-link', function () {
            let button = $(this);
            $(button.attr('data-target')).click();
        });

        $(document).on('click', '.seoaic-modal-close', function () {
            let button = $(this);
            let modal = button.parents('.seoaic-modal')

            if(modal.is('#search-terms-update-modal.loading')) {
                window.onbeforeunload = function() {
                    return "Process will be stopped, are you sure?";
                };
                window.location.reload()
            } else {
                seoaic_close_modal(modal)
            }

        });

        $(document).on('click', '.seo-ai_page_seoaic-ideas .mass-effect-button', function () {
            let button = $(this);
            let modal = button.parents('.seoaic-modal')
            modal.hide()
        });

        $(document).on('click', '.seoaic-ajax-button', function () {
            let button = $(this);

            let callback_before = button.attr('data-callback-before');
            if (callback_before !== undefined && callback_before !== '') {
                if (form_callbacks[callback_before](button)) {
                    return;
                }
            }

            let action = button.attr('data-action');
            let callback = button.attr('data-callback');
            let data = {'action': action};

            let item_id = button.attr('data-post-id');
            if (item_id !== undefined) {
                data['item_id'] = item_id;
            }

            //console.log(callback)
            post_ajax(data, callback);

        });

        $(document).on('click', '.seoaic-generate-image-button', function () {

            let it = $(this),
                action = it.attr('data-action'),
                post = it.attr('data-post'),
                type = it.attr('data-type'),
                desc = it.attr('data-desc'),
                callback = it.attr('data-callback'),
                promt = it.closest('.selections').find('.promt-key').val(),
                promtInput = it.closest('.selections').find('.promt-key'),
                editorTitle = $('.editor-post-title').text(),
                gen,
                data;

            if (promt != "") {

                promtInput.css({
                    'border-color': 'inherit'
                })

                if (type === 'modal') {

                    gen = it.closest('.selections').find('select').find(':selected').val()

                }

                data = {
                    'action': action,
                    'post': post,
                    'type': type,
                    'gen': gen,
                    'desc': desc,
                    'editorTitle': editorTitle,
                    'promt': promt,
                };

                post_ajax(data, callback);

            } else {
                promtInput.css({
                    'border-color': 'red'
                })
                alert('Image description cannot be empty')
            }

        });

        $(document).on('click', '.confirm-modal-button', function () {
            let button = $(this);
            let modal = $(button.attr('data-modal'));
            let form = modal.find('form');
            let action = button.attr('data-action');
            let item_id = button.attr('data-post-id');
            let callback = button.attr('data-callback');
            let additional = button.find('.additional-form-items').html();

            if (item_id !== undefined) {
                form.find('[name="item_id"]').val(item_id);
            }

            modal.find('[name="action"]').val(action);
            modal.attr('data-callback', callback);

            if (additional === undefined || additional === '') {
                form.find('.additional-items').empty();
            } else {
                form.find('.additional-items').html(additional);
                let checked_len = form.find('.additional-items input.seoaic-form-item[type="checkbox"]:checked').length;
                modal.find('.additional-items-amount').text(checked_len);
            }

            if (modal.hasClass()) {
                modal.hide()
            }
        });

        $(document).on('submit', '.seoaic-form', function (e) {
            e.preventDefault();
            e.stopPropagation();

            let form = $(this);
            let data = {};
            form.find('.seoaic-form-item').each(function () {
                let _item = $(this);
                let _type = _item.attr('type');
                let _form_item_name = _item.attr('name');

                switch (_type) {
                    case 'checkbox':
                    case 'radio':
                        if (form.find('[name="' + _form_item_name + '"]').length > 1) {
                            if (data[_form_item_name] === undefined) {
                                data[_form_item_name] = [];
                            }
                            if (_item.prop('checked')) {
                                data[_form_item_name].push(_item.val());

                                //console.log(_item.val())
                            }
                        } else if (_item.prop('checked')) {
                            data[_form_item_name] = _item.val();
                        }
                        break;
                    default:
                        data[_form_item_name] = _item.val();
                }

                if (_item.val() === 'seoaic_generate_keywords_prompt') {
                    //console.log(form.attr('data-callback'));
                }

                if (_item.val() === 'seoaicRemoveSearchTerms') {
                    data['search-engine'] = 'google';
                }

                if (_item.val() === 'seoaicAddSearchTerms') {

                }
            });

            data['seoaic_services'] = '';

            if ($('.seoaic_services')[0]) {
                let s = []

                form.find('.service-input').each(function () {
                    let name = $(this).val(),
                        text = $(this).next('textarea').val()
                    s.push({name, text});
                    data['seoaic_services'] = s;
                })
            }

            data['seoaic_pillar_links'] = '';

            if ($('.seoaic_pillar_wrap')[0]) {
                let s = []

                form.find('.seoaic_pillar_item').each(function () {
                    let item = $(this),
                        lang = item.find('select[name="seoaic_ml_language"]').val(),
                        name = item.find('input[name="pillar-name"]').val(),
                        url  = item.find('input[name="pillar-url"]').val(),
                        text = item.find('textarea').val();

                    s.push({name, url, text, lang});
                    data['seoaic_pillar_links'] = s;
                })
            }

            let services = ''
            $('select[name="select_service"] > :selected').each(function () {
                let service = $(this).val();
                //services.push(service);
                data['selected_services'] = service;

                //console.log(service)
            });

            let keywords = []
            $('select[name="select-keywords"] + .select2 .select2-selection__rendered .select2-selection__choice').each(function () {
                let keyword = $('> span', this).text();
                keywords.push(keyword);
            });
            data['selected_keywords'] = keywords;

            let locations = []
            $('select[name="select_location"] > :selected').each(function () {
                let location = $(this).val().split(",");
                for (const one of location) {
                    locations.push(one);
                }
            });
            data['selected_locations'] = locations;

            if ($('.seoaic_locations')[0]) {
                let s = []

                form.find('.location-input').each(function () {
                    s.push($(this).val());
                    data['seoaic_locations'] = s;
                })

                //console.log(s)
            }

            // Post mass generation prompt templates
            data['seoaic_posts_mass_generate_prompt_templates'] = '';

            const promptTemplatesEl = $('.posts_mass_generate_prompt_templates');
            if (promptTemplatesEl.length) {
                let s = [];

                promptTemplatesEl.find('.post-prompt-teplate-input').each((i, el) => {
                    const val = $(el).val();
                    if ('' != val.trim) {
                        s.push(val);
                    }
                });
                data['seoaic_posts_mass_generate_prompt_templates'] = s;
            }

            let form_id = form.attr('id');
            if (form_id !== undefined) {
                $('.seoaic-form-item[form="' + form_id + '"]').each(function () {
                    let _item = $(this);
                    let _type = _item.attr('type');
                    let _form_item_name = _item.attr('name');

                    switch (_type) {
                        case 'checkbox':
                        case 'radio':
                            if ($('.seoaic-form-item[form="' + form_id + '"][name="' + _form_item_name + '"]').length > 1) {
                                if (data[_form_item_name] === undefined) {
                                    data[_form_item_name] = [];
                                }
                                if (_item.prop('checked')) {
                                    data[_form_item_name].push(_item.val());
                                }
                            } else if (_item.prop('checked')) {
                                data[_form_item_name] = _item.val();
                            }
                            break;
                        default:
                            data[_form_item_name] = _item.val();
                    }
                });
            }

            seoaic_close_modal(form.parents('.seoaic-modal'));

            let callback_before = form.attr('data-before-callback');

            if (callback_before !== undefined && callback_before !== '') {
                if (form_callbacks[callback_before](data)) {
                    return;
                }
            }

            //console.log(form.attr('data-callback'));

            post_ajax(data, form.attr('data-callback'));
        });

        $(document).on('change', '.bulkactions select[name="action"]', function () {
            let select = $(this);
            let val = select.val();
            if ( val === 'seoaic_translate' ) {
                select.parent().find('.multilang-box').show(0);
            } else {
                select.parent().find('.multilang-box').hide(0);
            }
        });

        $(document).on('change', '.seoaic-on-update-save-content', function () {
            $('.seoaic-save-content-idea-button').attr('data-changed', 'true');
        });

        $(document).on('change', '#idea-mass-create-all', function () {
            $('.post:not(.post-is-generating) .idea-mass-create').prop('checked', true);
        });


        let ideas_uncheck_all_handler = function() {
            $('#idea-mass-create-all').removeAttr('checked');
            $('.idea-mass-create:checked').prop('checked', false);
            $('.seoaic-flip-box').removeClass('seoaic-flip-box-flipped');
            $('.mass-effect-button .additional-form-items').empty();
        };

        $(document).on('change', '.idea-mass-create', function () {
            let checkbox = $(this);
            //let posts_credit = $('#posts-credit').val() * 1;
            let val = checkbox.val();
            let checked_len;

            if (val !== 'all') {
                let input_id = 'idea-mass-create-' + val;
                checked_len = $('.post .idea-mass-create:checked').length;

                if (checkbox.prop('checked')) {
                    let input = '<label data-id="label-' + input_id + '"><input type="checkbox" checked class="seoaic-form-item" name="idea-mass-create" value="' + val + '"> <b>#' + val + '</b> - ' + $('#idea-post-' + val).find('.td-idea-title').text() + '<label>';
                    $('.mass-effect-button .additional-form-items').append(input);
                } else {
                    $('.mass-effect-button .additional-form-items').find('[data-id="label-' + input_id + '"]').remove();
                }
            } else {
                checked_len = $('.post .idea-mass-create').length;

                $('.mass-effect-button .additional-form-items').empty();

                $('.post .idea-mass-create').each(function () {
                    let _val = $(this).val();
                    let _input_id = 'idea-mass-create-' + _val;
                    let input = '<label data-id="label-' + _input_id + '"><input type="checkbox" checked class="seoaic-form-item" name="idea-mass-create" value="' + _val + '"> <b>#' + _val + '</b> - ' + $('#idea-post-' + _val).find('.td-idea-title').text() + '<label>';
                    $('.mass-effect-button .additional-form-items').append(input);
                });
            }


            $('.seoaic-checked-amount-num').text(checked_len);

            if (checked_len == 0) {
                ideas_uncheck_all_handler();
            } else {
                $('.seoaic-flip-box').addClass('seoaic-flip-box-flipped');
            }
        });

        $(document).on('click', '.idea-mass-create-uncheck-all', function () {
            ideas_uncheck_all_handler();
        });

        $(document).on('click', '.seoaic-cancel-content-idea-button', function (e) {
            idea_content_slide_close();
        });

        $(document).on('click', '.seoaic-save-content-idea-button', function (e) {

            let button = $(this);

            $('#seoaic-admin-body').addClass('seoaic-loading');
            $('#seoaic-admin-body').removeClass('seoaic-loading-success');

            let idea_content = {
                'idea_thumbnail': $('.seoaic-idea-content-thumbnail-textarea').val(),
                'idea_thumbnail_generator': $('#seoaic-image-generator').val(),
                'idea_post_type': $('#seoaic-post-type').val(),
                'idea_category': [],
                'idea_skeleton': [],
                'idea_keywords': [],
                'idea_description': $('.seoaic-idea-content-description-textarea').val()
            };

            let idea_post_date = $('.seoaic-content-idea-box-slide .seoaic-posting-idea-date').val();

            $('#seoaic-idea-content-category').find('.seoaic-form-item:checked').each(function (i) {
                idea_content.idea_category.push($(this).val());
            });

            $('#seoaic-idea-skeleton-sortable').find('li').each(function (i) {
                idea_content.idea_skeleton.push($(this).find('> span').text());
            });

            $('#seoaic-idea-keywords').find('li').each(function (i) {
                idea_content.idea_keywords.push($(this).find('> span').text());
            });

            $('.seoaic-save-content-idea-button').attr('data-changed', 'true');

            let data = {
                'action': button.attr('data-action'),
                'id': button.attr('data-post-id'),
                'idea_content': JSON.stringify(idea_content),
                'idea_post_date': idea_post_date
            };

            post_ajax(data, button.attr('data-callback'));
        });


        $(document).on('change', '#seoaic-background-generation', function () {
            let btn = $('#posts-mass-generate-button');
            if ($(this).prop('checked') === true) {
                btn.attr('type', 'submit');
            } else {
                btn.attr('type', 'button');
            }
        });

        $(document).on('click', '.idea-page .head-buttons .seoaic-generate-posts-button, .generate-competitor-content-btn', function () {
            get_knowledge_base_list();
        });

        $(document).on('click', '#posts-mass-generate-button[type="button"]', function () {
            let button = $(this);
            let form = $('#post-mass-creation-form');
            let modal = $('#seoaic-post-mass-creation-modal');
            let mass_prompt = form.find('textarea[name="mass_prompt"]').val();
            let mass_set_thumbnail = form.find('input[name="seoaic_mass_set_thumbnail"]').val();
            //let mass_service = form.find('.mass_service > *:selected').val();

            let mass_service = [];

            $('[name="select_service"] + .select2 .select2-selection__rendered .select2-selection__choice').each(function () {
                let service = $('> span', this).text();
                mass_service.push(service);
            });
            mass_service = mass_service ? mass_service.toString() : 'not set';

            let data = {
                action: modal.find('[name="action"]').val(),
                item_id: modal.find('.seoaic-form-item[name="idea-mass-create"]:checked').first().val(),
                seoaic_post_status: modal.find('[name="seoaic_post_status"]').val(),
                'seoaic-mass-idea-date': modal.find('[name="seoaic-mass-idea-date"]').val(),
                seoaic_subtitles_min: modal.find('[name="seoaic_subtitles_range_min"]').val(),
                seoaic_subtitles_max: modal.find('[name="seoaic_subtitles_range_max"]').val(),
                seoaic_words_min: modal.find('[name="seoaic_words_range_min"]').val(),
                seoaic_words_max: modal.find('[name="seoaic_words_range_max"]').val(),
                mass_prompt: mass_prompt,
                mass_service: mass_service,
                mass_set_thumbnail: mass_set_thumbnail,
            };

            //console.log(mass_service);

            let items_len = form.find('[name="idea-mass-create"]:checked').length;

            modal.addClass('generating');
            modal.find('.generating-num-pos').text('1');
            modal.find('.generating-num-total').text(items_len);

            button.attr('disabled', 'disabled');

            generating_process();
            post_ajax(data);
        });

        const get_knowledge_base_list = () => {
            let data = {action: 'seoaic_get_knowledge_base_list'}
            let seoaicNonceValue = wp_nonce_params[data.action];
            let select_knowledge_default = $('#select_knowledge_default');
            if (seoaicNonceValue !== '') {
                data._wpnonce = seoaicNonceValue;
            }

            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: data,
                success: function (data) {
                    if (data && data.knowledges.length > 0) {
                        const html = data.knowledges.map(item => `<option value="${item.id}">${item.name}</option>`).join('');
                        select_knowledge_default.siblings().remove();
                        select_knowledge_default.after(html);
                    }
                }
            });
        }

        $(document).on('change', 'form .additional-items input.seoaic-form-item[type="checkbox"]', function () {
            let form = $(this).parents('form');
            let modal = form.parents('.seoaic-modal');
            let checked_len = form.find('.additional-items input.seoaic-form-item[type="checkbox"]:checked').length;
            modal.find('.additional-items-amount').text(checked_len);
        });

        $(document).on('change', '.seoaic_post_status', function () {
            let select = $(this);
            let val = select.val();

            if ( val === 'schedule' ) {
                select.find('+ .idea-date-picker').show(0);
            } else {
                select.find('+ .idea-date-picker:not(.visible)').hide(0);
            }
        });

        $(document).on('change', '#seoaic-image-generator', function () {
            idea_content_generator_check($(this).val());

            $('.seoaic-save-content-idea-button').attr('data-changed', 'true');
        });

        $(document).on('change', '.seoaic_multilang-input', function () {
            let box = $(this).parents('.multilang-box');
            let len = box.find('.seoaic_multilang-input:checked').length;

            if ( len > 0 ) {
                box.find('.language-label-description').addClass('language-label-description-hidden');
            } else {
                box.find('.language-label-description').removeClass('language-label-description-hidden');
            }
        });

        $('.seoaic-settings-range').each(function () {
            let slider_box = $(this);
            let slider_item = slider_box.find('.seoaic-settings-range-slider');
            let slider_min = slider_box.attr('data-min') * 1;
            let slider_max = slider_box.attr('data-max') * 1;
            let slider_step = slider_box.attr('data-step') * 1;

            slider_item.slider({
                range: true,
                min: slider_min,
                max: slider_max,
                step: slider_step,
                values: [
                    slider_box.find('.seoaic-settings-range-min').val(),
                    slider_box.find('.seoaic-settings-range-max').val()
                ],
                slide: function (event, ui) {
                    slider_box.find('.range-min').text(ui.values[0]);
                    slider_box.find('.range-max').text(ui.values[1]);
                    slider_box.find('.seoaic-settings-range-min').val(ui.values[0]);
                    slider_box.find('.seoaic-settings-range-max').val(ui.values[1]);
                }
            });
        });

        let seoaic_is_simple_generation = false;
        function init_loading_process () {

            let background_process_container = $('#seoaic-admin-generate-loader');
            if (background_process_container.length > 0 && !background_process_container.hasClass('seoaic-background-process-finished')) {

                let background_process_interval = setInterval(function () {
                    $.ajax({
                        url: ajaxurl,
                        method: 'post',
                        data: {
                            action: 'seoaic_posts_mass_generate_check_status',
                            simple_post: seoaic_is_simple_generation,
                        },
                        success: function (response) {
                            background_process_container.each(function () {
                                let _cont = $(this);
                                _cont.find('.seoaic-background-process-loader').css({'width': response.width + '%'});
                            });

                            for (let k in response.posts) {
                                $('.seoaic-background-process-p-' + response.posts[k]).addClass('seoaic-background-process-generated');
                            }

                            if (response.status === 'complete') {
                                clearInterval(background_process_interval);

                                if ( false === seoaic_is_simple_generation ) {
                                    let alert_modal = $('#seoaic-alert-modal');
                                    alert_modal.attr('data-on-close-callback', 'window_reload');
                                    seoaic_open_modal(alert_modal, response.message);
                                } else {
                                    seoaic_open_modal($('#generated-post'), response.post_content);
                                    $('#generated_post_id').val(seoaic_is_simple_generation);
                                }
                            }
                        }
                    });
                }, 30000);
            }
        }
        init_loading_process();

        $(document).on('click', '.seoaic-background-process-opener', function () {
            $(this).parents('.seoaic-admin-posts-loader').addClass('seoaic-background-process-opened');
        });
        $(document).on('click', '.seoaic-background-process-closer', function () {
            $(this).parents('.seoaic-admin-posts-loader').removeClass('seoaic-background-process-opened');
        });


        $(document).on('click', '.seoaic-change-idea-post-date-button-close', function (e) {
            $(this).parent().find('.seoaic-posting-idea-date-checkbox').prop('checked', false);
        });

        $(document).on('click', '.seoaic-change-idea-post-date-button', function (e) {
            let change = confirm('Change posting date?');
            if (change) {
                let data = {
                    'action': $(this).attr('data-action'),
                    'id': $(this).attr('data-post-id'),
                    'idea_post_date': $(this).parent().find('.seoaic-change-posting-idea-date').val(),
                };
                post_ajax(data, 'window_reload');
            }
        });


        $('.non-submit').on('keyup keypress', function (e) {
            var keyCode = e.keyCode || e.which;
            if (keyCode === 13) {
                e.preventDefault();
                return false;
            }
        });

        $(document).on('click', '.seoaic-remove-idea-post-date-button', function (e) {
            let remove = confirm('Remove idea from posting?');
            if (remove) {
                let data = {
                    'action': $(this).attr('data-action'),
                    'id': $(this).attr('data-post-id'),
                };
                post_ajax(data, 'window_reload');
            }
        });

        const modalGenerateIdeas = $('#generate-ideas');
        const modalGenerateKeywords = $('#generate-keywords');
        const modalPlan = $('#plan-modal');
        const addKeyword = $('#add-keyword');
        const closeGenerateIdeas = modalGenerateIdeas.find('.close');
        const closeGenerateKeywords = modalGenerateKeywords.find('.close');
        const closeModalPlan = modalPlan.find('.close');
        const closeAddKeyword = addKeyword.find('.close');

        $(document).on('click', '.upgrade-my-plan', function (e) {
            seoaic_open_modal(modalPlan);
        });

        closeGenerateIdeas.on('click', function () {
            modalGenerateIdeas.fadeOut(200);
        });

        closeGenerateKeywords.on('click', function () {
            modalGenerateKeywords.fadeOut(200);
        });

        closeModalPlan.on('click', function () {
            modalPlan.fadeOut(200);
        });

        closeAddKeyword.on('click', function () {
            addKeyword.fadeOut(200);
        });

        $(window).on('click', function (e) {
            if ($(e.target).hasClass('seoaic-modal-bg')) {
                $('.seoaic-modal-bg').fadeOut(200);
            }
        });

        $(document).on('change', '[name="ideas_mass_create"]', function () {
            if ($('[name="ideas_mass_create"]:checked').val() === 'yes') {
                $('[name="idea_posting_date"]').removeAttr('disabled');
            } else {
                $('[name="idea_posting_date"]').attr('disabled', 'disabled');
            }
        });

        // Send upgrade mail plan

        $(document).on('click', '#submit-plan', function () {
            let it = $(this),
                postsnum = it.closest('.seoaic-popup').find('.posts-num-input').val(),
                ideasnum = it.closest('.seoaic-popup').find('.ideas-num-input').val(),
                email = it.closest('.seoaic-popup').find('.upgrade-email').val(),
                action = it.attr('data-action')

            if (postsnum < 10 || ideasnum < 10) {
                alert('Please, enter the number bigger than 10!');
            } else if (!postsnum || !ideasnum) {
                alert('Fields cannot be empty');
            } else if (!email) {
                alert('Email cannot be empty');
            } else {

                let data = {
                    action: action,
                    'postsNum': postsnum,
                    'ideasNum': ideasnum,
                    'email': email,
                };

                $(this).attr('disabled', true);
                $('#seoaic-admin-body').addClass('seoaic-loading-no-slide');
                post_ajax(data);
                seoaic_close_modal($('.seoaic-modal'))
            }
        });

        // emil validation
        const validateEmail = (email) => {
            return email.match(
                /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
            );
        };

        const validate = () => {
            let email = $('.upgrade-email').val(),
                $result = $('.upgrade-email').next('.result'),
                submit = $('#submit-plan');
            $result.text('');

            if (validateEmail(email)) {
                $result.text('Email is valid.');
                $result.css('color', 'green');
                submit.attr('disabled', false)
            } else {
                $result.text('Email is invalid.');
                $result.css('color', 'red');
                submit.attr('disabled', true)
            }
            return false;
        }

        $('.upgrade-email').on('input', validate);


        $(document).on('change', '.seoaic-change-posting-idea-date', function () {
            let input = $(this);
            let val = input.val();
            let old_val = input.attr('data-value');

            if ('' !== val && new Date(val).getTime() > new Date(old_val).getTime()) {
                //input.find('+ .seoaic-change-idea-post-date-button').removeAttr('disabled');
            } else {
                //input.find('+ .seoaic-change-idea-post-date-button').attr('disabled', 'disabled');
            }
        });

        $(document).on('click', '.seoaic-wizard-select-keywords-button', function(e) {
            const table = $('.generated-keywords-table');
            const button = $(e.currentTarget);
            const callback = button.data('callback');
            let items = [];

            table.find('.seoaic-check-key:checked').each((i, el) => {
                items.push($(el).data('keyword'));
            });

            const data = {
                action: button.data('action'),
                item_id: items.join(',')
            };

            post_ajax(data, callback);
        });

        $(document).on('click', '.wizard-entities-type-button', function(e) {
            const button = $(e.currentTarget);
            const callback = button.data('callback');
            const type = "undefined" !== typeof button.data('type') ? button.data('type') : '';
            const entities = "undefined" !== typeof button.data('entities') ? button.data('entities') : '';

            const data = {
                action: button.data('action'),
                type: type,
                entities: entities,
            };

            $('.wizard-entities-type-button').each((i, btn) => {
                $(btn).removeClass('active');
            });
            button.addClass('active');

            post_ajax(data, callback);
        });

        $(document).on('click', '.wizard-view-post-content', (e) => {
            e.preventDefault();

            const post_id = $(e.currentTarget).data('post-id');
            const table = $('.seoaic-posts-table');
            const selected_post = table.find('.seoaic-posts-table__row-item').filter((i, el) => {
                return $(el).data('post-id') == post_id;
            });

            if (selected_post.length) {
                const post_title = selected_post.find('.seoaic-post-title').text();
                const post_content = selected_post.find('.seoaic-post-content').html();

                const modal = $('#wizard-view-post-content');
                const modal_title = modal.find('.post-title');
                const modal_content = modal.find('.post-content');

                modal_title.text(post_title);
                modal_content.html(post_content);
            }
        });

        $(function () {
            $("#seoaic-idea-skeleton-sortable").sortable({
                placeholder: "ui-state-highlight",
                change: function (event, ui) {
                    $('.seoaic-save-content-idea-button').attr('data-changed', 'true');
                }
            });

            $("#seoaic-idea-skeleton-sortable").disableSelection();
        });



        // posts mass edit
        const massActionEl = $("#mass_edit_in_progress, #mass_review_in_progress, #mass_translate_in_progress");
        if (massActionEl.length) {
            const interval = 20; // seconds

            const check_status_ajax_func = function(complete_callback) {
                const complete_callback_function = complete_callback || function() {};
                const action = massActionEl.data('action');
                const loader = $(massActionEl.data('loader'));
                const seoaicNonceValue = wp_nonce_params[action];

                $.ajax({
                    url: ajaxurl,
                    method: 'post',
                    data: {
                        action: action,
                        _wpnonce: seoaicNonceValue
                    },
                    success: function(response) {
                        if (loader.length) {
                            loader.find('.seoaic-background-process-loader').css({'width': response.width + '%'});

                            for (let k in response.done) {
                                $('.seoaic-background-process-p-' + response.done[k]).addClass('seoaic-background-process-generated');
                            }
                        }

                        if ('seoaic_posts_mass_translate_check_status' == action) {
                            for (let k in response.failed) {
                                $('.seoaic-posts-table #seoaic_post_' + response.failed[k] + ' .translating').removeClass('translating').addClass('failed');
                            }
                            for (let k in response.done) {
                                const flagLink = $('.seoaic-posts-table #seoaic_post_' + k + ' .translating.language-' + response.done[k].language);
                                flagLink.removeClass('translating');
                                flagLink.attr('href', response.done[k].href);
                            }
                        }

                        if (response.status === 'complete') {
                            complete_callback_function();
                            const alert_modal = $('#seoaic-alert-modal');
                            alert_modal.attr('data-on-close-callback', 'window_reload');
                            seoaic_open_modal(alert_modal, response.message);
                        }
                    }
                });
            };
            // check_status_ajax_func();

            const timerId = setInterval(() => {
                check_status_ajax_func(function() {
                    clearInterval(timerId);
                    // setTimeout(() => {
                    //     document.location.reload();
                    // }, 4000);
                });
            }, interval * 1000);
        }

        (function(){
            const seoaicSettings = $('#seoaic-settings');
            if (seoaicSettings.length) {
                const postTypeSelect = seoaicSettings.find('#seoaic_post_type');
                if (1 != postTypeSelect.length) {
                    return;
                }

                postTypeSelect.on('change', (e) => {
                    const data = {
                        action: 'seoaic_settings_get_post_type_templates',
                        post_type: $(e.currentTarget).val()
                    };
                    const seoaicNonceValue = wp_nonce_params[data.action];
                    if (seoaicNonceValue !== '') {
                        data._wpnonce = seoaicNonceValue;
                    }

                    $.ajax({
                        url: ajaxurl,
                        method: 'post',
                        data: data,
                        success: function (data) {
                            const postTemplateSelect = seoaicSettings.find('#seoaic_post_template');

                            if (
                                "undefined" !== typeof data.options
                                && postTemplateSelect.length
                            ) {
                                postTemplateSelect.html(data.options);
                            }
                        }
                    });
                });
            }
        })();
    });
})(jQuery);

function seoaic_open_modal(modal, _content = false) {
    if (false !== _content) {
        modal.find('.seoaic-popup__content .modal-content').html(_content);
    }

    $('#seoaic-admin-body').addClass('seoaic-blur');
    $('body').addClass('modal-show');
    modal.fadeIn(200);
}


export {seoaic_open_modal};