/**
 * GRID LOCK - Prevent JavaScript from duplicating grid content
 * This locks the grid container after initial page load
 */

(function() {
    'use strict';
    
    console.log('🔒 Grid Lock Loading...');
    
    // Check if we're on a media grid page
    function isMediaGridPage() {
        const url = window.location.href;
        return url.includes('pcat=photos') || 
               url.includes('pcat=videos') || 
               url.includes('pcat=audios');
    }
    
    if (!isMediaGridPage()) {
        console.log('Not a media grid page, Grid Lock disabled');
        return;
    }
    
    console.log('🔒 Media Grid Page Detected - Activating Protection');
    
    let isLocked = false;
    let originalContent = '';
    
    // Lock function - prevents any modifications
    function lockGrid() {
        const container = document.getElementById('moreType');
        if (!container) {
            console.warn('⚠️ #moreType not found, retrying...');
            setTimeout(lockGrid, 100);
            return;
        }
        
        // Save the original content
        originalContent = container.innerHTML;
        const itemCount = container.querySelectorAll('.media-grid-item, .media-grid-empty').length;
        console.log('📌 Locking grid with', itemCount, 'items');
        
        isLocked = true;
        
        // Method 1: Prevent innerHTML changes
        const originalInnerHTMLDescriptor = Object.getOwnPropertyDescriptor(Element.prototype, 'innerHTML');
        Object.defineProperty(container, 'innerHTML', {
            get: function() {
                return originalContent;
            },
            set: function(value) {
                if (isLocked) {
                    console.log('🚫 Blocked innerHTML modification on locked grid');
                    return;
                }
                originalContent = value;
                originalInnerHTMLDescriptor.set.call(this, value);
            }
        });
        
        // Method 2: Prevent appendChild
        const originalAppendChild = container.appendChild;
        container.appendChild = function(node) {
            if (isLocked) {
                console.log('🚫 Blocked appendChild on locked grid');
                return node;
            }
            return originalAppendChild.call(this, node);
        };
        
        // Method 3: Prevent insertBefore
        const originalInsertBefore = container.insertBefore;
        container.insertBefore = function(newNode, referenceNode) {
            if (isLocked) {
                console.log('🚫 Blocked insertBefore on locked grid');
                return newNode;
            }
            return originalInsertBefore.call(this, newNode, referenceNode);
        };
        
        // Method 4: Prevent append
        if (container.append) {
            const originalAppend = container.append;
            container.append = function(...nodes) {
                if (isLocked) {
                    console.log('🚫 Blocked append on locked grid');
                    return;
                }
                return originalAppend.call(this, ...nodes);
            };
        }
        
        // Method 5: MutationObserver to restore original content if changed
        const observer = new MutationObserver(function(mutations) {
            if (!isLocked) return;
            
            let needsRestore = false;
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && (mutation.addedNodes.length > 0 || mutation.removedNodes.length > 0)) {
                    needsRestore = true;
                }
            });
            
            if (needsRestore) {
                console.warn('⚠️ Grid was modified! Restoring original content...');
                observer.disconnect();
                container.innerHTML = originalContent;
                observer.observe(container, { childList: true, subtree: true });
            }
        });
        
        observer.observe(container, {
            childList: true,
            subtree: true
        });
        
        console.log('✅ Grid is now LOCKED - No modifications allowed');
        
        // Method 6: Block jQuery modifications
        if (typeof jQuery !== 'undefined') {
            const $container = jQuery(container);
            
            const blockedMethods = ['html', 'append', 'prepend', 'after', 'before', 'replaceWith'];
            blockedMethods.forEach(function(method) {
                const original = jQuery.fn[method];
                $container[method] = function() {
                    if (isLocked) {
                        console.log('🚫 Blocked jQuery .' + method + '() on locked grid');
                        return this;
                    }
                    return original.apply(this, arguments);
                };
            });
        }
    }
    
    // Wait for DOM to be ready, then lock after a short delay
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(lockGrid, 500); // Wait 500ms after DOM ready
        });
    } else {
        setTimeout(lockGrid, 500);
    }
    
    // Also lock after window fully loads (including images)
    window.addEventListener('load', function() {
        if (!isLocked) {
            setTimeout(lockGrid, 200); // Lock 200ms after full load
        }
    });
    
    console.log('🔒 Grid Lock Armed - Will activate after page loads');
    
})();