<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . "/database.php";

$createBookingsTable = "CREATE TABLE IF NOT EXISTS bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    venue VARCHAR(100) NOT NULL,
    location VARCHAR(80) NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    party_size SMALLINT UNSIGNED NOT NULL,
    special_booking VARCHAR(60) NULL,
    special_request TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($createBookingsTable)) {
    die("Could not set up bookings. Please check your database permissions.");
}

$venues = [
    "royale" => [
        "venue" => "Golden Plate Restaurant Royale",
        "location" => "Spintex",
    ],
    "grande" => [
        "venue" => "Golden Plate Restaurant Grande",
        "location" => "Ahodwo",
    ],
];

$eventOptions = [
    "Wedding",
    "Graduation party",
    "Coming-of-age party",
    "Birthday",
    "Funeral",
];

$message = "";
$venueChoice = "";
$bookingDate = "";
$bookingTime = "";
$partySizeInput = "";
$specialBookingInput = "";
$specialRequest = "";

$storedBooking = $_SESSION["last_booking"] ?? [];
if (!empty($storedBooking)) {
    $venueChoice = $venueChoice === "" ? ($storedBooking["venue"] ?? "") : $venueChoice;
    $bookingDate = $bookingDate === "" ? ($storedBooking["booking_date"] ?? "") : $bookingDate;
    $bookingTime = $bookingTime === "" ? ($storedBooking["booking_time"] ?? "") : $bookingTime;
    $partySizeInput = $partySizeInput === "" ? (string) ($storedBooking["party_size"] ?? "") : $partySizeInput;
    $specialBookingInput = $specialBookingInput === "" ? ($storedBooking["special_booking"] ?? "") : $specialBookingInput;
    $specialRequest = $specialRequest === "" ? ($storedBooking["special_request"] ?? "") : $specialRequest;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $venueChoice = $_POST["venue"] ?? "";
    $bookingDate = trim($_POST["booking_date"] ?? "");
    $bookingTime = trim($_POST["booking_time"] ?? "");
    $partySizeInput = trim($_POST["party_size"] ?? "");
    $specialBookingInput = $_POST["special_booking"] ?? "";
    $specialRequest = trim($_POST["special_request"] ?? "");

    $partySize = filter_var(
        $partySizeInput,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1, "max_range" => 200]]
    );
    $parsedDate = DateTimeImmutable::createFromFormat("!Y-m-d", $bookingDate);
    $dateErrors = DateTimeImmutable::getLastErrors();
    $invalidDate = !$parsedDate
        || ($dateErrors !== false && ($dateErrors["warning_count"] > 0 || $dateErrors["error_count"] > 0))
        || $parsedDate->format("Y-m-d") !== $bookingDate
        || $parsedDate < new DateTimeImmutable("today");

    if (!isset($venues[$venueChoice])) {
        $message = "Choose one of the restaurant venues.";
    } elseif ($invalidDate) {
        $message = "Choose a valid date that is today or later.";
    } elseif (!preg_match("/^(?:[01]\\d|2[0-3]):[0-5]\\d$/", $bookingTime)) {
        $message = "Enter a valid booking time.";
    } elseif ($partySize === false) {
        $message = "Enter a number of people between 1 and 200.";
    } elseif ($specialBookingInput !== "" && !in_array($specialBookingInput, $eventOptions, true)) {
        $message = "Choose a valid special booking type.";
    } elseif (strlen($specialRequest) > 3000) {
        $message = "Keep your special request under 3,000 characters.";
    } else {
        $venue = $venues[$venueChoice]["venue"];
        $location = $venues[$venueChoice]["location"];
        $specialBooking = $specialBookingInput === "" ? null : $specialBookingInput;
        $savedRequest = $specialRequest === "" ? null : $specialRequest;
        $userId = (int) $_SESSION["user_id"];

        $insertBooking = $conn->prepare(
            "INSERT INTO bookings
                (user_id, venue, location, booking_date, booking_time, party_size, special_booking, special_request)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $insertBooking->bind_param(
            "isssisss",
            $userId,
            $venue,
            $location,
            $bookingDate,
            $bookingTime,
            $partySize,
            $specialBooking,
            $savedRequest
        );

        if ($insertBooking->execute()) {
            $_SESSION["last_booking"] = [
                "venue" => $venueChoice,
                "booking_date" => $bookingDate,
                "booking_time" => $bookingTime,
                "party_size" => (string) $partySize,
                "special_booking" => $specialBookingInput,
                "special_request" => $specialRequest,
            ];

            header("Location: booking.php?submitted=1");
            exit();
        }

        $message = "We could not submit your booking. Please try again.";
    }
}

function bookingEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking | Golden Plate Restaurant</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <div class="brand">🍽️ <span>Golden Plate</span></div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">🏠 Dashboard</a></li>
            <li><a href="profile.php">👤 Profile</a></li>
            <li><a href="booking.php" aria-current="page">📅 Booking</a></li>
            <li><a href="tables.php">🪑 Tables</a></li>
            <li><a href="menu.php">🍽️ Menu</a></li>
            <li><a href="payment.php">💳 Payment</a></li>
            <li><a href="reviews.php">⭐ Reviews</a></li>
            <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="topbar">
            <h2>Restaurant Booking</h2>
            <div class="user-info">👤 <?php echo htmlspecialchars($_SESSION["full_name"] ?? "Customer", ENT_QUOTES, "UTF-8"); ?></div>
        </div>

        <section class="welcome">
            <h1>Make a booking</h1>
            <p>Choose a venue and tell us about your visit or special event.</p>
        </section>

        <section class="booking-content">
            <?php if (isset($_GET["submitted"])): ?>
                <div class="success-message">Your booking request has been submitted. The restaurant will confirm availability and any additional charges.</div>
            <?php endif; ?>

            <?php if ($message !== ""): ?>
                <div class="error-message"><?php echo bookingEscape($message); ?></div>
            <?php endif; ?>

            <form class="booking-form" method="POST" action="booking.php">
                <div class="booking-section-heading">
                    <h2>Visit details</h2>
                    <p>All fields in this section are required.</p>
                </div>

                <div class="booking-grid">
                    <div class="form-group">
                        <label for="venue">Restaurant venue</label>
                        <select id="venue" name="venue" required>
                            <option value="">Choose a venue</option>
                            <?php foreach ($venues as $venueKey => $venueDetails): ?>
                                <option value="<?php echo bookingEscape($venueKey); ?>" <?php echo $venueChoice === $venueKey ? "selected" : ""; ?>><?php echo bookingEscape($venueDetails["venue"] . " - " . $venueDetails["location"]); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="booking_date">Date</label>
                        <input id="booking_date" name="booking_date" type="date" min="<?php echo date("Y-m-d"); ?>" value="<?php echo bookingEscape($bookingDate); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="booking_time">Time</label>
                        <input id="booking_time" name="booking_time" type="time" value="<?php echo bookingEscape($bookingTime); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="party_size">Number of people</label>
                        <input id="party_size" name="party_size" type="number" min="1" max="200" value="<?php echo bookingEscape($partySizeInput); ?>" placeholder="For example, 4" required>
                    </div>
                </div>

                <div class="booking-section-heading booking-event-heading">
                    <h2>Special booking <span>(optional venue rental)</span></h2>
                    <p>Select an event type if you are enquiring about a special occasion or rental.</p>
                </div>

                <div class="form-group">
                    <label for="special_booking">Event type</label>
                    <select id="special_booking" name="special_booking">
                        <option value="">Regular restaurant booking</option>
                        <?php foreach ($eventOptions as $eventOption): ?>
                            <option value="<?php echo bookingEscape($eventOption); ?>" <?php echo $specialBookingInput === $eventOption ? "selected" : ""; ?>><?php echo bookingEscape($eventOption); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="booking-section-heading booking-event-heading">
                    <h2>Special request <span>(optional)</span></h2>
                    <p>Tell us about arrangements you would like us to consider.</p>
                </div>

                <div class="form-group">
                    <label for="special_request">Request details</label>
                    <textarea id="special_request" name="special_request" rows="5" maxlength="3000" placeholder="For example: please bring a cake and sing for a birthday celebrant, or decorate an area for a wedding proposal."><?php echo bookingEscape($specialRequest); ?></textarea>
                    <small class="booking-note">Cake service, singing, proposal decorations, event rentals, and other special arrangements may have additional charges. Our team will confirm availability and charges with you.</small>
                </div>

                <div class="booking-actions">
                    <button class="btn btn-primary" type="submit">Submit booking request</button>
                </div>
            </form>
        </section>
    </main>
</div>
<script src="app.js" defer></script>
</body>
</html>
