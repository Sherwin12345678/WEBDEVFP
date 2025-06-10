<?php
session_start();
require 'connect.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Check if booking_id is set and valid
if (isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];

    // Ensure that booking_id is a valid integer
    if (filter_var($booking_id, FILTER_VALIDATE_INT)) {
        $user_id = $_SESSION['user_id'];

        // verify if the booking exists and belongs to the user
        $stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $booking_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Delete the booking if it exists
        if ($result->num_rows > 0) {
            // Prepare the DELETE statement
            $delete_stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
            $delete_stmt->bind_param("i", $booking_id);

            // Execute the DELETE query
            if ($delete_stmt->execute()) {
                $_SESSION['success'] = "Booking successfully canceled!";
            } else {
                $_SESSION['error'] = "Error deleting the booking. Please try again.";
            }
        } else {
            $_SESSION['error'] = "Booking not found or does not belong to you.";
        }
    } else {
        $_SESSION['error'] = "Invalid booking ID.";
    }
} else {
    $_SESSION['error'] = "No booking ID provided.";
}

//back
header("Location: booking.php");
exit();
?>
