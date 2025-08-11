<?php
// User Menu Page
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/functions.php';

requireUser(); // Ensure only regular users can access this page

// Get all available menu items
$menuItems = getMultipleResults("SELECT * FROM menu_items WHERE status = 'available' ORDER BY category, name");

// Group items by category
$categories = [];
foreach ($menuItems as $item) {
    $categories[$item['category']][] = $item;
}

include '../includes/header.php';
?>

<div class="container">
    <h1>Our Menu</h1>
    
    <!-- Cart Summary (sticky) -->
    <div class="cart-summary" id="cartSummary" style="display: none;">
        <h3>🛒 Your Cart</h3>
        <p><span id="cartCount">0</span> items in your cart</p>
        <a href="order.php" class="btn" id="viewCartBtn">
            Proceed to Checkout
        </a>
    </div>
    
    <?php foreach ($categories as $categoryName => $items): ?>
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header category-header">
                <?php echo htmlspecialchars($categoryName); ?>
            </div>
            <div class="card-body">
                <div class="menu-grid">
                    <?php foreach ($items as $item): ?>
                        <div class="menu-item">
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width:100%;height:200px;object-fit:cover;" onerror="this.style.display='none'">
                            <?php else: ?>
                                <div style="height: 200px; background: linear-gradient(135deg, #f8f9fa, #e9ecef); display: flex; align-items: center; justify-content: center; color: #6c757d; font-size: 3rem;">
                                    🍽️
                                </div>
                            <?php endif; ?>
                            <div class="menu-item-content">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p><?php echo htmlspecialchars($item['description']); ?></p>
                                
                                <div class="menu-item-footer">
                                    <span class="price"><?php echo formatCurrency($item['price']); ?></span>
                                    <div class="quantity-controls">
                                        <button onclick="changeQuantity(<?php echo $item['id']; ?>, -1)">-</button>
                                        <span id="qty-<?php echo $item['id']; ?>">0</span>
                                        <button onclick="changeQuantity(<?php echo $item['id']; ?>, 1)">+</button>
                                    </div>
                                </div>
                                
                                <button class="add-to-cart-btn" onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['price']; ?>)">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if (empty($categories)): ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 3rem;">
                <h3>🍽️ No menu items available</h3>
                <p>Please check back later for delicious food options!</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
let cart = JSON.parse(localStorage.getItem('foodCart')) || {};

function updateCartDisplay() {
    const totalItems = Object.values(cart).reduce((sum, qty) => sum + qty, 0);
    document.getElementById('cartCount').textContent = totalItems;
    document.getElementById('viewCartBtn').style.display = totalItems > 0 ? 'inline-block' : 'none';
    
    // Update quantity displays
    Object.keys(cart).forEach(itemId => {
        const qtyElement = document.getElementById('qty-' + itemId);
        if (qtyElement) {
            qtyElement.textContent = cart[itemId] || 0;
        }
    });
}

function changeQuantity(itemId, change) {
    if (!cart[itemId]) cart[itemId] = 0;
    cart[itemId] += change;
    if (cart[itemId] <= 0) {
        delete cart[itemId];
    }
    localStorage.setItem('foodCart', JSON.stringify(cart));
    updateCartDisplay();
}

function addToCart(itemId, itemName, price) {
    if (!cart[itemId]) cart[itemId] = 0;
    cart[itemId]++;
    localStorage.setItem('foodCart', JSON.stringify(cart));
    updateCartDisplay();
    
    // Show success message
    const message = document.createElement('div');
    message.className = 'alert alert-success';
    message.textContent = itemName + ' added to cart!';
    message.style.position = 'fixed';
    message.style.top = '20px';
    message.style.right = '20px';
    message.style.zIndex = '9999';
    document.body.appendChild(message);
    
    setTimeout(() => {
        document.body.removeChild(message);
    }, 2000);
}

// Initialize cart display
updateCartDisplay();
</script>

<?php include '../includes/footer.php'; ?>
