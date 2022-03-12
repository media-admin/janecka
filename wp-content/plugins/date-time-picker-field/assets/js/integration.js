(function( $ ) {

    // Add Color Picker to all inputs that have 'color-field' class
    if (typeof intregation_obj !== "undefined") {
      if(intregation_obj.is_free_there == 'true') {
        return false;
      }
    }


    function toggleTabsLite(term) {
      $('.' + term + '-tab-content, .' + term + '-tab_open').hide();
      $('.' + term + '-tab_close').show();
      $('.' + term + '-tab').addClass(term + '-tab-bg');
      $(document).delegate('.' + term + '-tab', 'click', function(){
        $(this).closest('.advertisement-wrap').find('.' + term + '-tab_open, .' + term + '-tab_close').toggle();
        $(this).closest('.advertisement-wrap').find('.' + term + '-tab-content').toggle();
      });

      $(document).delegate('.' + term + '-label', 'keyup', function(){
        $(this).closest('.advertisement-wrap').find('.' + term + '-heading').text($(this).val());
      });

      tab_radius(term);
    }


    function tab_radius(term) {
      var length = $('.' + term + '-tab-bg').length;
      $('.' + term + '-tab-bg').each(function(i, v) {
        if(i == (length - 2)) {
          $(v).css('border-radius', '0px 0px 8px 8px');
        } else {
          $(v).css('border-radius', '0px');
        }
      });
    }

    function get_forms(element) {
      var selected_plugin = $(element).closest('.cf7-lite-tab-content').find('select[name="integration[plugin]"] option:selected').val().toString();
      var fields = intregation_obj_lite.pickers;

      return fields;
    }


    function change_input(term) {
      $(document).delegate('.' + term + '-tab-content :input', 'chnage input', function() {
        $(this).closest('.' + term + '-tab-content').find('input[name="new_' + term + '_import"]').removeAttr('disabled').end().find('.new_' + term + '_import').removeAttr('disabled').end();
      });
    }

    function cancel(term, button, tab) {

      $(document).delegate(button, 'click', function(){
        //$(this).closest(tab).hide();
        $(this).closest('.advertisement-wrap').find('.' + term + '-tab_open, .' + term + '-tab_close').toggle();
      });
    }

    toggleTabsLite('cf7-lite');

    cancel('cf7-lite', '.cancel', '.cf7-lite-tab-content');
    //cancel('cf7-lite', '.cancel-manual', '.cf7-lite-tab-content');
    cancel('cf7-lite', '.cancel-details', '.cf7-lite-tab-content');


    $(document).delegate('.get-forms', 'click', function(){

      var plugin = $(this).closest('.cf7-lite-tab-content').find('select[name="integration[plugin]"] option:selected').val().toString();
/**
      if (plugin == 'manual') {

        var count = $(this).closest('.cf7-lite-tab-content').find('.form-details-item').length;
        if (count == 0) {
          $(this).closest('.cf7-lite-tab-content').find('.form-details-container').append($('.integration-sample').find('.manual-temp').find('.form-details-container').find('select[name="integration[form]"]').html(get_forms(this)).end().html()).end().find('.get-forms-container').hide().end();
        }
        var picker = parseInt($(this).closest('.cf7-lite-tab-content').find('select[name="integration[picker]"]').val());
        $(this).closest('.cf7-lite-tab-content').find('input[name="integration[selector]"]').val(intregation_obj_lite.pickers_n_selectors[picker]['selector']).end().find('input[name="integration[class]"]').val(intregation_obj_lite.pickers_n_selectors[picker]['class']).end();
      }
*/
    });
/**
    $(document).delegate('.new-integration', 'click', function() {
      var html = $('.integration-sample').find('.manual-temp').html();
      $('#cf7-lite-append-to').append($(html).find('.form-details-item').remove().end().find('.cf7-lite-tab-content').show().end().find('.cf7-lite-tab_close').hide().end().find('.cf7-lite-tab_open').show().end());
      $('#cf7-lite-append-to .advertisement-wrap').last().find('input[name="integration[label]"]').focus()
      tab_radius('cf7-lite');
    });
*/
    $(document).delegate('select[name="integration[plugin]"]', 'change', function() {

      var plugin = $(this).closest('.cf7-tab-content').find('select[name="integration[plugin]"] option:selected').val().toString();
      var form = $(this).closest('.cf7-tab-content').find('select[name="integration[form]"]');

      $(this).closest('.cf7-tab-content').find('select[name="integration[form]"]').html(get_forms(this)).end();
    });

    $(document).delegate('select[name="integration[picker]"]', 'change', function() {

      var picker = parseInt($(this).closest('.cf7-tab-content').find('select[name="integration[picker]"]').val());
      $(this).closest('.cf7-tab-content').find('input[name="integration[selector]"]').val(intregation_obj_lite.pickers_n_selectors[picker]['selector']).end();
    });

    change_input('ics');
    change_input('cf7-lite');

})( jQuery );
