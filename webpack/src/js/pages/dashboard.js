import {Mansory} from '../components/masonry';
import {ajaxValidation, delay} from "./rank-tracker";

$(document).ready(function () {
    if ($('.dashboard-page')[0]) {
        Get_dashboard_data()
    }
})

// const tooltip = () => {
//
//     let span = $('.dashboard-page .table .body .col-one span')
//
//     span.on("mouseover", (e) => {
//         let ths = $(e.currentTarget),
//             tooltip = '<div class="tooltip"><span>' + ths.text() + '</span></div>',
//             check_tooltip = ths.closest('.col-one').find('.tooltip')
//
//         if (!check_tooltip.length) {
//             ths.after(tooltip)
//         }
//     });
//
//     span.on("click", (e) => {
//         let ths = $(e.currentTarget)
//         if (ths.is('.active')) {
//             let a = ths.find('a'),
//                 url = ths.find('a').attr('href')
//             setTimeout(() => {
//                 a.addClass('active')
//             }, 100);
//             if (a.is('.active')) {
//                 window.open(url)
//             }
//         }
//         span.removeClass('active')
//         span.find('a').removeClass('active')
//         ths.addClass('active')
//     });
// }

// can be clicked
$(document).on('mousedown ontouchstart', function (e) {
    if (!$(e.target).is(".dashboard-page .table .body .col-one>span")) {
        let span = $('.dashboard-page .table .body .col-one>span'),
            a = $('a', span)
        span.removeClass('active')
        a.removeClass('active')
    }
});

const Get_dashboard_data = () => {

    let body = $('#seoaic-admin-body')
    let data = {
        action: 'seoaic_run_dashboard_data',
    }

    ajaxValidation(data)

    $.ajax({
        url: ajaxurl,
        method: 'post',
        data: data,
        beforeSend: function () {
            body.addClass('seoaic-loading')
        },
        success: function (data) {
            $('.full-width').html(data.html)
        },
        complete: function (data) {
            body.removeClass('seoaic-loading')
        }
    });

}