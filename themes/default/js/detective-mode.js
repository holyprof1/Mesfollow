/**
 * DETECTIVE MODE - Find What's Duplicating Content
 * Add this to profile.php BEFORE all other scripts
 */

(function() {
    'use strict';
    
    console.log('🔍 DETECTIVE MODE ACTIVE - Watching for duplications');
    
    // Track the grid container
    let initialHTML = '';
    let initialCount = 0;
    
    // Wait for page to load
    window.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('moreType');
        if (container) {
            initialHTML = container.innerHTML;
            initialCount = container.querySelectorAll('.media-grid-item, .media-grid-empty').length;
            console.log('📊 Initial content count:', initialCount);
            
            // Watch for changes
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        const currentCount = container.querySelectorAll('.media-grid-item, .media-grid-empty').length;
                        
                        if (currentCount > initialCount) {
                            console.error('🚨 DUPLICATION DETECTED!');
                            console.log('Previous count:', initialCount);
                            console.log('New count:', currentCount);
                            console.log('Added nodes:', mutation.addedNodes);
                            console.trace('Call stack:');
                            
                            // Try to find what function called this
                            const error = new Error();
                            console.log('Stack trace:', error.stack);
                            
                            initialCount = currentCount;
                        }
                    }
                });
            });
            
            observer.observe(container, {
                childList: true,
                subtree: true
            });
            
            console.log('👁️ Now watching #moreType for changes...');
        }
    });
    
    // Intercept common DOM manipulation methods
    const originalAppendChild = Element.prototype.appendChild;
    Element.prototype.appendChild = function(node) {
        if (this.id === 'moreType' || this.closest('#moreType')) {
            console.log('⚠️ appendChild called on #moreType:', node);
            console.trace('From:');
        }
        return originalAppendChild.call(this, node);
    };
    
    const originalInnerHTML = Object.getOwnPropertyDescriptor(Element.prototype, 'innerHTML');
    Object.defineProperty(Element.prototype, 'innerHTML', {
        set: function(value) {
            if (this.id === 'moreType' || this.closest('#moreType')) {
                console.log('⚠️ innerHTML set on #moreType');
                console.trace('From:');
            }
            return originalInnerHTML.set.call(this, value);
        },
        get: originalInnerHTML.get
    });
    
    // Watch jQuery if it exists
    if (typeof jQuery !== 'undefined') {
        const originalHtml = jQuery.fn.html;
        jQuery.fn.html = function(value) {
            if (arguments.length > 0) {
                const elem = this[0];
                if (elem && (elem.id === 'moreType' || jQuery(elem).closest('#moreType').length)) {
                    console.log('⚠️ jQuery .html() called on #moreType');
                    console.trace('From:');
                }
            }
            return originalHtml.apply(this, arguments);
        };
        
        const originalAppend = jQuery.fn.append;
        jQuery.fn.append = function() {
            const elem = this[0];
            if (elem && (elem.id === 'moreType' || jQuery(elem).closest('#moreType').length)) {
                console.log('⚠️ jQuery .append() called on #moreType');
                console.trace('From:');
            }
            return originalAppend.apply(this, arguments);
        };
    }
    
    console.log('✅ Detective mode ready. Open console and watch for 🚨 alerts!');
    
})();