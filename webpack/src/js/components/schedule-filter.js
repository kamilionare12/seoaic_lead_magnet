function filter_post_ajax(data, content, num, counter) {
    let seoaicNonceValue = wp_nonce_params[data.action];

    if (seoaicNonceValue !== '') {
        data._wpnonce = seoaicNonceValue;
    }

    $.ajax({
        url: ajaxurl,
        method: 'post',
        data: data,
        beforeSend: function () {
            $('#seoaic-admin-body').addClass('seoaic-loading');
        },
        success: function (data) {
            $('#seoaic-admin-body').removeClass('seoaic-loading');
            content.html(data);
            $('.top').find('h2').find('span').html($('.seoaic-table-idea-box > *').length)
        }
    })
}


$('.top').on('click', '[data-order],[data-search],[data-clear-date],[data-set-date]', function (e) {
    e.preventDefault();

    let it = $(this),
        content = $('.seoaic-table-idea-box'),
        order = it.attr('data-current'),
        search = it.closest('.search').find('input').val(),
        mindate = it.closest('.search').find('input').val(),
        maxdate = it.closest('.search').find('input').val(),
        defMin,
        defMax;

    if (it.attr('data-order')) {

        order = it.attr('data-order')
        if (order === 'ASC') {
            it.attr('data-order', 'DESC')
            it.attr('data-current', 'ASC')
        } else {
            it.attr('data-order', 'ASC')
            it.attr('data-current', 'DESC')
        }

        search = it.closest('.top').find('.search').find('input').val()
        order = it.closest('.top').find('[data-current]').attr('data-current')
        mindate = it.closest('.top').find('#seoaic-from-date').val()
        maxdate = it.closest('.top').find('#seoaic-to-date').val()


    } else if (it.attr('data-search')) {

        search = it.closest('.search').find('input').val()
        order = it.closest('.top').find('[data-current]').attr('data-current')
        mindate = it.closest('.top').find('#seoaic-from-date').val()
        maxdate = it.closest('.top').find('#seoaic-to-date').val()

    } else if (it.attr('data-set-date')) {

        order = it.closest('.top').find('[data-current]').attr('data-current')
        search = it.closest('.top').find('.search').find('input').val()
        mindate = it.closest('.top').find('#seoaic-from-date').val()
        maxdate = it.closest('.top').find('#seoaic-to-date').val()

    } else if (it.attr('data-clear-date')) {

        order = it.closest('.top').find('[data-current]').attr('data-current')
        search = it.closest('.top').find('.search').find('input').val()

        defMin = it.closest('.top').find('#seoaic-from-date').attr('data-default')
        defMax = it.closest('.top').find('#seoaic-to-date').attr('data-default')

        it.closest('.top').find('#seoaic-from-date').val(defMin)
        it.closest('.top').find('#seoaic-to-date').val(defMax)
    }

    let data    = {
        'action': it.attr('data-action'),
        'order'  : order,
        'search'  : search,
        'mindate'  : mindate,
        'maxdate'  : maxdate,
    };

    // console.log(order)
    // console.log(search)
    // console.log(mindate)
    // console.log(maxdate)

    // Run query
    filter_post_ajax(data, content);

});