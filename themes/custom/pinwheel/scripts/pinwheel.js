(function($, Drupal) {
  Drupal.behaviors.mainMenu = {
    attach: function(context, settings) {
      // Cache the elements we'll need
      var menu = $("#block-pinwheel-main-menu", context);
      // console.log(menu);
      var menuItems = menu.find("ul:first > li");

      // Toggle menu visibility
      menu.on("click", "#responsive-tab", function() {
        menuItems.toggleClass("expanded");
      });

      // Toggle submenu visibility
      menu.on("click", ".submenu-toggle", function() {
        $(this).siblings(".menu").children(".menu-item").toggleClass("expanded");
      });
    }
  };
})(jQuery, Drupal);
