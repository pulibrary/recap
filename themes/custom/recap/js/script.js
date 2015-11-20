$(document).ready(function() {
    var $header = $("header.front"),
        $clone = $header.before($header.clone().addClass("clone"));

    $(window).on("scroll", function() {
        var fromTop = $(window).scrollTop();
        $("body").toggleClass("down", (fromTop > 500));
    });

    // menu shows active parent
    $('#block-recap-main-menu li li:has(a.is-active)').parents('li').children('a').addClass('is-active');

    // var menuToggle = $('#js-centered-navigation-mobile-menu').unbind();
    //   $('#js-centered-navigation-menu').removeClass("show");

    //   menuToggle.on('click', function(e) {
    //     e.preventDefault();
    //     $('#js-centered-navigation-menu').slideToggle(function(){
    //       if($('#js-centered-navigation-menu').is(':hidden')) {
    //         $('#js-centered-navigation-menu').removeAttr('style');
    //       }
    //     });
    //   });

    // Cache the elements we'll need
    var menu = $('#block-recap-main-menu');
    var menuList = menu.find('ul:first');
    var listItems = menu.find('li').not('#responsive-tab');

    // Create responsive trigger
    menuList.prepend('<li id="responsive-tab" class="responsive-tab"><a href="#">Menu</a></li>');

    // Toggle menu visibility
    menu.on('click', '#responsive-tab', function(){
      listItems.slideToggle('fast');
      listItems.addClass('collapsed');
    });
});
