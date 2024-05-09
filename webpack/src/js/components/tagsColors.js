// https://github.com/k-ivan/Tags
(function () {

    'use strict';

    // Helpers
    function $$(selectors, context) {
        return (typeof selectors === 'string') ? (context || document).querySelectorAll(selectors) : [selectors];
    }

    function $(selector, context) {
        return (typeof selector === 'string') ? (context || document).querySelector(selector) : selector;
    }

    function create(tag, attr) {
        var element = document.createElement(tag);
        if (attr) {
            for (var name in attr) {
                if (element[name] !== undefined) {
                    element[name] = attr[name];
                }
            }
        }
        return element;
    }

    function whichTransitionEnd() {
        var root = document.documentElement;
        var transitions = {
            'transition': 'transitionend',
            'WebkitTransition': 'webkitTransitionEnd',
            'MozTransition': 'mozTransitionEnd',
            'OTransition': 'oTransitionEnd otransitionend'
        };

        for (var t in transitions) {
            if (root.style[t] !== undefined) {
                return transitions[t];
            }
        }
        return false;
    }

    function oneListener(el, type, fn, capture) {
        capture = capture || false;
        el.addEventListener(type, function handler(e) {
            fn.call(this, e);
            el.removeEventListener(e.type, handler, capture)
        }, capture);
    }

    function hasClass(cls, el) {
        return new RegExp('(^|\\s+)' + cls + '(\\s+|$)').test(el.className);
    }

    function addClass(cls, el) {
        if (!hasClass(cls, el))
            return el.className += (el.className === '') ? cls : ' ' + cls;
    }

    function removeClass(cls, el) {
        el.className = el.className.replace(new RegExp('(^|\\s+)' + cls + '(\\s+|$)'), '');
    }

    function toggleClass(cls, el) {
        (!hasClass(cls, el)) ? addClass(cls, el) : removeClass(cls, el);
    }

    function Tags(tag) {

        var el = $(tag);

        if (el.instance) return;
        el.instance = this;

        var type = el.type;
        var transitionEnd = whichTransitionEnd();

        var tagsArray = [];
        var KEYS = {
            ENTER: 13,
            COMMA: 188,
            BACK: 8
        };
        var isPressed = false;

        var timer;
        var wrap;
        var field;

        function init() {

            // create and add wrapper
            wrap = create('div', {
                'className': 'tags-container',
            });

            let palette,
                group

            palette = el.nextElementSibling
            //palette = wrap.nextSibling,
            // group = create('div', {
            //     'className': 'group-selector',
            // });

           // console.log(palette)

            wrap.appendChild(palette);

            let lb;
            lb = create('span', {
                'className': 'tags-label',
            });

            let selector;
            selector = create('span', {
                'className': 'palette-selector',
            });

            field = create('input', {
                'type': 'text',
                'className': 'tag-input',
                'placeholder': el.placeholder || '',
                'readOnly': true
            });

            wrap.appendChild(field);
            wrap.prepend(lb);
            wrap.prepend(selector);

            el.readOnly = true;

            let dataName = el.dataset.name,
                label = wrap.querySelector('.tags-label')

            label.prepend(dataName + ': ');

            if (el.value.trim() !== '') {
                hasTags();
            }

            el.type = 'hidden';
            el.parentNode.insertBefore(wrap, el.nextSibling);

            wrap.addEventListener('click', btnRemove, false);
            wrap.addEventListener('keydown', keyHandler, false);
            wrap.addEventListener('keyup', backHandler, false);
        }

        function hasTags() {
            var arr = el.value.trim().split(',');
            arr.forEach(function (item) {
                item = item.trim();
                if (~tagsArray.indexOf(item)) {
                    return;
                }
                var tag = createTag(item);
                tagsArray.push(item);
                wrap.insertBefore(tag, field);
            });
        }

        function createTag(name) {
            var tag = create('div', {
                'className': 'tag',
                'innerHTML': '<span class="tag__color" style="background-color: ' + name + '"></span><span class="tag__name">' + name.replace('#', '').replace('/r', '/') + '</span>' +
                    '<div class="tag__remove"></div>'
            });
//       var tagName = create('span', {
//         'className': 'tag__name',
//         'textContent': name
//       });
//       var delBtn = create('button', {
//         'className': 'tag__remove',
//         'innerHTML': '&times;'
//       });

//       tag.appendChild(tagName);
//       tag.appendChild(delBtn);
            return tag;
        }

        function btnRemove(e) {
            e.preventDefault();
            if (e.target.className === 'tag__remove') {
                var tag = e.target.parentNode;
                var name = $('.tag__name', tag);
                wrap.removeChild(tag);
                tagsArray.splice(tagsArray.indexOf(name.textContent), 1);
                el.value = tagsArray.join(',')
            }
            field.focus();
        }

        function keyHandler(e) {

            if (e.target.tagName === 'INPUT' && e.target.className === 'tag-input') {

                var target = e.target;
                var code = e.which || e.keyCode;

                if (field.previousSibling && code !== KEYS.BACK) {
                    removeClass('tag--marked', field.previousSibling);
                }

                var name = target.value.trim();

                // if(code === KEYS.ENTER || code === KEYS.COMMA) {
                if (code === KEYS.ENTER) {

                    target.blur();

                    addTag(name);

                    if (timer) clearTimeout(timer);
                    timer = setTimeout(function () {
                        target.focus();
                    }, 10);
                } else if (code === KEYS.BACK) {
                    if (e.target.value === '' && !isPressed) {
                        isPressed = true;
                        removeTag();
                    }
                }
            }
        }

        function backHandler(e) {
            isPressed = false;
        }

        function addTag(name) {

            // delete comma if comma exists
            name = name.toString().replace(/,/g, '').trim();

            if (name === '') return field.value = '';

            if (~tagsArray.indexOf(name)) {

                var exist = $$('.tag', wrap);

                Array.prototype.forEach.call(exist, function (tag) {
                    if (tag.firstChild.textContent === name) {

                        addClass('tag--exists', tag);

                        if (transitionEnd) {
                            oneListener(tag, transitionEnd, function () {
                                removeClass('tag--exists', tag);
                            });
                        } else {
                            removeClass('tag--exists', tag);
                        }


                    }

                });

                return field.value = '';
            }

            var tag = createTag(name);
            wrap.insertBefore(tag, field);
            tagsArray.push(name);
            field.value = '';
            el.value += (el.value === '') ? name : ',' + name;
        }

        function removeTag() {
            if (tagsArray.length === 0) return;

            var tags = $$('.tag', wrap);
            var tag = tags[tags.length - 1];

            if (!hasClass('tag--marked', tag)) {
                addClass('tag--marked', tag);
                return;
            }

            tagsArray.pop();

            wrap.removeChild(tag);

            el.value = tagsArray.join(',');
        }

        init();

        /* Public API */

        this.getTags = function () {
            return tagsArray;
        }

        this.clearTags = function () {
            if (!el.instance) return;
            tagsArray.length = 0;
            el.value = '';
            wrap.innerHTML = '';
            wrap.appendChild(field);
        }

        this.addTags = function (name) {
            if (!el.instance) return;
            if (Array.isArray(name)) {
                for (var i = 0, len = name.length; i < len; i++) {
                    addTag(name[i])
                }
            } else {
                addTag(name);
            }
            return tagsArray;
        }

        //console.log(this)

        this.destroy = function () {
            if (!el.instance) return;

            wrap.removeEventListener('click', btnRemove, false);
            wrap.removeEventListener('keydown', keyHandler, false);
            wrap.removeEventListener('keyup', keyHandler, false);

            wrap.parentNode.removeChild(wrap);

            tagsArray = null;
            timer = null;
            wrap = null;
            field = null;
            transitionEnd = null;

            delete el.instance;
            el.type = type;
        }
    }

    window.Tags = Tags;

})();

// Use

document.addEventListener('DOMContentLoaded', function () {

    const color = document.getElementById('seoaic_image_colors');
    //console.log(color);

    if (color !== null) {
        const palette = document.querySelectorAll('.color-palette-select');

        let i;

        for (i = 0; i < palette.length; i++) {
            palette[i].classList.add('palette-' + i);
            palette[i].setAttribute('data-index', i);
        }

        let tag_0 = new Tags('.palette-0'),
            tag_1 = new Tags('.palette-1'),
            tag_2 = new Tags('.palette-2')

        //tag_0.addTags('#000')


        $(".color").on("click", function () {
            let it = $(this),
                index = it.closest('.tags-container').prev('input').attr('data-index'),
                color = it.attr('data-color')
            if (index == 0) {
                tag_0.addTags(color)
            } else if (index == 1) {
                tag_1.addTags(color)
            } else if (index == 2) {
                tag_2.addTags(color)
            }
        });

        $(document).click(function (e) {
            if (!$(e.target).is(".palette-selector")) {
                $('.color-palettes').removeClass('active')
                //return false;
            }
        });
        $('.palette-selector').click(function (event) {
            let it = $(this),
                parent = it.closest('.tags-container').find('.color-palettes'),
                other = it.closest('.tags-container').siblings('.tags-container')

            other.find('.active').removeClass('active')
            parent.toggleClass('active');
            return false;
        });
    }

});


