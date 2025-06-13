<?php
session_start();
require 'connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_username'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle booking deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $_SESSION['success'] = "Booking deleted successfully!";
    header("Location: admin_dashboard.php");
    exit();
}

// Fetch all bookings with user information
$bookings = [];
$stmt = $conn->prepare("
    SELECT b.*, u.fullname, u.email, u.contact 
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    ORDER BY b.date, b.time_slot
");
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/styles.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
        }
        
        .bookings-table th, .bookings-table td {
            color: white;
            padding: 5px 8px;
            text-align: left;
            border-bottom: 1px solid var(--primary-color-light);
        }
        
        .bookings-table th {
            background-color: var(--primary-color);
            color: white;
        }
        
        .bookings-table tr:hover {
            background-color: var(--primary-color-light);
        }
        
        .action-buttons a {
            padding: 5px 10px;
            margin-right: 5px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-size: 14px;
        }
        
        .edit-btn {
            background-color: var(--secondary-color);
        }
        
        .delete-btn {
            background-color: #ff6b6b;
        }
        
        .success-message {
            color: #4BB543;
            background-color: rgba(75, 181, 67, 0.2);
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .nav__welcome {
            margin-right: 20px;
            color: var(--white);
            font-weight: bold;
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

    <section class="section__container dashboard-container">
        <h2 class="section__header">ADMIN DASHBOARD</h2>
        <p class="section__subheader">
            Manage all user bookings from this dashboard.
        </p>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <table class="bookings-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Training Type</th>
                    <th>Trainer</th>
                    <th>Date</th>
                    <th>Time Slot</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($booking['fullname']); ?><br>
                            <small><?php echo htmlspecialchars($booking['email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($booking['training_type']); ?></td>
                        <td><?php echo htmlspecialchars($booking['trainer']); ?></td>
                        <td><?php echo date('F j, Y', strtotime($booking['date'])); ?></td>
                        <td><?php echo htmlspecialchars($booking['time_slot']); ?></td>
                        
                        <td><?php echo !empty($booking['notes']) ? htmlspecialchars($booking['notes']) : 'N/A'; ?></td>
                        <td class="action-buttons">
                            <a href="edit_booking.php?id=<?php echo $booking['id']; ?>" class="edit-btn">
                                <i class="ri-edit-line"></i> Edit
                            </a>
                            <a href="admin_dashboard.php?delete_id=<?php echo $booking['id']; ?>" class="delete-btn" 
                               onclick="return confirm('Are you sure you want to delete this booking? This action cannot be undone.');">
                                <i class="ri-delete-bin-line"></i> Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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