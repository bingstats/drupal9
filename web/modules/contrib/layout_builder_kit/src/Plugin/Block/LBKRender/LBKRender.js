(function ($, Drupal) {
  Drupal.behaviors.renderType = {
    attach: function (context, settings) {

      // Show inputs according to either 'media' or 'media' render type.
      var renderTypeValue = $(".render-type").val();
      if(renderTypeValue === 'media') {
        // Hide Node inputs.
        $( "#entity-node" ).hide();
        $( "#view-mode-node" ).hide();
      } else {
        // Hide Media inputs.
        $( "#entity-media" ).hide();
        $( "#view-mode-media" ).hide();
      }

      // Select change function.
      $(".render-type").change(function(e) {
        var currentSelectValue = $(this).val();

        // Show proper inputs.
        if(currentSelectValue === 'media') {
          // Hide Node inputs.
          $( "#entity-node" ).hide();
          $( "#view-mode-node" ).hide();

          // Show Media inputs.
          $( "#entity-media" ).show();
          $( "#view-mode-media" ).show();
        }
        if(currentSelectValue === 'node') {
          // Hide Media inputs.
          $( "#entity-media" ).hide();
          $( "#view-mode-media" ).hide();

          // Show Node inputs.
          $( "#entity-node" ).show();
          $( "#view-mode-node" ).show();
        }

      });
    }
  };
})(jQuery, Drupal);
