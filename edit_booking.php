<?php
session_start();
require 'connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_username'])) {
    header("Location: admin_login.php");
    exit();
}

// Get booking ID from URL
if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$booking_id = $_GET['id'];

// Fetch booking details
$stmt = $conn->prepare("
    SELECT b.*, u.fullname 
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    $_SESSION['error'] = "Booking not found!";
    header("Location: admin_dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $training_type = $_POST['training_type'];
    $trainer = $_POST['trainer'];
    $date = $_POST['date'];
    $time_slot = $_POST['time_slot'];
    $notes = $_POST['notes'];

    // Check if the new time slot is available
    $check_stmt = $conn->prepare("
        SELECT id FROM bookings 
        WHERE trainer = ? AND date = ? AND time_slot = ? AND id != ?
    ");
    $check_stmt->bind_param("sssi", $trainer, $date, $time_slot, $booking_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error = "This time slot is already booked with the selected trainer!";
    } else {
        // Update the booking
        $update_stmt = $conn->prepare("
            UPDATE bookings 
            SET training_type = ?, trainer = ?, date = ?, time_slot = ?, notes = ?
            WHERE id = ?
        ");
        $update_stmt->bind_param("sssssi", $training_type, $trainer, $date, $time_slot, $notes, $booking_id);

        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Booking updated successfully!";
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Error updating booking: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/styles.css">
    <style>
        .edit-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .edit-form {
            background-color: var(--primary-color-light);
            padding: 2rem;
            border-radius: 10px;
        }
        
        select, textarea, input[type="date"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            background: var(--primary-color-extra-light);
            border: none;
            border-radius: 6px;
            color: var(--white);
            font-size: 14px;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .error-message {
            color: #ff3333;
            background-color: rgba(255, 51, 51, 0.2);
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <nav>
        <div class="nav__logo">
            <a href="index.html"><img src="img/logo.png" alt="logo" /></a>
        </div>
        
        <div style="display: flex; align-items: center;">
            <span class="nav__welcome">Admin: <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <button class="btn" onclick="window.location.href='admin_logout.php'">Logout</button>
        </div>
    </nav>

    <section class="section__container edit-container">
        <h2 class="section__header">EDIT BOOKING</h2>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="edit-form">
            <form method="POST" action="">
                <div style="margin-bottom: 1rem;">
                    <p><strong>User:</strong> <?php echo htmlspecialchars($booking['fullname']); ?></p>
                </div>
                
                <select name="training_type" required>
                    <option value="Personal Training" <?php echo $booking['training_type'] == 'Personal Training' ? 'selected' : ''; ?>>Personal Training</option>
                    <option value="Strength Training" <?php echo $booking['training_type'] == 'Strength Training' ? 'selected' : ''; ?>>Strength Training</option>
                    <option value="Cardio" <?php echo $booking['training_type'] == 'Cardio' ? 'selected' : ''; ?>>Cardio</option>
                    <option value="Yoga" <?php echo $booking['training_type'] == 'Yoga' ? 'selected' : ''; ?>>Yoga</option>
                    <option value="Boxing" <?php echo $booking['training_type'] == 'Boxing' ? 'selected' : ''; ?>>Boxing</option>
                </select>
                
                <select name="trainer" required>
                    <option value="Coach Sherwin" <?php echo $booking['trainer'] == 'Coach Sherwin' ? 'selected' : ''; ?>>Coach Sherwin</option>
                    <option value="Coach Charlene" <?php echo $booking['trainer'] == 'Coach Charlene' ? 'selected' : ''; ?>>Coach Charlene</option>
                    <option value="Coach Patrice" <?php echo $booking['trainer'] == 'Coach Patrice' ? 'selected' : ''; ?>>Coach Patrice</option>
                    <option value="Coach Kirsten" <?php echo $booking['trainer'] == 'Coach Kirsten' ? 'selected' : ''; ?>>Coach Kirsten</option>
                </select>
                
                <input type="date" name="date" value="<?php echo htmlspecialchars($booking['date']); ?>" required>
                
                <select name="time_slot" required>
                    <option value="7:00am - 08:00am" <?php echo $booking['time_slot'] == '7:00am - 08:00am' ? 'selected' : ''; ?>>07:00am - 08:00am</option>
                    <option value="8:00am - 9:00am" <?php echo $booking['time_slot'] == '8:00am - 9:00am' ? 'selected' : ''; ?>>08:00am - 09:00am</option>
                    <option value="10:00am - 11:00am" <?php echo $booking['time_slot'] == '10:00am - 11:00am' ? 'selected' : ''; ?>>10:00am - 11:00am</option>
                    <option value="2:00pm - 3:00pm" <?php echo $booking['time_slot'] == '2:00pm - 3:00pm' ? 'selected' : ''; ?>>2:00pm - 3:00pm</option>
                    <option value="3:00pm - 4:00pm" <?php echo $booking['time_slot'] == '3:00pm - 4:00pm' ? 'selected' : ''; ?>>3:00pm - 4:00pm</option>
                    <option value="4:00pm - 5:00pm" <?php echo $booking['time_slot'] == '4:00pm - 5:00pm' ? 'selected' : ''; ?>>4:00pm - 5:00pm</option>
                </select>
                
                <textarea name="notes" placeholder="Any special requests or notes..."><?php echo htmlspecialchars($booking['notes']); ?></textarea>
                
                <button type="submit" class="btn">Update Booking</button>
                <button type="button" class="btn" style="background-color: #ff6b6b;" onclick="window.location.href='admin_dashboard.php'">Cancel</button>
            </form>
        </div>
    </section>

    <footer class="section__container footer__container">
        <span class="bg__blur"></span>
        <span class="bg__blur footer__blur"></span>
        <div class="footer__col">
            <div class="footer__logo"><img src="img/logo.png" alt="logo" /></div>
            <p>
                Take the first step towards a healthier, stronger you with our
                unbeatable pricing plans. Let's sweat, achieve, and conquer together!
            </p>
            <div class="footer__socials">
                <a href="#"><i class="ri-facebook-fill"></i></a>
                <a href="#"><i class="ri-instagram-line"></i></a>
                <a href="#"><i class="ri-twitter-fill"></i></a>
            </div>
        </div>
        <div class="footer__col">
            <h4>Contact</h4>
            <a href="#">Contact Us</a>
            <a href="#">Privacy Policy</a>
            <a href="#">Terms & Conditions</a>
            <a href="#">BMI Calculator</a>
        </div>
    </footer>
</body>
</html>