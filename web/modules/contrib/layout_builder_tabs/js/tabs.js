(function ($, Drupal) {
  // Tabs JS

  // toggle the visibility of tabs
  function showTab(e, target = $(this)) {
    e.preventDefault();
    // Find the target panel.
    var targetContent = $(target.attr('href'));
    // Hide all panels and update aria attributes.
    targetContent.siblings().hide().removeClass('active').attr('aria-hidden', true).attr('aria-expanded', false);
    // Show the target panel and update aria attributes.
    targetContent.show().addClass('active').attr('aria-hidden', false).attr('aria-expanded', true);
    // Deselect all tabs.
    target.parent().siblings().find('a').removeClass('is-active').attr("aria-selected", false).attr('tabindex', "-1");
    // Select the active tab.
    target.addClass('is-active').attr("aria-selected", true).attr('tabindex', "0").focus();
  }

  // event handlers to toggle accordions
  $('.layout-tabs a').on('click', showTab);
  $('.layout-tabs a').on('keyup', function(e) {
    if (e.which == 37) {
      // show previous
      if ($(this).parent().is(':first-child')) {
        // select the last
        showTab(e, $(this).parent().siblings(':last-child').children('a'));
      }
      else {
        // select the previous
        showTab(e, $(this).parent().prev().children('a'));
      }
    }
    else if (e.which == 39) {
      // show next
      if ($(this).parent().is(':last-child')) {
        // select the first
        showTab(e, $(this).parent().siblings(':first-child').children('a'));
      }
      else {
        // select the previous
        showTab(e, $(this).parent().next().children('a'));
      }
    }
  });
  $('.tab-content .tab-pane').hide();
  $('.tab-content .tab-pane.active').show();

})(jQuery, Drupal);
