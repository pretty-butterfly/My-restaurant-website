<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . "/database.php";

$createReviewsTable = "CREATE TABLE IF NOT EXISTS customer_reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    satisfaction VARCHAR(20) NOT NULL,
    reason TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX customer_review_user (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($createReviewsTable)) {
    die("Could not set up customer reviews. Please check your database permissions.");
}

$satisfaction = "";
$reason = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $satisfaction = $_POST["satisfaction"] ?? "";
    $reason = trim($_POST["reason"] ?? "");

    if (!in_array($satisfaction, ["satisfied", "not_satisfied"], true)) {
        $message = "Choose whether you were satisfied with your experience.";
    } elseif ($satisfaction === "not_satisfied" && $reason === "") {
        $message = "Please tell us what you did not like so we can improve.";
    } elseif (strlen($reason) > 3000) {
        $message = "Keep your feedback under 3,000 characters.";
    } else {
        $userId = (int) $_SESSION["user_id"];
        $savedReason = $reason === "" ? null : $reason;
        $insertReview = $conn->prepare(
            "INSERT INTO customer_reviews (user_id, satisfaction, reason) VALUES (?, ?, ?)"
        );
        $insertReview->bind_param("iss", $userId, $satisfaction, $savedReason);

        if ($insertReview->execute()) {
            header("Location: reviews.php?submitted=1");
            exit();
        }

        $message = "We could not save your review. Please try again.";
    }
}

function reviewEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews | Golden Plate Restaurant</title>
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
            <li><a href="tables.php">🪑 Tables</a></li>
            <li><a href="menu.php">🍽️ Menu</a></li>
            <li><a href="payment.php">💳 Payment</a></li>
            <li><a href="reviews.php" aria-current="page">⭐ Reviews</a></li>
            <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="topbar">
            <h2>Guest Reviews</h2>
            <div class="user-info">👤 <?php echo reviewEscape($_SESSION["full_name"] ?? "Customer"); ?></div>
        </div>

        <section class="welcome">
            <h1>How was your experience?</h1>
            <p>Your feedback helps us make every visit better.</p>
        </section>

        <section class="review-content">
            <?php if (isset($_GET["submitted"])): ?>
                <div class="review-thank-you" role="status">
                    <span class="review-heart" aria-hidden="true">♥</span>
                    <p>Thank you. Please come again soon</p>
                </div>
            <?php else: ?>
                <?php if ($message !== ""): ?>
                    <div class="error-message"><?php echo reviewEscape($message); ?></div>
                <?php endif; ?>

                <form class="review-form" method="POST" action="reviews.php">
                    <fieldset class="review-choice-group">
                        <legend>Were you satisfied with your experience?</legend>
                        <label class="review-choice">
                            <input type="radio" name="satisfaction" value="satisfied" <?php echo $satisfaction === "satisfied" ? "checked" : ""; ?> required>
                            <span>Satisfied</span>
                        </label>
                        <label class="review-choice">
                            <input type="radio" name="satisfaction" value="not_satisfied" <?php echo $satisfaction === "not_satisfied" ? "checked" : ""; ?> required>
                            <span>Not satisfied</span>
                        </label>
                    </fieldset>

                    <div class="form-group review-reason" <?php echo $satisfaction !== "not_satisfied" ? "hidden" : ""; ?>>
                        <label for="reason">What did you not like?</label>
                        <textarea id="reason" name="reason" rows="5" maxlength="3000" placeholder="Tell us what happened and how we can improve."><?php echo reviewEscape($reason); ?></textarea>
                    </div>

                    <div class="review-actions">
                        <button class="btn btn-primary" type="submit">Send review</button>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </main>
</div>
<script>
    const reviewOptions = document.querySelectorAll('input[name="satisfaction"]');
    const reasonSection = document.querySelector(".review-reason");
    const reasonField = document.querySelector("#reason");

    function updateReviewReason() {
        const selectedOption = document.querySelector('input[name="satisfaction"]:checked');
        const needsReason = selectedOption && selectedOption.value === "not_satisfied";
        reasonSection.hidden = !needsReason;
        reasonField.required = Boolean(needsReason);
    }

    reviewOptions.forEach((option) => option.addEventListener("change", updateReviewReason));
    updateReviewReason();
</script>
<script src="app.js" defer></script>
</body>
</html>
