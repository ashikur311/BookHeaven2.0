<?php
session_start();
include_once("../db_connection.php");
require('../fpdf/fpdf.php');

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    die('Invalid order ID');
}

// Extract numeric part from order ID
$order_id_param = $_GET['order_id'];
$numeric_id = preg_replace('/[^0-9]/', '', $order_id_param);
$order_id = intval($numeric_id);

// Get order details
$order_query = "SELECT * FROM orders WHERE order_id = ?";
$order_stmt = $conn->prepare($order_query);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    die('Order not found');
}

$order = $order_result->fetch_assoc();

// Get order items
$items_query = "SELECT oi.*, b.title 
               FROM order_items oi 
               JOIN books b ON oi.book_id = b.book_id 
               WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$items = [];
$calculated_total = 0;
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
    $calculated_total += $item['quantity'] * $item['price'];
}

// Create PDF
class PDF extends FPDF {
    // Page header
    function Header() {
        // Logo path - using absolute server path
        $logo_path = $_SERVER['DOCUMENT_ROOT'] . '/BookHeaven2.0/assets/images/logo.png';
        
        // Only try to add logo if file exists
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 10, 6, 30);
        } else {
            // Fallback if logo not found
            $this->SetFont('Arial', 'B', 15);
            $this->Cell(40, 10, 'BookHeaven', 0, 0, 'L');
        }
        
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(80);
        $this->Cell(30, 10, 'Order Invoice', 0, 0, 'C');
        $this->Ln(20);
    }

    // Page footer
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Colored table
    function FancyTable($header, $data) {
        $this->SetFillColor(57, 171, 210);
        $this->SetTextColor(255);
        $this->SetDrawColor(128, 0, 0);
        $this->SetLineWidth(.3);
        $this->SetFont('', 'B');
        
        $w = array(100, 20, 30, 30);
        for($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();
        
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
        
        $fill = false;
        foreach($data as $row) {
            $this->Cell($w[0], 6, $row['title'], 'LR', 0, 'L', $fill);
            $this->Cell($w[1], 6, $row['quantity'], 'LR', 0, 'C', $fill);
            $this->Cell($w[2], 6, number_format($row['price'], 2), 'LR', 0, 'R', $fill);
            $this->Cell($w[3], 6, number_format($row['quantity'] * $row['price'], 2), 'LR', 0, 'R', $fill);
            $this->Ln();
            $fill = !$fill;
        }
        
        $this->Cell(array_sum($w), 0, '', 'T');
    }
}

// Create PDF instance
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Format order ID for display
$formatted_order_id = 'ORD-' . str_pad($order['order_id'], 4, '0', STR_PAD_LEFT);

// Order information
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Order #' . $formatted_order_id, 0, 1);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(40, 6, 'Order Date:', 0, 0);
$pdf->Cell(0, 6, date('F j, Y, g:i a', strtotime($order['order_date'])), 0, 1);

$pdf->Cell(40, 6, 'Status:', 0, 0);
$pdf->Cell(0, 6, ucfirst($order['status']), 0, 1);

$pdf->Cell(40, 6, 'Payment Method:', 0, 0);
$pdf->Cell(0, 6, strtoupper($order['payment_method']), 0, 1);

$pdf->Cell(40, 6, 'Shipping Address:', 0, 0);
$pdf->MultiCell(0, 6, $order['shipping_address'], 0, 1);

$pdf->Ln(10);

// Table header
$header = array('Item', 'Quantity', 'Unit Price', 'Subtotal');

// Table data
$data = array();
foreach ($items as $item) {
    $data[] = array(
        'title' => $item['title'],
        'quantity' => $item['quantity'],
        'price' => $item['price']
    );
}

// Create table
$pdf->FancyTable($header, $data);

// Calculate totals
$shipping = 0; // Set your shipping cost here if applicable
$grand_total = $calculated_total + $shipping;

// Display totals
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(150, 10, 'Subtotal:', 0, 0, 'R');
$pdf->Cell(30, 10, number_format($calculated_total, 2), 0, 1);

if ($shipping > 0) {
    $pdf->Cell(150, 10, 'Shipping:', 0, 0, 'R');
    $pdf->Cell(30, 10, number_format($shipping, 2), 0, 1);
}

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(150, 10, 'Grand Total:', 0, 0, 'R');
$pdf->Cell(30, 10, number_format($grand_total, 2), 0, 1);

// Output PDF
$pdf->Output('D', 'Order_' . $formatted_order_id . '.pdf');
?>