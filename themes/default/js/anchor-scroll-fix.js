/**
 * Anchor Scroll Fix - Better Version
 * Scrolls to content when clicking profile tabs
 */

(function() {
    'use strict';
    
    console.log('🔗 Anchor Scroll Loaded');
    
    // Function to scroll to content
    function scrollToContent() {
        // Try multiple possible targets
        const targets = [
            document.querySelector('.media-grid-container'),
            document.getElementById('moreType'),
            document.querySelector('.pageMiddle'),
            document.querySelector('.th_middle')
        ];
        
        for (let target of targets) {
            if (target && target.offsetHeight > 0) {
                console.log('📍 Scrolling to:', target);
                
                // Smooth scroll with offset for header
                const yOffset = -80; // Offset for fixed header
                const y = target.getBoundingClientRect().top + window.pageYOffset + yOffset;
                
                window.scrollTo({
                    top: y,
                    behavior: 'smooth'
                });
                return;
            }
        }
        
        console.warn('⚠️ No scroll target found');
    }
    
    // On page load, check if URL has pcat parameter
    window.addEventListener('load', function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('pcat')) {
            const pcat = urlParams.get('pcat');
            console.log('📄 Page loaded with pcat:', pcat);
            
            // Wait for content to render
            setTimeout(scrollToContent, 1000);
        }
    });
    
    // Listen for tab clicks
    document.addEventListener('DOMContentLoaded', function() {
        const tabLinks = document.querySelectorAll('.i_profile_menu_item a, .i_p_ffs a');
        
        tabLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                // Don't prevent default - let page load
                console.log('🖱️ Tab clicked:', this.href);
                
                // After navigation, scroll will happen from load event
            });
        });
    });
    
    console.log('✅ Anchor Scroll Ready');
    
})();