<?php
session_start();
require 'connect.php';

// Check kung may naka-login na user
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// pag handle ng form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $training_type = $_POST['training_type'];
    $trainer = $_POST['trainer'];
    $date = $_POST['date'];
    $time_slot = $_POST['time_slot'];
    $notes = $_POST['notes'];

    // Check kung may naka book na ibang account sa parehong date at time slot
    $check_user_stmt = $conn->prepare("SELECT id FROM bookings WHERE user_id = ? AND date = ? AND time_slot = ?");
    $check_user_stmt->bind_param("iss", $user_id, $date, $time_slot);
    $check_user_stmt->execute();
    $check_user_result = $check_user_stmt->get_result();

    if ($check_user_result->num_rows > 0) {
        $error = "You already have a booking at this date and time!";
    } else {
        // Check kung nakuha ang time slot na same trainer
        $check_availability_stmt = $conn->prepare("SELECT id FROM bookings WHERE trainer = ? AND date = ? AND time_slot = ?");
        $check_availability_stmt->bind_param("sss", $trainer, $date, $time_slot);
        $check_availability_stmt->execute();
        $check_availability_result = $check_availability_stmt->get_result();

        if ($check_availability_result->num_rows > 0) {
            $error = "This time slot is already booked with the selected trainer! Please choose a different time or trainer.";
        } else {
            // If all checks pass, proceed with booking
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, training_type, trainer, date, time_slot, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $user_id, $training_type, $trainer, $date, $time_slot, $notes);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Booking successfully created!";
                header("Location: booking.php");
                exit();
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
    }
}

// Fetch user's existing bookings
$bookings = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE user_id = ? ORDER BY date, time_slot");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch all bookings to show availability
$all_bookings = [];
$stmt = $conn->prepare("SELECT trainer, date, time_slot FROM bookings");
$stmt->execute();
$result = $stmt->get_result();
$all_bookings = $result->fetch_all(MYSQLI_ASSOC);

// booked slots data para sa Javascript
$booked_slots = [];
foreach ($all_bookings as $booking) {
    $key = $booking['trainer'] . '|' . $booking['date'] . '|' . $booking['time_slot'];
    $booked_slots[$key] = true;
}
$booked_slots_json = json_encode($booked_slots);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="styles/styles.css" />
    <title>Fitclub - Book a Session</title>
    <style>
        .booking-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .booking-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .booking-form {
            background-color: var(--primary-color-light);
            padding: 2rem;
            border-radius: 10px;
        }
        
        .booking-list {
            background-color: var(--primary-color-light);
            color: white;
            padding: 2rem;
            border-radius: 10px;
        }
        
        .booking-card {
            background-color: var(--primary-color-extra-light);
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--secondary-color);
        }
        
        .booking-card h4 {
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .booking-card p {
            margin-bottom: 0.3rem;
            color: var(--text-light);
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
        
        .success-message {
            color: #4BB543;
            background-color: rgba(75, 181, 67, 0.2);
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .error-message {
            color: #ff3333;
            background-color: rgba(255, 51, 51, 0.2);
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .time-slot-option {
            display: flex;
            justify-content: space-between;
        }
        
        .time-slot-option.unavailable {
            color: #ff6b6b;
            text-decoration: line-through;
        }
        
        option:disabled {
            color: #ff6b6b;
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
            <span class="nav__welcome">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <button class="btn" onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </nav>

    <section class="section__container booking-container">
        <h2 class="section__header">BOOK YOUR SESSION</h2>
        <p class="section__subheader">
            Schedule your personal training session with our expert trainers.
        </p>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="booking-grid">
            <div class="booking-form">
                <form method="POST" action="">
                    <select name="training_type" required>
                        <option value="" disabled selected>Select Training Type</option>
                        <option value="Personal Training">Personal Training</option>
                        <option value="Strength Training">Strength Training</option>
                        <option value="Cardio">Cardio</option>
                        <option value="Yoga">Yoga</option>
                        <option value="Boxing">Boxing</option>
                    </select>
                    
                    <select name="trainer" id="trainer-select" required>
                        <option value="" disabled selected>Select Trainer</option>
                        <option value="Self Train">Self Train</option>
                        <option value="Coach Sherwin">Coach Sherwin</option>
                        <option value="Coach Charlene">Coach Charlene</option>
                        <option value="Coach Patrice">Coach Patrice</option>
                        <option value="Coach Kirsten">Coach Kirsten</option>
                    </select>
                    
                    <input type="date" name="date" id="booking-date" min="<?php echo date('Y-m-d'); ?>" required>
                    
                    <select name="time_slot" id="time-slot-select" required>
                        <option value="" disabled selected>Select Time Slot</option>
                        <option value="7:00am - 08:00am">07:00am - 08:00am</option>
                        <option value="8:00am - 9:00am">08:00am - 09:00am</option>
                        <option value="10:00am - 11:00am">10:00am - 11:00am</option>
                        <option value="2:00pm - 3:00pm">2:00pm - 3:00pm</option>
                        <option value="3:00pm - 4:00pm">3:00pm - 4:00pm</option>
                        <option value="4:00pm - 5:00pm">4:00pm - 5:00pm</option>
                    </select>
                    
                    <textarea name="notes" placeholder="Any special requests or notes..."></textarea>
                    
                    <button type="submit" class="btn">Book Now</button>
                </form>
            </div>
            
            <div class="booking-list">
                <h3>Your Upcoming Sessions</h3>
                
                <?php if (empty($bookings)): ?>
                    <p>You don't have any upcoming sessions.</p>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <div class="booking-card">
                            <h4><?php echo htmlspecialchars($booking['training_type']); ?></h4>
                            <p><strong>Trainer:</strong> <?php echo htmlspecialchars($booking['trainer']); ?></p>
                            <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking['date'])); ?></p>
                            <p><strong>Time:</strong> <?php echo htmlspecialchars($booking['time_slot']); ?></p>
                            <?php if (!empty($booking['notes'])): ?>
                                <p><strong>Notes:</strong> <?php echo htmlspecialchars($booking['notes']); ?></p>
                            <?php endif; ?>
                            <form method="POST" action="cancel_booking.php" style="margin-top: 10px;">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                <button type="submit" class="btn" style="background-color: #ff6b6b; padding: 5px 10px; font-size: 12px;">Cancel Booking</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('booking-date');
            const trainerSelect = document.getElementById('trainer-select');
            const timeSlotSelect = document.getElementById('time-slot-select');
            
            // Parse the booked slots from PHP to JavaScript
            const bookedSlots = <?php echo $booked_slots_json; ?>;
            
            // Function to update time slot availability
            function updateTimeSlotAvailability() {
                const selectedTrainer = trainerSelect.value;
                const selectedDate = dateInput.value;
                
                // Enable all options first
                for (let i = 0; i < timeSlotSelect.options.length; i++) {
                    const option = timeSlotSelect.options[i];
                    if (option.value) {
                        option.disabled = false;
                        option.classList.remove('unavailable');
                    }
                }
                
                // If both trainer & date are selected, disable booked slots
                if (selectedTrainer && selectedDate) {
                    for (let i = 0; i < timeSlotSelect.options.length; i++) {
                        const option = timeSlotSelect.options[i];
                        if (option.value) {
                            const key = selectedTrainer + '|' + selectedDate + '|' + option.value;
                            if (bookedSlots[key]) {
                                option.disabled = true;
                                option.classList.add('unavailable');
                            }
                        }
                    }
                }
            }
            
            // Add event listeners
            trainerSelect.addEventListener('change', updateTimeSlotAvailability);
            dateInput.addEventListener('change', updateTimeSlotAvailability);
            
            // Start update
            updateTimeSlotAvailability();
        });
    </script>
</body>
</html>