(function ($, Drupal, drupalSettings) {

    'use strict';

    Drupal.behaviors.customSearchFields = {
      attach: function (context, settings) {

      $("body").once('customSearchFields').on('submit', '.search-container form', function(e) {
        e.preventDefault();

        $('.loading-gif').show();

        var search = $('.custom-search-field-input').val();
        var url = '/custom-search-fields/search';
        var data = {
          search: search.trim()
        }

        $.post(url,data,function(response){

          if(drupalSettings.custom_search_fields != undefined) {
            var results = drupalSettings.custom_search_fields.results;
          }
  
          if(response.result == 'OK') {
            $('.search-items-parent').load( window.location.href + " .search-items-parent > *" );
            setTimeout(function(){ $('.loading-gif').hide(); }, 1000);
          } else {
            $('.search-items-parent').load( window.location.href + " .search-items-parent > *" );
            setTimeout(function(){ $('.loading-gif').hide(); }, 1000);
          }
        });
      });

      }
    };

})(jQuery, Drupal, drupalSettings);
