"user strict";
new WOW().init();

$(document).ready(function(){
  $('[data-toggle="tooltip"]').tooltip();   
});

/* $('.timepicker').datetimepicker({
                format: 'LT'
            });*/
/*$('.datepicker').datetimepicker();*/
$(window).load(function(){
   // Hide Header on on scroll down
    var NavBar = $('.site_header ');
    var didScroll;
    var lastScrollTop = 0;
    var navbarHeight = NavBar.outerHeight();
    $(window).scroll(function(event) {
        didScroll = true;
    });
    setInterval(function() {
        if (didScroll) {
            hasScrolled();
            didScroll = false;
        }
    }, 100);

    function hasScrolled() {
        var st = $(this).scrollTop();
        if (st + $(window).height() < $(document).height()) {
            NavBar.addClass('sticky_header');
            if (st == 0) {
                NavBar.removeClass('sticky_header');
            }
        }
        lastScrollTop = st;
    }

   
   /*------------------ header top space js ----------------*/

    topHeight = $('.site_header ').height();
    $('.main-content').css({  'margin-top': topHeight});
});
