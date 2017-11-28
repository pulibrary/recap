(function ($, Drupal) {
  Drupal.behaviors.mainMenu = {
    attach: function (context, settings) {
      $('#block-recap-main-menu', context).once('#block-recap-main-menu li li:has(a.is-active)').parents('li').children('a').addClass('is-active');

      // Cache the elements we'll need
      var menu = $('#block-recap-main-menu');
      var menuList = menu.find('ul:first');
      var listItems = menu.find('li').not('#responsive-tab');

      // Create responsive trigger
      menuList.prepend('<li class="responsive-tab"><a href="/" class="responsive-logo"><img src="/themes/custom/recap/assets/public/images/recap-logo.png" /></a><button id="responsive-tab" class="responsive-menu">Menu</button></li>');

      // Toggle menu visibility
      menu.on('click', '#responsive-tab', function(){
        listItems.slideToggle('fast');
        listItems.addClass('collapsed');
      });
    }
  };
})(jQuery, Drupal);