// Food Ordering System JavaScript

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 300);
        }, 5000);
    });

    // Add smooth scrolling to anchor links
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
});

// Cart functionality
const Cart = {
    // Get cart from localStorage
    get: function() {
        return JSON.parse(localStorage.getItem('foodCart')) || {};
    },
    
    // Save cart to localStorage
    save: function(cart) {
        localStorage.setItem('foodCart', JSON.stringify(cart));
    },
    
    // Add item to cart
    add: function(itemId, quantity = 1) {
        const cart = this.get();
        cart[itemId] = (cart[itemId] || 0) + quantity;
        this.save(cart);
        this.updateDisplay();
    },
    
    // Remove item from cart
    remove: function(itemId) {
        const cart = this.get();
        delete cart[itemId];
        this.save(cart);
        this.updateDisplay();
    },
    
    // Update item quantity
    updateQuantity: function(itemId, quantity) {
        const cart = this.get();
        if (quantity <= 0) {
            delete cart[itemId];
        } else {
            cart[itemId] = quantity;
        }
        this.save(cart);
        this.updateDisplay();
    },
    
    // Clear entire cart
    clear: function() {
        localStorage.removeItem('foodCart');
        this.updateDisplay();
    },
    
    // Get total item count
    getItemCount: function() {
        const cart = this.get();
        return Object.values(cart).reduce((sum, qty) => sum + qty, 0);
    },
    
    // Update cart display elements
    updateDisplay: function() {
        const count = this.getItemCount();
        const cartCountElements = document.querySelectorAll('#cartCount, .cart-count');
        const cartBtnElements = document.querySelectorAll('#viewCartBtn, .view-cart-btn');
        const cartSummary = document.getElementById('cartSummary');
        
        cartCountElements.forEach(element => {
            element.textContent = count;
        });
        
        cartBtnElements.forEach(element => {
            element.style.display = count > 0 ? 'inline-block' : 'none';
        });
        
        // Show/hide cart summary
        if (cartSummary) {
            cartSummary.style.display = count > 0 ? 'block' : 'none';
        }
        
        // Update individual quantity displays
        const cart = this.get();
        Object.keys(cart).forEach(itemId => {
            const qtyElements = document.querySelectorAll(`#qty-${itemId}, .qty-${itemId}`);
            qtyElements.forEach(element => {
                element.textContent = cart[itemId] || 0;
            });
        });
    }
};

// Location utilities
const Location = {
    // Get current location
    getCurrent: function(callback) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    callback(null, {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude
                    });
                },
                function(error) {
                    callback(error, null);
                }
            );
        } else {
            callback(new Error('Geolocation is not supported'), null);
        }
    },
    
    // Calculate distance between two points (Haversine formula)
    calculateDistance: function(lat1, lon1, lat2, lon2) {
        const R = 6371; // Earth's radius in kilometers
        const dLat = this.toRadians(lat2 - lat1);
        const dLon = this.toRadians(lon2 - lon1);
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(this.toRadians(lat1)) * Math.cos(this.toRadians(lat2)) *
                Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    },
    
    // Convert degrees to radians
    toRadians: function(degrees) {
        return degrees * (Math.PI/180);
    }
};

// Form validation utilities
const Validation = {
    // Validate email
    isValidEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    // Validate phone number
    isValidPhone: function(phone) {
        const re = /^[\+]?[1-9][\d]{0,15}$/;
        return re.test(phone.replace(/[\s\-\(\)]/g, ''));
    },
    
    // Show validation error
    showError: function(element, message) {
        element.classList.add('error');
        let errorDiv = element.parentNode.querySelector('.error-message');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.style.color = 'red';
            errorDiv.style.fontSize = '12px';
            errorDiv.style.marginTop = '5px';
            element.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    },
    
    // Clear validation error
    clearError: function(element) {
        element.classList.remove('error');
        const errorDiv = element.parentNode.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
};

// Utility functions
const Utils = {
    // Format currency
    formatCurrency: function(amount) {
        return '$' + parseFloat(amount).toFixed(2);
    },
    
    // Debounce function
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // Show loading state
    showLoading: function(element, text = 'Loading...') {
        const originalText = element.textContent;
        element.textContent = text;
        element.disabled = true;
        return function hideLoading() {
            element.textContent = originalText;
            element.disabled = false;
        };
    },
    
    // Show success message
    showSuccess: function(message, duration = 3000) {
        this.showMessage(message, 'success', duration);
    },
    
    // Show error message
    showError: function(message, duration = 5000) {
        this.showMessage(message, 'error', duration);
    },
    
    // Show message
    showMessage: function(message, type = 'info', duration = 3000) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `alert alert-${type}`;
        messageDiv.textContent = message;
        messageDiv.style.position = 'fixed';
        messageDiv.style.top = '20px';
        messageDiv.style.right = '20px';
        messageDiv.style.zIndex = '9999';
        messageDiv.style.maxWidth = '300px';
        
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, duration);
    }
};

// Initialize cart display on page load
if (typeof Cart !== 'undefined') {
    Cart.updateDisplay();
}

// Global functions for backward compatibility
function changeQuantity(itemId, change) {
    const cart = Cart.get();
    const currentQty = cart[itemId] || 0;
    Cart.updateQuantity(itemId, currentQty + change);
}

function addToCart(itemId, itemName, price) {
    Cart.add(itemId, 1);
    Utils.showSuccess(itemName + ' added to cart!');
}

function getCurrentLocation() {
    Location.getCurrent(function(error, position) {
        if (error) {
            Utils.showError('Unable to get your location. Please enter coordinates manually.');
        } else {
            const latInput = document.getElementById('lat_input');
            const lonInput = document.getElementById('lon_input');
            const deliveryLatInput = document.getElementById('deliveryLat');
            const deliveryLonInput = document.getElementById('deliveryLon');
            
            if (latInput) latInput.value = position.latitude;
            if (lonInput) lonInput.value = position.longitude;
            if (deliveryLatInput) deliveryLatInput.value = position.latitude;
            if (deliveryLonInput) deliveryLonInput.value = position.longitude;
            
            if (typeof findNearestBranch === 'function') {
                findNearestBranch(position.latitude, position.longitude);
            }
        }
    });
}
