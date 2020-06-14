(function ($, Drupal) {
  Drupal.behaviors.customBookTabs = {
    attach: function (context, settings) {
      $(".lbk-tab-component .tabs-nav ul li a").click(function(e) {

        var currentTabValue = $(this).attr('href');
        $('.lbk-tab-component .tabs-nav ul li a').removeClass('tab-active');
        $('.lbk-tab-component .tabs-panel .tab-list').removeClass('tab-active');
        $(this).addClass('tab-active');
        $(currentTabValue).addClass('tab-active');

        e.preventDefault();
      });
    }
  };
})(jQuery, Drupal);