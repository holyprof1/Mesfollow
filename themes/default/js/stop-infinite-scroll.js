/**
 * STOP INFINITE SCROLL FOR MEDIA GRIDS
 * Place this AFTER all other JavaScript files in profile.php
 * This will override any existing infinite scroll functionality
 */

(function() {
    'use strict';
    
    // Flag to track if we're on a media grid page
    const isMediaGridPage = () => {
        const url = window.location.href;
        return url.includes('?pcat=photos') || 
               url.includes('?pcat=videos') || 
               url.includes('?pcat=audios');
    };
    
    // Disable infinite scroll if on media grid
    if (isMediaGridPage()) {
        console.log('Media grid detected - disabling infinite scroll');
        
        // Remove scroll event listeners
        window.removeEventListener('scroll', window.handleScroll);
        $(window).off('scroll');
        
        // Override any existing infinite scroll functions
        window.loadMore = function() { 
            console.log('Infinite scroll disabled for media grid'); 
        };
        
        window.morePost = function() { 
            console.log('More posts disabled for media grid'); 
        };
        
        // Prevent jQuery scroll handlers
        if (typeof jQuery !== 'undefined') {
            jQuery(window).off('scroll.infiniteScroll');
            jQuery(window).off('scroll');
        }
        
        // Stop any setInterval or setTimeout that might be loading posts
        const highestTimeoutId = setTimeout(";");
        for (let i = 0; i < highestTimeoutId; i++) {
            clearTimeout(i);
        }
    }
    
    // Video hover effects only
    document.addEventListener('DOMContentLoaded', function() {
        if (!isMediaGridPage()) return;
        
        const videos = document.querySelectorAll('.media-grid-item video');
        videos.forEach(video => {
            const item = video.closest('.media-grid-item');
            
            item.addEventListener('mouseenter', () => {
                video.play().catch(e => console.log('Video play failed'));
            });
            
            item.addEventListener('mouseleave', () => {
                video.pause();
                video.currentTime = 0;
            });
        });
    });
    
})();