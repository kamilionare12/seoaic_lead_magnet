(function ($){
    let a = '' +
            '<div class="service-section">' +
                '<div class="item">' +
                    '<input placeholder="Service name" type="text" class="form-input light" autocomplete="off" required/>' +
                    '<textarea placeholder="Service description (optional)" name="pillar-description" class="form-input light" autocomplete="off" rows="3"></textarea>' +
                    '<a href="#" class="delete" title="Remove service"></a>' +
                '</div>' +
            '</div>',
        b = $('.list'),
        c = $('.add')

    $(document).on('click', '.add:not(.add-loction)', function (e) {
        let it = $(this),
            list = it.prev('.list'),
            cl = it.attr('data-add'),
            read = 0

        e.preventDefault()
        list.append(a)
        list.find('input').addClass(cl).attr('name', cl).attr('readonly', false)
    })

    $(document).on('click', '.delete', function (e) {
        e.preventDefault()
        if ($(this).closest('.list').children().length > 0)
            $(this).closest('.service-section').remove()
    })

    $(document).on('click', '.seoaic_pillar_add_btn', function (e) {
        e.preventDefault();
        const templateElement = document.getElementById("seoaic_pillar_template");
        const template = templateElement.innerHTML;

        const langSelect = $('.seoaic-language-wrap .seoaic-language').clone();

        let table = document.getElementById('seoaic_pillar_tbody');
        let newRow = table.insertRow(table.rows.length);
        newRow.classList.add('seoaic_pillar_item');
        
        let $template = $(template);
        $template.find('.seoaic_pillar_lang').html(langSelect);
        let modifiedTemplate = $template.prop('outerHTML');

        newRow.innerHTML = modifiedTemplate;
    })

    $(document).on('click', '.delete-pillar', function (e) {
        e.preventDefault()
        if ($(this).closest('.seoaic_pillar_wrap tr').children().length > 0)
            $(this).closest('.seoaic_pillar_item').remove()
    })

    $(document).on('click', '.seoaic-edit-pillar', function (e) {
        e.preventDefault()

        let row = $(this).closest('.seoaic_pillar_item');
        let isDisabled = row.find('input, textarea, select').prop('disabled');
        row.find('input, textarea, select').prop('disabled', !isDisabled);
        $(this).toggleClass('seoaic-save-pillar', isDisabled);
    })

    $('textarea').on('input', function () {
        adjustTextareaHeight(this);
    });

    document.addEventListener('DOMContentLoaded', function() {
        var textareas = document.querySelectorAll('.seoaic_pillar_item textarea');

        textareas.forEach(function(textarea) {
            adjustTextareaHeight(textarea);

            textarea.addEventListener('input', function() {
                adjustTextareaHeight(this);
            });
        });
    });

    function adjustTextareaHeight(element) {
        element.style.height = 'auto';
        element.style.height = (element.scrollHeight) + 'px';
    }
})($)