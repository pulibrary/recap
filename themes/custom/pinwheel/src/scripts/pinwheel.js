(function($, Drupal) {
  Drupal.behaviors.mainMenu = {
    attach: function(context, settings) {
      // Cache the elements we'll need
      var menu = $("#block-pinwheel-main-menu");
      var menuList = menu.find("ul:first");
      var listItems = menu.find("li").not("#responsive-tab");

      // Create responsive trigger
      menuList.prepend(
        '<li class="responsive-tab"><button id="responsive-tab" class="responsive-menu">Menu</button></li>'
      );

      // Toggle menu visibility
      menu.on("click", "#responsive-tab", function() {
        listItems.toggleClass("expanded");
      });
    }
  };
})(jQuery, Drupal);
