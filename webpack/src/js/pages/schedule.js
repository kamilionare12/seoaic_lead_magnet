import AirDatepicker from 'air-datepicker'
import localeEn from 'air-datepicker/locale/en';
//locale: localeEn,
// new AirDatepicker('#input', {
//     range: true,
//     multipleDatesSeparator: ' - '
// });

let ds = $('#seoaic-from-date').attr('value'),
    dl = $('#seoaic-to-date').attr('value')

let dpMin, dpMax;

dpMin = new AirDatepicker('#seoaic-from-date', {
    autoClose: true,
    dateFormat: 'MM/dd/yyyy',
    locale: localeEn,
    minDate: ds,
    maxDate: dl,
    onSelect({date}) {
        dpMax.update({
            minDate: date
        })
    },
    onHide: function(dp) {
        if (dp) {
            let maxVal = $('body #seoaic-to-date').val();
            if (maxVal == '') {
                dpMax.show();
            }
        }
    },
})

dpMax = new AirDatepicker('#seoaic-to-date', {
    autoClose: true,
    dateFormat: 'MM/dd/yyyy',
    minDate: ds,
    locale: localeEn,
    maxDate: dl,
    onSelect({date}) {
        dpMin.update({
            maxDate: date
        })
    },
    onHide: function(dp) {
        if (dp) {
            let minVal = $('body #seoaic-from-date').val();
            if (minVal == '') {
                dpMin.show();
            }
        }
    },
})

//console.log(ds + dl)

// new AirDatepicker('.seoaic-change-posting-idea-date', {
//     timepicker: true,
//     timeFormat: 'hh:mm AA'
// });