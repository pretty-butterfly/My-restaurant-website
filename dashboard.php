<?php

session_start();

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit();

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Dashboard | Golden Plate Restaurant</title>

<link rel="stylesheet" href="style.css">

</head>

<body>

<div class="dashboard">

    <aside class="sidebar">

        <div class="brand">

            🍽️

            <span>Golden Plate</span>

        </div>

        <ul class="sidebar-menu">

            <li>
                <a href="dashboard.php">
                    🏠 Dashboard
                </a>
            </li>

            <li>
                <a href="profile.php">
                    👤 Profile
                </a>
            </li>

            <li>
                <a href="booking.php">
                    📅 Booking
                </a>
            </li>

            <li>
                <a href="tables.php">
                    🪑 Tables
                </a>
            </li>

            <li>
                <a href="menu.php">
                    🍽️ Menu
                </a>
            </li>

            <li>
                <a href="payment.php">
                    💳 Payment
                </a>
            </li>

            <li>
                <a href="reviews.php">
                    ⭐ Reviews
                </a>
            </li>

            <li>
                <a href="logout.php">
                    🚪 Logout
                </a>
            </li>

        </ul>

    </aside>

    <main class="dashboard-content">

        <div class="topbar">

            <h2>Dashboard</h2>

            <div class="user-info">

                👤

                <?php
                echo htmlspecialchars($_SESSION["full_name"]);
                ?>

            </div>

        </div>

        <section class="welcome">

            <h1>
                Welcome,
                <?php
                echo htmlspecialchars($_SESSION["full_name"]);
                ?>!
            </h1>

            <p>
                Welcome to your Golden Plate Restaurant customer dashboard.
            </p>

        </section>

        <div class="dashboard-cards">

            <div class="dashboard-card">

                <span>📅</span>

                <h3>Reservations</h3>

                <p>
                    Manage your restaurant reservations.
                </p>

                <a href="booking.php">
                    View Booking
                </a>

            </div>

            <div class="dashboard-card">

                <span>🪑</span>

                <h3>Tables</h3>

                <p>
                    Check available restaurant tables.
                </p>

                <a href="tables.php">
                    View Tables
                </a>

            </div>

            <div class="dashboard-card">

                <span>🍽️</span>

                <h3>Our Menu</h3>

                <p>
                    Explore our delicious menu.
                </p>

                <a href="menu.php">
                    View Menu
                </a>

            </div>

            <div class="dashboard-card">

                <span>💳</span>

                <h3>Payments</h3>

                <p>
                    View your payment information.
                </p>

                <a href="payment.php">
                    Payments
                </a>

            </div>

        </div>

    </main>

</div>

<script src="app.js" defer></script>
</body>
</html>