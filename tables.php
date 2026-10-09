<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . "/database.php";

$createReservationsTable = "CREATE TABLE IF NOT EXISTS table_reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    venue_key VARCHAR(20) NOT NULL,
    venue_name VARCHAR(100) NOT NULL,
    location VARCHAR(80) NOT NULL,
    view_key VARCHAR(20) NOT NULL,
    table_type VARCHAR(20) NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    party_size SMALLINT UNSIGNED NOT NULL,
    table_number VARCHAR(30) NOT NULL,
    capacity SMALLINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_table_slot (venue_key, table_number, booking_date, booking_time),
    INDEX reservation_lookup (venue_key, booking_date, booking_time, view_key, table_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($createReservationsTable)) {
    die("Could not set up table reservations. Please check your database permissions.");
}

$venues = [
    "royale" => ["name" => "Golden Plate Restaurant Royale", "location" => "Spintex"],
    "grande" => ["name" => "Golden Plate Restaurant Grande", "location" => "Ahodwo"],
];

$views = [
    "beach" => [
        "name" => "Beach view",
        "description" => "A relaxed dining area overlooking the beach.",
        "image" => "https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1000&q=85",
        "alt" => "Ocean and beach view from the shoreline",
    ],
    "grand_floor" => [
        "name" => "Grand floor",
        "description" => "A spacious restaurant floor with an open view.",
        "image" => "https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1000&q=85",
        "alt" => "Warmly lit restaurant dining room",
    ],
    "top_floor" => [
        "name" => "Top floor",
        "description" => "An open, glass-roof setting for views of the stars at night.",
        "image" => "https://images.unsplash.com/photo-1519608487953-e999c86e7455?auto=format&fit=crop&w=1000&q=85",
        "alt" => "Stars visible in a clear night sky",
    ],
];

$tableInventory = [];
foreach (["beach" => "BV", "grand_floor" => "GF", "top_floor" => "TF"] as $viewKey => $prefix) {
    $tableInventory[$viewKey] = [
        "single" => [
            ["number" => $prefix . "-S01", "capacity" => 1],
            ["number" => $prefix . "-S02", "capacity" => 1],
            ["number" => $prefix . "-S03", "capacity" => 1],
        ],
        "double" => [
            ["number" => $prefix . "-D01", "capacity" => 2],
            ["number" => $prefix . "-D02", "capacity" => 2],
            ["number" => $prefix . "-D03", "capacity" => 2],
        ],
        "group" => [
            ["number" => $prefix . "-G04-01", "capacity" => 4],
            ["number" => $prefix . "-G04-02", "capacity" => 4],
            ["number" => $prefix . "-G06-01", "capacity" => 6],
            ["number" => $prefix . "-G06-02", "capacity" => 6],
            ["number" => $prefix . "-G08-01", "capacity" => 8],
            ["number" => $prefix . "-G08-02", "capacity" => 8],
            ["number" => $prefix . "-G12-01", "capacity" => 12],
            ["number" => $prefix . "-G12-02", "capacity" => 12],
        ],
    ];
}

$confirmation = $_SESSION["table_reservation_confirmation"] ?? null;
unset($_SESSION["table_reservation_confirmation"]);
$message = "";
$venueChoice = "";
$viewChoice = "";
$tableType = "";
$bookingDate = "";
$bookingTime = "";
$partySizeInput = "";

$storedBooking = $_SESSION["last_booking"] ?? [];
if (!empty($storedBooking)) {
    $venueChoice = $venueChoice === "" ? ($storedBooking["venue"] ?? "") : $venueChoice;
    $bookingDate = $bookingDate === "" ? ($storedBooking["booking_date"] ?? "") : $bookingDate;
    $bookingTime = $bookingTime === "" ? ($storedBooking["booking_time"] ?? "") : $bookingTime;
    $partySizeInput = $partySizeInput === "" ? (string) ($storedBooking["party_size"] ?? "") : $partySizeInput;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $venueChoice = $_POST["venue"] ?? "";
    $viewChoice = $_POST["view"] ?? "";
    $tableType = $_POST["table_type"] ?? "";
    $bookingDate = trim($_POST["booking_date"] ?? "");
    $bookingTime = trim($_POST["booking_time"] ?? "");
    $partySizeInput = trim($_POST["party_size"] ?? "");

    $partySize = filter_var(
        $partySizeInput,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1, "max_range" => 12]]
    );
    $parsedDate = DateTimeImmutable::createFromFormat("!Y-m-d", $bookingDate);
    $dateErrors = DateTimeImmutable::getLastErrors();
    $invalidDate = !$parsedDate
        || ($dateErrors !== false && ($dateErrors["warning_count"] > 0 || $dateErrors["error_count"] > 0))
        || $parsedDate->format("Y-m-d") !== $bookingDate
        || $parsedDate < new DateTimeImmutable("today");

    if (!isset($venues[$venueChoice])) {
        $message = "Choose one of the restaurant venues.";
    } elseif (!isset($views[$viewChoice])) {
        $message = "Choose one of the available views.";
    } elseif (!isset($tableInventory[$viewChoice][$tableType])) {
        $message = "Choose a valid table type.";
    } elseif ($invalidDate) {
        $message = "Choose a valid date that is today or later.";
    } elseif (!preg_match("/^(?:[01]\\d|2[0-3]):[0-5]\\d$/", $bookingTime)) {
        $message = "Enter a valid booking time.";
    } elseif ($partySize === false) {
        $message = "Enter a party size between 1 and 12.";
    } elseif (($tableType === "single" && $partySize !== 1)
        || ($tableType === "double" && $partySize !== 2)
        || ($tableType === "group" && $partySize < 3)) {
        $message = "The number of people does not match the selected table type.";
    } else {
        $venue = $venues[$venueChoice];
        $userId = (int) $_SESSION["user_id"];
        $assignedTable = null;

        foreach ($tableInventory[$viewChoice][$tableType] as $table) {
            if ($table["capacity"] < $partySize) {
                continue;
            }

            $availability = $conn->prepare(
                "SELECT id FROM table_reservations
                 WHERE venue_key = ? AND table_number = ? AND booking_date = ? AND booking_time = ?
                   AND status <> 'Cancelled'
                 LIMIT 1"
            );
            $availability->bind_param(
                "ssss",
                $venueChoice,
                $table["number"],
                $bookingDate,
                $bookingTime
            );
            $availability->execute();

            if ($availability->get_result()->num_rows === 0) {
                $assignedTable = $table;
                break;
            }
        }

        if ($assignedTable === null) {
            $message = "No matching tables are available for that time and view. Choose another time or view.";
        } else {
            $viewName = $views[$viewChoice]["name"];
            $assignedNumber = $assignedTable["number"];
            $capacity = $assignedTable["capacity"];
            $insertReservation = $conn->prepare(
                "INSERT INTO table_reservations
                    (user_id, venue_key, venue_name, location, view_key, table_type,
                     booking_date, booking_time, party_size, table_number, capacity)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $insertReservation->bind_param(
                "isssssssisi",
                $userId,
                $venueChoice,
                $venue["name"],
                $venue["location"],
                $viewChoice,
                $tableType,
                $bookingDate,
                $bookingTime,
                $partySize,
                $assignedNumber,
                $capacity
            );

            if ($insertReservation->execute()) {
                $_SESSION["last_booking"] = [
                    "venue" => $venueChoice,
                    "booking_date" => $bookingDate,
                    "booking_time" => $bookingTime,
                    "party_size" => (string) $partySize,
                    "special_booking" => $_SESSION["last_booking"]["special_booking"] ?? "",
                    "special_request" => $_SESSION["last_booking"]["special_request"] ?? "",
                ];

                $_SESSION["table_reservation_confirmation"] = [
                    "number" => $assignedNumber,
                    "view" => $viewName,
                    "venue" => $venue["name"],
                    "location" => $venue["location"],
                    "date" => $bookingDate,
                    "time" => $bookingTime,
                    "party_size" => $partySize,
                ];
                header("Location: tables.php?reserved=1");
                exit();
            }

            $message = $conn->errno === 1062
                ? "That table was just reserved. Please submit again to check another table."
                : "We could not save your table reservation. Please try again.";
        }
    }
}

function tableEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose a Table | Golden Plate Restaurant</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <div class="brand">🍽️ <span>Golden Plate</span></div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">🏠 Dashboard</a></li>
            <li><a href="profile.php">👤 Profile</a></li>
            <li><a href="booking.php">📅 Booking</a></li>
            <li><a href="tables.php" aria-current="page">🪑 Tables</a></li>
            <li><a href="menu.php">🍽️ Menu</a></li>
            <li><a href="payment.php">💳 Payment</a></li>
            <li><a href="reviews.php">⭐ Reviews</a></li>
            <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="topbar">
            <h2>Choose a table</h2>
            <div class="user-info">👤 <?php echo tableEscape($_SESSION["full_name"] ?? "Customer"); ?></div>
        </div>

        <section class="welcome">
            <h1>Find your place</h1>
            <p>Select a restaurant, a view, and the table size that suits your party.</p>
        </section>

        <section class="tables-content">
            <?php if ($confirmation !== null): ?>
                <div class="success-message">
                    Table <?php echo tableEscape($confirmation["number"]); ?> has been reserved for <?php echo tableEscape((string) $confirmation["party_size"]); ?> at <?php echo tableEscape($confirmation["venue"] . " - " . $confirmation["location"]); ?>, <?php echo tableEscape($confirmation["date"] . " at " . substr($confirmation["time"], 0, 5)); ?>. Reservation status: pending.
                </div>
            <?php endif; ?>

            <?php if ($message !== ""): ?>
                <div class="error-message"><?php echo tableEscape($message); ?></div>
            <?php endif; ?>

            <form class="tables-form" method="POST" action="tables.php">
                <div class="booking-section-heading">
                    <h2>Reservation details</h2>
                    <p>Choose the restaurant and time first. We will assign an available numbered table that matches your party.</p>
                </div>

                <div class="booking-grid">
                    <div class="form-group">
                        <label for="venue">Restaurant</label>
                        <select id="venue" name="venue" required>
                            <option value="">Choose a venue</option>
                            <?php foreach ($venues as $venueKey => $venueDetails): ?>
                                <option value="<?php echo tableEscape($venueKey); ?>" <?php echo $venueChoice === $venueKey ? "selected" : ""; ?>><?php echo tableEscape($venueDetails["name"] . " - " . $venueDetails["location"]); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="booking_date">Date</label>
                        <input id="booking_date" name="booking_date" type="date" min="<?php echo date("Y-m-d"); ?>" value="<?php echo tableEscape($bookingDate); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="booking_time">Time</label>
                        <input id="booking_time" name="booking_time" type="time" value="<?php echo tableEscape($bookingTime); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="table_type">Table type</label>
                        <select id="table_type" name="table_type" required>
                            <option value="">Choose a table type</option>
                            <option value="single" <?php echo $tableType === "single" ? "selected" : ""; ?>>Single table (1 person)</option>
                            <option value="double" <?php echo $tableType === "double" ? "selected" : ""; ?>>Double table (2 people)</option>
                            <option value="group" <?php echo $tableType === "group" ? "selected" : ""; ?>>Group table (3-12 people)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="party_size">Number of people</label>
                        <input id="party_size" name="party_size" type="number" min="1" max="12" value="<?php echo tableEscape($partySizeInput); ?>" placeholder="Choose a table type first" required>
                        <small class="booking-note">Group table size determines the capacity and table number assigned.</small>
                    </div>
                </div>

                <div class="booking-section-heading table-view-heading">
                    <h2>Choose your view</h2>
                    <p>Preview each area, then select the one you would like.</p>
                </div>

                <div class="table-view-grid">
                    <?php foreach ($views as $viewKey => $viewDetails): ?>
                        <label class="table-view-option">
                            <input type="radio" name="view" value="<?php echo tableEscape($viewKey); ?>" <?php echo $viewChoice === $viewKey ? "checked" : ""; ?> required>
                            <span class="table-view-card">
                                <img src="<?php echo tableEscape($viewDetails["image"]); ?>" alt="<?php echo tableEscape($viewDetails["alt"]); ?>" loading="lazy">
                                <span class="table-view-copy">
                                    <strong><?php echo tableEscape($viewDetails["name"]); ?></strong>
                                    <span><?php echo tableEscape($viewDetails["description"]); ?></span>
                                </span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="table-assignment-note">Your exact table number is assigned automatically after you submit, based on the selected type, group size, view, and availability.</div>
                <div class="booking-actions">
                    <button class="btn btn-primary" type="submit">Reserve this table</button>
                </div>
            </form>
        </section>
    </main>
</div>
<script src="app.js" defer></script>
</body>
</html>
