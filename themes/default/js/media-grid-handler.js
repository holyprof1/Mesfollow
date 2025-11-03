/**
 * Media Grid View Handler - NO INFINITE SCROLL VERSION
 * Disabled infinite scroll to prevent duplicate loading
 */

(function() {
    'use strict';
    
    // Initialize grid view on page load
    document.addEventListener('DOMContentLoaded', function() {
        initializeMediaGrid();
    });
    
    /**
     * Initialize media grid functionality
     */
    function initializeMediaGrid() {
        const gridContainer = document.querySelector('.media-grid-container');
        if (!gridContainer) return;
        
        // Add hover effects for videos (play on hover)
        const videoItems = gridContainer.querySelectorAll('.media-grid-item video');
        videoItems.forEach(video => {
            const item = video.closest('.media-grid-item');
            
            item.addEventListener('mouseenter', function() {
                video.play().catch(e => console.log('Video play failed:', e));
            });
            
            item.addEventListener('mouseleave', function() {
                video.pause();
                video.currentTime = 0;
            });
        });
        
        // Lazy loading for images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    }
                });
            }, {
                rootMargin: '50px'
            });
            
            gridContainer.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }
    
    /**
     * Handle video playback in modal
     */
    window.playGridVideo = function(videoElement) {
        if (videoElement.paused) {
            videoElement.play();
        } else {
            videoElement.pause();
        }
    };
    
})();