/**
 * Mobile menu open handling
 */
jQuery(function () {
    const $tabs = jQuery('nav.notos ul.navtabs');
    $tabs.on('click', '.primary .opener', function (event) {
        $tabs.find('li').removeClass('active');
        jQuery(this).parent().addClass('active').next('.secondary').addClass('active');
        event.stopPropagation();
        event.preventDefault();
    });
});

/**
 * Close other notos toggle menus when opening a new one
 */
jQuery('input.notos-toggle').on('click', function () {
    jQuery('input.notos-toggle').not(this).prop('checked', false);
});

/**
 * Close notos toggle menus on clicks elsewhere
 */
jQuery('body').on('click', function(event) {
    const $target = jQuery(event.target);
    if($target.is('.notos-toggle') || $target.parents('.notos-toggle').length) return;
    const $toggle = jQuery('input.notos-toggle');
    if ($toggle.prop('checked')) {
        $toggle.prop('checked', false);
    }
});
