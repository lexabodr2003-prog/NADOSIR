// common
function getURLVar(key) {
    var value = [];

    var query = String(document.location).split('?');

    if (query[1]) {
        var part = query[1].split('&');

        for (i = 0; i < part.length; i++) {
            var data = part[i].split('=');

            if (data[0] && data[1]) {
                value[data[0]] = data[1];
            }
        }

        if (value[key]) {
            return value[key];
        } else {
            return '';
        }
    }
}

$(document).ready(function () {

    // Highlight any found errors
    $('.text-danger').each(function () {
        var element = $(this).parent().parent();

        if (element.hasClass('form-group')) {
            element.addClass('has-error');
        }
    });

    // Currency
    $('body').on('click', '#form-currency .currency-select', function (e) {
        e.preventDefault();

        $('#form-currency input[name=\'code\']').val($(this).attr('name'));

        $('#form-currency').submit();
    });

    // Language
    $('body').on('click', '#form-language .language-select', function (e) {
        e.preventDefault();

        $('#form-language input[name=\'code\']').val($(this).attr('name'));

        $('#form-language').submit();
    });

    /* Search */
    $('#search input[name=\'search\']').parent().find('#ds-search-button').on('click', function () {
        var url = $('base').attr('href') + 'index.php?route=product/search';

        var value = $('#search input[name=\'search\']').val();

        if (value.length > 0) {
            url += '&search=' + encodeURIComponent(value);
            location = url;
        }

    });

    $('#search input[name=\'search\']').on('keydown', function (e) {
        if (e.keyCode == 13) {
            $('#search input[name=\'search\']').parent().find('#ds-search-button').trigger('click');
        }
    });

    const searchForm = document.getElementById('search');
    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
    });

    /* Blog Search */
    $('#oct-blog-search-button').on('click', function () {
        var url = $('base').attr('href') + 'index.php?route=octemplates/blog/oct_blogsearch';

        var value = $('#blog_search input[name=\'blog_search\']').val();

        if (value.length > 0) {
            url += '&search=' + encodeURIComponent(value);
            location = url;
        }

    });

    $('#blog_search input[name=\'blog_search\']').on('keydown', function (e) {
        if (e.keyCode == 13) {
            $('#oct-blog-search-button').trigger('click');
        }
    });

    // Menu
    $('#menu .dropdown-menu').each(function () {
        var menu = $('#menu').offset();
        var dropdown = $(this).parent().offset();

        var i = (dropdown.left + $(this).outerWidth()) - (menu.left + $('#menu').outerWidth());

        if (i > 0) {
            $(this).css('margin-left', '-' + (i + 10) + 'px');
        }
    });

    // hide tooltip after click
    $("#grid-view, #list-view").mouseleave(function () {
        $('[data-toggle="tooltip"]').tooltip("hide");
    });

    // Checkout
    $(document).on('keydown', '#collapse-checkout-option input[name=\'email\'], #collapse-checkout-option input[name=\'password\']', function (e) {
        if (e.keyCode == 13) {
            $('#collapse-checkout-option #button-login').trigger('click');
        }
    });

    // tooltips on hover
    $('[data-toggle=\'tooltip\']').tooltip({
        container: 'body',
        boundary: 'window'
    });

    // Makes tooltips work on ajax generated content
    $(document).ajaxStop(function () {
        $('[data-toggle=\'tooltip\']').tooltip({
            container: 'body',
            boundary: 'window'
        });
    });
});

// Cart add remove functions
var cart = {
    'add': function (product_id, quantity, page = 0, clickedButton = 0) {
        $.ajax({
            url: 'index.php?route=checkout/cart/add',
            type: 'post',
            data: 'product_id=' + product_id + '&quantity=' + (typeof (quantity) != 'undefined' ? quantity : 1),
            dataType: 'json',
            cache: false,
            beforeSend: function () {
                $('#cart > button').button('loading');

                if (clickedButton) {
                    // Зберігаємо оригінальний вміст кнопки
                    $(clickedButton).data('original-content', $(clickedButton).html());
                    // Замінюємо вміст кнопки на спінер
                    $(clickedButton).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>').prop('disabled', true);
                }
            },
            complete: function () {
                $('#cart > button').button('reset');

                if (clickedButton) {
                    // Повертаємо оригінальний вміст кнопки після таймаута, під час якого показуємо галочку
                    setTimeout(function () {
                        $(clickedButton).html('<i class="fas fa-check fsz-16"></i>').prop('disabled', true);
                    }, 1000);

                    setTimeout(function () {
                        $(clickedButton).html($(clickedButton).data('original-content')).prop('disabled', false);
                    }, 1200);
                }
            },
            success: function (json) {
                $('.alert-dismissible, .text-danger').remove();

                if (page == 1 && json['error']) {
                    const headerHeight = document.querySelector('header').offsetHeight,
                        productID = document.getElementById('oct-product-id'),
                        productIDToCompare = parseInt(product_id);

                    if (productID) {
                        const tabsHeight = document.getElementById('oct-tabs').offsetHeight,
                            productIDValue = parseInt(productID.value);

                        if (document.body.getAttribute("data-popup-options")) {
                            octPopupProductOptions(product_id);
                            return;
                        } else if (productIDValue === productIDToCompare) {
                            scrollToElement('.ds-product-actions-middle', null, -headerHeight - tabsHeight - 42);
                            return;
                        }
                    }
                }

                if (json['redirect']) {
                    if (document.body.getAttribute("data-popup-options")) {
                        octPopupProductOptions(product_id);

                    } else {
                        location = json['redirect'];
                    }
                }

                if (json['error'] && json['error']['error_warning']) {
                    scNotify('danger', '<div class="alert-text-item">' + json['error']['error_warning'] + '</div>');
                }

                if (json['success']) {
                    if (json['isPopup']) {
                        octPopupCart();
                    } else {
                        scNotify('success', json['success']);
                    }

                    let cartIdsHolder = document.querySelector("[data-cart-ids]");

                    if (json.oct_cart_ids && json.oct_cart_ids.length > 0 && cartIdsHolder) {
                        cartIdsHolder.dataset.cartIds = json.oct_cart_ids;
                        setTimeout(function () {
                            setCartBtnAdded();
                        }, 1);
                    }

                    // Need to set timeout otherwise it wont update the total
                    setTimeout(function () {
                        $('#cart .ds-cart-qty').addClass('active').html(json['total_products']);
                    }, 100);
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },
    'update': function (key, quantity) {
        $.ajax({
            url: 'index.php?route=checkout/cart/edit',
            type: 'post',
            data: 'key=' + key + '&quantity=' + (typeof (quantity) != 'undefined' ? quantity : 1),
            dataType: 'json',
            cache: false,
            beforeSend: function () {
                $('#cart > button').button('loading');
            },
            complete: function () {
                $('#cart > button').button('reset');
            },
            success: function (json) {
                // Need to set timeout otherwise it wont update the total
                setTimeout(function () {
                    $('#cart .ds-cart-qty').html(json['total_products']);
                    $('.rm-header-cart-text').html(json['total_amount']);
                }, 100);

                var now_location = String(document.location.pathname);

                if ((now_location == '/cart/') || (now_location == '/cart') || (now_location == '/checkout/') || (now_location == '/checkout') || (getURLVar('route') == 'checkout/cart') || (getURLVar('route') == 'checkout/checkout')) {
                    location = 'index.php?route=checkout/cart';
                } else {
                    $('#cart > ul').load('index.php?route=common/cart/info ul li');
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },
    'remove': function (key) {
        $.ajax({
            url: 'index.php?route=checkout/cart/remove',
            type: 'post',
            data: 'key=' + key,
            dataType: 'json',
            cache: false,
            beforeSend: function () {
                $('#cart > button').button('loading');
            },
            complete: function () {
                console.log('complete');
                $('#cart > button').button('reset');
            },
            success: function (json) {
                let cartIdsHolder = document.querySelector("[data-cart-ids]");

                if (json.oct_cart_ids && json.oct_cart_ids.length > 0 && cartIdsHolder) {
                    cartIdsHolder.dataset.cartIds = json.oct_cart_ids;
                }

                if (json['total_products'] > 0) {
                    $('#cart .ds-cart-qty').removeClass('active');
                }

                // Need to set timeout otherwise it wont update the total
                setTimeout(function () {
                    $('#cart .ds-cart-qty').html(json['total_products']);
                }, 100);

                var now_location = String(document.location.pathname);

                if ((now_location == '/cart/') || (now_location == '/cart') || (now_location == '/checkout/') || (now_location == '/checkout') || (getURLVar('route') == 'checkout/cart') || (getURLVar('route') == 'checkout/checkout')) {
                    location = 'index.php?route=checkout/cart';
                } else {
                    $('#cart > ul').load('index.php?route=common/cart/info ul li');
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    }
}

var voucher = {
    'add': function () {

    },
    'remove': function (key) {
        $.ajax({
            url: 'index.php?route=checkout/cart/remove',
            type: 'post',
            data: 'key=' + key,
            dataType: 'json',
            cache: false,
            beforeSend: function () {
                $('#cart > button').button('loading');
            },
            complete: function () {
                $('#cart > button').button('reset');
            },
            success: function (json) {
                // Need to set timeout otherwise it wont update the total
                setTimeout(function () {
                    $('#cart .ds-cart-qty').html(json['total_products']);
                }, 100);

                var now_location = String(document.location.pathname);

                if ((now_location == '/cart/') || (now_location == '/cart') || (now_location == '/checkout/') || (now_location == '/checkout') || (getURLVar('route') == 'checkout/cart') || (getURLVar('route') == 'checkout/checkout')) {
                    location = 'index.php?route=checkout/cart';
                } else {
                    $('#cart > ul').load('index.php?route=common/cart/info ul li');
                }

                const cartModal = document.getElementById('cartModal');

                if (cartModal) {
                    // закрити а потім відкрити заново модальне вікно
                    $('#cartModal').modal('hide');
                    octPopupCart();

                    setTimeout(function () {
                        $('#cartModal').modal('show');
                    }, 500);

                    return;
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    }
}

var wishlist = {
    'add': function (product_id) {
        $.ajax({
            url: 'index.php?route=account/wishlist/add',
            type: 'post',
            data: 'product_id=' + product_id,
            dataType: 'json',
            cache: false,
            success: function (json) {
                $('.alert-dismissible').remove();

                if (json['redirect']) {
                    location = json['redirect'];
                }

                if (json['success']) {
                    scNotify('success', json['success']);
                    $('.ds-header-wishlist-button .badge, .ds-mobile-bottom-nav-item-wishlist .ds-mobile-bottom-nav-item-badge').html(json['total_wishlist']);
                }

            },
            error: function (xhr, ajaxOptions, thrownError) {
                console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },
    'remove': function (product_id) {
        $.ajax({
            url: 'index.php?route=octemplates/events/helper/wishlistRemove',
            type: 'post',
            data: 'product_id=' + product_id,
            dataType: 'json',
            cache: false,
            success: function (json) {

                if (json['success']) {
                    scNotify('success', json['success']);
                    $('.ds-header-wishlist-button .badge, .ds-mobile-bottom-nav-item-wishlist .ds-mobile-bottom-nav-item-badge').html(json['total_wishlist']);
                }
            }
        });
    }
}

var compare = {
    'add': function (product_id) {
        $.ajax({
            url: 'index.php?route=product/compare/add',
            type: 'post',
            data: 'product_id=' + product_id,
            dataType: 'json',
            cache: false,
            success: function (json) {
                $('.alert-dismissible').remove();

                if (json['success']) {
                    scNotify('success', json['success']);
                    $('.ds-header-compare-button .badge, .ds-mobile-bottom-nav-item-compare .ds-mobile-bottom-nav-item-badge').html(json['total_compare']);
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },
    'remove': function (product_id) {
        $.ajax({
            url: 'index.php?route=octemplates/events/helper/compareRemove',
            type: 'post',
            data: 'product_id=' + product_id,
            dataType: 'json',
            cache: false,
            success: function (json) {

                if (json['success']) {
                    scNotify('success', json['success']);
                    $('.ds-header-compare-button .badge, .ds-mobile-bottom-nav-item-compare .ds-mobile-bottom-nav-item-badge').html(json['total_compare']);
                }
            }
        });
    }
}

/* Agree to Terms */
$(document).delegate('.agree', 'click', function (e) {
    e.preventDefault();
    masked('body', true);
    $('#modal-agree').remove();

    let element = this,
        link = '';
    let r = $(element).data('rel');

    if (r && r != 'undefined') {
        link = 'index.php?route=information/information/agree&information_id=' + r;
    } else {
        link = $(element).attr('href');
    }

    $.ajax({
        url: link,
        type: 'get',
        dataType: 'html',
        cache: false,
        success: function (data) {
            let html;
            
            html = '<div class="modal fade" id="modal-agree" tabindex="-1" role="dialog" aria-labelledby="modal-agree" aria-hidden="true">';
            html += '  <div class="modal-dialog modal-dialog-centered wide">';
            html += '    <div class="modal-content">';
            html += '      <div class="modal-header p-0 pb-4">';
            html += '        <h5 class="modal-title fsz-20 fw-700 d-flex align-items-center justify-content-between">' + $(element).text() + '</h5>';
            html += '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="13" viewBox="0 0 12 13" fill="none"><path d="M11.8029 11.5725C12.0633 11.8329 12.0633 12.2551 11.8029 12.5155C11.6731 12.6453 11.5025 12.7111 11.3319 12.7111C11.1612 12.7111 10.9906 12.6462 10.8608 12.5155L5.99911 7.65384L1.13743 12.5155C1.00766 12.6453 0.837017 12.7111 0.666369 12.7111C0.495722 12.7111 0.325075 12.6462 0.195312 12.5155C-0.0651039 12.2551 -0.0651039 11.8329 0.195312 11.5725L5.057 6.71085L0.195312 1.8492C-0.0651039 1.58878 -0.0651039 1.16657 0.195312 0.906158C0.455727 0.645742 0.877907 0.645742 1.13832 0.906158L6.00001 5.76787L10.8617 0.906158C11.1221 0.645742 11.5443 0.645742 11.8047 0.906158C12.0651 1.16657 12.0651 1.58878 11.8047 1.8492L6.943 6.71085L11.8029 11.5725Z" fill="#00A8E8" /></svg></button>';
            html += '      </div>';
            html += '      <div class="modal-body modal-body-agree pt-4 secondary-text modal-body-popup-text fsz-16">' + data + '</div>';
            html += '    </div>';
            html += '  </div>';
            html += '</div>';

            $('body').append(html);
            masked('body', false);
            $('#modal-agree').modal('show');
        }
    });
});

// Autocomplete */
(function ($) {
    $.fn.autocomplete = function (option) {
        return this.each(function () {
            this.timer = null;
            this.items = new Array();

            $.extend(this, option);

            $(this).attr('autocomplete', 'off');

            // Focus
            $(this).on('focus', function () {
                this.request();
            });

            // Blur
            $(this).on('blur', function () {
                setTimeout(function (object) {
                    object.hide();
                }, 200, this);
            });

            // Keydown
            $(this).on('keydown', function (event) {
                switch (event.keyCode) {
                    case 27: // escape
                        this.hide();
                        break;
                    default:
                        this.request();
                        break;
                }
            });

            // Click
            this.click = function (event) {
                event.preventDefault();

                value = $(event.target).parent().attr('data-value');

                if (value && this.items[value]) {
                    this.select(this.items[value]);
                }
            }

            // Show
            this.show = function () {
                var pos = $(this).position();

                $(this).siblings('ul.dropdown-menu').css({
                    top: pos.top + $(this).outerHeight(),
                    left: pos.left
                });

                $(this).siblings('ul.dropdown-menu').show();
            }

            // Hide
            this.hide = function () {
                $(this).siblings('ul.dropdown-menu').hide();
            }

            // Request
            this.request = function () {
                clearTimeout(this.timer);

                this.timer = setTimeout(function (object) {
                    object.source($(object).val(), $.proxy(object.response, object));
                }, 200, this);
            }

            // Response
            this.response = function (json) {
                html = '';

                if (json.length) {
                    for (i = 0; i < json.length; i++) {
                        this.items[json[i]['value']] = json[i];
                    }

                    for (i = 0; i < json.length; i++) {
                        if (!json[i]['category']) {
                            html += '<li data-value="' + json[i]['value'] + '"><a href="#">' + json[i]['label'] + '</a></li>';
                        }
                    }

                    // Get all the ones with a categories
                    var category = new Array();

                    for (i = 0; i < json.length; i++) {
                        if (json[i]['category']) {
                            if (!category[json[i]['category']]) {
                                category[json[i]['category']] = new Array();
                                category[json[i]['category']]['name'] = json[i]['category'];
                                category[json[i]['category']]['item'] = new Array();
                            }

                            category[json[i]['category']]['item'].push(json[i]);
                        }
                    }

                    for (i in category) {
                        html += '<li class="dropdown-header">' + category[i]['name'] + '</li>';

                        for (j = 0; j < category[i]['item'].length; j++) {
                            html += '<li data-value="' + category[i]['item'][j]['value'] + '"><a href="#">&nbsp;&nbsp;&nbsp;' + category[i]['item'][j]['label'] + '</a></li>';
                        }
                    }
                }

                if (html) {
                    this.show();
                } else {
                    this.hide();
                }

                $(this).siblings('ul.dropdown-menu').html(html);
            }

            $(this).after('<ul class="dropdown-menu"></ul>');
            $(this).siblings('ul.dropdown-menu').delegate('a', 'click', $.proxy(this.click, this));

        });
    }
})(window.jQuery);