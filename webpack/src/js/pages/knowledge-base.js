(function ($) {
    $(document).ready(function () {
        $(document).on('click', '.show_scanning_rules', function (event) {
            event.preventDefault();
            $(this).siblings().slideToggle();
        });

        $(document).on('click', '.add-rules', function (event) {
            event.preventDefault();
            var parentElement = $(this).closest('.form-item');
            var newFormItem = parentElement.clone();
            newFormItem.find('input').val("");
            parentElement.after(newFormItem);
        });

        $(document).on('click', '.remove-rules', function (event) {
            event.preventDefault();
            let formItems = $(this).closest('.scanning_rules-content-item').find('.form-item');
            if (formItems.length > 1) {
                let parentElement = $(this).closest('.form-item');
                parentElement.remove();
            } else {
                alert("At least one item must remain.");
            }
        });

        $(document).on('click', '.data-sources-head ul li', function (event) {
            var $item = $(this).closest('.data-sources-item');
            var index = $(this).index();
        
            $item.find(".data-sources-content-wrap").removeClass("active").eq(index).addClass("active");
            $item.find(".data-sources-head ul li").removeClass("active");
            $(this).addClass("active");

            if($(this).text().toLowerCase() === 'page' || $(this).text().toLowerCase() === 'text' ) {
                $item.find(".scanning-rules").css('display', 'none')
            } else {
                $item.find(".scanning-rules").css('display', 'block')
            }
        
            $item.find('.data_source_mode').val($(this).text().toLowerCase());
        });

        const get_knowledge_base_list = () => {
            let data = {action: 'seoaic_get_knowledge_base_list'}
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
                    if (data && data.knowledges) {
                        const html = data.knowledges.map(item => `
                            <div class="row-line">
                                <div class="name"><span>${item.name}</span></div>
                                    <div class="description">${item.description}</div>
                                    <div class="status">
                                        ${item.status ? `<span class="${item.status}">${item.status}</span>` : '-'}
                                    </div>
                                    <div class="tokens">${item.tokens ? item.tokens : '-'}</div>
                                    <div class="actions">
                                        <button title="Edit An AI Knowledge Base" data-title="Edit An AI Knowledge Base" data-id="${item.id}" type="button" class="seoaic-edit ml-auto seoaic-edit_knowledge-base" data-mode="edit" data-form-callback="window_reload" data-content="Edit Idea">
                                            <div class="dn edit-form-items">
                                                <input type="hidden" name="item_id" value="${item.id}" data-label="Id">
                                            </div>
                                        </button>
                                        <button title="Remove" type="button" class="seoaic-remove seoaic-remove-kb" data-id="${item.id}"></button>
                                    </div>
                                </div>
                            `).join('');
                
                        $('#knowledge_base .row-line:not(.heading)').remove();
                        $('#knowledge_base .row-line.heading').after(html);
                        $('#seoaic-admin-body').removeClass('seoaic-loading');
                    }

                    $('#seoaic-admin-body').removeClass('seoaic-loading')
                }
            });
        }

        function seoaic_open_modal(_content = false, redirect = false) {
            let modal = $('#seoaic-alert-modal');

            if (false !== _content) {
                modal.find('.seoaic-popup__content .modal-content').html(_content);
            }

            if (redirect) {
                let currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.delete("action");
                window.location.href = currentUrl.href;
            }

            $('#seoaic-admin-body').addClass('seoaic-blur');
            $('body').addClass('modal-show');
            modal.fadeIn(200);
        }

        const create_knowledge_base = () => {
            let data = {action: 'seoaic_create_knowledge_base'}
            let seoaicNonceValue = wp_nonce_params[data.action];
            if (seoaicNonceValue !== '') {
                data._wpnonce = seoaicNonceValue;
            }

            let formData = $('#knowledge-base-form').serializeArray().reduce(function(obj, item) {
                obj[item.name] = item.value;
                return obj;
            }, {});

            data.formData = formData

            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: data,
                beforeSend: function () {
                    $('#seoaic-admin-body').addClass('seoaic-loading')
                },
                success: function (data) {
                    if (data && data.id) {
                        const currentUrl = window.location.href,
                              newUrl = currentUrl.replace("action=create", "action=edit");
                        window.location.href = newUrl + '&item_id=' + data.id;
                    }

                    if (data.status === 'error') {
                        seoaic_open_modal(data.message);
                    }

                    $('#seoaic-admin-body').removeClass('seoaic-loading')
                }
            });
        }

        $(document).on('click', '.seoaic-create-kb-button', function () {
            const currentUrl = window.location.href,
                  newUrl = currentUrl + '&action=create';

            window.location.href = newUrl;
        })

        $(document).on('click', '.seoaic-remove-kb', function () {
            const id = $(this).attr('data-id'),
            result = confirm('Do you want to remove this knowledge base?');

            if (result) {
                remove_knowledge_base(id);
            }
        });
        function remove_knowledge_base(id) {
            let data = { 
                action: 'seoaic_remove_knowledge_base',
                item_id: id
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
                    $('#seoaic-admin-body').addClass('seoaic-loading')
                },
                success: function (data) {
                    if (data && data.message) {
                        seoaic_open_modal(data.message);
                        const id = $('#knowledge_bases_id').val();
                        $('.invalid-val').remove();

                        setTimeout(() => {
                            get_knowledge_base_list();
                        }, 5000);
                    }

                    $('#seoaic-admin-body').removeClass('seoaic-loading')
                },
            });
        };

        $(document).on('click', '.seoaic-edit_knowledge-base', function () {
            const id = $(this).attr('data-id')
            const currentUrl = window.location.href,
                  newUrl = currentUrl + '&action=edit&item_id=' + id;

            window.location.href = newUrl;
        })

        $(document).on('click', '.seoaic-save-kb-button', function (event) {
            create_knowledge_base()
        })

        $(document).on('click', '.seoaic-save-kb-action', function (event) {
            const id = $('#knowledge_bases_id').val();
            save_knowledge_base(id)
        })

        const save_knowledge_base = (id) => {
            let data = {action: 'seoaic_save_knowledge_base'}
            let seoaicNonceValue = wp_nonce_params[data.action];
            if (seoaicNonceValue !== '') {
                data._wpnonce = seoaicNonceValue;
            }

            data.knowledge_id = id;

            let formData = $('#knowledge-base-form').serializeArray().reduce(function(obj, item) {
                obj[item.name] = item.value;
                return obj;
            }, {});

            data.formData = formData

            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: data,
                beforeSend: function () {
                    $('#seoaic-admin-body').addClass('seoaic-loading')
                },
                success: function (data) {
                    if (data && data.id) {
                        location.reload();
                    }

                    $('#seoaic-admin-body').removeClass('seoaic-loading')
                }
            });
        }

        function kbcheckAction() {
            const params = new URLSearchParams(window.location.search),
                  actionValue = params.get('action'),
                  id =  params.get('item_id');

            if (actionValue === 'create' || actionValue === 'edit') {
                $('.knowledge-bases .inner').css('display', 'none')
                $('.knowledge-bases .knowledge-base-content').css('display', 'block')
            }

            if (actionValue === 'create') {
                $('.knowledge-bases .inner').css('display', 'none')
            }

            if (actionValue === 'edit') {
                get_data_sources_list(id)
            }

            if (!actionValue) {
                get_knowledge_base_list();
            }
        }
        
        if($('#seoaic-admin-body').hasClass('knowledge-bases')){
            kbcheckAction()
        };

        $(document).on('click', '.add_sources', function (event) {
            event.preventDefault();
            const parentElement = $(this).closest('.data-sources-wrap').find('.data-sources-item').last();
            var newFormItem = parentElement.clone();
            newFormItem.removeClass('data-sources-item-main');
            newFormItem.find('.data_source_item').val("");
            newFormItem.find('.data_source_max_pages').val(10);
            newFormItem.find('.scanning_rules-content').css('display', 'none');
            newFormItem.find('.data-sources-head li, .data-sources-head .data-sources-content-wrap').removeClass('active');
            newFormItem.find('.data-sources-head li').first().addClass('active');
            newFormItem.find('.data-sources-head .data-sources-content-wrap').first().addClass('active');
            newFormItem.find('.invalid-val').remove();

            const includes = newFormItem.find('.scanning_rules-content-item.scanning_rules-include .form-item'),
                  excludes = newFormItem.find('.scanning_rules-content-item.scanning_rules-exclude .form-item');

            removeBlocks(includes);
            removeBlocks(excludes);

            $(this).closest('.data-source-scan').before(newFormItem);
        });

        function removeBlocks(blocks) {
            if (blocks.length > 1) {
                for (var i = 1; i < blocks.length; i++) {
                    blocks[i].parentNode.removeChild(blocks[i]);
                }
            }
        }

        $(document).on('click', '.scan_sources', function (event) {
            event.preventDefault();
            let checkUrls = true;

            const dataUrls = $('.data-sources-item .data_source_url');
            dataUrls.each(function () {
                if ($(this).val() !== "") {                    
                    if (!checkUrl($(this))) {
                        checkUrls = false
                    }
                }
            });
            
            if (checkUrls) {
                create_data_sources();
                const newItem = $('.data-sources-item').last();
                newDataSourceBlock(newItem);
            }
        })

        function newDataSourceBlock(item) {
            item.find('.data_source_item').val("");
            item.find('.scanning_rules-content').css('display', 'none');
            item.find('.data-sources-head li, .data-sources-head .data-sources-content-wrap').removeClass('active');
            item.find('.data-sources-head li').first().addClass('active');
            item.find('.data-sources-head .data-sources-content-wrap').first().addClass('active');
            item.find('.invalid-val').remove();

            const includes = item.find('.scanning_rules-content-item.scanning_rules-include .form-item'),
                  excludes = item.find('.scanning_rules-content-item.scanning_rules-exclude .form-item');

            removeBlocks(includes);
            removeBlocks(excludes);
        }

        const create_data_sources = () => {
            let data = { action: 'seoaic_save_knowledge_base_data_sources' };
            let seoaicNonceValue = wp_nonce_params[data.action];
            if (seoaicNonceValue !== '') {
                data._wpnonce = seoaicNonceValue;
            }
        
            const dataSource = {
                sources: []
            };
        
            let form = $('#data-sources');
        
            form.find('.data-sources-item').each(function () {
                let fomItem = $(this),
                    mode = fomItem.find('.data_source_mode').val(),
                    dataUrl = fomItem.find('.data-sources-content-wrap.active .seoaic-form-item-url .data_source_item'),
                    url = dataUrl.val(),
                    maxPages = fomItem.find('.data-sources-content-wrap.active .data_source_max_pages').val(),
                    id = fomItem.find('.data_source_id').val(),
                    parsedMaxPages = maxPages ? parseInt(maxPages) : 10,
                    inRules = [],
                    exRules = [],
                    sourceObject = {
                        id: id,
                        data: url,
                        maxPages: parsedMaxPages,
                        mode: mode ? mode : 'domain',
                        includeRules: [],
                        excludeRules: [],
                    };
                
                if (url) {
                    fomItem.find('.scanning-rules .form-item').each(function () {
                        let item = $(this),
                            includeRule = item.find('.include-rules').val(),
                            excludeRule = item.find('.exclude-rules').val();
            
                        if (includeRule !== undefined && includeRule !== '') {
                            inRules.push(includeRule);
                        }
                        if (excludeRule !== undefined && excludeRule !== '') {
                            exRules.push(excludeRule);
                        }
                    });
            
                    sourceObject.includeRules = inRules;
                    sourceObject.excludeRules = exRules;
            
                    dataSource.sources.push(sourceObject);
                }
            });
        
            data.dataSources = dataSource;

            const knowledge_bases_id = $('#knowledge_bases_id').val();

            data.data_id = knowledge_bases_id;

            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: data,
                beforeSend: function () {
                    $('#seoaic-admin-body').addClass('seoaic-loading')
                },
                success: function (data) {
                    if (data && data.message) {
                        $('.invalid-val').remove();
                        seoaic_open_modal('<p>' + data.message + '</p>');
                        setTimeout(() => {
                            const id = $('#knowledge_bases_id').val();
                            get_data_sources_list(id)
                            get_crawled_pages(id)
                            $('.action-knowledge-base').css('display', 'flex')
                        }, 10000);
                    }
                },
            });
        };

        function validateURL(url) {
            const urlPattern = /^https:\/\/([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,6}(\/\S*)?$/;
            return urlPattern.test(url);
        }        
    
        function checkUrl(url) {
            if (url.hasClass('data_source_url') && !validateURL(url.val()) && url.val() != '') {
                $('.invalid-val').remove();
                url.parent().after('<span class="invalid-val">The link must contain the https protocol</span>')
                return false;
            } else {
                url.closest('.form-item-url').find('.invalid-val').remove();
                return true;
            }
        }

        function get_data_sources_list(id) {
            let data = { 
                action: 'seoaic_get_data_sources_list',
                item_id: id
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
                    $('#seoaic-admin-body').addClass('seoaic-loading')
                },
                success: function (data) {
                    if (data && data.knowledge) {
                        const generatedHTML = data.knowledge.dataSources.map(item => {
                            const dataSourceHtml = `
                                <div class="data-sources-item">
                                    <input type="hidden" name="data-sources-id" class="data_source_id" value="${item.id}">
                                    <div class="actions">
                                        ${(item.status !== 'trained' && item.status !== 'training' && item.status !== 'failed') ? `<button class="seoaic-train seoaic-train_data_source confirm-modal-button" data-id="${item.id}">Train</button>` : ''}
                                        <button title="Remove" type="button" class="seoaic-remove seoaic-remove_data_source" data-id="${item.id}" data-content="Do you want to remove this Data Source?"></button>
                                    </div>
                                    <div class="data-sources-head">
                                        <ul>
                                            <li class="active">${item.mode}</li>
                                        </ul>
                                        ${item.mode === 'domain' ? `
                                        <div class="data-sources-content-wrap active">
                                            <div class="data-sources-content">
                                                <div class="form-item seoaic-form-item-url">
                                                    <label for="data-sources-domain">URL / Domain <span>Check our <a href="#">‘URL Setup Guide’</a> for step-by-step instructions and best practices.</span></label>
                                                    <div class="data-sources-domain-wrap">
                                                        <span></span>
                                                        <input type="url" name="data-sources-domain" class="data_source_item data_source_url" data-mode="domain" value="${item.data}">
                                                    </div>
                                                </div>
                                                <div class="form-item form-item-pages">
                                                    <label for="data-sources-pages">Max pages</label>
                                                    <input type="number" name="data-sources-pages" class="data_source_item data_source_max_pages" min="10" value="${item.maxPages}">
                                                </div>
                                            </div>
                                        </div>` : ''}
                                        ${item.mode === 'page' ? `
                                        <div class="data-sources-content-wrap active">
                                            <div class="data-sources-content">
                                                <div class="form-item seoaic-form-item-url">
                                                    <label for="data-sources-page">URL / Page <span>Check our <a href="#">‘URL Setup Guide’</a> for step-by-step instructions and best practices.</span></label>
                                                    <div class="data-sources-domain-wrap">
                                                        <span></span>
                                                        <input type="url" name="data-sources-page" class="data_source_item data_source_url" data-mode="page" value="${item.data}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>` : ''}
                                        ${item.mode === 'text' ? `
                                        <div class="data-sources-content-wrap active">
                                            <div class="data-sources-content">
                                                <div class="form-item seoaic-form-item-url">
                                                    <label for="data-sources-text">Text area <span>Lorem ipsum dolor sit amet consectetur. At turpis porta vulputate mauris</span></label>
                                                    <div class="data-sources-domain-wrap">
                                                        <textarea name="data-sources-text" class="data_source_item" data-mode="text" rows="4">${item.data}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>` : ''}

                                    </div>
                                    ${item.mode === 'domain' ? `
                                    <div class="scanning-rules">
                                        <a href="#" class="show_scanning_rules">Show scanning rules</a>
                                        <div class="scanning_rules-content">
                                            <div class="scanning_rules-content-wrap">
                                                <div class="scanning_rules-content-item scanning_rules-include">
                                                    <h4>Include rules: <span>Define the URL ranges OR URLs including words you want the scanner to include</span></h4>
                                                    ${item.includeRules ? generateRulesHtml(item.includeRules, 'include') : ''}
                                                </div>

                                                <div class="scanning_rules-content-item scanning_rules-exclude">
                                                    <h4>Exclude rules: <span>Define the URL ranges OR URLs including words you want the scanner to exclude</span></h4>
                                                    ${item.excludeRules ? generateRulesHtml(item.excludeRules, 'exclude') : ''}
                                                </div>
                                            </div>
                                        </div>
                                    </div>` : ''}
                                <input type="hidden" class="data_source_item data_source_mode" value="${item.mode}">
                            </div>`;

                            return { dataSourceHtml };
                        });

                        const dataSourceHtml = generatedHTML.map(item => item.dataSourceHtml);

                        $('#knowledge_bases_id').val(data.knowledge.id);
                        $('#knowledge-base-name').val(data.knowledge.name);
                        $('#knowledge-base-description').val(data.knowledge.description);
                        $('.knowledge-base-content .data-sources-item:not(.data-sources-item-main)').remove();
                        $('.knowledge-base-content .data-sources-item-main').before(dataSourceHtml);
                        $('#seoaic-admin-body').removeClass('seoaic-loading');

                        $('.data-sources-item-main').find('.data_source_item').val("");
                        $('.data-sources-item-main').find('.data_source_max_pages').val(10);

                        if (data.knowledge.dataSources.length) {
                            $('.action-knowledge-base').removeClass('hide').addClass('show');
                            get_crawled_pages(data.knowledge.id)
                        } else {
                            $('.action-knowledge-base').removeClass('show').addClass('hide');
                            $('.knowledge-base-body .status-wrap').css('display', 'none');
                        }
                    }

                    $('#seoaic-admin-body').removeClass('seoaic-loading')
                },
            });
        };

        function get_crawled_pages(id, page, search = '', status = '') {
            let data = { 
                action: 'seoaic_get_crawled_pages',
                item_id: id,
                page: page,
                searchBy: search,
                status: status
            };
            let seoaicNonceValue = wp_nonce_params[data.action];
            if (seoaicNonceValue !== '') {
                data._wpnonce = seoaicNonceValue;
            }
        
            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: data,
                // beforeSend: function () {
                //     $('#seoaic-admin-body').addClass('seoaic-loading');
                // },
                success: function (data) {
                    console.log(data);
                    if (data && data.sources) {
                        const id = $('#knowledge_bases_id').val();
                        if (data.pages < 1 || data.statuses.hasOwnProperty('training') || data.statuses.hasOwnProperty('scanning') ) {
                            setTimeout(() => {
                                get_crawled_pages(id);
                            }, 5000);
                        }

                        let statusHtml = '';
                        if (data.statuses) {
                            const statusObjHTML = Object.keys(data.statuses).map(key => {
                                const val = data.statuses[key];
                                statusHtml += `<span class="${key}" data-status="${key}">${key}: ${val}</span>`;
                            });
                            $('.knowledge-base-body .status-wrap').css('display', 'flex');
    
                            $('.knowledge-base-body .status-wrap .status').html(statusHtml);
    
                            const generatedHTML = data.sources.map(item => {
                                const updatedAtDate = new Date(item.updated_at);
                                const year = updatedAtDate.getFullYear();
                                const month = updatedAtDate.getMonth() + 1;
                                const day = updatedAtDate.getDate();
                                const hours = updatedAtDate.getHours();
                                const minutes = updatedAtDate.getMinutes();
                                const seconds = updatedAtDate.getSeconds();
                                let disabled = '';
                                
                                const formattedDate = `${year}-${month < 10 ? '0' + month : month}-${day < 10 ? '0' + day : day} ${hours < 10 ? '0' + hours : hours}:${minutes < 10 ? '0' + minutes : minutes}:${seconds < 10 ? '0' + seconds : seconds}`;
    
                                if (data.pages < 1 || data.statuses.hasOwnProperty('training') || data.statuses.hasOwnProperty('scanning') ) {
                                    disabled = 'disabled';
                                }

                                const datSourceAiHtml = `
                                    <div class="row-line ${item.status}"">
                                        <div class="name">
                                            <div class="checkbox-wrapper-mc">
                                                <input id="source-item-${item.id}" type="checkbox" class="source-item" ${disabled} name="source-item" value="${item.id}">
                                                <label for="source-item-${item.id}" class="check">
                                                    <div class="checkbox-wrapper-svg">
                                                        <svg width="18px" height="18px" viewBox="0 0 18 18">
                                                            <path d="M1,9 L1,3.5 C1,2 2,1 3.5,1 L14.5,1 C16,1 17,2 17,3.5 L17,14.5 C17,16 16,17 14.5,17 L3.5,17 C2,17 1,16 1,14.5 L1,9 Z"></path>
                                                            <polyline points="1 9 7 14 15 4"></polyline>
                                                        </svg>
                                                    </div>
                                                </label>
                                            </div>
                                            <span>${item.page_url}</span>
                                        </div>
                                        <div class="status">${item.status ? `<span class="${item.status}" title="${item.filedReason}">${item.status}</span>` : '-'}</div>
                                        <div class="description">${formattedDate}</div>
                                        <div class="tokens">${item.tokens ? item.tokens : '-'}</div>
                                        <div class="actions">
                                            <button class="seoaic-rescan seoaic-rescan-source-item" data-id="${item.id}">Rescan</button>
                                            <button title="Remove" type="button" class="seoaic-remove seoaic-remove-source-item" data-id="${item.id}" data-content="Do you want to remove this Data Source?"></button>
                                        </div>
                                    </div>
                                `;
    
                                return { datSourceAiHtml };
                            });
    
                            const datSourceAiHtml = generatedHTML.map(item => item.datSourceAiHtml);
    
                            $('.knowledge-base-content .row-line:not(.heading)').remove();
                            $('.knowledge-base-content .row-line.heading').after(datSourceAiHtml);
                            $('#seoaic-admin-body').removeClass('seoaic-loading');
    
                            pagination(data.page, data.pages)
                        }
                    }

                    //$('#seoaic-admin-body').removeClass('seoaic-loading')
                },
            });
        };

        function pagination(page, pages) {
            const paginationContainer = document.getElementById("pagination-container");
            paginationContainer.innerHTML = "";
        
            const firstLink = document.createElement("a");
            firstLink.href = "javascript:void(0);";
            firstLink.classList.add("prev", "page-numbers");
            firstLink.textContent = "First";
            firstLink.setAttribute("data-page", 1);
            if (page > 1) {
                paginationContainer.appendChild(firstLink);
            }
        
            const pagesToShow = 4;
            const currentPageGroup = Math.ceil(page / pagesToShow);
            const totalGroups = Math.ceil(pages / pagesToShow);
        
            const startPage = Math.max(1, page - pagesToShow);
            const endPage = Math.min(page + pagesToShow, pages);
        
            for (let i = startPage; i <= endPage; i++) {
                const pageNumber = i;
                const pageLink = createPageLink(pageNumber, page);
                paginationContainer.appendChild(pageLink);
            }
        
            const lastLink = document.createElement("a");
            lastLink.href = "javascript:void(0);";
            lastLink.classList.add("next", "page-numbers");
            lastLink.textContent = "Last";
            lastLink.setAttribute("data-page", pages);
            if (page < pages) {
                paginationContainer.appendChild(lastLink);
            }
        }
        
        function createPageLink(pageNumber, currentPage) {
            const pageLink = document.createElement("a");
            pageLink.href = "javascript:void(0);";
            pageLink.classList.add("page-numbers");
            pageLink.textContent = pageNumber;
            pageLink.setAttribute("data-page", pageNumber);
        
            if (pageNumber === currentPage) {
                pageLink.classList.add("current");
            }
        
            return pageLink;
        }
        
        $(document).on('click', '#pagination-container a', function (event) {
            event.preventDefault();
        
            const id = $('#knowledge_bases_id').val(),
                page = $(this).attr('data-page'),
                search = $('.data_search').val();
        
            get_crawled_pages(id, page, search);
        });

        $(document).on('click', '.seoaic_data_source_search', function (event) {
            event.preventDefault();
        
            const id = $('#knowledge_bases_id').val(),
                search = $(this).closest('.search').find('.data_search').val();
        
            get_crawled_pages(id, 1, search);
        });

        $(document).on('click', '.data-item-actions .status span', function (event) {
            event.preventDefault();
        
            const id = $('#knowledge_bases_id').val(),
                status = $(this).attr('data-status'),
                search = $(this).closest('.data-item-actions').find('.data_search').val();
        
            get_crawled_pages(id, 1, search, status);
        });

        function generateRulesHtml(rules, anchor) {
            const ruleItemHtml = `
                <div class="form-item">
                    <input type="text" name="${anchor}_rules" class="${anchor}-rules" value="%value%">
                    <button class="add-rules" data-rules="${anchor}"></button>
                    <button class="remove-rules" data-rules="${anchor}"><span></span></button>
                </div>
            `;
        
            if (!rules || rules.length === 0) {
                return ruleItemHtml.replace('%value%', '');
            }
        
            const ruleHtml = rules.map(rule => ruleItemHtml.replace('%value%', rule)).join('');
        
            return ruleHtml;
        }
        
        // New ajax
        $(document).on('click', '.seoaic-rescan_data_source', function () {
            const id = $('#knowledge_bases_id').val(),
                  data_id = $(this).attr('data-id');

            rerun_data_source(id, data_id);
        });
        function rerun_data_source(id, data_id) {
            let data = { 
                action: 'seoaic_rerun_data_source',
                item_id: id,
                data_id: data_id
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
                    $('#seoaic-admin-body').addClass('seoaic-loading')
                },
                success: function (data) {
                    if (data && data.message) {
                        seoaic_open_modal(data.message);
                        const id = $('#knowledge_bases_id').val();
                        $('.invalid-val').remove();

                        setTimeout(() => {
                            get_data_sources_list(id)
                            get_crawled_pages(id)
                        }, 5000);
                    }

                    $('#seoaic-admin-body').removeClass('seoaic-loading')
                },
            });
        };

        $(document).on('click', '.seoaic-remove_data_source', function () {
            const id = $('#knowledge_bases_id').val(),
                  data_id = $(this).attr('data-id'),
                  result = confirm('Do you want to remove this data source?');

            if (result) {
                remove_data_source(id, data_id);
            }
        });
        function remove_data_source(id, data_id) {
            let data = { 
                action: 'seoaic_remove_data_source',
                item_id: id,
                data_id: data_id
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
                    $('#seoaic-admin-body').addClass('seoaic-loading')
                },
                success: function (data) {
                    if (data && data.message) {
                        seoaic_open_modal(data.message);
                        const id = $('#knowledge_bases_id').val();
                        $('.invalid-val').remove();

                        setTimeout(() => {
                            get_data_sources_list(id)
                            get_crawled_pages(id)
                        }, 500);
                    }

                    $('#seoaic-admin-body').removeClass('seoaic-loading')
                },
            });
        };

        $(document).on('click', '.seoaic-train_data_source', function () {
            const id = $('#knowledge_bases_id').val(),
                  data_id = $(this).attr('data-id');

                  train_data_source(id, data_id);
        });
        function train_data_source(id, data_id) {
            let data = { 
                action: 'seoaic_train_data_source',
                item_id: id,
                data_id: data_id
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
                    $('#seoaic-admin-body').addClass('seoaic-loading')
                },
                success: function (data) {
                    if (data && data.message) {
                        seoaic_open_modal(data.message);
                        const id = $('#knowledge_bases_id').val();
                        $('.invalid-val').remove();

                        setTimeout(() => {
                            get_data_sources_list(id)
                            get_crawled_pages(id)
                        }, 5000);
                    }

                    $('#seoaic-admin-body').removeClass('seoaic-loading')
                },
            });
        };

        $(document).on('click', '.seoaic-train-kb-button', function () {
            const id = $('#knowledge_bases_id').val();
            train_knowledge_base(id);
        });
        function train_knowledge_base(id) {
            let data = { 
                action: 'seoaic_train_knowledge_base',
                item_id: id
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
                    $('#seoaic-admin-body').addClass('seoaic-loading')
                },
                success: function (data) {
                    if (data && data.message) {
                        seoaic_open_modal('<p>' + data.message + '</p>');
                        const id = $('#knowledge_bases_id').val();
                        $('.invalid-val').remove();

                        setTimeout(() => {
                            get_data_sources_list(id)
                            get_crawled_pages(id)
                        }, 5000);
                    }

                    $('#seoaic-admin-body').removeClass('seoaic-loading')
                },
            });
        };

        $(document).on('click', '.seoaic-rescan-kb-button', function () {
            const id = $('#knowledge_bases_id').val();
            rerun_knowledge_base(id);
        })
        function rerun_knowledge_base(id) {
            let data = { 
                action: 'seoaic_rerun_knowledge_base',
                item_id: id
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
                    $('#seoaic-admin-body').addClass('seoaic-loading')
                },
                success: function (data) {
                    if (data && data.message) {
                        seoaic_open_modal(data.message);
                        const id = $('#knowledge_bases_id').val();
                        $('.invalid-val').remove();

                        setTimeout(() => {
                            get_data_sources_list(id)
                            get_crawled_pages(id)
                        }, 5000);
                    }

                    $('#seoaic-admin-body').removeClass('seoaic-loading')
                },
            });
        };

        $(document).on('click', '#data-source-item-remove', function () {
            const ids = getSelectedSources();

            let result = confirm('Do you want to remove this data source items?');

            if (result) {
                dataSourceItemActions(ids, 'seoaic_remove_sources');
            }            
        })

        $(document).on('click', '.seoaic-remove-source-item', function () {
            const id = $(this).attr('data-id');

            let checkboxValues = [];
            checkboxValues.push(id);

            let result = confirm('Do you want to remove this data source item?');

            if (result) {
                dataSourceItemActions(checkboxValues, 'seoaic_remove_sources');
            }
        })

        $(document).on('click', '.seoaic-rescan-source-item', function () {
            const id = $(this).attr('data-id');

            let checkboxValues = [];
            checkboxValues.push(id);

            dataSourceItemActions(checkboxValues, 'seoaic_rerun_sources');
        })

        // $(document).on('click', '#data-source-item-rescan', function () {
        //     const ids = getSelectedSources();

        //     dataSourceItemActions(ids, '');
        // })

        const dataSourceItemActions = (ids, action) => {
            let data = { action: action };
            let seoaicNonceValue = wp_nonce_params[data.action];
            if (seoaicNonceValue !== '') {
                data._wpnonce = seoaicNonceValue;
            }
            
            if (ids) {
                data.ids = {
                    sourceIds: ids
                };
            }

            const knowledge_bases_id = $('#knowledge_bases_id').val();

            data.item_id = knowledge_bases_id;
        
            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: data,
                success: function (data) {
                    if (data && data.message) {
                        seoaic_open_modal(data.message);
                        const id = $('#knowledge_bases_id').val();
                        $('.invalid-val').remove();

                        setTimeout(() => {
                            get_crawled_pages(id)
                        }, 500);
                    }
                },
            });
        };

        function getSelectedSources() {
            const checkedCheckboxes = document.querySelectorAll('.knowledge-base-data-source-item .flex-table .source-item:checked');

            let checkboxValues = [];
            checkedCheckboxes.forEach(function (checkbox) {
                checkboxValues.push(checkbox.value);
            });

            return checkboxValues;
        }

        $(document).on('change', '#source-item-all', function () {
            if ($(this).prop('checked')) {
                $('.source-item').prop('checked', true);
            } else {
                $('.source-item').prop('checked', false);
            }
        });
        
    });
})(jQuery);