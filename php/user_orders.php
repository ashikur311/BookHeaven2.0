<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: authentication.php");
  exit();
}
include_once("../db_connection.php");

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? 0; // Replace with your actual session variable

// Fetch user data
$user_data = [];
if ($user_id) {
    $user_query = "SELECT u.username, u.email,u.user_profile,u.create_time, ui.* 
                  FROM users u 
                  LEFT JOIN user_info ui ON u.user_id = ui.user_id 
                  WHERE u.user_id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
}

// Handle AJAX request for order details
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_order_details' && isset($_GET['order_id'])) {
    header('Content-Type: application/json');
    
    $order_id = intval($_GET['order_id']);
    $response = [];
    
    try {
        // Get order details
        $order_query = "SELECT * FROM orders WHERE order_id = ?";
        $order_stmt = $conn->prepare($order_query);
        $order_stmt->bind_param("i", $order_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();
        
        if ($order_result->num_rows === 0) {
            $response['error'] = 'Order not found';
        } else {
            $order = $order_result->fetch_assoc();
            
            // Get order items
            $items_query = "SELECT oi.*, b.title, b.cover_image_url 
                          FROM order_items oi 
                          JOIN books b ON oi.book_id = b.book_id 
                          WHERE oi.order_id = ?";
            $items_stmt = $conn->prepare($items_query);
            $items_stmt->bind_param("i", $order_id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            
            $items = [];
            while ($item = $items_result->fetch_assoc()) {
                $items[] = $item;
            }
            
            $response = [
                'success' => true,
                'order' => $order,
                'items' => $items
            ];
        }
    } catch (Exception $e) {
        $response['error'] = 'Database error: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// Fetch orders for the user
$orders_query = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();

// Count orders by status for stats
$stats = [
    'total' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

$all_orders = [];
while ($order = $orders_result->fetch_assoc()) {
    $all_orders[] = $order;
    $stats['total']++;
    $stats[strtolower($order['status'])]++; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - BookHeaven</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/BookHeaven2.0/css/user_order.css">
</head>
<body>
    <?php include_once("../header.php"); ?>
    
    <main>
        <aside>
            <section class="user-info">
                <img src="/BookHeaven2.0/<?php echo htmlspecialchars($user_data['user_profile'] ?? 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'); ?>"
                    alt="<?php echo htmlspecialchars($user_data['username']); ?>" class="user-avatar">
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user_data['username'] ?? 'Guest'); ?></div>
                    <small>Member since: <?php echo date('M Y', strtotime($user_data['create_time'] ?? 'now')); ?></small>
                </div>
            </section>
            <section>
                <nav>
                    <ul>
                        <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wish List</a></li>
                        <li><a href="user_orders.php" class="active"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
                        <li><a href="user_subscription.php"><i class="fas fa-calendar-check"></i> My Subscription</a></li>
                        <li><a href="user_setting.php"><i class="fas fa-cog"></i> Setting</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </section>
        </aside>
        
        <div class="orders_content">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <p><?php echo $stats['total']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pending Orders</h3>
                    <p><?php echo $stats['pending']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Shipped Orders</h3>
                    <p><?php echo $stats['shipped']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Delivered Orders</h3>
                    <p><?php echo $stats['delivered']; ?></p>
                </div>
            </div>

            <div class="orders-table">
                <h2>Order History</h2>
                <p><br></p>
                <?php if (count($all_orders) === 0): ?>
                    <div class="no-orders">
                        <i class="fas fa-shopping-bag"></i>
                        <p>You haven't placed any orders yet.</p>
                        <a href="/BookHeaven2.0/index.php" class="btn btn-shop">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Order Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_orders as $order): ?>
                                <tr>
                                    <td>#ORD-<?php echo str_pad($order['order_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                    <td>৳<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status status-<?php echo strtolower($order['status']); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-view" onclick="openModal(<?php echo $order['order_id']; ?>)">
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include_once("../footer.php"); ?>

<!-- Order Details Modal -->
<div id="orderModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Order Details - <span id="modalOrderId"></span></h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="order-info">
            <div><strong>Order Date:</strong> <span id="orderDate"></span></div><br>
            <div><strong>Status:</strong> <span id="orderStatus"></span></div><br>
            <div><strong>Payment Method:</strong> <span id="paymentMethod"></span></div><br>
            <div><strong>Shipping Address:</strong> <span id="shippingAddress"></span></div><br><br>
        </div>
        <table class="order-items">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody id="orderItems">
                <!-- Items will be inserted here by JavaScript -->
            </tbody>
        </table>
        <div class="order-total">
            <strong>Total: <span id="orderTotal"></span></strong>
        </div>
        <div class="modal-actions">
            <button class="btn btn-pdf" onclick="downloadPDF()">Download PDF</button>
            <button class="btn btn-close" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

    <script>
function openModal(orderId) {
        // Show loading state
        document.getElementById('orderItems').innerHTML = '<tr><td colspan="4" class="loading">Loading order details...</td></tr>';
        document.getElementById('modalOrderId').textContent = `#ORD-${orderId.toString().padStart(4, '0')}`;
        document.getElementById('orderModal').style.display = 'flex';

        // Fetch order details via AJAX to the same page
        fetch(`user_orders.php?ajax=get_order_details&order_id=${orderId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                if (!data.success) {
                    throw new Error('Invalid response format');
                }

                // Set order header info
                const orderDate = new Date(data.order.order_date);
                document.getElementById('orderDate').textContent = orderDate.toLocaleDateString('en-US', {
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                // Set status with proper styling
                const statusElement = document.getElementById('orderStatus');
                statusElement.textContent = data.order.status.charAt(0).toUpperCase() + data.order.status.slice(1);
                statusElement.className = 'status status-' + data.order.status.toLowerCase();
                
                document.getElementById('paymentMethod').textContent = 
                    data.order.payment_method.toUpperCase();
                document.getElementById('shippingAddress').textContent = 
                    data.order.shipping_address;
                
                // Populate items
                const itemsContainer = document.getElementById('orderItems');
                itemsContainer.innerHTML = '';
                
                if (data.items.length === 0) {
                    itemsContainer.innerHTML = '<tr><td colspan="4">No items found in this order</td></tr>';
                } else {
                    data.items.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${item.title || 'Unknown Item'}</td>
                            <td>${item.quantity || 1}</td>
                            <td>৳${parseFloat(item.price || 0).toFixed(2)}</td>
                            <td>৳${((item.quantity || 1) * (item.price || 0)).toFixed(2)}</td>
                        `;
                        itemsContainer.appendChild(row);
                    });
                }
                
                // Set total
                document.getElementById('orderTotal').textContent = 
                    `৳${parseFloat(data.order.total_amount || 0).toFixed(2)}`;
            })
            .catch(error => {
                console.error('Error fetching order details:', error);
                document.getElementById('orderItems').innerHTML = 
                    `<tr><td colspan="4" class="error">Error loading order details: ${error.message}</td></tr>`;
            });
    }

    function closeModal() {
        document.getElementById('orderModal').style.display = 'none';
    }

    function downloadPDF() {
        const orderId = document.getElementById('modalOrderId').textContent.replace('#', '');
        // alert('PDF generation would be implemented for order: ' + orderId);
        // In a real implementation, you would redirect to a PDF generation script
        window.location.href = `generate_pdf.php?order_id=${orderId}`;
    }

    // Close modal when clicking outside the content
    window.onclick = function(event) {
        const modal = document.getElementById('orderModal');
        if (event.target === modal) {
            closeModal();
        }
    };
    </script>
</body>
</html>
