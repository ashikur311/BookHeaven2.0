<?php
// Start session and include database connection
session_start();
require_once('../db_connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /BookHeaven2.0/php/authentication.php");
    exit();
}

// Get session user ID
$session_user_id = $_SESSION['user_id'];

// Handle become partner action
if (isset($_POST['become_partner'])) {
    $insert_query = "INSERT INTO partners (user_id, status, joined_at) VALUES (?, 'pending', NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("i", $session_user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $success_message = "Your partner application has been submitted for admin approval.";
    } else {
        $error_message = "Failed to submit partner application. Please try again.";
    }
}

// Fetch partner information (including partner_id)
$partner_query = "
    SELECT p.partner_id, p.user_id, p.status, p.income, p.joined_at, u.username
    FROM partners p
    JOIN users u ON p.user_id = u.user_id
    WHERE p.user_id = ?
";
$stmt = $conn->prepare($partner_query);
$stmt->bind_param("i", $session_user_id);
$stmt->execute();
$partner_result = $stmt->get_result();
$partner = $partner_result->fetch_assoc();

// If user is not a partner, show the agreement form
if (!$partner) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Become a Partner</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            :root {
                --primary-color: #57abd2;
                --secondary-color: #f8f5fc;
                --accent-color: rgb(223, 219, 227);
                --text-color: #333;
                --light-purple: #e6d9f2;
                --dark-text: #212529;
                --light-text: #f8f9fa;
                --card-bg: #f8f9fa;
                --aside-bg: #f0f2f5;
                --nav-hover: #e0e0e0;
                --column-hover: #cee9ea;
            }
            .dark-mode {
                --primary-color: #57abd2;
                --secondary-color: #2d3748;
                --accent-color: #4a5568;
                --text-color: #f8f9fa;
                --light-purple: #4a5568;
                --dark-text: #f8f9fa;
                --light-text: #212529;
                --card-bg: #1a202c;
                --aside-bg: #1a202c;
                --nav-hover: #4a5568;
                --column-hover: #656565;
            }
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            
            body {
                display: flex;
                flex-direction: column;
                min-height: 100vh;
                background-color: var(--secondary-color);
                color: var(--text-color);
            }
            
            .partner-agreement-container {
                max-width: 800px;
                margin: 50px auto;
                padding: 30px;
                background-color: var(--card-bg);
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            .partner-agreement-title {
                color: var(--primary-color);
                margin-bottom: 20px;
                text-align: center;
            }
            
            .agreement-content {
                margin-bottom: 30px;
                line-height: 1.6;
            }
            
            .agreement-points {
                margin: 20px 0;
                padding-left: 20px;
            }
            
            .btn {
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 1rem;
                transition: all 0.3s;
                display: block;
                width: 200px;
                margin: 20px auto 0;
                text-align: center;
            }
            
            .btn-primary {
                background-color: var(--primary-color);
                color: white;
            }
            
            .btn-primary:hover {
                background-color: #3a8fc5;
            }
            
            .alert {
                padding: 10px 15px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            
            .alert-success {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            
            .alert-danger {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            
            .alert-info {
                background-color: #d1ecf1;
                color: #0c5460;
                border: 1px solid #bee5eb;
            }
        </style>
    </head>
    <body>
    <?php include_once("../header.php"); ?>
    
    <div class="partner-agreement-container">
        <h1 class="partner-agreement-title">Become a Partner</h1>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php else: ?>
            <div class="agreement-content">
                <p>You are not currently registered as a partner. By becoming a partner, you can earn money by sharing your books with our community.</p>
                
                <h3>Partner Agreement:</h3>
                <ul class="agreement-points">
                    <li>You retain ownership of all books you share</li>
                    <li>You earn 70% of the rental revenue for each book</li>
                    <li>Books must be in good condition and legally owned by you</li>
                    <li>You are responsible for shipping books to renters</li>
                    <li>You must maintain accurate inventory of your books</li>
                    <li>Returns must be processed within 3 business days</li>
                </ul>
                
                <p>By clicking "Become Partner" below, you agree to these terms and conditions.</p>
            </div>
            
            <form method="POST">
                <button type="submit" name="become_partner" class="btn btn-primary">Become Partner</button>
            </form>
        <?php endif; ?>
    </div>
    
    <?php include_once("../footer.php"); ?>
    </body>
    </html>
    <?php
    exit();
}

// If partner status is pending, show waiting message
if ($partner['status'] === 'pending') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Partner Approval Pending</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            :root {
                --primary-color: #57abd2;
                --secondary-color: #f8f5fc;
                --accent-color: rgb(223, 219, 227);
                --text-color: #333;
                --light-purple: #e6d9f2;
                --dark-text: #212529;
                --light-text: #f8f9fa;
                --card-bg: #f8f9fa;
                --aside-bg: #f0f2f5;
                --nav-hover: #e0e0e0;
                --column-hover: #cee9ea;
            }
            .dark-mode {
                --primary-color: #57abd2;
                --secondary-color: #2d3748;
                --accent-color: #4a5568;
                --text-color: #f8f9fa;
                --light-purple: #4a5568;
                --dark-text: #f8f9fa;
                --light-text: #212529;
                --card-bg: #1a202c;
                --aside-bg: #1a202c;
                --nav-hover: #4a5568;
                --column-hover: #656565;
            }
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            
            body {
                display: flex;
                flex-direction: column;
                min-height: 100vh;
                background-color: var(--secondary-color);
                color: var(--text-color);
            }
            
            .pending-container {
                max-width: 600px;
                margin: 50px auto;
                padding: 30px;
                background-color: var(--card-bg);
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                text-align: center;
            }
            
            .pending-icon {
                font-size: 3rem;
                color: #ffc107;
                margin-bottom: 20px;
            }
            
            .pending-title {
                color: var(--primary-color);
                margin-bottom: 15px;
            }
            
            .pending-message {
                margin-bottom: 20px;
                line-height: 1.6;
            }
            
            .alert-info {
                background-color: #d1ecf1;
                color: #0c5460;
                border: 1px solid #bee5eb;
                padding: 10px 15px;
                border-radius: 4px;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
    <?php include_once("../header.php"); ?>
    
    <div class="pending-container">
        <div class="pending-icon">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <h1 class="pending-title">Partner Approval Pending</h1>
        <p class="pending-message">
            Thank you for applying to become a partner. Your application is currently under review by our admin team.
            You will gain access to the partner dashboard once your application is approved.
        </p>
        <div class="alert alert-info">
            We typically process applications within 1-2 business days. You'll receive an email notification once your application is approved.
        </div>
    </div>
    
    <?php include_once("../footer.php"); ?>
    </body>
    </html>
    <?php
    exit();
}

// Only show dashboard if partner status is 'active'
// if ($partner['status'] !== 'active') {
//     header("Location: ../index.php");
//     exit();
// }

// Store partner_id for queries
$partner_id = $partner['partner_id'];

// Handle apply return action
if (isset($_POST['apply_return'])) {
    $book_id = intval($_POST['book_id']);
    $update_query = "
        UPDATE partner_books
        SET status = 'return apply'
        WHERE id = ? AND partner_id = ?
    ";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $book_id, $partner_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $success_message = "Return request submitted successfully!";
    } else {
        $error_message = "Failed to submit return request.";
    }
}

// Handle delete return book action
if (isset($_POST['delete_return'])) {
    $book_id = intval($_POST['book_id']);
    // Retrieve related rent_book_id
    $stmt = $conn->prepare("SELECT rent_book_id FROM partner_books WHERE id = ? AND partner_id = ?");
    $stmt->bind_param("ii", $book_id, $partner_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $rent_book_id = $row['rent_book_id'];
        
        // Delete from partner_books
        $delPartner = $conn->prepare("DELETE FROM partner_books WHERE id = ? AND partner_id = ?");
        $delPartner->bind_param("ii", $book_id, $partner_id);
        $delPartner->execute();
        
        // Delete from rent_books
        $delRent = $conn->prepare("DELETE FROM rent_books WHERE rent_book_id = ?");
        $delRent->bind_param("i", $rent_book_id);
        $delRent->execute();

        if ($delPartner->affected_rows > 0) {
            $success_message = "Book removed successfully from both lists!";
        } else {
            $error_message = "Failed to remove book.";
        }
    } else {
        $error_message = "Book not found or permission denied.";
    }
}

// Get partner's active books
$active_books_query = "
    SELECT pb.id, pb.revenue, pb.status, pb.added_at,
           rb.title, rb.writer, rb.genre
    FROM partner_books pb
    JOIN rent_books rb ON pb.rent_book_id = rb.rent_book_id
    WHERE pb.partner_id = ? AND pb.status IN ('visible', 'on rent', 'pending')
";
$stmt = $conn->prepare($active_books_query);
$stmt->bind_param("i", $partner_id);
$stmt->execute();
$active_books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get partner's return books
$return_books_query = "
    SELECT pb.id, pb.revenue, pb.status, pb.added_at,
           rb.title, rb.writer, rb.genre
    FROM partner_books pb
    JOIN rent_books rb ON pb.rent_book_id = rb.rent_book_id
    WHERE pb.partner_id = ? AND pb.status IN ('return apply', 'return')
";
$stmt = $conn->prepare($return_books_query);
$stmt->bind_param("i", $partner_id);
$stmt->execute();
$return_books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate stats
$total_books    = count($active_books) + count($return_books);
$in_rent        = 0;
$return_requests = 0;
$total_income   = 0;

foreach ($active_books as $book) {
    if ($book['status'] === 'on rent') {
        $in_rent++;
    }
    if ($book['revenue']) {
        $total_income += $book['revenue'];
    }
}

foreach ($return_books as $book) {
    if ($book['status'] === 'return apply') {
        $return_requests++;
    }
    if ($book['revenue']) {
        $total_income += $book['revenue'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
          :root {
            --primary-color: #57abd2;
            --secondary-color: #f8f5fc;
            --accent-color: rgb(223, 219, 227);
            --text-color: #333;
            --light-purple: #e6d9f2;
            --dark-text: #212529;
            --light-text: #f8f9fa;
            --card-bg: #f8f9fa;
            --aside-bg: #f0f2f5;
            --nav-hover: #e0e0e0;
            --column-hover: #cee9ea;
        }
        .dark-mode {
            --primary-color: #57abd2;
            --secondary-color: #2d3748;
            --accent-color: #4a5568;
            --text-color: #f8f9fa;
            --light-purple: #4a5568;
            --dark-text: #f8f9fa;
            --light-text: #212529;
            --card-bg: #1a202c;
            --aside-bg: #1a202c;
            --nav-hover: #4a5568;
            --column-hover: #656565;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: var(--secondary-color);
            color: var(--text-color);
        }
        
        main {
            display: flex;
            flex: 1;
        }
        
        aside {
            width: 250px;
            background-color: var(--aside-bg);
            padding: 20px 0;
            border-right: 1px solid var(--accent-color);
        }
        
        .nav-logo {
            padding: 0 20px 20px;
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            border-bottom: 1px solid var(--accent-color);
            margin-bottom: 20px;
        }
        
        nav ul {
            list-style: none;
        }
        
        nav ul li {
            margin: 5px 0;
        }
        
        nav ul li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            text-decoration: none;
            color: var(--text-color);
            transition: all 0.3s;
        }
        
        nav ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        nav ul li a:hover, nav ul li a.active {
            background-color: var(--nav-hover);
            color: var(--primary-color);
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .dashboard-title {
            font-size: 1.8rem;
            color: var(--primary-color);
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-title {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .section-title {
            font-size: 1.3rem;
            margin: 25px 0 15px;
            color: var(--primary-color);
            padding-bottom: 8px;
            border-bottom: 2px solid var(--accent-color);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background-color: var(--card-bg);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--accent-color);
        }
        
        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }
        
        tr:hover {
            background-color: var(--column-hover);
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-available {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rented {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-pending {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-return {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #3a8fc5;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.75rem;
        }
        
        .theme-toggle {
            background: none;
            border: none;
            color: var(--text-color);
            cursor: pointer;
            font-size: 1.2rem;
        }
        
        .alert {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
<?php include_once("../header.php"); ?>
<main>
    <aside>
        <div class="nav-logo">
            <?php echo htmlspecialchars($partner['username']); ?>
            <div style="font-size: 0.8rem; margin-top: 5px;">
                Partner since <?php echo date('M Y', strtotime($partner['joined_at'])); ?>
            </div>
        </div>
        <nav>
            <ul>
                <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="partner_add_books.php"><i class="fas fa-book"></i> Add Book</a></li>
               
            </ul>
        </nav>
    </aside>
    <div class="main-content">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Partner Dashboard</h1>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-title">Total Books</div>
                <div class="stat-value"><?php echo $total_books; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">In Rent</div>
                <div class="stat-value"><?php echo $in_rent; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Return Requests</div>
                <div class="stat-value\ ?>"><?php echo $return_requests; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Income</div>
                <div class="stat-value">$<?php echo number_format($total_income, 2); ?></div>
            </div>
        </div>

        <h2 class="section-title">Active Books</h2>
        <table>
            <thead>
                <tr>
                    <th>Title</th><th>Writer</th><th>Added Date</th><th>Revenue</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($active_books): ?>
                    <?php foreach ($active_books as $book): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['writer']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($book['added_at'])); ?></td>
                            <td>$<?php echo number_format($book['revenue'] ?? 0, 2); ?></td>
                            <td>
                                <?php
                                $class = '';
                                switch ($book['status']) {
                                    case 'visible': $class = 'status-available'; break;
                                    case 'on rent': $class = 'status-rented'; break;
                                    case 'pending': $class = 'status-pending'; break;
                                }
                                ?>
                                <span class="status <?php echo $class; ?>"><?php echo ucfirst($book['status']); ?></span>
                            </td>
                            <td>
                                <!-- Hidden form for submission -->
                                <form id="return-form-<?php echo $book['id']; ?>" method="POST" style="display:none;">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <input type="hidden" name="apply_return" value="1">
                                </form>
                                <button type="button" class="btn btn-primary btn-sm" onclick="handleApplyReturn(<?php echo $book['id']; ?>, '<?php echo $book['status']; ?>')">Apply Return</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">No active books found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h2 class="section-title">Return Book Requests</h2>
        <table>
            <thead>
                <tr>
                    <th>Title</th><th>Writer</th><th>Added Date</th><th>Revenue</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($return_books): ?>
                    <?php foreach ($return_books as $book): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['writer']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($book['added_at'])); ?></td>
                            <td>$<?php echo number_format($book['revenue'] ?? 0, 2); ?></td>
                            <td><span class="status status-return"><?php echo ucfirst(str_replace('_', ' ', $book['status'])); ?></span></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <button type="submit" name="delete_return" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">No return requests found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
<?php include_once("../footer.php"); ?>

<script>
function handleApplyReturn(bookId, status) {
    if (status === 'on rent') {
        alert('Your book is currently on rent. Please wait until it is visible again before applying for return.');
    } else {
        document.getElementById('return-form-' + bookId).submit();
    }
}

// Theme toggle logic (unchanged)
</script>
</body>
</html>