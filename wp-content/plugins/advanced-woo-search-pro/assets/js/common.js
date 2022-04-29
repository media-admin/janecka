// Hooks
var AwsHooks = AwsHooks || {};
AwsHooks.filters = AwsHooks.filters || {};

(function($){
    "use strict";

    var selector = '.aws-container';
    var instance = 0;
    var pluginPfx = 'aws_opts';

    AwsHooks.add_filter = function( tag, callback, priority ) {

        if( typeof priority === "undefined" ) {
            priority = 10;
        }

        AwsHooks.filters[ tag ] = AwsHooks.filters[ tag ] || [];
        AwsHooks.filters[ tag ].push( { priority: priority, callback: callback } );

    };

    AwsHooks.apply_filters = function( tag, value, options ) {

        var filters = [];

        if( typeof AwsHooks.filters[ tag ] !== "undefined" && AwsHooks.filters[ tag ].length > 0 ) {

            AwsHooks.filters[ tag ].forEach( function( hook ) {

                filters[ hook.priority ] = filters[ hook.priority ] || [];
                filters[ hook.priority ].push( hook.callback );
            } );

            filters.forEach( function( AwsHooks ) {

                AwsHooks.forEach( function( callback ) {
                    value = callback( value, options );
                } );

            } );
        }

        return value;

    };

    $.fn.aws_search = function( options ) {

        var methods = {

            init: function() {

                var html = '';

                if ( d.filters ) {

                    var filters = $.parseJSON( d.filters.replace( /'/g, '"' ) );

                    html += '<div id="aws-main-filter-' + instance + '" class="aws-main-filter__dropdown">';

                    $.each(filters.filters, function (i, result) {

                        $.each(result, function (i, result) {
                            html += '<div class="aws-main-filter__choose" data-filter="' + i + '">' + result + '</div>';
                        });

                    });

                    html += '</div>';

                }

                html += '<div id="aws-search-result-' + instance + '" class="aws-search-result" style="display: none;">';

                html += '<div class="aws_result_scroll">';

                html += '<div class="aws_result_inner"></div>';

                html += '</div>';

                html += '</div>';

                // @since 2.16
                var appendResultsTo = AwsHooks.apply_filters( 'aws_results_append_to', 'body', { instance: instance, form: self, data: d } );

                $(appendResultsTo).append(html);

                methods.addClasses();

                setTimeout(function() { methods.resultLayout(); }, 500);
                setTimeout(function() { methods.mainFilterLayout(); }, 500);

            },

            onKeyup: function(e) {

                searchFor = $searchField.val();
                searchFor = searchFor.trim();
                searchFor = searchFor.replace( /<>\{\}\[\]\\\/]/gi, '' );
                searchFor = searchFor.replace( /\s\s+/g, ' ' );

                methods.removeSearchAddon();
                $searchSuggest.text( searchFor );

                for ( var i = 0; i < requests.length; i++ ) {
                    requests[i].abort();
                }

                methods.searchRequest();

            },

            searchRequest: function() {

                if ( ! d.ajaxSearch ) {
                    return;
                }

                $(d.resultBlock).find('.mCSB_container, .mCSB_dragger').css('top', 0);

                if ( ( typeof cachedResponse[d.filter] != 'undefined' ) && cachedResponse[d.filter].hasOwnProperty( searchFor ) ) {
                    methods.showResults( cachedResponse[d.filter][searchFor] );
                    return;
                }

                if ( searchFor === '' ) {
                    $(d.resultBlock).find('.aws_result_inner').html('');
                    methods.hideLoader();
                    methods.resultsHide();
                    return;
                }

                if ( searchFor.length < d.minChars ) {
                    $(d.resultBlock).find('.aws_result_inner').html('');
                    methods.hideLoader();
                    return;
                }

                if ( d.showLoader ) {
                    methods.showLoader();
                }

                var searchTimeout = d.searchTimeout > 100 ? d.searchTimeout : 300;

                clearTimeout( keyupTimeout );
                keyupTimeout = setTimeout( function() {
                    methods.ajaxRequest();
                }, searchTimeout );

            },

            ajaxRequest: function() {

                var data = {
                    action: 'aws_action',
                    keyword : searchFor,
                    aws_page: d.pageId,
                    aws_tax: d.tax,
                    aws_id: d.id,
                    aws_filter: d.filter,
                    lang: d.lang,
                    pageurl: window.location.href,
                    typedata: 'json'
                };

                requests.push(

                    $.ajax({
                        type: 'POST',
                        url: ajaxUrl,
                        data: data,
                        dataType: 'json',
                        success: function( response ) {

                            if ( cachedResponse[d.filter] == undefined ) {
                                cachedResponse[d.filter] = new Array();
                            }

                            cachedResponse[d.filter][searchFor] = response;

                            methods.showResults( response );

                            methods.showResultsBlock();

                            methods.analytics( searchFor );

                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.log( "Request failed: " + textStatus );
                            methods.hideLoader();
                        }
                    })

                );

            },

            showResults: function( response ) {

                var target = d.targetBlank ? 'target="_blank"' : '';
                var resultNum = 0;

                var html = '';

                html += '<div class="aws_results ' + response.style + '">';


                if ( typeof response.tax !== 'undefined' ) {

                    $.each(response.tax, function (i, taxes) {

                        if ( ( typeof taxes !== 'undefined' ) && taxes.length > 0 ) {
                            $.each(taxes, function (i, taxitem) {

                                resultNum++;

                                html += '<div class="aws_result_item aws_result_tax" data-title="' + taxitem.name + '">';

                                html += '<a class="aws_result_link_top" href="' + taxitem.link + '" ' + target + '>' + taxitem.name + '</a>';

                                html += '<span class="aws_result_content">';
                                        html += '<span class="aws_result_head">';
                                            if ( taxitem.image ) {
                                                html += '<img height="16" width="16" src="' + taxitem.image + '" class="aws_tax_image">';
                                            }
                                            html += taxitem.name;
                                            if ( taxitem.count ) {
                                                html += '<span class="aws_result_count">&nbsp;(' + taxitem.count + ')</span>';
                                            }
                                        html += '</span>';
                                        if ( ( typeof taxitem.excerpt !== 'undefined' ) && taxitem.excerpt ) {
                                            html += '<span class="aws_result_excerpt">' + taxitem.excerpt + '</span>';
                                        }
                                    html += '</span>';
                                html += '</div>';

                            });
                        }

                    });

                }

                if ( typeof response.users !== 'undefined' ) {

                    $.each(response.users, function (i, users) {

                        if ( ( typeof users !== 'undefined' ) && users.length > 0 ) {
                            $.each(users, function (i, useritem) {

                                resultNum++;

                                html += '<div class="aws_result_item aws_result_user" data-title="' + useritem.name + '">';

                                html += '<a class="aws_result_link_top" href="' + useritem.link + '" ' + target + '>' + useritem.name + '</a>';

                                html += '<span class="aws_result_content">';
                                        html += '<span class="aws_result_head">';
                                            if ( useritem.image ) {
                                                html += '<img height="16" width="16" src="' + useritem.image + '" class="aws_tax_image">';
                                            }
                                            html += useritem.name;
                                        html += '</span>';
                                        if ( ( typeof useritem.excerpt !== 'undefined' ) && useritem.excerpt ) {
                                            html += '<span class="aws_result_excerpt">' + useritem.excerpt + '</span>';
                                        }
                                    html += '</span>';
                                html += '</div>';

                            });
                        }

                    });

                }

                if ( ( typeof response.products !== 'undefined' ) && response.products.length > 0 ) {

                    $.each(response.products, function (i, result) {

                        resultNum++;

                        var isOnSale = result.on_sale ? ' on-sale' : '';

                        html += '<div class="aws_result_item' + isOnSale + '" data-title="' + result.title.replace(/(<[\s\S]*>)/gm, '') + '">';

                        html += '<a class="aws_result_link_top" href="' + result.link + '" ' + target + '>' + result.title.replace(/(<[\s\S]*>)/gm, '') + '</a>';

                        if ( result.image ) {
                            html += '<span class="aws_result_image">';
                            html += '<img src="' + result.image + '">';
                            html += '</span>';
                        }

                        html += '<span class="aws_result_content">';

                        html += '<span class="aws_result_head">';

                        html += '<span class="aws_result_title">';

                        if ( result.featured ) {
                            html += '<span class="aws_result_featured" title="Featured"><svg version="1.1" viewBox="0 0 20 21" xmlns="http://www.w3.org/2000/svg" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns" xmlns:xlink="http://www.w3.org/1999/xlink"><g fill-rule="evenodd" stroke="none" stroke-width="1"><g transform="translate(-296.000000, -422.000000)"><g transform="translate(296.000000, 422.500000)"><path d="M10,15.273 L16.18,19 L14.545,11.971 L20,7.244 L12.809,6.627 L10,0 L7.191,6.627 L0,7.244 L5.455,11.971 L3.82,19 L10,15.273 Z"/></g></g></g></svg></span>';
                        }

                        html += result.title;

                        html += '</span>';

                        if ( result.price ) {
                            html += '<span class="aws_result_price">' + result.price + '</span>';
                        }

                        html += '</span>';

                        if ( result.add_to_cart ) {

                            html += '<span class="aws_add_to_cart">';

                            html += '<span style="z-index:2;" data-cart-text="' + result.add_to_cart.i18n_view_cart + '" data-cart-url="' + result.add_to_cart.cart_url + '" data-product_id="' + result.add_to_cart.id + '" data-permalink="' + result.add_to_cart.permalink + '" data-cart="' + result.add_to_cart.url + '" class="aws_cart_button">';
                                html += '<span class="aws_cart_button_text">' + result.add_to_cart.text + '</span>';
                            html += '</span>';

                            if ( result.add_to_cart.quantity && result.add_to_cart.permalink.indexOf('add-to-cart') !== -1 ) {

                                var step = ( typeof result.add_to_cart.quantity_step !== 'undefined' ) ? result.add_to_cart.quantity_step : '1';
                                var quantity_val = ( typeof result.add_to_cart.quantity_value !== 'undefined' ) ? result.add_to_cart.quantity_value : '1';
                                var quantity_min = ( typeof result.add_to_cart.quantity_min !== 'undefined' ) ? result.add_to_cart.quantity_min : '1';
                                var quantity_max = ( typeof result.add_to_cart.quantity_max !== 'undefined' ) ? result.add_to_cart.quantity_max : '';
                                var quantity_inputmode = ( typeof result.add_to_cart.inputmode !== 'undefined' ) ? result.add_to_cart.inputmode : 'numeric';
                                html += '<input style="z-index:2;" type="number" inputmode="' + quantity_inputmode + '" data-quantity class="aws_quantity_field" step="' + step + '" min="' + quantity_min + '" max="' + quantity_max + '" name="quantity" value="' + quantity_val + '" title="Quantity" size="4">';

                                if ( methods.isMobile() ) {
                                    html += '<span class="aws_quantity_change" data-quantity-change="plus">+</span><span class="aws_quantity_change" data-quantity-change="minus">-</span>';
                                }

                            }

                            html += '</span>';

                        }

                        if ( result.stock_status ) {
                            var statusClass = result.stock_status.status ? 'in' : 'out';
                            html += '<span class="aws_result_stock ' + statusClass + '">';
                            html += result.stock_status.text;
                            html += '</span>';
                        }

                        if ( result.sku ) {
                            html += '<span class="aws_result_sku">' + d.sku + result.sku + '</span>';
                        }

                        if ( result.brands ) {
                            html += '<span class="aws_result_brands">';

                            $.each(result.brands, function (i, brand) {
                                html += '<span class="aws_brand">';

                                if ( brand.image ) {
                                    html += '<img height="16" width="16" src="' + brand.image + '" class="aws_brand_image">';
                                }

                                html += '<span class="aws_brand_name">' + brand.name + '</span>';

                                html += '</span>';
                            });

                            html += '</span>';
                        }

                        if ( result.excerpt ) {
                            html += '<span class="aws_result_excerpt">' + result.excerpt + '</span>';
                        }

                        if ( result.rating ) {
                            html += '<span class="aws_rating">';
                            html += '<span class="aws_votes">';
                            html += '<span class="aws_current_votes" style="width: ' + result.rating + '%;"></span>';
                            html += '</span>';
                            html += '<span class="aws_review">' + result.reviews + '</span>';
                            html += '';
                            html += '</span>';
                        }

                        if ( result.variations ) {
                            html += '<span class="aws_variations">';

                            $.each(result.variations, function (i, variation) {
                                if ( variation ) {
                                    html += '<span class="aws_variation">';

                                    html += '<span class="aws_variation_name">' + i.replace('pa_', '') + '</span>';

                                    html += '<span class="aws_variations_list">';
                                    $.each(variation, function (i, var_name) {
                                        html += '<span class="aws_variation_subname">' + var_name + '</span>';
                                    });
                                    html += '</span>';

                                    html += '</span>';
                                }
                            });

                            html += '</span>';
                        }

                        if ( result.categories ) {
                            html += '<span class="aws_result_term">' + result.categories + '</span>';
                        }

                        html += '</span>';

                        if ( result.on_sale ) {
                            html += '<span class="aws_result_sale">';
                                html += '<span class="aws_onsale">' + d.saleBadge + '</span>';
                            html += '</span>';
                        }

                        html += '</div>';

                    });


                    if ( d.showMore && d.showPage ) {
                        html += '<a class="aws_result_item aws_search_more" href="#">' + d.more + '</a>';
                    }

                    //html += '<li class="aws_result_item"><a href="#">Next Page</a></li>';

                }


                if ( ! resultNum ) {
                    $( d.resultBlock).addClass('aws_no_result');
                    html += '<a class="aws_result_item">' + d.notFound + '</a>';
                } else {
                    $( d.resultBlock).removeClass('aws_no_result');
                }

                if ( resultNum === 1 ) {
                    $( d.resultBlock).addClass('aws_one_result');
                } else {
                    $( d.resultBlock).removeClass('aws_one_result');
                }


                html += '</div>';

                // @since 2.05
                html = AwsHooks.apply_filters( 'aws_results_html', html, { response: response, data: d } );

                
                methods.hideLoader();

                $(d.resultBlock).find('.aws_result_inner').html( html );

                methods.showResultsBlock();

                if ( eShowResults ) {
                    self[0].dispatchEvent( eShowResults );
                }

            },

            showResultsBlock: function() {
                methods.resultLayout();
                methods.resultsShow();
            },

            showLoader: function() {
                $searchForm.addClass('aws-processing');
            },

            hideLoader: function() {
                $searchForm.removeClass('aws-processing');
            },

            resultsShow: function() {
                $(d.resultBlock).show();
                $searchForm.addClass('aws-form-active');
            },

            resultsHide: function() {
                $(d.resultBlock).hide();
                $searchForm.removeClass('aws-form-active');
            },

            onFocus: function( event ) {

                var show = AwsHooks.apply_filters( 'aws_show_modal_layout', false, { instance: instance, form: self, data: d } );

                if ( ! $('body').hasClass('aws-overlay') && ( ( methods.isMobile() && d.mobileScreen && ! methods.isFixed() ) || show ) ) {
                    methods.showMobileLayout();
                }

                if ( searchFor !== '' ) {
                    methods.showResultsBlock();
                }

            },

            hideResults: function( event ) {
                if ( ! $(event.target).closest( self ).length
                    && ! $(event.target).closest( d.mainFilter ).length
                    && ! $(event.target).closest( d.resultBlock ).length
                ) {
                    methods.resultsHide();
                    methods.removeSearchAddon();
                }
            },

            isResultsVisible:function() {
                return $(d.resultBlock).is(":visible");
            },

            removeHovered: function() {
                $( d.resultBlock ).find('.aws_result_item').removeClass('hovered');
            },

            addSearchAddon: function() {

                if ( $searchAddon.length > 0 ) {
                    var title = $(this).data('title');
                    if ( title ) {
                        $searchAddon.text(title).addClass('active');
                    }
                }

            },

            removeSearchAddon: function() {

                if ( $searchAddon.length > 0 ) {
                    $searchAddon.text('').removeClass('active');
                }

            },

            resultLayout: function () {

                var $resultsBlock = $( d.resultBlock );
                var offset = self.offset();
                var bodyOffset = $('body').offset();
                var bodyPosition = $('body').css('position');
                var bodyHeight = $(document).height();
                var resultsHeight = $resultsBlock.height();

                // @since 2.45
                var forcePosition = AwsHooks.apply_filters( 'aws_results_force_position', false, { resultsBlock: $resultsBlock, form: self } );

                if ( offset && bodyOffset ) {

                    var styles = {
                        width: self.outerWidth(),
                        top : 0,
                        left: 0
                    };

                    if ( styles.width <= 500 ) {
                        $resultsBlock.addClass('less500');
                    } else {
                        $resultsBlock.removeClass('less500');
                    }

                    if ( bodyPosition === 'relative' || bodyPosition === 'absolute' || bodyPosition === 'fixed' ) {
                        styles.top = offset.top + $(self).innerHeight() - bodyOffset.top;
                        styles.left = offset.left - bodyOffset.left;
                    } else {
                        styles.top = offset.top + $(self).innerHeight();
                        styles.left = offset.left;
                    }

                    if ( ( ( bodyHeight - offset.top < 500 ) && ! forcePosition ) || ( forcePosition && forcePosition == 'top' ) ) {
                        resultsHeight = methods.getResultsBlockHeight();
                        if ( ( ( bodyHeight - offset.top < resultsHeight ) && ( offset.top >= resultsHeight ) ) || forcePosition ) {
                            styles.top = styles.top - resultsHeight - $(self).innerHeight();
                        }
                    }

                    // @since 2.10
                    styles = AwsHooks.apply_filters( 'aws_results_layout', styles, { resultsBlock: $resultsBlock, form: self } );

                    $resultsBlock.css( styles );

                }

            },

            mainFilterLayout: function () {
                var offset = self.offset();

                if ( offset ) {

                    var bodyWidth = $('body').outerWidth();
                    var bodyHeight = $('body').height();
                    var width = self.outerWidth();
                    var top = offset.top + $(self).innerHeight();
                    var left = offset.left;
                    var right = bodyWidth - left - width;
                    var bodyPosition = $('body').css('position');
                    var filterWidth = $( d.mainFilter ).outerWidth();
                    var filterHeight = $( d.mainFilter ).outerHeight();
                    var toRight = false;

                    // @since 2.45
                    var forcePosition = AwsHooks.apply_filters( 'aws_results_force_position', false, { mainFilter: d.mainFilter, form: self } );

                    var styles = {
                        top : top,
                        right: right
                    };

                    if ( bodyPosition === 'relative' || bodyPosition === 'absolute' || bodyPosition === 'fixed' ) {
                        var bodyOffset = $('body').offset();
                        styles.top = styles.top - bodyOffset.top;
                        styles.right = styles.right + bodyOffset.left;
                    }

                    if ( d.btsLayout == '3' || d.btsLayout == '4' || d.btsLayout == '6' ) {
                        styles.right = styles.right + width - filterWidth;
                        toRight = true;
                    }

                    if ( $('body').hasClass('rtl') ) {
                        if ( toRight ) {
                            styles.right = styles.right - width + filterWidth;
                        } else {
                            styles.right = styles.right + width - filterWidth;
                        }
                    }

                    if ( ( ( bodyHeight - offset.top < filterHeight ) && ! forcePosition ) || ( forcePosition && forcePosition == 'top' ) ) {
                        styles.top = styles.top - filterHeight - $(self).innerHeight();
                    }

                    //  @since 2.42
                    styles = AwsHooks.apply_filters( 'aws_main_filter_layout', styles, { mainFilter: d.mainFilter, form: self } );

                    $( d.mainFilter ).css( styles );

                }

            },

            getResultsBlockHeight: function() {

                var $resultsBlock = $( d.resultBlock );
                var resultsHeight = $resultsBlock.height();

                if ( resultsHeight === 0 ) {
                    var copied_elem = $resultsBlock.clone()
                        .attr("id", false)
                        .css({visibility:"hidden", display:"block",
                            position:"absolute"});
                    $("body").append(copied_elem);
                    copied_elem.find('.mCSB_outside').attr('style', '');
                    resultsHeight = copied_elem.height();
                    copied_elem.remove();
                }

                return resultsHeight;

            },

            getDocumentMargins: function() {
                var htmlMargin = $('html').outerHeight(true) - $('html').outerHeight();
                var bodyMargin = $('body').outerHeight(true) - $('body').outerHeight();
                return htmlMargin + bodyMargin;
            },

            showMainFilter: function () {
                methods.mainFilterLayout();
                $(d.mainFilter).toggleClass('active');
            },

            hideMainFilter: function ( e ) {
                if ( ! $(e.target).closest( $mainFilter ).length ) {
                    $(d.mainFilter).removeClass('active');
                }
            },

            changeMainFilter: function () {

                var self = $(this);
                var value = self.text();
                var newFilterId = self.data('filter');

                $mainFilterCurrent.text( value );

                if ( d.filter !== newFilterId ) {
                    d.filter = newFilterId;
                    $filterHiddenField.val( newFilterId );
                    if ( $catHiddenField.length > 0 ) {
                        $catHiddenField.val( methods.analyticsGetCat() );
                    }
                    methods.searchRequest();
                }

            },

            showMobileLayout: function() {
                self.after('<div class="aws-placement-container"></div>');
                self.addClass('aws-mobile-fixed').prepend('<div class="aws-mobile-fixed-close"><svg width="17" height="17" viewBox="1.5 1.5 21 21"><path d="M22.182 3.856c.522-.554.306-1.394-.234-1.938-.54-.543-1.433-.523-1.826-.135C19.73 2.17 11.955 10 11.955 10S4.225 2.154 3.79 1.783c-.438-.371-1.277-.4-1.81.135-.533.537-.628 1.513-.25 1.938.377.424 8.166 8.218 8.166 8.218s-7.85 7.864-8.166 8.219c-.317.354-.34 1.335.25 1.805.59.47 1.24.455 1.81 0 .568-.456 8.166-7.951 8.166-7.951l8.167 7.86c.747.72 1.504.563 1.96.09.456-.471.609-1.268.1-1.804-.508-.537-8.167-8.219-8.167-8.219s7.645-7.665 8.167-8.218z"></path></svg></div>');
                $('body').addClass('aws-overlay').append('<div class="aws-overlay-mask"></div>').append( self );
                $searchField.focus();
            },

            hideMobileLayout: function() {
                $('.aws-placement-container').after( self ).remove();
                self.removeClass('aws-mobile-fixed');
                $('body').removeClass('aws-overlay');
                $('.aws-mobile-fixed-close').remove();
                $('.aws-overlay-mask').remove();
            },

            isFixed: function() {
                var $checkElements = self.add(self.parents());
                var isFixed = false;
                $checkElements.each(function(){
                    if ($(this).css("position") === "fixed") {
                        isFixed = true;
                        return false;
                    }
                });
                return isFixed;
            },

            analytics: function( label ) {
                if ( d.useAnalytics ) {

                    var ga_cat = methods.analyticsGetCat();
                    var sPage = '/?s=' + encodeURIComponent( 'ajax-search:' + label ) + '&awscat=' + encodeURIComponent( ga_cat );

                    try {
                        if ( typeof gtag !== 'undefined' && gtag !== null ) {
                            gtag('event', 'AWS search', {
                                'event_label': label,
                                'event_category': 'AWS Search Form ' + d.id,
                                'transport_type' : 'beacon'
                            });
                            gtag('event', 'page_view', {
                                'page_path': sPage,
                                'page_title' : 'AWS search'
                            });
                        }
                        if ( typeof ga !== 'undefined' && ga !== null ) {
                            ga('send', 'event', 'AWS search', 'AWS Search Form ' + d.id, label);
                            ga( 'send', 'pageview', sPage );
                        }
                        if ( typeof pageTracker !== "undefined" && pageTracker !== null ) {
                            pageTracker._trackPageview( sPage );
                            pageTracker._trackEvent( 'AWS search', 'AWS Search Form ' + d.id, label )
                        }
                        if ( typeof _gaq !== 'undefined' && _gaq !== null ) {
                            _gaq.push(['_trackEvent', 'AWS search', 'AWS Search Form ' + d.id, label ]);
                            _gaq.push(['_trackPageview', sPage]);
                        }
                        // This uses Monster Insights method of tracking Google Analytics.
                        if ( typeof __gaTracker !== 'undefined' && __gaTracker !== null ) {
                            __gaTracker( 'send', 'pageview', sPage );
                            __gaTracker( 'send', 'event', 'AWS search', 'AWS Search Form ' + d.id, label );
                        }
                    }
                    catch (error) {
                    }

                }
            },

            analyticsGetCat: function() {
                var ga_cat = 'Form:' + d.id ;
                if ( $mainFilterCurrent.length > 0 ) {
                    ga_cat = ga_cat + ' Filter:' + $mainFilterCurrent.text();
                } else {
                    ga_cat = ga_cat + ' Filter:All';
                }
                return ga_cat;
            },

            addClasses: function() {
                if ( methods.isMobile() || d.showClear ) {
                    $searchForm.addClass('aws-show-clear');
                }
            },

            isMobile: function() {
                var check = false;
                (function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
                return check;
            },

            quantityChange: function() {

                var self = $(this);
                var parent = self.closest('.aws_add_to_cart');
                var quantity = parent.find('[data-quantity]');
                var quantityVal = parseFloat( quantity.val() );
                var quantityStep = parseFloat( quantity.attr('step') );
                var quantityMin = quantity.attr('min') ? parseFloat( quantity.attr('min') ) : 1;
                var quantityMax = quantity.attr('max') ? parseFloat( quantity.attr('max') ) : 9999;
                var sign = $(this).data('quantity-change');

                var countDecimals = function (value) {
                    if(Math.floor(value) === value) return 0;
                    return value.toString().split(".")[1].length || 0;
                }

                if ( sign === 'minus' && quantityVal > quantityMin ) {
                    quantityVal = quantityVal - quantityStep;
                } else if (  sign === 'plus' && quantityVal < quantityMax ) {
                    quantityVal = quantityVal + quantityStep;
                }

                quantityVal = quantityVal.toFixed( countDecimals(quantityStep) );

                quantity.val( quantityVal );

            },

            addToCart: function() {
                var self = $(this);
                var parent = self.closest('.aws_add_to_cart');
                var cartUrl = self.data('cart-url');
                var cartText = self.data('cart-text');
                var quantity = parent.find('[data-quantity]');
                var quantityVal = 1;

                if (parent.hasClass('active')) {
                    return;
                }

                if ( self.data('permalink').indexOf('add-to-cart') === -1 ) {
                    window.location = self.data('permalink');
                    return;
                }

                if ( quantity ) {
                    quantityVal = parseInt( quantity.val() );
                    quantityVal = ( quantityVal && quantityVal < 0 ) ? quantityVal * -1 : ( quantityVal ? quantityVal : 1 );
                }

                self.addClass('loading');

                $.ajax({
                    type: 'POST',
                    url: self.data('cart'),
                    data: {
                        product_sku: '',
                        product_id: self.data('product_id'),
                        quantity: quantityVal
                    },
                    success: function( response ) {
                        self.removeClass('loading');
                        parent.addClass('active');

                        if ( cartText && cartUrl ) {
                            self.html('<a href="'+cartUrl+'">'+cartText+'</a>');
                            self.removeAttr( "data-cart" );
                        } else {
                            self.find('span').text( d.itemAdded );
                        }

                        // Trigger event so themes can refresh other areas.
                        $( document.body ).trigger( 'added_to_cart', [ response.fragments, response.cart_hash, null ] );

                    },
                    error: function (data, dummy) {
                        self.removeClass('loading');
                    }
                });

            },

            createCustomEvent: function( event, params ) {

                var customEvent = false;
                params = params || null;

                if ( typeof window.CustomEvent === "function" ) {
                    customEvent = new CustomEvent( event, { bubbles: true, cancelable: true, detail: params } );

                }
                else if ( document.createEvent ) {
                    customEvent = document.createEvent( 'CustomEvent' );
                    customEvent.initCustomEvent( event, true, true, params );
                }

                return customEvent;

            },

            createAndDispatchEvent: function( obj, event, params ) {

                var customEvent = methods.createCustomEvent( event, params );

                if ( customEvent ) {
                    obj.dispatchEvent( customEvent );
                }

            }

        };


        var self               = $(this),
            $searchForm        = self.find('.aws-search-form'),
            $searchField       = self.find('.aws-search-field'),
            $searchSuggest     = self.find('.aws-suggest__keys'),
            $searchAddon       = self.find('.aws-suggest__addon'),
            $mainFilter        = self.find('.aws-main-filter'),
            $mainFilterCurrent = self.find('.aws-main-filter__current'),
            $filterHiddenField = self.find('.awsFilterHidden'),
            $catHiddenField    = self.find('.awsCatHidden'),
            $searchButton      = self.find('.aws-search-btn'),
            haveResults        = false,
            eShowResults       = false,
            requests           = Array(),
            searchFor          = '',
            keyupTimeout,
            cachedResponse     = new Array();


        var ajaxUrl = ( self.data('url') !== undefined ) ? self.data('url') : false;

        if ( document.createEvent ){
            eShowResults = document.createEvent("Event");
            eShowResults.initEvent('awsShowingResults', true, true);
            eShowResults.eventName = 'awsShowingResults';
        }

        if ( options === 'relayout' ) {
            var d = self.data(pluginPfx);
            methods.resultLayout();
            methods.mainFilterLayout();
            return;
        }

        instance++;

        self.data( pluginPfx, {
            id : ( self.data('id') !== undefined ) ? self.data('id') : 1,
            lang : ( self.data('lang') !== undefined ) ? self.data('lang') : false,
            minChars  : ( self.data('min-chars') !== undefined ) ? self.data('min-chars') : 1,
            showLoader: ( self.data('show-loader') !== undefined ) ? self.data('show-loader') : true,
            showMore: ( self.data('show-more') !== undefined ) ? self.data('show-more') : true,
            ajaxSearch: ( self.data('ajax-search') !== undefined ) ? self.data('ajax-search') : true,
            showPage: ( self.data('show-page') !== undefined ) ? self.data('show-page') : true,
            showClear: ( self.data('show-clear') !== undefined ) ? self.data('show-clear') : false,
            targetBlank: ( self.data('target-blank') !== undefined ) ? self.data('target-blank') : false,
            mobileScreen: ( self.data('mobile-screen') !== undefined ) ? self.data('mobile-screen') : false,
            useAnalytics: ( self.data('use-analytics') !== undefined ) ? self.data('use-analytics') : false,
            searchTimeout: ( self.data('timeout') !== undefined ) ? parseInt( self.data('timeout') ) : 300,
            filters: ( self.data('filters') !== undefined ) ? self.data('filters') : false,
            instance: instance,
            filter: ( self.data('init-filter') !== undefined ) ? self.data('init-filter') : 1,
            btsLayout: ( self.data('buttons-order') !== undefined ) ? self.data('buttons-order') : '1',
            resultBlock: '#aws-search-result-' + instance,
            mainFilter: '#aws-main-filter-' + instance,
            pageId: ( self.data('page-id') !== undefined ) ? self.data('page-id') : 0,
            tax: ( self.data('tax') !== undefined ) ? self.data('tax') : 0,
            notFound: self.data('notfound'),
            more: self.data('more'),
            sku: self.data('sku'),
            itemAdded: self.data('item-added'),
            saleBadge: self.data('sale-badge')
        });


        var d = self.data( pluginPfx );

        // AWS is fully loaded
        methods.createAndDispatchEvent( document, 'awsLoaded', { instance: instance, form: self, data: d } );

        $filterHiddenField.val( d.filter );


        if ( $searchForm.length > 0 ) {
            methods.init.call(this);
        }


        $searchField.on( 'keyup input', function(e) {
            if ( e.keyCode != 40 && e.keyCode != 38 ) {
                methods.onKeyup(e);
            }
        });


        $searchField.on( 'focus', function (e) {
            $searchForm.addClass('aws-focus');
            methods.onFocus(e);
        });


        $searchField.on( 'focusout', function (e) {
            $searchForm.removeClass('aws-focus');
        });


        $mainFilter.on( 'click', function (e) {
            methods.showMainFilter.call(this);
        });


        $(d.mainFilter).on( 'mouseenter', function (e) {
            $searchField.trigger('mouseenter');
        });


        $searchButton.on( 'click', function (e) {
            if ( d.showPage && $searchField.val() !== '' ) {
                $searchForm.submit();
            }
        });


        $( d.resultBlock ).on( 'click', '.aws_search_more', function(e) {
            e.preventDefault();
            $searchForm.submit();
        });


        $(d.mainFilter).find('.aws-main-filter__choose').on( 'click', function (e) {
            methods.changeMainFilter.call(this);
        });


        $(document).on( 'click', function (e) {
            methods.hideResults(e);
        });


        $(document).on( 'click', function (e) {
            methods.hideMainFilter(e);
        });


        $(window).on( 'resize', function(e) {
            methods.resultLayout();
            methods.mainFilterLayout();
        });


        $(window).on( 'scroll', function(e) {
            if ( $( d.resultBlock ).css('display') == 'block' ) {
                methods.resultLayout();
            }
            if ( $( d.mainFilter ).css('display') == 'block' ) {
                methods.mainFilterLayout();
            }
        });


        $( d.resultBlock ).on( 'mouseenter', '.aws_result_item', function() {
            methods.addSearchAddon.call(this);
            methods.removeHovered();
            $(this).addClass('hovered');
            $searchField.trigger('mouseenter');
        });


        $( d.resultBlock ).on( 'mouseleave', '.aws_result_item', function() {
            methods.removeHovered();
        });


        $( d.resultBlock ).on( 'click', '[data-cart]', function(e) {
            e.preventDefault();
            e.stopPropagation();
            methods.addToCart.call(this);
        });

        $( d.resultBlock ).on( 'click', '[data-quantity]', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });

        $( d.resultBlock ).on( 'click', '[data-quantity-change]', function(e) {
            e.preventDefault();
            e.stopPropagation();
            methods.quantityChange.call(this);
        });

        $( d.resultBlock ).on( 'click', 'span[href], [data-link]', function(e) {
            e.preventDefault();
            var link = $(this).data('link') ? $(this).data('link') : $(this).attr('href');
            if ( link === '' || link === '#' ) {
              return;
            }
            e.stopPropagation();
            if ( link ) {
                window.location = link;
            }
        });

        $searchForm.find('.aws-search-clear').on( 'click', function (e) {
            $searchField.val('');
            $searchField.focus();
            methods.resultsHide();
            methods.removeSearchAddon();
            searchFor = '';
        });

        $searchForm.on( 'keypress', function(e) {
            if ( e.keyCode == 13 && ( ! d.showPage || $searchField.val() === '' ) ) {
                e.preventDefault();
            }
        });

        $( self ).on( 'click', '.aws-mobile-fixed-close', function(e) {
            methods.hideMobileLayout();
        });

        $(window).on( 'keydown', function(e) {

            if ( e.keyCode == 40 || e.keyCode == 38 ) {
                if ( methods.isResultsVisible() ) {

                    e.stopPropagation();
                    e.preventDefault();

                    var $item = $( d.resultBlock ).find('.aws_result_item');
                    var $hoveredItem = $( d.resultBlock ).find('.aws_result_item.hovered');

                    if ( e.keyCode == 40 ) {

                        if ( $hoveredItem.length > 0 ) {
                            methods.removeHovered();
                            $hoveredItem.next().addClass('hovered');
                        } else {
                            $item.first().addClass('hovered');
                        }

                    }

                    if ( e.keyCode == 38 ) {

                        if ( $hoveredItem.length > 0 ) {
                            methods.removeHovered();
                            $hoveredItem.prev().addClass('hovered');
                        } else {
                            $item.last().addClass('hovered');
                        }

                    }

                    var activeItemOffset = $(".aws_result_item.hovered").position();
                    if ( activeItemOffset ) {
                        var $scrollDiv = $( d.resultBlock ).find('.aws_result_scroll');
                        $scrollDiv.animate({
                            scrollTop: activeItemOffset.top + $scrollDiv.scrollTop()
                        }, 400);
                    }

                    methods.addSearchAddon.call( $( d.resultBlock ).find('.aws_result_item.hovered') );

                }
            }

        });

    };


    // Call plugin method

    $(document).ready( function() {

        $(selector).each( function() {
            $(this).aws_search();
        });

        // Enfold header
        $('[data-avia-search-tooltip]').on( 'click', function() {
            window.setTimeout(function(){
                $(selector).aws_search();
            }, 1000);
        } );

        // Search results filters fix
        var $filters_widget = $('.woocommerce.widget_layered_nav_filters');
        var searchQuery = window.location.search;

        if ( $filters_widget.length > 0 && searchQuery ) {
            if ( searchQuery.indexOf('type_aws=true') !== -1 ) {
                var $filterLinks = $filters_widget.find('ul li.chosen a');
                if ( $filterLinks.length > 0 ) {
                    var addQuery = '&type_aws=true';
                    var filterParam = searchQuery.match('[?&]filter=([^&]+)');
                    var idParam = searchQuery.match('[?&]id=([^&]+)');
                    if ( filterParam ) {
                        addQuery = addQuery + '&filter=' + filterParam[1];
                    }
                    if ( idParam ) {
                        addQuery = addQuery + '&id=' + idParam[1];
                    }
                    $filterLinks.each( function() {
                        var filterLink = $(this).attr("href");
                        if ( filterLink && filterLink.indexOf('post_type=product') !== -1 ) {
                            $(this).attr( "href", filterLink + addQuery );
                        }
                    });
                }
            }
        }

    });


})( jQuery );