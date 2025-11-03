/**
 * DISABLE INFINITE SCROLL FOR MEDIA GRIDS - FINAL FIX
 * This MUST be the LAST script in profile.php
 */
(function() {
    'use strict';
    
    console.log('Media Grid Protection Loaded');
    
    // Check if we're on a media grid page
    function isMediaGrid() {
        const url = window.location.href;
        const hasMediaParam = url.includes('?pcat=photos') || 
                             url.includes('?pcat=videos') || 
                             url.includes('?pcat=audios') ||
                             url.includes('&pcat=photos') || 
                             url.includes('&pcat=videos') || 
                             url.includes('&pcat=audios');
        
        const hasGridContainer = document.querySelector('.media-grid-container') !== null;
        
        return hasMediaParam || hasGridContainer;
    }
    
    if (isMediaGrid()) {
        console.log('🛑 Media Grid Detected - Blocking Infinite Scroll');
        
        // Method 1: Remove all scroll event listeners
        const oldAddEventListener = EventTarget.prototype.addEventListener;
        EventTarget.prototype.addEventListener = function(type, listener, options) {
            if (type === 'scroll' && isMediaGrid()) {
                console.log('🚫 Blocked scroll event listener on media grid');
                return; // Don't add the listener
            }
            return oldAddEventListener.call(this, type, listener, options);
        };
        
        // Method 2: Override common infinite scroll functions
        window.loadMore = function() { 
            console.log('🚫 loadMore() blocked on media grid'); 
        };
        
        window.morePost = function() { 
            console.log('🚫 morePost() blocked on media grid'); 
        };
        
        window.loadMorePosts = function() { 
            console.log('🚫 loadMorePosts() blocked on media grid'); 
        };
        
        // Method 3: Disable jQuery scroll handlers
        if (typeof jQuery !== 'undefined') {
            const originalOn = jQuery.fn.on;
            jQuery.fn.on = function(events) {
                if (typeof events === 'string' && events.includes('scroll') && isMediaGrid()) {
                    console.log('🚫 Blocked jQuery scroll handler on media grid');
                    return this;
                }
                return originalOn.apply(this, arguments);
            };
            
            // Remove existing handlers
            jQuery(window).off('scroll');
            jQuery(document).off('scroll');
        }
        
        // Method 4: Stop the infinite scroll by marking container as "done"
        const moreType = document.getElementById('moreType');
        if (moreType) {
            moreType.setAttribute('data-complete', 'true');
            moreType.setAttribute('data-no-more', 'true');
            moreType.classList.add('no-infinite-scroll');
        }
        
        // Method 5: Override AJAX calls to htmlPosts.php when on grid
        const originalFetch = window.fetch;
        window.fetch = function(url, options) {
            if (isMediaGrid() && typeof url === 'string' && url.includes('htmlPosts.php')) {
                console.log('🚫 Blocked AJAX request to htmlPosts.php on media grid');
                return Promise.resolve(new Response('', { status: 200 }));
            }
            return originalFetch.apply(this, arguments);
        };
        
        // Method 6: Override jQuery AJAX
        if (typeof jQuery !== 'undefined') {
            const originalAjax = jQuery.ajax;
            jQuery.ajax = function(settings) {
                if (isMediaGrid() && settings && settings.url && settings.url.includes('htmlPosts.php')) {
                    console.log('🚫 Blocked jQuery AJAX to htmlPosts.php on media grid');
                    return jQuery.Deferred().resolve('').promise();
                }
                return originalAjax.apply(this, arguments);
            };
        }
    }
    
    // Video hover effects (keep these working)
    document.addEventListener('DOMContentLoaded', function() {
        if (!isMediaGrid()) return;
        
        const videos = document.querySelectorAll('.media-grid-item video');
        videos.forEach(video => {
            const item = video.closest('.media-grid-item');
            if (!item) return;
            
            item.addEventListener('mouseenter', () => {
                video.play().catch(e => console.log('Video play blocked by browser'));
            });
            
            item.addEventListener('mouseleave', () => {
                video.pause();
                video.currentTime = 0;
            });
        });
    });
    
    console.log('✅ Media Grid Protection Active');
    
})();