import IMask from 'imask';
import AirDatepicker from 'air-datepicker';
import localeEn from 'air-datepicker/locale/en';
//locale: localeEn,

// var numberMask = IMask('#posts-num-input', {
//     mask: Number,  // enable number mask
//
//     // other options are optional with defaults below
//     scale: 2,  // digits after point, 0 for integers
//     thousandsSeparator: '',  // any single char
//     padFractionalZeros: false,  // if true, then pads zeros at end to the length of scale
//     normalizeZeros: true,  // appends or removes zeros at ends
//     radix: ',',  // fractional delimiter
//     mapToRadix: ['.'],  // symbols to process as radix
//
//     // additional number interval options (e.g.)
//     min: -10000,
//     max: 10000
// });

// Set time

var start = new Date(),
    prevDay,
    startHours = 1;

// 09:00 AM
start.setHours(1);
start.setMinutes(0);

// If today is Saturday or Sunday set 10:00 AM
if ([6, 0].indexOf(start.getDay()) != -1) {
    start.setHours(10);
    startHours = 10
}
$(".form-input-time").each(function () {
    $(this).attr('readonly', true)
    let time = new AirDatepicker(this, {
        //minHours: 1,
        dateFormat: '#',
        startDate: start,
        minHours: startHours,
        locale: localeEn,
        timepicker: true,
        language: 'en',
        timeFormat: 'hh:mm AA',
        maxHours: 24,
        classes: 'only-timepicker',
        onSelect: function (e) {

            $('.form-input-time').each(function () {
                let time = $(this).val(),
                    input = $(this),
                    fil = time.replace('# ','');

                while(fil.charAt(0) === '0')
                {
                    fil = fil.substring(1);
                }

                input.val(fil)
            })
        }
    })
})

// editor click
$('.form-input-time').each(function () {
    let it = $(this),
        parent = it.closest('.time-select'),
        done = parent.find('.done'),
        edit = parent.find('.edit')

    $(document).click(function (e) {
        if (!$(e.target).is(".done, .form-input-time, .edit, .air-datepicker *")) {
            $('.done').removeClass('active')
            //return false;
        }
    });

    it.click(function () {
        done.addClass('active')
    })

    done.click(function () {
        done.removeClass('active')
    })

})

$('.edit').click(function () {
    let it = $(this),
        done = it.prev('.done'),
        doneNum = it.prev('.label').prev('.ready'),
        input = it.prev('.done').prev('input'),
        inputNim = it.prev('.label').prev('.ready').prev('.spin').prev('.form-input-num')
    done.addClass('active')
    doneNum.addClass('active')

    input.click()
    input.focus()

    inputNim.click()
    inputNim.focus()
})



// $(function () {
//     $("input").keydown(function () {
//         // Save old value.
//         if (!$(this).val() || (parseInt($(this).val()) <= 11 && parseInt($(this).val()) >= 0))
//             $(this).data("old", $(this).val());
//     });
//     $("input").keyup(function () {
//         // Check correct, else revert back to old value.
//         if (!$(this).val() || (parseInt($(this).val()) <= 11 && parseInt($(this).val()) >= 0))
//             ;
//         else
//             $(this).val($(this).data("old"));
//     });
// });



//document.body.className += "js";

$('.form-input-num').each(function() {

    let it = $(this),
        parent = it.closest('.num-select'),
        up = parent.find('.up'),
        down = parent.find('.down'),
        ready = parent.find('.ready')

    it.attr('readonly', true)

    up.on('click', function () {
        var value = parseInt(it.val(), 10);
        value = isNaN(value) ? 0 : value;
        value++;
        it.val(value);
    })

    down.click('click', function () {
        var value = parseInt(it.val(), 10);
        if (value >= 1) {
            value = isNaN(value) ? 0 : value;
            value--;
            it.val(value);
        }
    })

    it.click(function () {
        ready.addClass('active')
        parent.addClass('active')
    })

    $(document).click(function (e) {
        if (!$(e.target).is(".ready, .num-select *")) {
            $('.ready').removeClass('active')
            parent.removeClass('active')
            //return false;
        }
    });

    ready.click(function () {
        ready.removeClass('active')
        parent.removeClass('active')
    })
})

function checks(it, time, posts) {
    setTimeout(function () {
        if (it.is(":checked")) {
            $(time).css({
                'visibility': 'visible'
            })
            $(posts).css({
                'visibility': 'visible'
            })
        } else {
            $(time).css({
                'visibility': 'hidden'
            })
            $(posts).css({
                'visibility': 'hidden'
            })
        }
    },100)
}

$('.seoaic-form-item[type="checkbox"]').each(function ()  {
    let it = $(this),
        parent = it.closest('tr'),
        time = parent.find('.time-select'),
        posts = parent.find('.num-select')
    checks(it, time, posts)

    $(this).on('change', function() {
        let it = $(this),
            parent = it.closest('tr'),
            time = parent.find('.time-select'),
            posts = parent.find('.num-select')

        checks(it, time, posts)
    })
})
