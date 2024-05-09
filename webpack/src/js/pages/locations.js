function onlyUnique(value, index, array) {
    return array.indexOf(value) === index;
}

$('select[name="select_location"]').select2()

$(document).on('submit', '#seoaicLocationsForm', function (e) {
    e.preventDefault()

    let form = $(this),
        body = $('#seoaic-admin-body'),
        inputs = document.querySelectorAll('form input.location-input.light'),
        cities_selected = $(this).find('f.select-cities > :selected:not([value="1"])'),
        //locations = Array.from(inputs).map(e => e.value),
        arr = [],
        loc = [],
        locations = $(this).find('.location-section').each(function () {

            let it = $(this),
                val = [],
                cities = it.find('.select-cities > :selected:not([value="1"])'),
                states = it.find('.select-states > :selected:not([value="1"])'),
                countries = it.find('.select-countries > :selected:not([value="1"])')

            if (cities.length) {
                cities.each(function () {
                    val.push($(this).val());
                })
            } else if (states.length) {
                states.each(function () {
                    val.push($(this).val());
                })
            } else if (countries.length) {
                countries.each(function () {
                    val.push($(this).val());
                })
            }

            loc.push(val);
        })

    arr['locations'] = loc

    let mode = $('.seoaic-loaction-form-container [name="mode"]').val();
    let data = {
        action: form.attr('data-action'),
        id: form.attr('data-post-id'),
        locations: arr['locations'].flat(1).filter(onlyUnique),
        data_html: this.innerHTML,
        mode: mode,
    }
    switch (mode) {
        case 'manual':
            $('#seoaic-idea-locations .seoaic-form-item').each(function () {
                data.locations.push($(this).val());
            });
            break;
    }

    let seoaicNonceValue = wp_nonce_params[data.action];

    if (seoaicNonceValue !== '') {
        data._wpnonce = seoaicNonceValue;
    }

    $.ajax({
        url: ajaxurl,
        method: 'post',
        data: data,
        beforeSend: function (response) {
            body.addClass('seoaic-loading');
        },
        success: function (response, textStatus, XMLHttpRequest) {

            //console.log(response)

        }
    });

    $(document).on("ajaxStop", function () {
        body.removeClass('seoaic-loading');
    });
})

$(document).on('click', '.close-dropdown', function () {
    $('[data-action="seoaicSaveLocationGroup"]').click()
})