jQuery(document).ready(function ($) {
    'use strict';


    //Sortable for filters
    $('.aws-form-filters tbody').sortable({
        handle: ".aws-sort",
        items: ".aws-filter-item:not(.disabled)",
        axis: "y",
        update: function() {
            var instanceId;
            var order = new Array();

            $('.aws-filter-item').each( function() {
                instanceId = $(this).data('instance');
                order.push( $(this).data('id') );
            });

            $.ajax({
                type: 'POST',
                url: aws_vars.ajaxurl,
                data: {
                    action: 'aws-orderFilter',
                    instanceId: instanceId,
                    order: JSON.stringify(order),
                    _ajax_nonce: aws_vars.ajax_nonce
                },
                dataType: "json"
            });

        }
    }).disableSelection();


    // Select2 init

    function aws_init_select2() {

        $('.aws-rules-table select.aws-select2').select2({
            minimumResultsForSearch: 15
        });

        var awsSelect2Ajax = $('.aws-rules-table select.aws-select2-ajax');
        if ( awsSelect2Ajax.length > 0 ) {
            awsSelect2Ajax.each(function( index ) {
                $(this).select2({
                    ajax: {
                        type: 'POST',
                        delay: 250,
                        url: aws_vars.ajaxurl,
                        dataType: "json",
                        data: function (params) {
                            return {
                                search: params.term,
                                action: $(this).data('ajax'),
                                _ajax_nonce: aws_vars.ajax_nonce
                            };
                        },
                    },
                    placeholder: $(this).data('placeholder'),
                    minimumInputLength: 3,
                });
            });
        }

    }

    aws_init_select2();

    // Advanced admin filters

    var awsUniqueID = function() {
        return Math.random().toString(36).substr(2, 11);
    };

    var awsGetRuleTemplate = function( groupID, ruleID) {

        var template = $(this).closest('.aws-rules').find('#awsRulesTemplate').html();

        if ( typeof groupID !== 'undefined' ) {
            template = template.replace( /\[group_(.+?)\]/gi, '[group_'+groupID+']' );
        }

        if ( typeof ruleID !== 'undefined' ) {
            template = template.replace( /\[rule_(.+?)\]/gi, '[rule_'+ruleID+']' );
            template = template.replace( /data-aws-rule="(.+?)"/gi, 'data-aws-rule="'+ruleID+'"' );
        }

        return template;

    };

    $(document).on( 'click', '[data-aws-remove-rule]', function(e) {
        e.preventDefault();
        var $table = $(this).closest('.aws-rules-table');
        var $container = $(this).closest('.aws-rules');
        $(this).closest('[data-aws-rule]').remove();

        if ( $table.find('[data-aws-rule]').length < 1 ) {
            $table.remove();
        }

        if ($container.find('[data-aws-rule]').length < 1 ) {
            $container.addClass('aws-rules-empty');
        }

    });


    $(document).on( 'click', '[data-aws-add-rule]', function(e) {
        e.preventDefault();

        var groupID = $(this).closest('.aws-rules-table').data('aws-group');
        var ruleID = awsUniqueID();
        var rulesTemplate = awsGetRuleTemplate.call(this, groupID, ruleID);

        $(this).closest('.aws-rules-table').find( '.aws-rule' ).last().after( rulesTemplate );
        $(this).closest('.aws-rules').removeClass('aws-rules-empty');
        aws_init_select2();

    });


    $(document).on( 'click', '[data-aws-add-group]', function(e) {
        e.preventDefault();

        var groupID = awsUniqueID();
        var rulesTemplate = awsGetRuleTemplate.call(this, groupID);

        rulesTemplate = '<table class="aws-rules-table" data-aws-group="' + groupID + '"><tbody>' + rulesTemplate + '</tbody></table>';
        $(this).closest('.aws-rules').find('.aws-rules-table').last().after( rulesTemplate );
        $(this).closest('.aws-rules').removeClass('aws-rules-empty');
        aws_init_select2();

    });

    $(document).on( 'click', '[data-aws-add-first-filter]', function(e) {
        e.preventDefault();

        var groupID = awsUniqueID();
        var rulesTemplate = awsGetRuleTemplate.call(this, groupID);

        rulesTemplate = '<table class="aws-rules-table" data-aws-group="' + groupID + '"><tbody>' + rulesTemplate + '</tbody></table>';
        $(this).closest('.aws-rules').prepend( rulesTemplate );
        $(this).closest('.aws-rules').removeClass('aws-rules-empty');
        aws_init_select2();

    });

    $(document).on('change', '[data-aws-param]', function(evt, params) {

        var newParam = this.value;
        var ruleGroup = $(this).closest('[data-aws-rule]');

        var section = ruleGroup.data('aws-filter-section');

        var ruleOperator = ruleGroup.find('[data-aws-operator]');
        var ruleValues = ruleGroup.find('[data-aws-value]');
        var ruleParams = ruleGroup.find('[data-aws-param]');
        var ruleSuboptions = ruleGroup.find('[data-aws-suboption]');

        var ruleID = ruleGroup.data('aws-rule');
        var groupID = $(this).closest('[data-aws-group]').data('aws-group');

        ruleGroup.addClass('aws-pending');

        if ( ruleSuboptions.length ) {
            ruleSuboptions.remove();
            ruleGroup.find('.select2-container').remove();
        }

        $.ajax({
            type: 'POST',
            url: aws_vars.ajaxurl,
            dataType: "json",
            data: {
                action: 'aws-getRuleGroup',
                name: newParam,
                section: section,
                ruleID: ruleID,
                groupID: groupID,
                _ajax_nonce: aws_vars.ajax_nonce
            },
            success: function (response) {
                if ( response ) {

                    ruleGroup.removeClass('adv');

                    if ( typeof response.data.aoperators !== 'undefined' ) {
                        ruleOperator.html( response.data.aoperators );
                    }

                    if ( typeof response.data.avalues !== 'undefined' ) {
                        ruleValues.html( response.data.avalues );
                    }

                    if ( typeof response.data.asuboptions !== 'undefined' ) {
                        ruleParams.after( response.data.asuboptions );
                        ruleGroup.addClass('adv');
                    }

                    ruleGroup.removeClass('aws-pending');

                    aws_init_select2();

                }
            }
        });

    });

    $(document).on('change', '[data-aws-suboption]', function(evt, params) {

        var suboptionParam = this.value;
        var ruleGroup = $(this).closest('[data-aws-rule]');

        var section = ruleGroup.data('aws-filter-section');

        var ruleParam = ruleGroup.find('[data-aws-param] option:selected').val();
        var ruleValues = ruleGroup.find('[data-aws-value]');

        var ruleID = ruleGroup.data('aws-rule');
        var groupID = $(this).closest('[data-aws-group]').data('aws-group');

        ruleGroup.addClass('aws-pending');

        $.ajax({
            type: 'POST',
            url: aws_vars.ajaxurl,
            dataType: "json",
            data: {
                action: 'aws-getSuboptionValues',
                param: ruleParam,
                suboption: suboptionParam,
                section: section,
                ruleID: ruleID,
                groupID: groupID,
                _ajax_nonce: aws_vars.ajax_nonce
            },
            success: function (response) {
                if ( response ) {
                    ruleValues.html( response.data );
                    ruleGroup.removeClass('aws-pending');
                    aws_init_select2();
                }
            }
        });

    });


    // Image upload
    $('.image-upload-btn').click(function(e) {

        e.preventDefault();

        var container = $(this).closest('td');
        var size = $(this).data('size');
        var custom_uploader;

        //If the uploader object has already been created, reopen the dialog
        if (custom_uploader) {
            custom_uploader.open();
            return;
        }

        //Extend the wp.media object
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Choose Image'
            },
            multiple: false,
            type : 'image'
        });

        //When a file is selected, grab the URL and set it as the text field's value
        custom_uploader.on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            //console.log(attachment);

            var image_size = attachment.sizes['full'];

            if ( attachment.sizes[size] ) {
                image_size = attachment.sizes[size];
            } else if ( attachment.sizes['woocommerce_gallery_thumbnail'] ) {
                image_size = attachment.sizes['woocommerce_gallery_thumbnail'];
            } else if ( attachment.sizes['woocommerce_thumbnail'] ) {
                image_size = attachment.sizes['woocommerce_thumbnail'];
            }

            var image_src = image_size.url;

            container.find('.image-hidden-input').val(image_src);
            container.find('.image-preview').attr('src', image_src );
        });

        //Open the uploader dialog
        custom_uploader.open();

    });


    $('.image-remove-btn').click(function(e) {
        e.preventDefault();

        var container = $(this).closest('td');

        container.find('img').attr('src', '');
        container.find('.image-hidden-input').val('');

    });

    // Rename instance
    $('.aws-instance-name').on( 'click', function(e) {

        var self = $(this);

        var name = self.text();
        var newName = prompt( 'Type new name for this search form', name );
        var instanceId = self.data('id');

        if ( newName && ( name !== newName ) ) {

            $.ajax({
                type: 'POST',
                url: aws_vars.ajaxurl,
                data: {
                    action: 'aws-renameForm',
                    id: instanceId,
                    name: newName,
                    _ajax_nonce: aws_vars.ajax_nonce
                },
                dataType: "json",
                success: function (data) {
                    self.text(newName);
                }
            });

        }

    });

    // Copy instance
    $('.aws-table.aws-form-instances .aws-actions .copy').on( 'click', function(e) {

        e.preventDefault();

        var self = $(this);
        var instanceId = self.data('id');

        $.ajax({
            type: 'POST',
            url: aws_vars.ajaxurl,
            data: {
                action: 'aws-copyForm',
                id: instanceId,
                _ajax_nonce: aws_vars.ajax_nonce
            },
            dataType: "json",
            success: function (data) {
                location.reload();
            }
        });

    });

    // Remove instance
    $('.aws-table.aws-form-instances .aws-actions .delete').on( 'click', function(e) {

        e.preventDefault();

        var self = $(this);
        var instanceId = self.data('id');

        if ( confirm( "Are you sure want to delete this search form?" ) ) {

            $.ajax({
                type: 'POST',
                url: aws_vars.ajaxurl,
                data: {
                    action: 'aws-deleteForm',
                    id: instanceId,
                    _ajax_nonce: aws_vars.ajax_nonce
                },
                dataType: "json",
                success: function (data) {
                    location.reload();
                }
            });

        }

    });

    // Add instance
    $('.aws-insert-instance').on( 'click', function(e) {

        e.preventDefault();
        e.stopPropagation();

        $.ajax({
            type: 'POST',
            url: aws_vars.ajaxurl,
            data: {
                action: 'aws-addForm',
                _ajax_nonce: aws_vars.ajax_nonce
            },
            dataType: "json",
            success: function (data) {
                location.reload();
            }
        });

    });

    // Add filter
    $('.aws-insert-filter').on( 'click', function(e) {

        e.preventDefault();

        var self = $(this);
        var instanceId = self.data('instance');

        $.ajax({
            type: 'POST',
            url: aws_vars.ajaxurl,
            data: {
                action: 'aws-addFilter',
                instanceId: instanceId,
                _ajax_nonce: aws_vars.ajax_nonce
            },
            dataType: "json",
            success: function (data) {
                location.reload();
            }
        });

    });

    // Copy filter
    $('.aws-table.aws-form-filters .aws-actions .copy').on( 'click', function(e) {

        e.preventDefault();

        var self = $(this);
        var instanceId = self.data('instance');
        var filterId = self.data('id');

        $.ajax({
            type: 'POST',
            url: aws_vars.ajaxurl,
            data: {
                action: 'aws-copyFilter',
                instanceId: instanceId,
                filterId: filterId,
                _ajax_nonce: aws_vars.ajax_nonce
            },
            dataType: "json",
            success: function (data) {
                location.reload();
            }
        });

    });

    // Remove filter
    $('.aws-table.aws-form-filters .aws-actions .delete').on( 'click', function(e) {

        e.preventDefault();

        var self = $(this);
        var instanceId = self.data('instance');
        var filterId = self.data('id');

        if ( confirm( "Are you sure want to delete this filter?" ) ) {

            $.ajax({
                type: 'POST',
                url: aws_vars.ajaxurl,
                data: {
                    action: 'aws-deleteFilter',
                    instanceId: instanceId,
                    filterId: filterId,
                    _ajax_nonce: aws_vars.ajax_nonce
                },
                dataType: "json",
                success: function (data) {
                    window.top.location.href = window.location.href.replace(/&filter=\d*/g,"");
                }
            });

        }

    });

    var changingState = false;

    // Change option state
    $('[data-change-state]').on( 'click', function(e) {

        e.preventDefault();

        if ( changingState ) {
            return;
        } else {
            changingState = true;
        }

        var self = $(this);
        var $parent = self.closest('td');
        var setting = self.data('setting');
        var option = self.data('name');
        var state = self.data('change-state');

        $parent.addClass('loading');

        $.ajax({
            type: 'POST',
            url: aws_vars.ajaxurl,
            data: {
                action: 'aws-changeState',
                instanceId: aws_vars.instance,
                filterId: aws_vars.filter,
                setting: setting,
                option: option,
                state: state,
                _ajax_nonce: aws_vars.ajax_nonce
            },
            dataType: "json",
            success: function (data) {
                $parent.removeClass('loading');
                $parent.toggleClass('active');
                changingState = false;
            }
        });

    });

    // Clear cache
    $('#aws-clear-cache .button').on( 'click', function(e) {

        e.preventDefault();

        var $clearCacheBlock = $(this).closest('#aws-clear-cache');

        $clearCacheBlock.addClass('loading');

        $.ajax({
            type: 'POST',
            url: aws_vars.ajaxurl,
            data: {
                action: 'aws-clear-cache',
                _ajax_nonce: aws_vars.ajax_nonce
            },
            dataType: "json",
            success: function (data) {
                alert('Cache cleared!');
                $clearCacheBlock.removeClass('loading');
            }
        });

    });

    // Reindex table
    var $reindexBlock = $('#aws-reindex');
    var $reindexBtn = $('#aws-reindex .button');
    var $reindexProgress = $('#aws-reindex .reindex-progress');
    var $reindexCount = $('#aws-reindex-count strong');
    var syncStatus;
    var processed;
    var toProcess;
    var processedP;
    var syncData = false;

    // Reindex table
    $reindexBtn.on( 'click', function(e) {

        e.preventDefault();

        syncStatus = 'sync';
        processed  = 0;
        toProcess  = 0;
        processedP = 0;

        $reindexBlock.addClass('loading');
        $reindexProgress.html ( processedP + '%' );

        sync('start');

    });


    function sync( data ) {

        $.ajax({
            type: 'POST',
            url: aws_vars.ajaxurl,
            data: {
                action: 'aws-reindex',
                data: data,
                _ajax_nonce: aws_vars.ajax_nonce
            },
            dataType: "json",
            timeout:0,
            success: function (response) {
                if ( 'sync' !== syncStatus ) {
                    return;
                }

                toProcess = response.data.found_posts;
                processed = response.data.offset;

                processedP = Math.floor( processed / toProcess * 100 );

                syncData = response.data;

                if ( 0 === response.data.offset && ! response.data.start ) {

                    // Sync finished
                    syncStatus = 'finished';

                    console.log( response.data );
                    console.log( "Reindex finished!" );

                    $reindexBlock.removeClass('loading');

                    $reindexCount.text( response.data.found_posts );

                } else {

                    console.log( response.data );

                    $reindexProgress.html ( processedP + '%' );

                    // We are starting a sync
                    syncStatus = 'sync';

                    sync( response.data );
                }

            },
            error : function( jqXHR, textStatus, errorThrown ) {
                console.log( "Request failed: " + textStatus );

                if ( textStatus == 'timeout' || jqXHR.status == 504 ) {
                    console.log( 'timeout' );
                    if ( syncData ) {
                        setTimeout(function() { sync( syncData ); }, 1000);
                    }
                } else if ( textStatus == 'error') {
                    if ( syncData ) {

                        if ( 0 !== syncData.offset && ! syncData.start ) {
                            setTimeout(function() { sync( syncData ); }, 3000);
                        }

                    }
                }

            },
            complete: function ( jqXHR, textStatus ) {
            }
        });

    }

    // Dismiss welcome notice

    $( '.aws-welcome-notice.is-dismissible' ).on('click', '.notice-dismiss', function ( event ) {

        $.ajax({
            type: 'POST',
            url: aws_vars.ajaxurl,
            data: {
                action: 'aws-hideWelcomeNotice',
                _ajax_nonce: aws_vars.ajax_nonce
            },
            dataType: "json",
            success: function (data) {
            }
        });

    });

});