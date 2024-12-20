(function ($, Drupal) {
    Drupal.behaviors.spotifyAutocomplete = {
      attach: function (context, settings) {
        console.log('Initializing autocomplete behavior...');
    
        // Update the autocomplete path dynamically based on type selection.
        $('select[name="type"]').on('change', function () {
          const selectedType = $(this).val();
          console.log('Type changed to:', selectedType);
    
          // Update the autocomplete path for the query input field.
          $('.form-autocomplete').attr(
            'data-autocomplete-path',
            `/spotify/autocomplete?type=${selectedType}`
          );
        });
 
        // Log when the autocomplete behavior is attached.
        $('.form-autocomplete', context).once('spotify-autocomplete').each(function () {
          console.log('Autocomplete attached to:', this);
        });
      },
    };
  })(jQuery, Drupal);
  