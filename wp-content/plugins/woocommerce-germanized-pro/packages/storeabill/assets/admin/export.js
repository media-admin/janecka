window.storeabill = window.storeabill || {};
window.storeabill.admin = window.storeabill.admin || {};

( function( $, admin ) {

    /**
     * productExportForm handles the export process.
     */
    var exportForm = function( $form, params = {} ) {
        var self = this;

        this.$form  = $form;
        this.xhr    = false;
        this.params = params;

        // Initial state.
        this.$form.find( '.sab-exporter-progress' ).val( 0 );

        // Methods.
        this.processStep = this.processStep.bind( this );
        this.getFormData = this.getFormData.bind( this );

        this.dates = $( '.range_datepicker' ).datepicker({
            changeMonth: true,
            changeYear: true,
            defaultDate: '',
            dateFormat: 'yy-mm-dd',
            numberOfMonths: 1,
            minDate: '-20Y',
            maxDate: '+1D',
            showButtonPanel: true,
            showOn: 'focus',
            buttonImageOnly: true,
            onSelect: function() {
                var option = $( this ).is( '.from' ) ? 'minDate' : 'maxDate',
                    date   = $( this ).datepicker( 'getDate' );

                self.dates.not( this ).datepicker( 'option', option, date );
            },
        });

        $( document ).on( 'click', '.sab-exporter-date-adjuster', { exportForm: this }, this.onAdjustDate );

        // Events.
        $form.on( 'submit', { exportForm: this }, this.onSubmit );
    };

    exportForm.prototype.onAdjustDate = function( event ) {
        var $link = $( this ),
            exportForm = event.data.exportForm,
            dates  = exportForm.dates,
            today = new Date();

        if ( 'current_month' === $link.data( 'adjust' ) ) {
            $( dates[0] ).datepicker( "setDate", new Date( today.getFullYear(), today.getMonth(), 1 ) );
            $( dates[1] ).datepicker( "setDate", new Date( today.getFullYear(), today.getMonth() + 1, 0 ) );
        } else if ( 'last_month' === $link.data( 'adjust' ) ) {
            $( dates[0] ).datepicker( "setDate", new Date( today.getFullYear(), today.getMonth() - 1, 1 ) );
            $( dates[1] ).datepicker( "setDate", new Date( today.getFullYear(), today.getMonth(), 0 ) );
        }

        return false;
    };

    /**
     * Handle export form submission.
     */
    exportForm.prototype.onSubmit = function( event ) {
        event.preventDefault();

        var currentDate    = new Date(),
            $this          = event.data.exportForm,
            day            = currentDate.getDate(),
            month          = currentDate.getMonth() + 1,
            year           = currentDate.getFullYear(),
            timestamp      = currentDate.getTime(),
            filename       = 'sab-' + $this.params.document_type + '-' + $this.params.type + '-export-' + day + '-' + month + '-' + year + '-' + timestamp + '.' + $this.params.extension;

        $this.$form.addClass( 'sab-exporter__exporting' );
        $this.$form.find( '.sab-exporter-progress' ).val( 0 );
        $this.$form.find( '.sab-exporter-button' ).prop( 'disabled', true );
        $this.processStep( { 'step': 1, 'filters': $this.getFormData(), 'filename': filename } );
    };

    exportForm.prototype.getFormData = function() {
        var $this = this,
            data  = {};

        $.each( $this.$form.serializeArray(), function( index, item ) {
            if ( item.name.indexOf( '[]' ) !== -1 ) {
                item.name = item.name.replace( '[]', '' );
                data[ item.name ] = $.makeArray( data[ item.name ] );
                data[ item.name ].push( item.value );
            } else {
                data[ item.name ] = item.value;
            }
        });

        return data;
    };

    /**
     * Process the current export step.
     */
    exportForm.prototype.processStep = function( props ) {
        var $this    = this,
            ajaxData = props;

        $.extend( ajaxData, {
            'action'        : 'storeabill_admin_export',
            'type'          : this.params.type,
            'document_type' : this.params.document_type,
            'security'      : this.params.export_nonce
        } );

        $.ajax( {
            type: 'POST',
            url: this.params.ajax_url,
            data: ajaxData,
            dataType: 'json',
            success: function( response ) {
                if ( response.success ) {
                    if ( 'done' === response.step ) {
                        $this.$form.find( '.sab-exporter-progress' ).val( response.percentage );
                        window.location = response.url;
                        setTimeout( function() {
                            $this.$form.removeClass( 'sab-exporter__exporting' );
                            $this.$form.find( '.sab-exporter-button' ).prop( 'disabled', false );
                        }, 2000 );
                    } else {
                        $this.$form.find( '.sab-exporter-progress' ).val( response.percentage );

                        $.extend( response, {
                            'step': parseInt( response.step, 10 ),
                            'filename': props.filename,
                        } );

                        $this.processStep( response );
                    }
                } else {
                    $this.$form.find( '.sab-notice-wrapper .notice' ).remove();

                    $this.$form.removeClass( 'sab-exporter__exporting' );
                    $this.$form.find( '.sab-exporter-button' ).prop( 'disabled', false );

                    if ( response.hasOwnProperty( 'messages' ) ) {
                        $.each( response.messages, function( i, message ) {
                            $( '.sab-notice-wrapper' ).append( '<div class="notice is-dismissible notice-error"><p>' + message + '</p><button type="button" class="notice-dismiss"></button></div>' );
                        });
                    }
                }
            }
        } ).fail( function( response ) {
            window.console.log( response );
        } );
    };

    /**
     * Function to call productExportForm on jquery selector.
     */
    $.fn.sab_export_form = function( params ) {
        new exportForm( this, params );
        return this;
    };

    /**
     * Core
     */
    admin.export = {
        params: {},

        init: function() {
            var self    = storeabill.admin.export;
            self.params = storeabill_admin_export_params;

            $( '.sab-exporter' ).sab_export_form( self.params );
        }
    };

    $( document ).ready( function() {
        storeabill.admin.export.init();
    });

})( jQuery, window.storeabill.admin );
