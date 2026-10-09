<?php

session_start();
include __DIR__ . "/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST["full_name"]);
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if (
        empty($full_name) ||
        empty($username) ||
        empty($email) ||
        empty($password)
    ) {
        $message = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {

        $check = $conn->prepare(
            "SELECT id FROM users WHERE username = ? OR email = ?"
        );

        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = "Username or email already exists.";

        } else {

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO users
                (full_name, username, email, phone, password)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssss",
                $full_name,
                $username,
                $email,
                $phone,
                $hashed_password
            );

            if ($stmt->execute()) {

                header("Location: login.php?registered=1");
                exit();

            } else {

                $message = "Registration failed. Please try again.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Register | Golden Plate Restaurant</title>

<link rel="stylesheet" href="style.css">

</head>

<body class="auth-body">

<div class="auth-container">

    <div class="restaurant-logo">
        🍽️
    </div>

    <h1>Golden Plate</h1>

    <p class="auth-subtitle">
        Create your customer account
    </p>

    <?php if (!empty($message)): ?>

        <div class="error-message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="form-group">

            <label>Full Name</label>

            <input
                type="text"
                name="full_name"
                placeholder="Enter your full name"
                required
            >

        </div>

        <div class="form-group">

            <label>Username</label>

            <input
                type="text"
                name="username"
                placeholder="Choose a username"
                required
            >

        </div>

        <div class="form-group">

            <label>Email Address</label>

            <input
                type="email"
                name="email"
                placeholder="Enter your email"
                required
            >

        </div>

        <div class="form-group">

            <label>Phone Number</label>

            <input
                type="text"
                name="phone"
                placeholder="Enter your phone number"
            >

        </div>

        <div class="form-group">

            <label>Password</label>

            <input
                type="password"
                name="password"
                placeholder="Create a password"
                required
            >

        </div>

        <div class="form-group">

            <label>Confirm Password</label>

            <input
                type="password"
                name="confirm_password"
                placeholder="Confirm your password"
                required
            >

        </div>

        <button type="submit" class="auth-button">
            Create Account
        </button>

    </form>

    <p class="auth-link">

        Already have an account?

        <a href="login.php">Login here</a>

    </p>

</div>

<script src="app.js" defer></script>
</body>
</html>