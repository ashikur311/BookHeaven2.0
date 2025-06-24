<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: authentication.php");
  exit();
}

// Database connection
require_once '../db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: authentication.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/**
 * Get cart items for the current user
 */
function getCartItems($conn, $user_id) {
    $cart_query = "SELECT c.id, c.quantity, b.book_id, b.title, b.price, b.cover_image_url, 
                  GROUP_CONCAT(DISTINCT w.name SEPARATOR ', ') AS writers
                  FROM cart c
                  JOIN books b ON c.book_id = b.book_id
                  JOIN book_writers bw ON b.book_id = bw.book_id
                  JOIN writers w ON bw.writer_id = w.writer_id
                  WHERE c.user_id = ?
                  GROUP BY c.id, b.book_id";
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Calculate cart totals
 */
function calculateCartTotals($cart_items) {
    $subtotal = 0;
    $item_count = 0;
    
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
        $item_count += $item['quantity'];
    }
    
    $delivery = 60; // Fixed delivery charge
    $total = $subtotal + $delivery;
    
    return [
        'subtotal' => $subtotal,
        'item_count' => $item_count,
        'delivery' => $delivery,
        'total' => $total
    ];
}

/**
 * Handle AJAX requests
 */
function handleAjaxRequests($conn, $user_id) {
    header('Content-Type: application/json');
    
    if (!isset($_POST['action'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }
    
    $action = $_POST['action'];
    $response = ['success' => false];
    
    switch ($action) {
        case 'update':
            $cart_id = $_POST['cart_id'] ?? null;
            $book_id = $_POST['book_id'] ?? null;
            $quantity = $_POST['quantity'] ?? 1;
            
            if (!$cart_id || !$book_id) {
                $response['message'] = 'Invalid request';
                break;
            }
            
            // Verify the cart item belongs to the user
            $verify_query = "SELECT id FROM cart WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($verify_query);
            $stmt->bind_param("ii", $cart_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $response['message'] = 'Cart item not found';
                break;
            }
            
            // Update the quantity
            $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ii", $quantity, $cart_id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                // Get updated cart totals
                $cart_result = getCartItems($conn, $user_id);
                $cart_items = [];
                while ($item = $cart_result->fetch_assoc()) {
                    $cart_items[] = $item;
                }
                $totals = calculateCartTotals($cart_items);
                $response['totals'] = $totals;
            } else {
                $response['message'] = 'Database error';
            }
            break;
            
        case 'remove':
            $cart_id = $_POST['cart_id'] ?? null;
            
            if (!$cart_id) {
                $response['message'] = 'Invalid request';
                break;
            }
            
            // Verify the cart item belongs to the user
            $verify_query = "SELECT id FROM cart WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($verify_query);
            $stmt->bind_param("ii", $cart_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $response['message'] = 'Cart item not found';
                break;
            }
            
            // Delete the item
            $delete_query = "DELETE FROM cart WHERE id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $cart_id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                // Get updated cart totals
                $cart_result = getCartItems($conn, $user_id);
                $cart_items = [];
                while ($item = $cart_result->fetch_assoc()) {
                    $cart_items[] = $item;
                }
                $totals = calculateCartTotals($cart_items);
                $response['totals'] = $totals;
            } else {
                $response['message'] = 'Database error';
            }
            break;
            
        case 'move_to_wishlist':
            $cart_id = $_POST['cart_id'] ?? null;
            $book_id = $_POST['book_id'] ?? null;
            
            if (!$cart_id || !$book_id) {
                $response['message'] = 'Invalid request';
                break;
            }
            
            // Verify the cart item belongs to the user
            $verify_query = "SELECT id FROM cart WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($verify_query);
            $stmt->bind_param("ii", $cart_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $response['message'] = 'Cart item not found';
                break;
            }
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Check if book already in wishlist
                $check_query = "SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?";
                $stmt = $conn->prepare($check_query);
                $stmt->bind_param("ii", $user_id, $book_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    // Add to wishlist
                    $insert_query = "INSERT INTO wishlist (user_id, book_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($insert_query);
                    $stmt->bind_param("ii", $user_id, $book_id);
                    $stmt->execute();
                }
                
                // Remove from cart
                $delete_query = "DELETE FROM cart WHERE id = ?";
                $stmt = $conn->prepare($delete_query);
                $stmt->bind_param("i", $cart_id);
                $stmt->execute();
                
                // Get updated cart totals
                $cart_result = getCartItems($conn, $user_id);
                $cart_items = [];
                while ($item = $cart_result->fetch_assoc()) {
                    $cart_items[] = $item;
                }
                $totals = calculateCartTotals($cart_items);
                
                $conn->commit();
                
                $response['success'] = true;
                $response['totals'] = $totals;
                $response['message'] = 'Book moved to wishlist successfully';
                
            } catch (Exception $e) {
                $conn->rollback();
                $response['message'] = 'Error moving to wishlist: ' . $e->getMessage();
            }
            break;
            
        case 'clear':
            // Delete all items for the user
            $delete_query = "DELETE FROM cart WHERE user_id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['totals'] = [
                    'subtotal' => 0,
                    'item_count' => 0,
                    'delivery' => 0,
                    'total' => 0
                ];
            } else {
                $response['message'] = 'Database error';
            }
            break;
            
        case 'place_order':
            $payment_method = $_POST['payment_method'] ?? null;
            $shipping_address = $_POST['shipping_address'] ?? '';
            
            if (!$payment_method) {
                $response['message'] = 'Please select a payment method';
                break;
            }
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Get cart items
                $cart_result = getCartItems($conn, $user_id);
                $cart_items = [];
                while ($item = $cart_result->fetch_assoc()) {
                    $cart_items[] = $item;
                }
                
                if (empty($cart_items)) {
                    throw new Exception("Your cart is empty");
                }
                
                // Calculate totals
                $totals = calculateCartTotals($cart_items);
                
                // Create order
                $order_query = "INSERT INTO orders (user_id, status, total_amount, payment_method, shipping_address) 
                               VALUES (?, 'pending', ?, ?, ?)";
                $stmt = $conn->prepare($order_query);
                $stmt->bind_param("idss", $user_id, $totals['total'], $payment_method, $shipping_address);
                $stmt->execute();
                $order_id = $conn->insert_id;
                
                // Add order items
                foreach ($cart_items as $item) {
                    $order_item_query = "INSERT INTO order_items (order_id, book_id, quantity, price) 
                                        VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($order_item_query);
                    $stmt->bind_param("iiid", $order_id, $item['book_id'], $item['quantity'], $item['price']);
                    $stmt->execute();
                }
                
                // Clear cart
                $delete_query = "DELETE FROM cart WHERE user_id = ?";
                $stmt = $conn->prepare($delete_query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                $response['success'] = true;
                $response['order_id'] = $order_id;
                $response['message'] = 'Order placed successfully!';
                
            } catch (Exception $e) {
                $conn->rollback();
                $response['message'] = 'Error placing order: ' . $e->getMessage();
            }
            break;
            
        default:
            $response['message'] = 'Invalid action';
    }
    
    echo json_encode($response);
    exit();
}

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    handleAjaxRequests($conn, $user_id);
}

// Get cart items and calculate totals
$cart_result = getCartItems($conn, $user_id);
$cart_items = [];
while ($item = $cart_result->fetch_assoc()) {
    $cart_items[] = $item;
}

$totals = calculateCartTotals($cart_items);

// Get user shipping address if available
$user_address = '';
$user_info_query = "SELECT address FROM user_info WHERE user_id = ?";
$stmt = $conn->prepare($user_info_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user_info = $result->fetch_assoc();
    $user_address = $user_info['address'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/BookHeaven2.0/css/cart.css">
</head>

<body>
    <?php include_once("../header.php"); ?>
    
    <div class="container">
        <div class="cart-header">
            <h1><i class="fas fa-shopping-cart"></i> Your Shopping Cart</h1>
            <div class="cart-actions">
                <button class="btn btn-outline" onclick="window.location.href='/BookHeaven2.0/index.php'">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </button>
                <?php if($totals['item_count'] > 0): ?>
                <button class="btn btn-danger" id="clearCartBtn">
                    <i class="fas fa-trash"></i> Clear Cart
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="cart-layout">
            <div class="cart-items" id="cartItemsContainer">
                <?php if(count($cart_items) > 0): ?>
                    <?php foreach($cart_items as $item): ?>
                    <div class="cart-item" data-cart-id="<?= $item['id'] ?>" data-book-id="<?= $item['book_id'] ?>">
                        <img src="/BookHeaven2.0/<?= htmlspecialchars($item['cover_image_url']) ?>" 
                             alt="<?= htmlspecialchars($item['title']) ?>" class="cart-item-image">
                        <div class="cart-item-details">
                            <h3 class="cart-item-title"><?= htmlspecialchars($item['title']) ?></h3>
                            <p class="cart-item-author">By <?= htmlspecialchars($item['writers']) ?></p>
                            <div class="cart-item-price">৳<?= number_format($item['price'], 2) ?></div>
                            <div class="cart-item-actions">
                                <div class="quantity-control">
                                    <button class="quantity-btn minus"><i class="fas fa-minus"></i></button>
                                    <input type="number" value="<?= $item['quantity'] ?>" min="1" class="quantity-input">
                                    <button class="quantity-btn plus"><i class="fas fa-plus"></i></button>
                                </div>
                                <button class="wishlist-item" data-cart-id="<?= $item['id'] ?>" data-book-id="<?= $item['book_id'] ?>">
                                    <i class="fas fa-heart"></i> Move to Wishlist
                                </button>
                                <button class="remove-item" data-cart-id="<?= $item['id'] ?>">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <h2>Your Cart is Empty</h2>
                        <p>Looks like you haven't added any items to your cart yet.</p>
                        <button class="btn btn-primary" style="margin-top: 20px;" onclick="window.location.href='/BookHeaven2.0/index.php'">
                            <i class="fas fa-book"></i> Browse Books
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if(count($cart_items) > 0): ?>
            <div class="cart-summary">
                <h2 class="summary-title">Order Summary</h2>
                <div class="summary-row">
                    <span>Subtotal (<?= $totals['item_count'] ?> <?= $totals['item_count'] === 1 ? 'item' : 'items' ?>)</span>
                    <span id="summarySubtotal">৳<?= number_format($totals['subtotal'], 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery Charge</span>
                    <span id="summaryDelivery">৳<?= number_format($totals['delivery'], 2) ?></span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total</span>
                    <span id="summaryTotal">৳<?= number_format($totals['total'], 2) ?></span>
                </div>

                <div class="payment-method">
                    <h3>Payment Method</h3>
                    <label>
                        <input type="radio" name="payment_method" value="cod" checked> 
                        <i class="fas fa-money-bill-wave"></i> Cash on Delivery
                    </label>
                    <div class="payment-method-details" id="codDetails">
                        Pay when you receive your order
                    </div>
                    
                    <label>
                        <input type="radio" name="payment_method" value="online"> 
                        <i class="fas fa-credit-card"></i> Online Payment
                    </label>
                    <div class="payment-method-details" id="onlineDetails" style="display:none;">
                        Secure payment via credit card or mobile banking
                    </div>
                </div>

                <div class="address-input">
                    <h3>Shipping Address</h3>
                    <textarea id="shippingAddress" placeholder="Enter your shipping address"><?= htmlspecialchars($user_address) ?></textarea>
                </div>

                <button class="checkout-btn" id="checkoutBtn">
                    <i class="fas fa-lock"></i> Confirm Order
                </button>

                <div style="margin-top: 20px; font-size: 0.9rem; text-align: center;">
                    <p><i class="fas fa-shield-alt"></i> Secure Checkout</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Order Confirmation Modal -->
<div class="modal" id="confirmationModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title">Confirm Your Order</h3>
      <button class="close-modal">&times;</button>
    </div>
    <div class="modal-body">
      <div class="order-items" id="orderItemsList">
        <!-- Items will be dynamically inserted here -->
      </div>
      <div class="order-totals">
        <div class="order-total-row">
          <span>Subtotal:</span>
          <span id="modalSubtotal">৳0.00</span>
        </div>
        <div class="order-total-row">
          <span>Delivery Charge:</span>
          <span id="modalDelivery">৳0.00</span>
        </div>
        <div class="order-total-row order-total">
          <span>Total:</span>
          <span id="modalTotal">৳0.00</span>
        </div>
      </div>
      <div class="payment-method">
        <h3>Payment Method</h3>
        <p id="modalPaymentMethod">Cash on Delivery</p>
      </div>
      <div class="address-input">
        <h3>Shipping Address</h3>
        <p id="modalShippingAddress">No address provided</p>
      </div>
    </div>
    <div class="modal-actions">
      <button class="modal-btn modal-btn-cancel">Cancel</button>
      <button class="modal-btn modal-btn-confirm" id="confirmOrderBtn">
        <i class="fas fa-check-circle"></i> Confirm Order
      </button>
    </div>
  </div>
</div>
    <?php include_once("../footer.php"); ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Theme synchronization with header
        function syncTheme() {
            const darkMode = localStorage.getItem('darkMode');
            if (darkMode === 'enabled') {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }
        }

        // Call syncTheme when page loads and when theme changes in header
        document.addEventListener('DOMContentLoaded', syncTheme);

        // Listen for storage events to sync theme when changed in other tabs/windows
        window.addEventListener('storage', syncTheme);

        // Payment method selection
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'cod') {
                    document.getElementById('codDetails').style.display = 'block';
                    document.getElementById('onlineDetails').style.display = 'none';
                    document.getElementById('checkoutBtn').innerHTML = '<i class="fas fa-lock"></i> Confirm Order';
                } else {
                    document.getElementById('codDetails').style.display = 'none';
                    document.getElementById('onlineDetails').style.display = 'block';
                    document.getElementById('checkoutBtn').innerHTML = '<i class="fas fa-lock"></i> Proceed to Payment';
                }
            });
        });

        // Quantity Controls
        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const input = this.parentElement.querySelector('.quantity-input');
                let value = parseInt(input.value);
                const cartItem = this.closest('.cart-item');
                const cartId = cartItem.dataset.cartId;
                const bookId = cartItem.dataset.bookId;

                if (this.classList.contains('minus') && value > 1) {
                    value = value - 1;
                    input.value = value;
                    updateCartItem(cartId, bookId, value);
                } else if (this.classList.contains('plus')) {
                    value = value + 1;
                    input.value = value;
                    updateCartItem(cartId, bookId, value);
                }
            });
        });

        // Update cart item quantity via AJAX
        function updateCartItem(cartId, bookId, quantity) {
            $.ajax({
                url: window.location.href,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'update',
                    cart_id: cartId,
                    book_id: bookId,
                    quantity: quantity
                },
                success: function(data) {
                    if (data.success) {
                        // Update summary totals
                        updateSummaryTotals(data.totals);
                    } else {
                        alert('Error updating cart: ' + data.message);
                    }
                },
                error: function() {
                    alert('Error communicating with server');
                }
            });
        }

        // Update summary totals
        function updateSummaryTotals(totals) {
            document.getElementById('summarySubtotal').textContent = `৳${totals.subtotal.toFixed(2)}`;
            document.getElementById('summaryDelivery').textContent = `৳${totals.delivery.toFixed(2)}`;
            document.getElementById('summaryTotal').textContent = `৳${totals.total.toFixed(2)}`;
            
            // Update item count in subtotal label
            const itemText = totals.item_count === 1 ? 'item' : 'items';
            document.querySelector('.summary-row:nth-child(1) span:first-child').textContent = 
                `Subtotal (${totals.item_count} ${itemText})`;
        }

        // Remove Item
        document.querySelectorAll('.remove-item').forEach(item => {
            item.addEventListener('click', function () {
                const cartId = this.dataset.cartId;
                
                if (confirm('Are you sure you want to remove this item from your cart?')) {
                    $.ajax({
                        url: window.location.href,
                        type: 'POST',
                        dataType: 'json',
                        data: { 
                            action: 'remove',
                            cart_id: cartId 
                        },
                        success: function(data) {
                            if (data.success) {
                                // Remove item from DOM
                                document.querySelector(`.cart-item[data-cart-id="${cartId}"]`).remove();
                                
                                // Update summary totals
                                updateSummaryTotals(data.totals);
                                
                                // If no items left, show empty cart message
                                if (document.querySelectorAll('.cart-item').length === 0) {
                                    document.getElementById('cartItemsContainer').innerHTML = `
                                        <div class="empty-cart">
                                            <i class="fas fa-shopping-cart"></i>
                                            <h2>Your Cart is Empty</h2>
                                            <p>Looks like you haven't added any items to your cart yet.</p>
                                            <button class="btn btn-primary" style="margin-top: 20px;" onclick="window.location.href='/BookHeaven2.0/index.php'">
                                                <i class="fas fa-book"></i> Browse Books
                                            </button>
                                        </div>
                                    `;
                                    document.querySelector('.cart-summary').style.display = 'none';
                                }
                            } else {
                                alert('Error removing item: ' + data.message);
                            }
                        },
                        error: function() {
                            alert('Error communicating with server');
                        }
                    });
                }
            });
        });

        // Move to Wishlist
        document.querySelectorAll('.wishlist-item').forEach(item => {
            item.addEventListener('click', function () {
                const cartId = this.dataset.cartId;
                const bookId = this.dataset.bookId;
                
                if (confirm('Move this item to your wishlist?')) {
                    $.ajax({
                        url: window.location.href,
                        type: 'POST',
                        dataType: 'json',
                        data: { 
                            action: 'move_to_wishlist',
                            cart_id: cartId,
                            book_id: bookId
                        },
                        success: function(data) {
                            if (data.success) {
                                // Remove item from DOM
                                document.querySelector(`.cart-item[data-cart-id="${cartId}"]`).remove();
                                
                                // Update summary totals
                                updateSummaryTotals(data.totals);
                                
                                // Show success message
                                alert(data.message);
                                
                                // If no items left, show empty cart message
                                if (document.querySelectorAll('.cart-item').length === 0) {
                                    document.getElementById('cartItemsContainer').innerHTML = `
                                        <div class="empty-cart">
                                            <i class="fas fa-shopping-cart"></i>
                                            <h2>Your Cart is Empty</h2>
                                            <p>Looks like you haven't added any items to your cart yet.</p>
                                            <button class="btn btn-primary" style="margin-top: 20px;" onclick="window.location.href='/BookHeaven2.0/index.php'">
                                                <i class="fas fa-book"></i> Browse Books
                                            </button>
                                        </div>
                                    `;
                                    document.querySelector('.cart-summary').style.display = 'none';
                                }
                            } else {
                                alert('Error: ' + data.message);
                            }
                        },
                        error: function() {
                            alert('Error communicating with server');
                        }
                    });
                }
            });
        });

        // Clear Cart
        document.getElementById('clearCartBtn')?.addEventListener('click', function () {
            if (confirm('Are you sure you want to clear your cart?')) {
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    dataType: 'json',
                    data: { 
                        action: 'clear'
                    },
                    success: function(data) {
                        if (data.success) {
                            // Clear cart items
                            document.getElementById('cartItemsContainer').innerHTML = `
                                <div class="empty-cart">
                                    <i class="fas fa-shopping-cart"></i>
                                    <h2>Your Cart is Empty</h2>
                                    <p>Looks like you haven't added any items to your cart yet.</p>
                                    <button class="btn btn-primary" style="margin-top: 20px;" onclick="window.location.href='/BookHeaven2.0/index.php'">
                                        <i class="fas fa-book"></i> Browse Books
                                    </button>
                                </div>
                            `;
                            document.querySelector('.cart-summary').style.display = 'none';
                        } else {
                            alert('Error clearing cart: ' + data.message);
                        }
                    },
                    error: function() {
                        alert('Error communicating with server');
                    }
                });
            }
        });

        // Checkout Button
        // Checkout Button - Show Confirmation Modal
document.getElementById('checkoutBtn')?.addEventListener('click', function() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const shippingAddress = document.getElementById('shippingAddress').value;
    
    if (!shippingAddress.trim()) {
        alert('Please enter your shipping address');
        return;
    }
    
    // Get all cart items
    const cartItems = document.querySelectorAll('.cart-item');
    
    // Clear previous items in modal
    document.getElementById('orderItemsList').innerHTML = '';
    
    // Add each item to the modal
    cartItems.forEach(item => {
        const title = item.querySelector('.cart-item-title').textContent;
        const price = item.querySelector('.cart-item-price').textContent;
        const quantity = item.querySelector('.quantity-input').value;
        
        const itemElement = document.createElement('div');
        itemElement.className = 'order-item';
        itemElement.innerHTML = `
            <span class="order-item-name">${title}</span>
            <span class="order-item-qty">${quantity}x</span>
            <span class="order-item-price">${price}</span>
        `;
        document.getElementById('orderItemsList').appendChild(itemElement);
    });
    
    // Update totals in modal
    document.getElementById('modalSubtotal').textContent = document.getElementById('summarySubtotal').textContent;
    document.getElementById('modalDelivery').textContent = document.getElementById('summaryDelivery').textContent;
    document.getElementById('modalTotal').textContent = document.getElementById('summaryTotal').textContent;
    
    // Update payment method in modal
    const paymentText = paymentMethod === 'cod' ? 'Cash on Delivery' : 'Online Payment';
    document.getElementById('modalPaymentMethod').textContent = paymentText;
    
    // Update address in modal
    document.getElementById('modalShippingAddress').textContent = shippingAddress;
    
    // Update confirm button text based on payment method
    const confirmBtn = document.getElementById('confirmOrderBtn');
    if (paymentMethod === 'online') {
        confirmBtn.innerHTML = '<i class="fas fa-credit-card"></i> Proceed to Payment';
    } else {
        confirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Order';
    }
    
    // Show the modal
    document.getElementById('confirmationModal').style.display = 'flex';
});

// Close Modal
document.querySelector('.close-modal').addEventListener('click', function() {
    document.getElementById('confirmationModal').style.display = 'none';
});

document.querySelector('.modal-btn-cancel').addEventListener('click', function() {
    document.getElementById('confirmationModal').style.display = 'none';
});

// Click outside modal to close
window.addEventListener('click', function(event) {
    if (event.target === document.getElementById('confirmationModal')) {
        document.getElementById('confirmationModal').style.display = 'none';
    }
});

// Confirm Order Button in Modal
document.getElementById('confirmOrderBtn').addEventListener('click', function() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const shippingAddress = document.getElementById('shippingAddress').value;
    
    // Hide the modal
    document.getElementById('confirmationModal').style.display = 'none';
    
    // Proceed with the order placement
    $.ajax({
        url: window.location.href,
        type: 'POST',
        dataType: 'json',
        data: { 
            action: 'place_order',
            payment_method: paymentMethod,
            shipping_address: shippingAddress
        },
        success: function(data) {
            if (data.success) {
                // Clear cart display
                document.getElementById('cartItemsContainer').innerHTML = `
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <h2>Order Placed Successfully!</h2>
                        <p>Your order ID is: ${data.order_id}</p>
                        <button class="btn btn-primary" style="margin-top: 20px;" onclick="window.location.href='/BookHeaven2.0/index.php'">
                            <i class="fas fa-book"></i> Continue Shopping
                        </button>
                    </div>
                `;
                document.querySelector('.cart-summary').style.display = 'none';
                
                // Show success message
                alert('Order placed successfully! Order ID: ' + data.order_id);
                
                // Redirect based on payment method
                if (paymentMethod === 'online') {
                    window.location.href = '/BookHeaven2.0/payment.php?order_id=' + data.order_id;
                }
            } else {
                alert('Error placing order: ' + data.message);
            }
        },
        error: function() {
            alert('Error communicating with server');
        }
    });
});
    </script>
</body>
</html>