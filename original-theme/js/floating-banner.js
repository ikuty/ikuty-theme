/**
 * Floating Banner Position Controller
 * Adjusts the vertical position of floating banners to align with content-area
 */

jQuery(document).ready(function($) {
    // Check if floating banner exists
    const floatingBanner = $('.floating-banner');
    if (floatingBanner.length === 0) {
        return;
    }

    // Function to update floating banner position
    function updateFloatingBannerPosition() {
        const contentArea = $('.content-area');
        if (contentArea.length === 0) {
            return;
        }

        // Get content-area position
        const contentAreaTop = contentArea.offset().top;
        const scrollTop = $(window).scrollTop();
        
        // Calculate the top position - align with content-area top but keep it sticky when scrolling
        let topPosition = Math.max(contentAreaTop, scrollTop + 70);
        
        // Update the floating banner position
        floatingBanner.css({
            'top': topPosition + 'px',
            'position': 'absolute'
        });
    }

    // Initial position setup
    updateFloatingBannerPosition();

    // Update position on scroll and resize
    $(window).on('scroll', updateFloatingBannerPosition);
    $(window).on('resize', function() {
        setTimeout(updateFloatingBannerPosition, 100);
    });

    // Update position when page layout changes (e.g., after images load)
    $(window).on('load', function() {
        setTimeout(updateFloatingBannerPosition, 200);
    });
});