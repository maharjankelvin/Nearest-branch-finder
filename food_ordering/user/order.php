<?php
// User Order Placement
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/functions.php';

requireUser(); // Ensure only regular users can access this page

$message = '';
$orderPlaced = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deliveryAddress = sanitizeInput($_POST['delivery_address']);
    $deliveryLat = floatval($_POST['delivery_lat']);
    $deliveryLon = floatval($_POST['delivery_lon']);
    $cartData = json_decode($_POST['cart_data'], true);
    
    if (empty($cartData)) {
        $message = '<div class="alert alert-error">Your cart is empty.</div>';
    } else {
        // Find nearest branch
        $nearestBranch = findNearestBranch($deliveryLat, $deliveryLon);
        
        if (!$nearestBranch) {
            $message = '<div class="alert alert-error">No branches available for delivery.</div>';
        } else {
            try {
                // Calculate total
                $total = 0;
                $validItems = [];
                
                foreach ($cartData as $itemId => $quantity) {
                    $item = getSingleResult("SELECT * FROM menu_items WHERE id = ? AND status = 'available'", [$itemId]);
                    if ($item && $quantity > 0) {
                        $validItems[] = [
                            'item' => $item,
                            'quantity' => $quantity,
                            'subtotal' => $item['price'] * $quantity
                        ];
                        $total += $item['price'] * $quantity;
                    }
                }
                
                if (empty($validItems)) {
                    $message = '<div class="alert alert-error">No valid items in cart.</div>';
                } else {
                    // Create order
                    $stmt = executeQuery(
                        "INSERT INTO orders (user_id, branch_id, delivery_address, delivery_latitude, delivery_longitude, total_amount) VALUES (?, ?, ?, ?, ?, ?)",
                        [$_SESSION['user_id'], $nearestBranch['id'], $deliveryAddress, $deliveryLat, $deliveryLon, $total]
                    );
                    
                    $orderId = $conn->insert_id;
                    
                    // Add order items
                    foreach ($validItems as $validItem) {
                        executeQuery(
                            "INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)",
                            [$orderId, $validItem['item']['id'], $validItem['quantity'], $validItem['item']['price']]
                        );
                    }
                    
                    $message = '<div class="alert alert-success">Order placed successfully! Order ID: #' . $orderId . '</div>';
                    $orderPlaced = true;
                }
            } catch (Exception $e) {
                $message = '<div class="alert alert-error">Failed to place order. Please try again.</div>';
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h1>Place Your Order</h1>
    
    <?php echo $message; ?>
    
    <?php if (!$orderPlaced): ?>
        <div class="card">
            <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>🛒 Your Cart</h2>
                    <div>
                        <button onclick="displayCart()" class="btn btn-secondary btn-sm">🔄 Refresh Cart</button>
                        <a href="menu.php" class="btn btn-primary btn-sm">➕ Add More Items</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="cartItems">
                    <div style="text-align: center; padding: 2rem;">
                        <p>Loading cart...</p>
                        <div style="margin-top: 1rem;">
                            <button onclick="displayCart()" class="btn btn-primary">🔄 Load Cart</button>
                        </div>
                    </div>
                </div>
                <hr>
                <div id="cartTotal" style="text-align: right; font-size: 1.2rem; font-weight: bold; margin-top: 1rem;">
                    Total: $0.00
                </div>
            </div>
        </div>
        
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h2>Delivery Information</h2>
            </div>
            <div class="card-body">
                <form method="POST" id="orderForm">
                    <input type="hidden" name="cart_data" id="cartDataInput">
                    <input type="hidden" name="delivery_lat" id="deliveryLat">
                    <input type="hidden" name="delivery_lon" id="deliveryLon">
                    
                    <div class="form-group">
                        <label for="delivery_address">Delivery Address:</label>
                        <textarea id="delivery_address" name="delivery_address" rows="3" required placeholder="Enter your complete delivery address"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" class="btn btn-secondary" onclick="getCurrentLocation()">📍 Use Current Location</button>
                        <button type="button" class="btn btn-info" onclick="useSampleLocation()" style="margin-left: 0.5rem;">🏠 Use Sample Kathmandu Location</button>
                        <br><small style="margin-top: 0.5rem; display: block;">Or enter coordinates manually:</small>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="lat_input">Latitude:</label>
                            <input type="number" id="lat_input" step="any" placeholder="e.g., 27.7172 (Kathmandu)">
                        </div>
                        <div class="form-group">
                            <label for="lon_input">Longitude:</label>
                            <input type="number" id="lon_input" step="any" placeholder="e.g., 85.3240 (Kathmandu)">
                        </div>
                    </div>
                    
                    <div id="nearestBranchInfo" style="margin: 1rem 0; padding: 1rem; background: #f8f9fa; border-radius: 4px; display: none;">
                        <h4>Nearest Branch:</h4>
                        <p id="branchDetails"></p>
                    </div>
                    
                    <div style="margin-top: 2rem;">
                        <h4>Payment Method: Cash on Delivery</h4>
                        <p>You will pay when your order is delivered.</p>
                    </div>
                    
                    <button type="submit" class="btn btn-success" id="placeOrderBtn" disabled>Place Order</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body" style="text-align: center;">
                <h2>Thank You!</h2>
                <p>Your order has been placed successfully and sent to the nearest branch.</p>
                <p>You will receive your order with cash on delivery option.</p>
                <a href="orders.php" class="btn btn-primary">View My Orders</a>
                <a href="menu.php" class="btn btn-secondary">Order Again</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Get menu items data for cart display
const menuItems = <?php 
    $items = getMultipleResults("SELECT id, name, price FROM menu_items WHERE status = 'available'");
    $itemsMap = [];
    foreach ($items as $item) {
        $itemsMap[$item['id']] = $item;
    }
    echo json_encode($itemsMap);
?>;

function displayCart() {
    const cart = JSON.parse(localStorage.getItem('foodCart')) || {};
    const cartItemsDiv = document.getElementById('cartItems');
    const cartTotalDiv = document.getElementById('cartTotal');
    const cartDataInput = document.getElementById('cartDataInput');
    
    console.log('Cart data:', cart); // Debug log
    console.log('Menu items:', menuItems); // Debug log
    
    if (Object.keys(cart).length === 0) {
        cartItemsDiv.innerHTML = '<div style="text-align: center; padding: 2rem;"><h3>🛒 Your cart is empty</h3><p>Add some delicious items from our menu!</p><a href="menu.php" class="btn btn-primary">Browse Menu</a></div>';
        cartTotalDiv.innerHTML = '<strong>Total: $0.00</strong>';
        document.getElementById('placeOrderBtn').disabled = true;
        return;
    }
    
    let html = '<div style="overflow-x: auto;"><table class="table"><thead><tr><th>Item</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th>Action</th></tr></thead><tbody>';
    let total = 0;
    let hasValidItems = false;
    
    Object.keys(cart).forEach(itemId => {
        if (menuItems[itemId] && cart[itemId] > 0) {
            const item = menuItems[itemId];
            const quantity = cart[itemId];
            const subtotal = item.price * quantity;
            total += subtotal;
            hasValidItems = true;
            
            html += `<tr>
                <td><strong>${item.name}</strong></td>
                <td>$${parseFloat(item.price).toFixed(2)}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <button onclick="updateCartQuantity(${itemId}, -1)" class="btn btn-sm" style="padding: 0.2rem 0.5rem;">-</button>
                        <span style="min-width: 30px; text-align: center;">${quantity}</span>
                        <button onclick="updateCartQuantity(${itemId}, 1)" class="btn btn-sm" style="padding: 0.2rem 0.5rem;">+</button>
                    </div>
                </td>
                <td><strong>$${subtotal.toFixed(2)}</strong></td>
                <td>
                    <button onclick="removeFromCart(${itemId})" class="btn btn-danger btn-sm">🗑️ Remove</button>
                </td>
            </tr>`;
        }
    });
    
    html += '</tbody></table></div>';
    
    if (!hasValidItems) {
        cartItemsDiv.innerHTML = '<div style="text-align: center; padding: 2rem;"><h3>🛒 Your cart is empty</h3><p>Add some delicious items from our menu!</p><a href="menu.php" class="btn btn-primary">Browse Menu</a></div>';
        cartTotalDiv.innerHTML = '<strong>Total: $0.00</strong>';
        document.getElementById('placeOrderBtn').disabled = true;
        return;
    }
    
    cartItemsDiv.innerHTML = html;
    cartTotalDiv.innerHTML = `<strong style="font-size: 1.3rem; color: #28a745;">Total: $${total.toFixed(2)}</strong>`;
    cartDataInput.value = JSON.stringify(cart);
    
    // Enable order button if cart has items and coordinates are set
    const lat = document.getElementById('deliveryLat').value;
    const lon = document.getElementById('deliveryLon').value;
    document.getElementById('placeOrderBtn').disabled = !(hasValidItems && lat && lon);
}

// Add cart management functions
function updateCartQuantity(itemId, change) {
    const cart = JSON.parse(localStorage.getItem('foodCart')) || {};
    cart[itemId] = Math.max(0, (cart[itemId] || 0) + change);
    
    if (cart[itemId] === 0) {
        delete cart[itemId];
    }
    
    localStorage.setItem('foodCart', JSON.stringify(cart));
    displayCart();
    
    // Update cart display in header if it exists
    if (typeof Cart !== 'undefined' && Cart.updateDisplay) {
        Cart.updateDisplay();
    }
}

function removeFromCart(itemId) {
    if (confirm('Remove this item from cart?')) {
        const cart = JSON.parse(localStorage.getItem('foodCart')) || {};
        delete cart[itemId];
        localStorage.setItem('foodCart', JSON.stringify(cart));
        displayCart();
        
        // Update cart display in header if it exists
        if (typeof Cart !== 'undefined' && Cart.updateDisplay) {
            Cart.updateDisplay();
        }
    }
}

function findNearestBranch(lat, lon) {
    if (!lat || !lon) {
        document.getElementById('placeOrderBtn').disabled = true;
        return;
    }
    
    // Show loading state
    const branchInfo = document.getElementById('nearestBranchInfo');
    const branchDetails = document.getElementById('branchDetails');
    
    branchDetails.innerHTML = '🔍 Finding nearest branch...';
    branchInfo.style.display = 'block';
    
    // Simulate finding nearest branch (in real app, this would be an AJAX call)
    setTimeout(() => {
        branchDetails.innerHTML = `
            <div style="background: #d4edda; padding: 1rem; border-radius: 5px;">
                <h5>📍 Nearest Branch Found:</h5>
                <p><strong>Location:</strong> Coordinates ${lat.toFixed(4)}, ${lon.toFixed(4)}</p>
                <p><strong>Service:</strong> ✅ Delivery available to this location</p>
                <p><strong>Estimated Time:</strong> 30-45 minutes</p>
                <small>💡 Our system will automatically assign your order to the closest branch</small>
            </div>
        `;
        
        // Enable order button if cart has items
        const cart = JSON.parse(localStorage.getItem('foodCart')) || {};
        const hasItems = Object.keys(cart).length > 0;
        document.getElementById('placeOrderBtn').disabled = !hasItems;
        
        if (hasItems) {
            document.getElementById('placeOrderBtn').style.background = '#28a745';
            document.getElementById('placeOrderBtn').innerHTML = '🚀 Place Order (Cash on Delivery)';
        }
    }, 1000);
}

// Enhanced location functions
function getCurrentLocation() {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '📍 Getting Location...';
    button.disabled = true;
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            
            document.getElementById('lat_input').value = lat.toFixed(6);
            document.getElementById('lon_input').value = lon.toFixed(6);
            document.getElementById('deliveryLat').value = lat;
            document.getElementById('deliveryLon').value = lon;
            
            // Show success message
            button.innerHTML = '✅ Location Found!';
            button.style.background = '#28a745';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '';
                button.disabled = false;
            }, 2000);
            
            findNearestBranch(lat, lon);
        }, function(error) {
            alert('Unable to get your location. Please enter coordinates manually or check your browser permissions.');
            button.innerHTML = originalText;
            button.disabled = false;
        });
    } else {
        alert('Geolocation is not supported by this browser.');
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

// Sample location function for testing
function useSampleLocation() {
    // Central Kathmandu coordinates
    const lat = 27.7172;
    const lon = 85.3240;
    
    document.getElementById('lat_input').value = lat;
    document.getElementById('lon_input').value = lon;
    document.getElementById('deliveryLat').value = lat;
    document.getElementById('deliveryLon').value = lon;
    
    // Set sample address
    document.getElementById('delivery_address').value = 'Kathmandu Durbar Square Area, Basantapur, Kathmandu 44600, Nepal';
    
    findNearestBranch(lat, lon);
}

// Update coordinates when manually entered
document.getElementById('lat_input').addEventListener('input', function() {
    const lat = parseFloat(this.value);
    const lon = parseFloat(document.getElementById('lon_input').value);
    
    if (!isNaN(lat) && !isNaN(lon)) {
        document.getElementById('deliveryLat').value = lat;
        document.getElementById('deliveryLon').value = lon;
        findNearestBranch(lat, lon);
    }
});

document.getElementById('lon_input').addEventListener('input', function() {
    const lat = parseFloat(document.getElementById('lat_input').value);
    const lon = parseFloat(this.value);
    
    if (!isNaN(lat) && !isNaN(lon)) {
        document.getElementById('deliveryLat').value = lat;
        document.getElementById('deliveryLon').value = lon;
        findNearestBranch(lat, lon);
    }
});

// Initialize cart display and add event listeners
document.addEventListener('DOMContentLoaded', function() {
    displayCart();
    
    // Add form validation
    const orderForm = document.getElementById('orderForm');
    orderForm.addEventListener('submit', function(e) {
        const cart = JSON.parse(localStorage.getItem('foodCart')) || {};
        const lat = document.getElementById('deliveryLat').value;
        const lon = document.getElementById('deliveryLon').value;
        const address = document.getElementById('delivery_address').value.trim();
        
        if (Object.keys(cart).length === 0) {
            e.preventDefault();
            alert('Your cart is empty! Please add items from the menu.');
            return false;
        }
        
        if (!lat || !lon) {
            e.preventDefault();
            alert('Please provide your delivery location (use "Get Current Location" or enter coordinates manually).');
            return false;
        }
        
        if (!address) {
            e.preventDefault();
            alert('Please enter your delivery address.');
            return false;
        }
        
        // Show confirmation
        if (!confirm('Place order for $' + calculateCartTotal().toFixed(2) + '? You will pay cash on delivery.')) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        const submitBtn = document.getElementById('placeOrderBtn');
        submitBtn.innerHTML = '🚀 Placing Order...';
        submitBtn.disabled = true;
    });
});

// Helper function to calculate cart total
function calculateCartTotal() {
    const cart = JSON.parse(localStorage.getItem('foodCart')) || {};
    let total = 0;
    
    Object.keys(cart).forEach(itemId => {
        if (menuItems[itemId] && cart[itemId] > 0) {
            total += menuItems[itemId].price * cart[itemId];
        }
    });
    
    return total;
}

// Auto-refresh cart display when page becomes visible
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        displayCart();
    }
});

// Initialize cart display
displayCart();

// Clear cart after successful order
<?php if ($orderPlaced): ?>
localStorage.removeItem('foodCart');
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>
