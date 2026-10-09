<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . "/database.php";

$columnsResult = $conn->query("SHOW COLUMNS FROM users");
if (!$columnsResult) {
    die("Could not check the user profile fields.");
}

$existingColumns = [];
while ($column = $columnsResult->fetch_assoc()) {
    $existingColumns[$column["Field"]] = true;
}

$profileColumns = [
    "age" => "SMALLINT UNSIGNED NULL",
    "gender" => "VARCHAR(30) NULL",
    "allergies" => "TEXT NULL",
];

foreach ($profileColumns as $columnName => $columnDefinition) {
    if (!isset($existingColumns[$columnName])) {
        if (!$conn->query("ALTER TABLE users ADD COLUMN `$columnName` $columnDefinition")) {
            die("Could not set up the user profile fields. Check database permissions.");
        }
    }
}

$userId = (int) $_SESSION["user_id"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullName = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $ageInput = trim($_POST["age"] ?? "");
    $gender = trim($_POST["gender"] ?? "");
    $allergies = trim($_POST["allergies"] ?? "");
    $age = null;
    $allowedGenders = ["Female", "Male", "Non-binary", "Other", "Prefer not to say"];

    if ($fullName === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Enter your name and a valid email address.";
    } elseif ($ageInput !== "") {
        $validatedAge = filter_var(
            $ageInput,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1, "max_range" => 120]]
        );

        if ($validatedAge === false) {
            $message = "Enter an age between 1 and 120.";
        } else {
            $age = $validatedAge;
        }
    }

    if ($message === "" && $gender !== "" && !in_array($gender, $allowedGenders, true)) {
        $message = "Select a valid gender option.";
    }

    if ($message === "") {
        $emailCheck = $conn->prepare(
            "SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1"
        );
        $emailCheck->bind_param("si", $email, $userId);
        $emailCheck->execute();

        if ($emailCheck->get_result()->num_rows > 0) {
            $message = "That email address is already used by another account.";
        } else {
            $update = $conn->prepare(
                "UPDATE users
                 SET full_name = ?, email = ?, phone = ?, age = ?, gender = ?, allergies = ?
                 WHERE id = ?"
            );
            $update->bind_param(
                "ssssssi",
                $fullName,
                $email,
                $phone,
                $age,
                $gender,
                $allergies,
                $userId
            );

            if ($update->execute()) {
                $_SESSION["full_name"] = $fullName;
                $_SESSION["email"] = $email;
                header("Location: profile.php?updated=1");
                exit();
            }

            $message = "Could not save your profile. Please try again.";
        }
    }
}

$profileQuery = $conn->prepare(
    "SELECT full_name, email, phone, age, gender, allergies FROM users WHERE id = ?"
);
$profileQuery->bind_param("i", $userId);
$profileQuery->execute();
$profileResult = $profileQuery->get_result();

if ($profileResult->num_rows !== 1) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$profile = $profileResult->fetch_assoc();
if ($_SERVER["REQUEST_METHOD"] === "POST" && $message !== "") {
    $profile["full_name"] = $fullName;
    $profile["email"] = $email;
    $profile["phone"] = $phone;
    $profile["age"] = $ageInput;
    $profile["gender"] = $gender;
    $profile["allergies"] = $allergies;
}

function profileValue(array $profile, string $key): string
{
    return htmlspecialchars((string) ($profile[$key] ?? ""), ENT_QUOTES, "UTF-8");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | Golden Plate Restaurant</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <div class="brand">🍽️ <span>Golden Plate</span></div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">🏠 Dashboard</a></li>
            <li><a href="profile.php" aria-current="page">👤 Profile</a></li>
            <li><a href="booking.php">📅 Booking</a></li>
            <li><a href="tables.php">🪑 Tables</a></li>
            <li><a href="menu.php">🍽️ Menu</a></li>
            <li><a href="payment.php">💳 Payment</a></li>
            <li><a href="reviews.php">⭐ Reviews</a></li>
            <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="topbar">
            <h2>Your Profile</h2>
            <div class="user-info">👤 <?php echo profileValue($profile, "full_name"); ?></div>
        </div>

        <section class="welcome">
            <h1>Personal information</h1>
            <p>Keep your contact details and dining preferences up to date.</p>
        </section>

        <section class="profile-content">
            <?php if (isset($_GET["updated"])): ?>
                <div class="success-message">Your profile has been updated.</div>
            <?php endif; ?>

            <?php if ($message !== ""): ?>
                <div class="error-message"><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></div>
            <?php endif; ?>

            <form class="profile-form" method="POST" action="profile.php">
                <div class="form-group">
                    <label for="full_name">Full name</label>
                    <input id="full_name" name="full_name" type="text" autocomplete="name" value="<?php echo profileValue($profile, "full_name"); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" autocomplete="email" value="<?php echo profileValue($profile, "email"); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Contact number</label>
                    <input id="phone" name="phone" type="tel" autocomplete="tel" value="<?php echo profileValue($profile, "phone"); ?>">
                </div>

                <div class="form-group">
                    <label for="age">Age</label>
                    <input id="age" name="age" type="number" min="1" max="120" value="<?php echo profileValue($profile, "age"); ?>">
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">Select an option</option>
                        <?php foreach (["Female", "Male", "Non-binary", "Other", "Prefer not to say"] as $genderOption): ?>
                            <option value="<?php echo htmlspecialchars($genderOption, ENT_QUOTES, "UTF-8"); ?>" <?php echo ($profile["gender"] ?? "") === $genderOption ? "selected" : ""; ?>><?php echo htmlspecialchars($genderOption, ENT_QUOTES, "UTF-8"); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group profile-form-full">
                    <label for="allergies">Food allergies</label>
                    <textarea id="allergies" name="allergies" rows="4" placeholder="List any food allergies, or enter None"><?php echo profileValue($profile, "allergies"); ?></textarea>
                </div>

                <div class="profile-form-actions profile-form-full">
                    <button class="btn btn-primary" type="submit">Save profile</button>
                </div>
            </form>
        </section>
    </main>
</div>
<script src="app.js" defer></script>
</body>
</html>
