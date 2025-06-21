<?php
// download_ticket.php

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once("../db_connection.php");
require_once __DIR__ . '/../fpdf/fpdf.php';

// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define log file
define('LOG_FILE', __DIR__ . '/../tickets/error_log.txt');

// Function to log errors
function log_error($message) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(LOG_FILE, "[$timestamp] $message\n", FILE_APPEND);
}

// Check if user is logged in
function check_user_logged_in() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    return $_SESSION['user_id'];
}

// Fetch user details
function get_user_details($conn, $user_id) {
    $stmt = $conn->prepare("SELECT email, username FROM users WHERE user_id = ?");
    if (!$stmt) {
        log_error("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        return null;
    }
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        log_error("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
        return null;
    }
    return $stmt->get_result()->fetch_assoc();
}

// Check if user is registered for the event
function is_user_registered_for_event($conn, $user_id, $event_id) {
    $stmt = $conn->prepare("SELECT ticket_id FROM event_participants WHERE user_id = ? AND event_id = ?");
    $stmt->bind_param("ii", $user_id, $event_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Send the ticket email
function send_ticket_email($user_email, $ticket_path) {
    $python_script = __DIR__ . '/sendticket.py';
    $command = "python " . escapeshellarg($python_script) . " " . escapeshellarg($user_email) . " " . escapeshellarg($ticket_path) . " 2>&1";
    return shell_exec($command);
}

// Generate and save the PDF ticket
function generate_pdf_ticket($event, $user_name, $user_email, $ticket_id) {
    $ticket_dir = __DIR__ . '/../tickets';
    if (!file_exists($ticket_dir)) {
        if (!mkdir($ticket_dir, 0777, true)) {
            log_error("Failed to create tickets directory");
            return false;
        }
    }

    try {
        // Create a PDF with dimensions equivalent to 1400px x 1750px at 96 DPI (≈ 370mm x 462mm)
        $pdf = new FPDF('P', 'mm', array(370, 462)); // 1400x1750 pixels
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false);

        // Background color
        $pdf->SetFillColor(245, 245, 245);
        $pdf->Rect(0, 0, 370, 462, 'F'); // Full background

        // Outer border
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Rect(15, 15, 340, 432); // margin border

        // Add the logo
        $logo_path = __DIR__ . '/../assets/images/logo.png';
        if (file_exists($logo_path)) {
            $pdf->Image($logo_path, 25, 25, 60); // larger logo
        }

        // Event Name
        $pdf->SetFont('Arial', 'B', 28);
        $pdf->SetXY(100, 30); // next to logo
        $pdf->MultiCell(240, 15, $event['name'], 0, 'L');

        // Line separator
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Line(25, 100, 345, 100);

        // Event Details
        $pdf->SetFont('Arial', '', 20);
        $y_position = 115;

        $pdf->SetXY(25, $y_position);
        $pdf->Cell(50, 12, 'Venue:', 0, 0);
        $pdf->MultiCell(280, 12, $event['venue'], 0, 'L');
        $y_position += 30;

        $pdf->SetXY(25, $y_position);
        $pdf->Cell(50, 12, 'Date:', 0, 0);
        $pdf->Cell(0, 12, date('F j, Y, g:i a', strtotime($event['event_date'])), 0, 1);
        $y_position += 20;

        $pdf->SetXY(25, $y_position);
        $pdf->Cell(50, 12, 'Attendee:', 0, 0);
        $pdf->Cell(0, 12, $user_name, 0, 1);
        $y_position += 20;

        $pdf->SetXY(25, $y_position);
        $pdf->Cell(50, 12, 'Email:', 0, 0);
        $pdf->Cell(0, 12, $user_email, 0, 1);
        $y_position += 20;

        $pdf->SetXY(25, $y_position);
        $pdf->Cell(50, 12, 'Ticket ID:', 0, 0);
        $pdf->Cell(0, 12, $ticket_id, 0, 1);
        $y_position += 30;

        // Event Banner (replacing QR Code Placeholder)
        $banner_path = __DIR__ . '/../' . $event['banner_url']; // Concatenate the base path with the relative banner URL
        if (file_exists($banner_path)) {
            $pdf->Image($banner_path, 25, $y_position, 320, 140); // Image size (width x height)
        } else {
            $pdf->SetXY(270, 120);
            $pdf->SetFont('Arial', 'I', 14);
            $pdf->Cell(80, 80, 'No Banner Available', 1, 1, 'C');
        }

        // Footer
        $pdf->SetFont('Arial', 'I', 16);
        $pdf->SetXY(15, 440);
        $pdf->Cell(340, 10, 'www.bookheaven.com', 0, 0, 'C');

        // Save PDF
        $ticket_path = $ticket_dir . '/' . $ticket_id . '.pdf';
        $pdf->Output('F', $ticket_path);

        return $ticket_path;
    } catch (Exception $e) {
        log_error("FPDF Error: " . $e->getMessage());
        return false;
    }
}

// Main flow

$user_id = check_user_logged_in();
$user = get_user_details($conn, $user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);
    $result_check = is_user_registered_for_event($conn, $user_id, $event_id);

    if ($result_check->num_rows > 0) {
        $existing = $result_check->fetch_assoc();
        $ticket_id = $existing['ticket_id'];
        $ticket_path = __DIR__ . '/../tickets/' . $ticket_id . '.pdf';

        if (file_exists($ticket_path)) {
            $output = send_ticket_email($user['email'], $ticket_path);

            if (strpos($output, 'Ticket sent successfully') !== false) {
                header("Location: events.php?resent=1&event_id=" . $event_id);
                exit();
            } else {
                header("Location: events.php?error=email");
                exit();
            }
        }
    }

    // Fetch event details
    $stmt_event = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
    $stmt_event->bind_param("i", $event_id);
    $stmt_event->execute();
    $result_event = $stmt_event->get_result();

    if ($result_event->num_rows > 0) {
        $event = $result_event->fetch_assoc();
        $ticket_id = 'TICKET-' . strtoupper(uniqid());

        // Generate PDF Ticket
        $ticket_path = generate_pdf_ticket($event, $user['username'], $user['email'], $ticket_id);

        if ($ticket_path) {
            $output = send_ticket_email($user['email'], $ticket_path);

            if (strpos($output, 'Ticket sent successfully') !== false) {
                // Prepare the UPDATE statement
                $stmt_update = $conn->prepare("UPDATE event_participants SET ticket_id = ?, joined_at = NOW() WHERE user_id = ? AND event_id = ?");
                $stmt_update->bind_param("sii", $ticket_id, $user_id, $event_id);

                // Execute the update query
                if ($stmt_update->execute()) {
                    // Redirect to the events page with success status
                    echo "<script>alert('Ticket sent successfully!'); window.location.href='events.php?updated=1&event_id=" . $event_id . "';</script>";
                    exit();
                } else {
                    // Log the error and redirect if the update fails
                    log_error("Update failed: (" . $stmt_update->errno . ") " . $stmt_update->error);
                    echo "<script>alert('Failed to send ticket. Please try again later.'); window.location.href='events.php?error=update_failed';</script>";
                    exit();
                }
            } else {
                unlink($ticket_path); // Delete the ticket file if email sending fails
                header("Location: events.php?error=email");
                exit();
            }
        } else {
            header("Location: events.php?error=pdf");
            exit();
        }
    } else {
        header("Location: events.php?error=invalid_event");
        exit();
    }
} else {
    header("Location: events.php?error=invalid_request");
    exit();
}
?>
