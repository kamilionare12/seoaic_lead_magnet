import ApexCharts from '../../js/components/apexcharts'
import {activateActionButton} from "./competitors";
import {select_all} from "./competitors";

$(document).ready(function () {
    getCompetitorsData()
    compare_competitors_terms()
    unSelect()
    getSelectedCompetitors()
    //rankHistory()
    addClassToLastSubTerm()
    selectAllSubItems()
    comparePosition()
    checkSelectedKeywords()
    termsSorting()
    FilterLocationData()
    filterTerms()
    collapseButton()
    addSearchTerms()
    checkUpdateAvailable()
    postedPostsModal()
    disableNoRankedKeywordsGenerate()
    rankHistory()
    // activateActionButton(
    //     '.top button.seoaic-remove-main',
    //     'data-keyword',
    //     'data-post-id',
    //     '.seoaic-check-key, [name="seoaic-select-all-keywords"]',
    //     '.search-terms-list',
    //     1,
    //     true)

    // Competitors modal article analysis
    get_My_Article_Top_Table_Popup()
    competitor_Article_Popup_Table_Analysis()
    toggleMyArticleInfo()
    toggleCompetitor()
    menu_tabs()
    run_charts()
})

// Main functions
const get_positions_dates = (dates = false, el, term_history = false, traffic = false) => {
    let data = JSON.parse(el.attr('data-charts')),
        positions = [],
        traffics = [],
        dates_array = [],
        pos_1_3 = [],
        pos_4_10 = [],
        pos_11_30 = [],
        pos_31_50 = [],
        pos_51_100 = [],
        etv = [],
        impressions_etv = [],
        estimated_paid_traffic_cost = []

    if (term_history) {
        return data
    }

    if (!data) {
        return
    }

    Object.keys(data.reverse()).forEach(function (element) {
        let date = new Date('1-' + data[element].month + '-' + data[element].year),
            monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            month = monthNames[data[element].month - 1],
            year = date.getFullYear(),
            formattedDate = `${month}, ${year}`

        dates_array.push(formattedDate)

        pos_1_3.push(data[element].metrics.pos_1_3 ? data[element].metrics.pos_1_3 : 0)
        pos_4_10.push(data[element].metrics.pos_4_10 ? data[element].metrics.pos_4_10 : 0)
        pos_11_30.push(data[element].metrics.pos_11_30 ? Math.floor(data[element].metrics.pos_11_30) : 0)
        pos_31_50.push(data[element].metrics.pos_31_50 ? data[element].metrics.pos_31_50 : 0)
        pos_51_100.push(data[element].metrics.pos_51_100 ? data[element].metrics.pos_51_100 : 0)

        etv.push(data[element].metrics.etv ? Math.floor(data[element].metrics.etv) : 0)
        impressions_etv.push(data[element].metrics.impressions_etv ? Math.floor(data[element].metrics.impressions_etv) : 0)
        estimated_paid_traffic_cost.push(data[element].metrics.estimated_paid_traffic_cost ? Math.floor(data[element].metrics.estimated_paid_traffic_cost) : 0)
    });

    positions.push(
        {name: '1-3', data: pos_1_3, color: "#3538FE"},
        {name: '4-10', data: pos_4_10, color: "#5D78F7"},
        {name: '11-30', data: pos_11_30, color: "#9089FB"},
        {name: '31-50', data: pos_31_50, color: "#A551F6"},
        {name: '51-100', data: pos_51_100, color: "#FF4893"}
    );

    traffics.push(
        {name: 'Traffic volume', data: etv, color: "#3538FE"},
        {name: 'Impressions', data: impressions_etv, color: "#5D78F7"},
        {name: 'Traffic value ($)', data: estimated_paid_traffic_cost, color: "#9089FB"},
    );

    return dates ? dates_array : traffic ? traffics : positions;
}

const charts = (data, dates, type_chart, modal, height = 580, font_size = '14px') => {
    const font = {
        fontSize: font_size,
        fontWeight: 'bold',
        fontFamily: 'Archivo',
        colors: '#100717',
    }

    const options = {
        series: [],
        chart: {
            height: height,
            type: 'area',
            zoom: {
                enabled: false
            },
            stacked: true,
            toolbar: {
                show: false
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            width: 2,
            curve: 'smooth'
        },
        title: {
            text: 'Positions',
            align: 'left',
            style: font,
            margin: 30,
            offsetX: 0,
            offsetY: -15,
        },
        legend: {
            tooltipHoverFormatter: function (val, opts) {
                return val
            },
            position: 'top',
            margin: 50,
            offsetX: -35,
            offsetY: -15,
            horizontalAlign: 'left',
            fontSize: font_size,
            fontWeight: 'bold',
            fontFamily: 'Archivo',
            color: '#100717',
            itemMargin: {
                horizontal: 16.5,
                vertical: 0
            },
        },
        markers: {
            size: 0,
            hover: {
                sizeOffset: 6
            }
        },
        xaxis: {
            type: '',
            categories: [],
            labels: {
                style: font
            },
            axisBorder: {
                show: true,
                color: 'rgba(16, 7, 23, 0.15)',
                height: 1,
                width: '100%',
                offsetX: 0,
                offsetY: 0
            },
            axisTicks: {
                show: true,
                borderType: 'solid',
                color: '#000',
                height: 7,
                offsetX: 0,
                offsetY: -4
            },
            tooltip: {
                enabled: false,
            },
        },
        yaxis: {
            labels: {
                formatter: (val) => {
                    return Math.floor(val)
                },
                style: font
            },
            axisBorder: {
                show: true,
                color: 'rgba(16, 7, 23, 0.15)',
                offsetX: 0,
                offsetY: 0
            },
        },
        tooltip: {
            shared: true,
            style: font,
            y: {
                formatter: function (value) {
                    return value
                }
            }
        },
        grid: {
            borderColor: 'rgba(16, 7, 23, 0.15)',
        }
    };

    if (dates) {
        options['xaxis']['categories'] = dates
        options['xaxis']['type'] = 'categories'
    } else {
        options['xaxis']['type'] = 'datetime'
    }

    let chart_term_positions = new ApexCharts(document.querySelector(modal), options);
    chart_term_positions.render();

    const run_graphs = (ths) => {
        let modal = ths.attr('data-chart-id'),
            type_chart = ths.attr('data-chart-type'),
            term_history = false,
            traffic = false

        if (ths.is('[data-chart-id="#chart_term_positions"]')) {
            term_history = true
        }

        if (ths.is('[data-chart-id="#chart_competitors_positions"].traffic')) {
            traffic = true
        }

        let dates = get_positions_dates(true, ths, term_history, traffic),
            data = get_positions_dates(false, ths, term_history, traffic)

        if (ths.is('[data-chart-id="#chart_term_positions"]')) {
            dates = false
        }

        if (ths.is('[data-chart-id="#chart_competitors_positions"]:not(.tab)')) {
            let tabs = $('#seoai-graphs-tabs'),
                modal_title = tabs.closest('.seoaic-modal').find('.seoaic-popup__header h3'),
                positions_tab = tabs.find('.positions'),
                traffic_tab = tabs.find('.traffic'),
                data_positions = ths.attr('data-charts'),
                data_traffic = ths.attr('data-charts')

            positions_tab.addClass('active')
            traffic_tab.removeClass('active')
            positions_tab.attr('data-charts', data_positions)
            traffic_tab.attr('data-charts', data_traffic)
            modal_title.html('Ranking Positions')
        }

        if (ths.is('[data-chart-id="#chart_competitors_positions"].tab')) {
            let tabs = $(modal),
                modal_title = tabs.closest('.seoaic-modal').find('.seoaic-popup__header h3'),
                title = ths.text()

            modal_title.html(title)
        }

        chart_term_positions.updateOptions({
            series: data,
        }, true, false, false)

        if (dates) {
            chart_term_positions.updateOptions({
                xaxis: {
                    type: 'categories',
                    categories: dates,
                },
                title: {
                    text: '',
                },
                tooltip: {
                    shared: true
                }
            })
        } else {
            chart_term_positions.updateOptions({
                xaxis: {
                    type: 'datetime'
                },
                markers: {
                    size: 5,
                    hover: {
                        size: 5
                    }
                },
            })
        }

        if ($(chart_term_positions.el).attr('id') === 'dashboard_chart_positions') {
            chart_term_positions.updateOptions({
                chart: {
                    offsetY: -25,
                },
                legend: {
                    offsetY: 5,
                },
            })
        }

    }

    $(document).on('click', '[data-charts]', function () {
        run_graphs($(this))
    })

    const dashboard_graph_run = () => {
        let dashboard_ranking = $('#dashboard_chart_positions')
        if (dashboard_ranking[0]) {
            run_graphs(dashboard_ranking)
        }
    }
    dashboard_graph_run()
}

const dashboard_tabs = (a) => {
    let menu = $('.dashboard-page .menu-section-seoai')
    let positions = menu.find('a.positions')
    let traffic = menu.find('a.traffic')
    if (a.is('.positions')) {
        positions.addClass('checked')
        traffic.removeClass('checked')
    } else {
        traffic.addClass('checked')
        positions.removeClass('checked')
    }
}

const menu_tabs = () => {
    let tabs = '.menu-section-seoai'

    $(tabs).on('click', 'a', function (e) {
        e.preventDefault()
        let a = $(this).closest('ul').find('a')

        a.removeClass('checked')
        $(this).addClass('checked')
        dashboard_tabs($(this))
    })
}

const run_charts = () => {
    charts([], [], 'area', '#chart_competitors_positions', 580)
    charts([], '', 'area', '#chart_term_positions', 580)
    const dashboard_chart = () => {
        charts([], [], 'area', '#dashboard_chart_positions', 290, '12px')
    }
    if ($('#dashboard_chart_positions')[0]) {
        dashboard_chart()
        $(document).ajaxSuccess((event, xhr, settings) => {
            if (settings.data && settings.data.startsWith('action=seoaic_run_dashboard_data')) {
                dashboard_chart()
                menu_tabs()
            }
        });
    }
}

const getAverage = (el_class) => {
    let table_article = $('.competitor-article'),
        table_average = $('.averages'),
        average_el = table_average.find('.' + el_class),
        el = table_article.find('.' + el_class + ':not([data-val="—"])'),
        el_percentage = table_article.find('.' + el_class + ':not([data-val-percentage="—"])'),
        el_backlinks = table_article.find('.' + el_class + ':not([data-val-backlinks="—"])'),
        el_length = el.length,
        el_length_percentage = el.length,
        el_length_backlinks = el_backlinks.length

    let sum_percentage = 0
    if (el_class === 'keyword-density') {
        el_percentage.each(function () {
            if ($(this)) {
                sum_percentage += parseFloat($(this).attr('data-val-percentage'));
            }
        })
    }

    let sum_backlinks = 0
    if (el_class === 'paragraphs') {
        el_backlinks.each(function () {
            if ($(this)) {
                sum_backlinks += parseFloat($(this).attr('data-val-backlinks'));
            }
        })
    }

    let sum = 0;
    el.each(function () {
        sum += parseInt($(this).text());
    })

    let el_average = Math.round(sum / el_length),
        el_average_percentage = sum_percentage / el_length_percentage,
        percentage = el_class === 'keyword-density' ? ' <small>(' + parseFloat(el_average_percentage.toFixed(2)) + '%)</small>' : '',

        rank_help = el_class === 'paragraphs' ? '<span class="help">Domain Rank: ' + el_average + '</span> ' : '',
        backlinks_help = el_class === 'paragraphs' ? '<span class="help">Backlinks: ' + sum_backlinks / el_length_backlinks + '</span> ' : '',
        help = rank_help || backlinks_help ? '<div class="help-wrap">' + rank_help + backlinks_help + '</div> ' : '',
        backlinks = el_class === 'paragraphs' ? ' <small>(' + shortNumber(sum_backlinks / el_length_backlinks) + ')</small>' + help : '';

    average_el.html(el_average ? el_average + percentage + backlinks : '—')
}

const resetAverages = () => {
    let table_average = $('.averages > .row-line > *')
    table_average.each(function () {
        $(this).html('—')
    })
}

const getAllAverages = () => {
    getAverage('h1-titles')
    getAverage('h2-titles')
    getAverage('sentences')
    getAverage('keyword-density')
    getAverage('words')
    getAverage('paragraphs')
    getAverage('readability')
}

const get_My_Article_Top_Table_Popup = () => {
    $(document).on('click', '[data-action="seoaic_get_search_term_competitors"]', function (e) {
        const run_ajax = () => {
            let ths = $(this),
                index = ths.attr('data-index'),
                data_id = ths.attr('data-id'),
                action = 'seoaic_my_article_popup_top_table_analysis',
                content = ths.closest('body').find('#add-competitors').find('.top'),
                data = {
                    action: action,
                    index: index ? index : data_id
                }
            if (data_id) {
                data['keyword_serp'] = true
            }

            ajaxValidation(data)
            resetAverages()

            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: data,
                beforeSend: function () {

                },
                success: function (data) {
                    content.html(data)
                    toggleMyArticleInfo()
                    openerInfo()
                },
                complete: function (data) {

                }
            });
        }

        $(document).on("ajaxComplete", function (event, xhr, settings) {
            if (settings.data && settings.data.startsWith('action=seoaic_get_search_term_competitors')) {
                run_ajax();
            }
        });
    })
}

const set_competitor_analysis_toggle = (modal = $('#add-competitors')) => {

    let competitor_articles = modal.find('.content-table .competitor-article .row-line')

    competitor_articles.each(function () {
        let index = $(this).attr('data-index'),
            competitor = modal.find('.content-table .body [data-index="' + index + '"] .domain .inner'),
            toggle_length = competitor.find('.toggle').length

        if (!toggle_length) {
            competitor.append('<div class="toggle"></div>')
        }

    })

}

const ajax_competitor_analysis = (data, content_section, load_more_btn, index) => {

    ajaxValidation(data)

    console.log('load_more_btn ' + load_more_btn)
    $.ajax({
        url: ajaxurl,
        method: 'post',
        data: data,
        beforeSend: function () {
            content_section.addClass('loading')
            $('#add-competitors').find('.load-more-btn').attr('data-index', index)
            $('#add-competitors').find('.load-more-btn').attr('data-load-more', index)
        },
        success: function (data) {
            load_more_btn.before(data);
            getAllAverages()
            set_competitor_analysis_toggle()
        },
        complete: function () {
            content_section.removeClass('loading')
        }
    });
}
const competitor_Article_Popup_Table_Analysis = () => {
    $(document).on('click', '.load-more-btn, [data-modal="#add-competitors"]', function (e) {

        e.preventDefault()

        let ths = $(this),
            index = ths.attr('data-index'),
            data_id = ths.attr('data-id'),
            load_more = ths.attr('data-load-more'),
            action = 'seoaic_competitor_article_popup_table_analysis',
            content = ths.closest('body').find('#add-competitors').find('.competitor-article'),
            load_more_btn = content.find('.load-more-btn'),
            term_keyword = $(this).attr('data-term-keyword'),
            keyword_name = $(this).closest('.row-line-container').find('.keyword > span').text(),
            keyword = keyword_name ? keyword_name : term_keyword

        if (ths.is('.load-more-btn')) {
            let
                data = {
                    action: action,
                    index: index,
                    load_more: load_more,
                    term_keyword: keyword
                }
            if (data_id) {
                data['keyword_serp'] = true
            }

            ajax_competitor_analysis(data, content, load_more_btn, index)
        }

        if (ths.is('[data-modal="#add-competitors"]')) {
            content.find('.row-line, .article-info').remove()
            load_more_btn.attr('data-term-keyword', keyword)
            load_more_btn.attr('data-index', data_id)
            load_more_btn.attr('data-load-more', data_id)
            if (data_id) {
                load_more_btn.attr('data-id', data_id)
            }
        }

    })
}

const compare_competitors_terms = () => {
    $(document).on('click', 'a[data-action="seoaic_compare_competitors_term"]', function (e) {
        e.preventDefault();

        const btn = $(this);
        const modal = $(btn.attr('data-modal'));
        const table = modal.find('.content-table');
        const other_positions = table.next('.other-top-5-positions')
        const competitors = btn.closest('.table-competitors-compare').find('.heading .col.website');
        const competitors_table = table.find('.body');
        const competitors_analysis = table.find('.competitor-article');
        const load_more = modal.find('.load-more-btn');
        const my_analysis = modal.find('.top .flex-table');
        const location = btn.closest('.table-competitors-compare').find('.heading [data-location]').attr('data-location');
        const keyword = btn.attr('data-keyword');
        const heading = modal.find('.heading span');
        //const action = btn.attr('data-action');
        const actions = ['seoaic_compare_my_article', 'seoaic_compare_my_competitors', 'seoaic_compare_analysis', 'seoaic_compare_other_positions'];
        let competitors_array = []

        $(modal).addClass('second-inner-modal')

        other_positions.remove()

        competitors.each(function () {
            competitors_array.push($(this).attr('data-website'))
        })

        heading.text(keyword);
        let loading = '<div class="table-row"><div>Loading Data...</div></div>'
        competitors_table.html(loading);
        my_analysis.html(loading);
        competitors_analysis.addClass('loading');
        load_more.show()

        actions.forEach((action) => {

            const data = {
                action: action,
                keyword: keyword,
                location: location
            };

            if (competitors_array) {
                data['competitors'] = competitors_array
            }

            ajaxValidation(data)

            let make_queue = $.ajaxQueue({
                url: ajaxurl,
                method: 'post',
                data: data,
                beforeSend: function () {
                    getAllAverages()
                },
                success: function (data) {

                    if (action === 'seoaic_compare_my_article') {

                        my_analysis.html(data)

                    }

                    if (action === 'seoaic_compare_my_competitors') {

                        competitors_table.html(data);
                        toggleMyArticleInfo()
                        openerInfo()

                    }

                    if (action === 'seoaic_compare_analysis') {

                        competitors_analysis
                            .removeClass('loading')
                            .prepend(data);
                        set_competitor_analysis_toggle()
                        load_more.hide()
                        table.after('<div class="other-top-5-positions"><div class="loader-ellipsis medium"></div></div>')

                    }

                    if (action === 'seoaic_compare_other_positions') {

                        table.next('.other-top-5-positions').remove()
                        table.after(data)

                    }
                },
                complete: function () {
                    getAllAverages()
                    get_top_google_analysis(keyword, location, modal)
                    if ($(modal).is(":hidden")) {
                        make_queue.abort();
                        make_queue.clearQueue();
                    }
                }
            });
        });
    })
}

const get_top_google_analysis = (keyword, location, modal) => {

    const competitors_section = $('.other-top-5-positions .body')
    const analysis_section = $('.other-top-5-positions .competitor-article')
    const other_positions = $('.other-top-5-positions .body .table-row .domain .inner > span')
    const competitor_analysis_load = $('.other-top-5-positions .competitor-article .load-more-btn')
    let competitors = [];
    other_positions.each(function () {
        competitors.push({
            competitor: $(this).text(),
            index: $(this).closest('[data-index]').attr('data-index')
        })
    })

    const other_position_length = competitors.length
    competitors.forEach((competitor, i) => {
        const data = {
            action: 'seoaic_get_top_google_analysis',
            keyword: keyword,
            location: location,
            competitor: competitor['competitor']
        };

        ajaxValidation(data)

        let make_queue = $.ajaxQueue({
            url: ajaxurl,
            method: 'post',
            data: data,
            success: function (data) {
                competitor_analysis_load.before(data)
                competitors_section.find('[data-index="' + competitor['index'] + '"] .domain .inner .toggle').remove()
                competitors_section.find('[data-index="' + competitor['index'] + '"] .domain .inner').append('<div class="toggle"></div>')
                let analysis = analysis_section.find('.article-analysis').last()
                analysis.attr('data-index', competitor['index'])

                if (i + 1 === other_position_length) {
                    competitor_analysis_load.remove()
                }
            },
            complete: function () {
                if ($(modal).is(":hidden")) {
                    make_queue.clearQueue();
                }
            }
        });
    });
}

const getCompetitorsData = () => {
    $(document).on('click', '[data-action="seoaic_get_search_term_competitors"], [data-action="seoaicGetKeywordSuggestions"]', function (e) {

        e.preventDefault();
        const btn = $(this);
        const generate_terms = '[data-action="seoaicGetKeywordSuggestions"]';
        const generate_competitors = '[data-action="seoaic_get_search_term_competitors"]';
        const modalID = $(btn.attr('data-modal'));
        const submitBTN = modalID.find('button.seoaic-modal-close');
        const table = modalID.find('.table');
        const keywordTitle = modalID.find('.heading>p>span');
        const slug = btn.attr('data-keyword');
        const data_id = btn.attr('data-id');
        const keyword_index = btn.attr('data-index');
        const location = btn.attr('data-location');
        const keyword = btn.closest('.row-line-container').find('.keyword span').text();
        const keyword_title = keyword ? keyword : btn.closest('.row-line').find('.keyword span').text();
        const action = btn.attr('data-action');

        let data = {
            action: action,
            keyword: keyword_index
        };

        if (btn.is(generate_terms)) {
            data['keyword'] = slug
            data['location'] = btn.closest('.row-line').find('.rank-location').attr('data-location-slug')
        }

        if (data_id) {
            data['data_id'] = data_id
            data['keyword'] = data_id
        }

        const selected = [];
        btn.closest('.column-key,.competitors').find('li').each(function () {
            let ths = $(this)
            let domain = ths.find('a span').text()
            let position = ths.find('.pos').text()
            selected.push({
                position: position,
                domain: domain
            });
        });

        let load_more_analysis = btn.attr('data-load-more'),
            action_analysis = 'seoaic_competitor_article_popup_table_analysis',
            content_analysis = btn.closest('body').find('#add-competitors .competitor-article'),
            load_more_btn_analysis = content_analysis.find('.load-more-btn'),
            term_keyword = btn.is('[data-modal="#add-competitors"]') ? btn.attr('data-term-keyword') : btn.attr('data-index'),
            data_analysis = {
                action: action_analysis,
                index: keyword_index,
                load_more: load_more_analysis,
                term_keyword: term_keyword
            }

        if (data_id) {
            data_analysis['keyword'] = data_id
            data_analysis['index'] = data_id
            data_analysis['keyword_serp'] = true
        }

        content_analysis.addClass('loading')
        content_analysis.find('.row-line').remove()

        keywordTitle.html(keyword_title)

        if (btn.attr('data-term-keyword')) {
            keywordTitle.html(btn.attr('data-term-keyword'))
        }
        keywordTitle.addClass('data-keyword_slug')
        submitBTN.attr('data-keyword_slug', slug).attr('data-location', location).attr('data-id', data_id)
        submitBTN.addClass('data-keyword_slug')

        ajaxValidation(data)

        $.ajax({
            url: ajaxurl,
            method: 'post',
            data: data,
            beforeSend: function () {
                table.find('.body').html('<div class="table-row"><div>Loading Data...</div></div>');
            },
            success: function (data) {

                if (btn.is(generate_competitors)) {
                    if (data.status === 'error') {
                        table.find('.body').html('<div class="table-row"><div>' + data.message + '</div></div>');
                        return;
                    }

                    table.find('.body').html(data);

                    selected.forEach((item) => {
                        setTimeout(() => {
                            modalID.find('.seoaic-competitor-check').each(function () {
                                const current_domain = $(this).val();
                                const current_position = $(this).data('position');
                                const domain_position = current_domain + current_position;
                                if (domain_position === (item.domain + item.position) || domain_position === ('www.' + item.domain + item.position)) {
                                    $(this).prop("checked", true);
                                }
                            });
                        }, 200);
                    });

                    setTimeout(() => {

                        selectedCounter()

                    }, 300);

                    ajax_competitor_analysis(data_analysis, content_analysis, load_more_btn_analysis, keyword_index)

                } else if (btn.is(generate_terms)) {
                    if (data.status === 'error') {
                        table.find('.body').html('<div class="table-row"><div>' + data.status + '</div></div>');
                        return;
                    }
                    table.find('.body').html(data);

                    selected.forEach((item) => {

                        let it = 'www.' + item

                        table.find('.seoaic-competitor-check').each(function () {

                            if ($(this).val() === it) {
                                $(this).prop("checked", true);
                            }

                        })
                    });
                }
                selectedCounterTerms()
            }
        });
    })
}

const selectedCounter = () => {
    let checkbox = $('#add-competitors .seoaic-competitor-check'),
        selected = $('#add-competitors .seoaic-competitor-check:checked').length,
        counter = $('#add-competitors .visibility.selected span'),
        unselect = $('#add-competitors .visibility.selected .unselect')

    if (selected > 0) {
        unselect.fadeIn()
    } else {
        unselect.fadeOut()
    }
    counter.html('(' + selected + ')')
}

const selectedCounterTerms = () => {
    let checkbox = $('#generate-terms .seoaic-competitor-check'),
        selected = $('#generate-terms .seoaic-competitor-check:checked').length,
        counter = $('#generate-terms .visibility.selected span'),
        unselect = $('#generate-terms .visibility.selected .unselect')

    if (selected > 0) {
        unselect.fadeIn()
    } else {
        unselect.fadeOut()
    }
    counter.html('(' + selected + ')')
}

const unSelect = () => {

    $(document).on('click', '.unselect', (e) => {

        e.preventDefault()

        $(e.currentTarget).closest('.table').find('.seoaic-competitor-check:checked').each(function () {
            $(this).prop("checked", false).change();
        })
    })
}

const getSelectedCompetitors = () => {
    $(document).on('click', 'button[data-action="seoaicAddedCompetitors"], button[data-action="seoaicAddSubTerms"]', function (e) {

        e.preventDefault();

        if ($(this).is('[data-action="seoaicAddedCompetitors"]')) {

            let its = $(this),
                body = $('#seoaic-admin-body'),
                checked = document.querySelectorAll('#add-competitors .seoaic-popup.add-competitors .seoaic-competitor-check:checked'),
                keyWordSlug = its.attr('data-keyword_slug'),
                data_id = its.attr('data-id'),
                remove = body.find('[data-modal="#add-competitors"][data-keyword="' + keyWordSlug + '"]').closest('.competitors').find('ul'),
                addTo = body.find('[data-modal="#add-competitors"][data-keyword="' + keyWordSlug + '"]').closest('.competitors'),
                action = its.attr('data-action')
            // commaList = [...checked].map(el => {
            //         return el.value
            //     }
            // ).join(',')

            const dataArray = [];

            checked.forEach(item => {
                const position = item.getAttribute('data-position');
                const domain = item.getAttribute('value');
                dataArray.push({
                    position: position,
                    domain: domain
                });
            });

            let data = {
                action: action,
                competitors: dataArray,
                keyword_slug: keyWordSlug
            }

            if (data_id) {
                data['data_id'] = data_id
            }

            checkCheckedCompetitors(checked, keyWordSlug)

            ajaxValidation(data)

            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: data,
                success: function (data) {

                    if (data.status === 'success') {
                        remove.remove()
                        addTo.prepend(data.competitors);
                    }

                }
            });
        } else if ($(this).is('[data-action="seoaicAddSubTerms"]')) {
            let its = $(this),
                checked = document.querySelectorAll('.generate-terms .seoaic-competitor-check:checked'),
                keyWordSlug = its.attr('data-keyword_slug'),
                remove = $('#seoaic-admin-body').find('.flex-table').find('[data-keyword="' + keyWordSlug + '"]').closest('.column-key').attr('data-selections'),
                addTo = $('#seoaic-admin-body').find('.flex-table').find('[data-keyword="' + keyWordSlug + '"]').closest('.column-key'),
                //.find('[data-keyword="' + keyWordSlug + ']'),
                action = its.attr('data-action'),
                location = its.attr('data-location'),
                manualInputCheck = its.closest('.seoaic-popup').find('.add-additional-terms input').val(),

                commaList = [...checked].map(el => {
                        return el.value
                    }
                ).join(','),

                manualInputTerms = commaList ? (manualInputCheck ? ',' + manualInputCheck : '') : manualInputCheck

            let terms = commaList + manualInputTerms

            let queue = terms.split(',')

            ajaxQueueRunTerms(
                queue,
                action,
                location,
                keyWordSlug,
                false,
                'Adding sub terms'
            )
        }
    })
}

const checkCheckedCompetitors = (checked, key) => {
    if (checked.length) {
        $('.competitors .modal-button[data-keyword="' + key + '"]').text('+ Edit competitors')
    } else {
        $('.competitors .modal-button[data-keyword="' + key + '"]').text('+ Show competitors')
    }
}

const rankHistory = () => {
    $(document).on('click', 'a[data-modal="#rank-history"], button[data-modal="#rank-history"]', function (e) {
        e.preventDefault()
        let it = $(this),
            modal = it.attr('data-modal')
        $(modal).find('.seoaic-popup__header h3').html(it.attr('data-title'))

        if (!it.is('.competitor-compare')) {
            $(modal).find('.seoaic-popup__content .table #chart_term_positions').show()
        }
    })
}

// suggestions data
const addClassToLastSubTerm = () => {
    let slug = '';
    $('[data-parent]').each(function () {
        let it = $(this),
            slug = it.attr('data-parent')

        const last = Array.from(
            document.querySelectorAll('[data-children="' + slug + '"]')
        ).pop();

        $(last).addClass('last')
    })
}

const selectAllKeywords = () => {
    $(document).on('click', '[name="seoaic-select-all-keywords"]', (e) => {
        setTimeout(function () {
            let delete_btn = $('.rank-tracker, .competitors-page').find('.seoaic-remove-main'),
                checked = []

            $('.row-line:not(.heading) .seoaic-check-key:checked').each(function () {
                checked.push($(this).attr('data-keyword'))
            })

            delete_btn.attr('data-post-id', checked.join(','))
        }, 100);
    })
}
selectAllKeywords()

const selectAllSubItems = () => {
    $(document).on('change', '.seoaic-check-key', (e) => {
        let it = $(e.currentTarget),
            slug = it.attr('data-keyword'),
            delete_btn = $('.seoaic-remove-main'),
            checked = []

        $('.row-line:not(.heading) .seoaic-check-key:checked').each(function () {
            checked.push($(this).attr('data-keyword'))
        })

        delete_btn.attr('data-post-id', checked.join(','))

        if (it.is(':checked')) {
            $('[data-children="' + slug + '"]').find('.seoaic-check-key').prop('checked', true);
        } else {
            $('[data-children="' + slug + '"]').find('.seoaic-check-key').prop('checked', false);
        }
    })
}

const comparePosition = () => {
    $(document).on('click', '[data-modal="#competitor-compare"]', (e) => {

        e.preventDefault()

        let it = $(e.currentTarget),
            competitor = it.html(),
            competitor_pos = it.attr('data-position'),
            your_position = it.closest('.row-line').find('.search_engine a').html(),
            modal = $('#competitor-compare .table')

        modal.find('.heading .competitor').html(competitor)
        modal.find('.body .position:first-child').html(your_position)
        modal.find('.body .position:last-child').html(competitor_pos)

    })
}

const checkSelectedKeywords = () => {
    let modal = $('#add-idea'),
        form = modal.find('.seoaic-form'),
        input = form.find('#add-idea-name'),
        select = form.find('[name="select-keywords"]')

    $(document).on('change', select, (e) => {
        let it = $(e.currentTarget),
            selected = it.find('option:selected')

        if (selected[0]) {
            input.prop('required', false)
        } else {
            input.prop('required', true)
        }

    })

}

// Search Terms Sorting
const termsSorting = () => {
    const table = $(document).find('#seoaic-admin-body.rank-tracker .flex-table');
    let body = table.find('> div:not(.heading)');

    $(document).ready(function () {
        table.find('#seoaic-admin-body.rank-tracker .search-vol[data-order="ASC"]').each(function () {
            $(this).click()
        });
    })

    table.on('click', '.heading > div:not(.search-intent):not(.serp):not(.check)', (e) => {
        e.preventDefault();

        const column = $(e.currentTarget);
        const columnName = column.attr('class');
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
            aNum = aNum ? aNum : 0;
            let bNum = Number($(b).find('.' + columnName).text().replace(/[^0-9.-]+/g, ""));
            bNum = bNum ? bNum : 0;

            return aNum - bNum;
        }

        const sortingMethod = columnName === 'keyword' ? sortStrings : sortNums

        body.sort(sortingMethod);

        table.children().not('.heading').remove();
        table.append(body);

        $('[data-parent]').each(function () {

            let it = $(this),
                parent = it.attr('data-parent')

            $('[data-children="' + parent + '"]').each(function () {

                let children = $('[data-children="' + parent + '"]').detach()

                it.after(children)

            })

        })
    })
}

const FilterLocationData = () => {
    const table = $(document).find('#seoaic-admin-body.rank-tracker .flex-table');
    let body = table.find('> div:not(.heading)'),
        heading = table.find('.row-line.heading .rank-location'),
        select = $('#rank-location-filter'),
        location = document.cookie.split("; ").find((row) => row.startsWith("seoaiLocoSelect")),
        selected = location ? location.replace('seoaiLocoSelect=', '') : '',
        countries = document.querySelectorAll('.rank-location span'),

        loco = [].slice.call(countries).map(el => {
            return el.innerHTML
        })

    let html = '',
        count = []
    new Set(loco).forEach(function (element) {
        let value = '⎻'
        if (element) {
            value = element;
        }
        html += '<option>' + value + '</option>';
        count.push(value)
    });

    if (count.length >= 2) {
        heading.html('<select id="rank-location-filter"><option value="" selected>All locations</option>' + html + '</select>')
    } else {
        heading.html('Location')
    }

    select.html('<option value="" selected>All locations</option>' + html)

    //console.log(count.length)

    table.on('change', '#rank-location-filter', (e) => {
        const value = $(e.currentTarget).val();

        createCookie("seoaiLocoSelect", value, 30)

        if (value) {
            body.each((i, e) => {
                if ($(e).find('.rank-location span').text() === value) {
                    $(e).stop().slideDown(400);
                } else {
                    $(e).stop().slideUp(400);
                }
            })
        } else {
            body.stop().slideDown(400);
        }
    })

    if (selected) {
        $('#rank-location-filter').val(selected).change();
    }
}

function closeOpen(element) {
    if (element.is('.open')) {
        element.removeClass('open')
    } else {
        element.addClass('open')
    }
}

function collapseButton() {

    let collapse_button = $('.collapse-button')

    collapse_button.each(function () {
        let keyword = $(this).attr('data-keyword-name'),
            children = $('[data-children="' + keyword + '"]')

        if (children.length) {
            $(this).css({
                "opacity": "1",
                "visibility": "visible",
                "pointer-events": "auto",
            })
        }
    })

    $(document).on('click', '.collapse-button', function () {

        let ths = $(this),
            keyword = ths.attr('data-keyword-name')

        closeOpen(ths)

        $('[data-children="' + keyword + '"]').each(function () {
            closeOpen($(this))
        })
    })
}

const postedPostsModal = (e) => {

    let link = $('.created-posts')

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

    link.on('click', '[data-modal="#generate-ideas"]', (e) => {
        e.preventDefault()
        let btn = $(e.currentTarget),
            keyWord = btn.closest('.row-line').find('.check input').attr('data-keyword'),
            keywords = [];

        keywords.push(keyWord);

        $('[name="select-keywords"]').val(keywords).change();
        //$('[data-modal="#generate-ideas"] [name="action"]').val('seoaic_generate_ideas').change();

    })


    // link.on('click', '[data-modal="#generate-ideas"]', function (e){
    //     let btn = $(this);
    //     if ( btn.hasClass('generate-keyword-based') ) {
    //         let keywords = [];
    //         keywords.push(btn);
    //
    //         $('[name="select-keywords"]').val(keywords);
    //     }
    // });

}

const ajaxQueueRunTerms = (
    queue,
    action,
    location,
    parent_term = '',
    update = false,
    modal_content = ''
) => {

    let modal = $('#search-terms-update-modal'),
        admin_body = $('#seoaic-admin-body'),
        submit = modal.find('.seoaic-popup__btn-right'),
        loading_progress = modal.find('.lds-indication .loader'),
        from_num = modal.find('.status-update .from'),
        to_num = modal.find('.status-update .to'),
        terms_count = queue.length,
        body = $('body'),
        status_ul = modal.find('.status-update + ul')

    modal.find('.seoaic-popup__content .modal-content').html(modal_content);
    from_num.html(0)
    to_num.html(terms_count)
    submit.attr('action', 'seoaicUpdateSearchTerms')
    modal.fadeIn(200);
    admin_body.addClass('seoaic-blur');
    body.addClass('modal-show');

    modal.addClass('loading ajax-terms-work')
    modal.find('.lds-indication').addClass('show-on-scanning')

    queue.forEach(function (element, i) {

        let num_val = i + 1,
            data = {
                action: action,
                item_name: element,
                location: location,
            }

        if (parent_term) {
            data['parent_term'] = parent_term;
        }

        if (update) {
            data['run_update'] = 1;
            data['keyword'] = element.keyword;
            data['slug'] = element.slug;
            data['location'] = element.location;
        }

        let seoaicNonceValue = wp_nonce_params[data.action];

        if (seoaicNonceValue !== '') {
            data._wpnonce = seoaicNonceValue;
        }

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

                    // modal
                    //     .removeClass('ajax-terms-work')
                    //     .removeClass('loading')

                    setTimeout(() => {
                        window.location.reload();
                    }, 500);

                }

            },
            complete: function (responseText) {
                from_num.text(num_val)
                status_ul.scrollTop(function () {
                    return this.scrollHeight;
                });
            }
        });

    });
}

const addSearchTerms = (e) => {

    $(document).on('submit', '.seoaic-form-add-search-term', function (e) {

        e.preventDefault();
        e.stopPropagation();

        let form = $(this),
            get_modal_id = form.closest('.seoaic-modal').attr('id'),
            data = {};
        form.find('.seoaic-form-item').each(function () {

            let _item = $(this);
            let _form_item_name = _item.attr('name');

            if (_form_item_name === 'item_name') {
                let name = _item.val().replace(/^[,\s]+|[,\s]+$/g, ''),
                    name_validate = name.replace(/\s*,\s*/g, ','),
                    double_spaces = name_validate.replace(/ +(?= )/g, '');
                data['item_name'] = double_spaces.split(',')
            } else {
                data[_form_item_name] = _item.val();
            }

            let keywords = []
            $('select[name="select-keywords"] + .select2 .select2-selection__rendered .select2-selection__choice').each(function () {
                let keyword = $('> span', this).text();
                keywords.push(keyword);
            });
            data['select-keywords'] = keywords;


            if (_item.val() === 'seoaicRemoveSearchTerms') {
                data['search-engine'] = 'google';
            }

        });

        let item_name = data['item_name'],
            keywords_selected = data['select-keywords'],
            merge = item_name.concat(keywords_selected),
            filtered_terms = merge.filter(function (el) {
                return el !== '';
            })

        data['item_name'] = filtered_terms.filter(uniqueArray)

        seoaic_close_modal($('#' + get_modal_id));

        if (form.is('[data-call-another-modal]')) {
            setTimeout(() => {
                seoaic_open_modal($(form.attr('data-call-another-modal')))
            }, 500)
        }

        let queue = data['item_name'],
            action = data['action'],
            location = data['seoaic_location']

        ajaxQueueRunTerms(
            queue,
            action,
            location,
            '',
            false,
            'Adding terms',
        )

        // console.log(data);
        // console.log(array2);
        // console.log(array3.filter(uniqueArray));

    });

}

const checkUpdateAvailable = (e) => {

    let updateStatus = $('.terms_update_ready')[0],
        modal = $('#search-terms-update-modal'),
        admin_body = $('#seoaic-admin-body'),
        content = admin_body.attr('data-update-modal-content'),
        submit = modal.find('.seoaic-popup__btn-right'),
        loading_progress = modal.find('.lds-indication .loader'),
        from_num = modal.find('.status-update .from'),
        to_num = modal.find('.status-update .to'),
        terms = admin_body.attr('data-update-ready-terms'),
        ready_to_update_terms = terms ? JSON.parse(admin_body.attr('data-update-ready-terms')) : '',
        update_terms_count = terms ? Object.keys(ready_to_update_terms).length : 0,
        body = $('body')

    if (ready_to_update_terms) {
        modal.find('.seoaic-popup__content .modal-content').html(content);
        from_num.html(0)
        to_num.html(update_terms_count)
        submit.attr('action', 'seoaicUpdateSearchTerms')
        modal.fadeIn(200);
        admin_body.addClass('seoaic-blur');
        body.addClass('modal-show');
    }

    submit.on('click', (e) => {
        e.preventDefault()
        let btn = $(e.currentTarget)

        modal.addClass('loading')

        if (!ready_to_update_terms) {
            return false
        }

        ajaxQueueRunTerms(
            ready_to_update_terms,
            submit.attr('action'),
            '',
            '',
            true,
            'Updating terms'
        )
    })

}

const disableNoRankedKeywordsGenerate = (e) => {

    let row_line = $(".rank-tracker .row-line")

    //console.log(row_line)

    row_line.each(function () {
        let it = $(this),
            search_vol = it.find('.search-vol').text(),
            num = isNaN(parseInt(search_vol)) ? 0 : parseInt(search_vol),
            competitors_btn = it.closest('.row-line').find('[data-modal="#add-competitors"]'),
            generate_keys_btn = it.closest('.row-line').find('[data-modal="#generate-terms"]')

        if (num <= 50) {
            competitors_btn.addClass('disabled')
            generate_keys_btn.addClass('disabled')
        }
        //console.log(num)
    })

}

const filterTerms = () => {
    $('.terms-filter-input').keyup(delay(function (e) {
        let it = $(e.currentTarget),
            form = it.closest('form'),
            input = form.find('input'),
            clear = form.find('.clear-filter'),
            terms_table = form.closest('.bottom').find('.flex-table'),
            admin_body = form.closest('#seoaic-admin-body'),
            data = {
                action: 'seoaicFilteringTerms',
            },
            data_action = {
                action: 'seoaicFilteringTerms',
            }

        form.on('click', '.clear-filter', (e) => {
            e.preventDefault()
            input.each(function () {
                $(this).val($(this).attr('data-default'))
                $('.terms-filter-input').keyup()
                //run(data_action)
            })
        })

        input.each(function () {
            data[$(this).attr('name')] = $(this).val()
        })

        ajaxValidation(data)

        $.ajax({
            url: ajaxurl,
            method: 'post',
            data: data,
            beforeSend: function () {
                clear.removeClass('active')
                admin_body.addClass('seoaic-blur seoaic-loading')

            },
            success: function (data) {

                terms_table.html(data.html)

            },
            complete: function () {

                admin_body.removeClass('seoaic-blur seoaic-loading')
                FilterLocationData()
                termsSorting()
                clear.addClass('active')
                collapseButton()
                collapseButton()

            }
        });
    }, 500));
}

const openerInfo = () => {
    $(document).on('click', '.my-content-toggle-modal', function (e) {
        e.preventDefault()

        let ths = $(this),
            inner = ths.closest('.h2-tags').find('.inner'),
            ul = inner.find('ul'),
            li = ul.find('li')

        setTimeout(() => {
            if (ths.is('.view_less')) {

                listHideShow(li, 'none')
                ths.text('View more')
                ths.removeClass('view_less')

            } else {
                ths.text('View less')
                ths.addClass('view_less')

                li.css({
                    'display': 'block'
                })
            }
        }, 100);
    })
}

const listHideShow = (li, none = 'none') => {
    for (let i = 4; i < li.length; i++) {
        li[i].style.display = none;
    }
}

const toggleMyArticleInfo = () => {
    $('.my-website-info, .serp').on('click', '.toggle', (e) => {
        let ths = $(e.currentTarget),
            row = ths.closest('.row-line')

        row.toggleClass('active')
    })
    $('.my-article-info .inner').each(function (e) {
        let ths = $(this),
            ul = ths.find('ul'),
            li = ul.find('li')

        if (li.length < 5) {
            ths.next('a').hide()
        }

        listHideShow(li, 'none')
        openerInfo()
    })
}

const toggleCompetitor = () => {

    $(document).on('click', '.domain .toggle', (e) => {
        let this_is = $(e.currentTarget)

        closeOpen(this_is)

        let index = this_is.closest('[data-index]').attr('data-index'),
            content_table = this_is.closest('.content-table'),
            rows = content_table.find('[data-index="' + index + '"')

        if (this_is.is('.open')) {
            rows.each(function () {
                $(this).next('.article-info').addClass('active')
            })
        } else {
            rows.each(function () {
                $(this).next('.article-info').removeClass('active')
            })
        }

    })
}

// Events
$(document).on('change', '#add-competitors .seoaic-competitor-check', (e) => {
    selectedCounter()
})

$(document).on('click', '.add-search-term', (e) => {
    $('#add-idea').find('.seoaic-form')
        .attr('data-call-another-modal', '#search-terms-update-modal')
        .addClass('seoaic-form-add-search-term')
        .removeClass('seoaic-form')
})

$(document).on('click', '.show-filters', (e) => {
    $(e.currentTarget)
        .toggleClass('active')
        .closest('body').find('.filter-section').toggleClass('active')
})

$(document).on('change', '#generate-terms .seoaic-competitor-check', (e) => {
    selectedCounterTerms()
})

$('[data-action="seoaic_get_search_term_competitors"]').each(function () {
    let check = $(this).closest('.competitors').find('ul li')

    if (check[0]) {
        $(this).text('+ Edit competitors')
    } else {
        $(this).text('+ Show competitors')
    }
})


// Helpers
export const ajaxValidation = (data) => {
    let seoaicNonceValue = wp_nonce_params[data.action];

    if (seoaicNonceValue !== '') {
        data._wpnonce = seoaicNonceValue;
    }
}

export function delay(callback, ms) {
    var timer = 0;
    return function () {
        var context = this, args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function () {
            callback.apply(context, args);
        }, ms || 0);
    };
}

function createCookie(name, value, days) {
    var expires;
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toGMTString();
    } else {
        expires = "";
    }
    document.cookie = name + "=" + value + expires + "; path=/";
}

const seoaic_close_modal = (modal) => {
    modal.fadeOut(200, function () {
        $('#seoaic-admin-body').removeClass('seoaic-blur');
    });

}

const seoaic_open_modal = (modal, _content = false) => {
    // if (false !== _content) {
    //     modal.find('.seoaic-popup__content .modal-content').html(_content);
    // }
    $('#seoaic-admin-body').addClass('seoaic-blur');
    modal.fadeIn(200);
}

const uniqueArray = (value, index, array) => {
    return array.indexOf(value) === index;
}

const stripHtml = (html) => {
    let tmp = document.createElement("DIV");
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || "";
}

function shortNumber(number) {
    const absNumber = Math.abs(number);
    const suffixes = ["", "k", "m", "b"];
    let suffixIndex = 0;
    let convertedNumber = absNumber;

    while (convertedNumber >= 1000 && suffixIndex < suffixes.length - 1) {
        convertedNumber /= 1000;
        suffixIndex++;
    }

    convertedNumber = convertedNumber.toFixed(1);

    convertedNumber += suffixes[suffixIndex];

    return number < 0 ? `-${convertedNumber}` : convertedNumber;
}
