$(document).ready(function () {
    $('[name="select_location"]').select2({
        minimumResultsForSearch: -1
    });
});

$(document).on('click', '.location-input, .country, .state, .city', function (e) {
    let it = $(this),
        data = {
            'action': 'seoaicAjaxLocations',
        }, id = parseInt(it.attr('data-id')), input = it.closest('.dropdown').prev('input'), inputVal = input.val(),
        location = it.html().replace(/<span>.*<\/span>/, ""),
        state = it.closest('[data-type="city"]').attr('data-parent'),
        country = it.closest('[data-type="state"]').attr('data-parent'),
        span = it.closest('.dropdown').find('li > span'),
        close = it.closest('.dropdown').next('.close-section').find('.close-dropdown')

    input.val('')
    input.attr('data-location', location)

    span.removeClass('checked')
    it.addClass('checked')

    if (it.is('span')) {
        close.addClass('show')
    }

    if (it.is('.location-input')) {

        data['type'] = 'country';
        $('.location-input').removeClass('active')
        it.addClass('active')

    } else if (it.is('.country')) {

        // if (! it.is('.search') ) {
        //     data['type'] = 'state';
        //     data['country_id'] = id;
        //     data['parent'] = location;
        //     it.toggleClass('active')
        // }

        data['type'] = 'state';
        data['country_id'] = id;
        data['parent'] = location;
        it.toggleClass('active')

        input.val(location)

    } else if (it.is('.state')) {

        data['type'] = 'city';
        data['state_id'] = id;
        data['country_id'] = parseInt(it.closest('ul').prev('span').attr('data-id'));
        data['parent'] = location;
        it.toggleClass('active')
        // input.val(country + ' ┄ ' + location)
        input.val(location)

    } else if (it.is('.city')) {

        // data['type'] = 'city';
        // data['state_id'] = id;
        // data['country_id'] = parseInt(it.closest('ul').prev('span').attr('data-id'));
        // data['parent'] = location;
        it.toggleClass('active')
        //input.val(country + ' ┄ ' + state + ' ┄ ' + location)
        input.val(location)

    }

    if (!it.is('.loaded, .city'))
        $.ajax({
            url: ajaxurl,
            method: 'post',
            data: data,
            beforeSend: function () {
                it.addClass('loading')
            }, success: function (data) {

                if (data.content.content === '') {
                    if (it.is('.country')) {
                        it.addClass('no-data')
                        it.append('<span>(no states found for this country)</span>')
                    }
                }

                it.removeClass('loading')
                it.addClass('loaded')

                if (it.is('.location-input')) {
                    it.next('.dropdown').remove()
                    it.after('<div class="dropdown"><div class="searches"><div class="input-search"><input class="seoaic-search-location" type="text" placeholder="Search location"/><div class="clear-search"></div></div><div class="searches-results"></div></div>' + data.content.content + '</div><div class="close-section"><div class="close-dropdown">Save</div></div>')

                    input = it.next('.dropdown').find('.seoaic-search-location')
                    typingDetect(1500, input)

                } else {

                    it.closest('li').find('ul').remove()
                    it.closest('li').append(data.content.content)
                }

            }, error: function (xhr) {
                alert(xhr.responseText);
            }
        })

    $(document).on('click', '.close-dropdown', function () {
        $(this).closest('form').find('[type="submit"]').click()
    })

    $(document).on('click', '.clear-search', function () {
        $(this).siblings('input').val('').keyup().change()
        $(this).siblings('input').addClass('typing')
    })
})

function searchLocations(its) {

    let it = its,
        val = it.val(),
        res = it.closest('.searches').find('.searches-results'),
        drop = res.closest('.dropdown'),
        data = {
            'action': 'seoaicSearchLocations',
            's': val,
        }

    $.ajax({
        url: ajaxurl,
        method: 'post',
        data: data,
        beforeSend: function () {

        }, success: function (data) {

            let content = data.content.content;

            if (content === '<ul></ul>' || content === '') {
                drop.removeClass('find-results')
                res.html('')
                it.next('.notes').remove()
                it.after('<small class="notes">No results found</small>')
            } else {
                drop.addClass('find-results')
                res.html(content)
                it.next('.notes').remove()
                it.after('<small class="notes">' + data.count + ' results found</small>')
            }
            //res.html(data.content.content)
        }, error: function (xhr) {
            //alert(xhr.responseText);
        }
    });
}

function typingDetect(timer, input) {
    let typingTimer,
        doneTypingInterval = timer
    //input = $('.seoaic-search-location')

    $(document).on('keyup', input, function () {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(doneTyping, doneTypingInterval);

        if (input.val() === '') {
            input.removeClass('filled')
            input.next('.notes').remove()
        } else {
            input.addClass('filled')
        }
    });

    $(document).on('keydown', input, function () {
        clearTimeout(typingTimer);
        input.addClass('typing')
    });

    function doneTyping() {

        input.removeClass('typing')

        if (input.val().length >= 4) {
            input.next('.notes').remove()
            searchLocations(input)
            input.closest('.dropdown').removeClass('not-valid-search')
        } else {
            input.closest('.dropdown').addClass('not-valid-search')
            input.closest('.dropdown').find('.searches-results ul').html('')
            input.next('.notes').remove()
            input.after('<small class="notes">4 characters required minimum</small>')
        }

    }
}

$(document).click(function (e) {
    if (!$(e.target).is(".location-input, .country, .state, .dropdown, .dropdown *, .searches, .searches *")) {
        $('.location-input').removeClass('active')
    }
});