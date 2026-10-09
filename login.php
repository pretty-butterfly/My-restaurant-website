<?php

session_start();
include __DIR__ . "/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $login = trim($_POST["login"]);
    $password = $_POST["password"];

    if (empty($login) || empty($password)) {

        $message = "Please enter your username/email and password.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, full_name, username, email, password
             FROM users
             WHERE username = ? OR email = ?"
        );

        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["email"] = $user["email"];

                header("Location: dashboard.php");
                exit();

            } else {

                $message = "Incorrect password.";

            }

        } else {

            $message = "Account not found.";

        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login | Golden Plate Restaurant</title>

<link rel="stylesheet" href="style.css">

</head>

<body class="auth-body">

<div class="auth-container">

    <div class="restaurant-logo">
        🍽️
    </div>

    <h1>Golden Plate</h1>

    <p class="auth-subtitle">
        Customer Login
    </p>

    <?php if (isset($_GET["registered"])): ?>

        <div class="success-message">
            Registration successful! Please login.
        </div>

    <?php endif; ?>

    <?php if (!empty($message)): ?>

        <div class="error-message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="form-group">

            <label>Username or Email</label>

            <input
                type="text"
                name="login"
                placeholder="Enter username or email"
                required
            >

        </div>

        <div class="form-group">

            <label>Password</label>

            <input
                type="password"
                name="password"
                placeholder="Enter your password"
                required
            >

        </div>

        <button type="submit" class="auth-button">
            Login
        </button>

    </form>

    <p class="auth-link">

        Don't have an account?

        <a href="register.php">Create an account</a>

    </p>

</div>

<script src="app.js" defer></script>
</body>
</html>