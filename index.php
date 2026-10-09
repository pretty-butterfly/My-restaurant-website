<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Golden Plate Restaurant</title>

    <link rel="stylesheet" href="style.css">

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(180deg, #fffdf9 0%, #fff4e7 100%);
            color: #1d2c30;
        }

        /* NAVIGATION */

        .navbar {
            width: 100%;
            padding: 18px 7%;
            background: rgba(17, 45, 41, 0.96);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 10;
            box-shadow: 0 10px 30px rgba(17, 45, 41, 0.12);
        }

        .logo {
            color: #f2c45b;
            font-size: 25px;
            font-weight: bold;
        }

        .logo span {
            color: #fff7f3;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 25px;
        }

        .nav-links a {
            color: rgba(255,255,255,0.88);
            text-decoration: none;
            font-weight: 500;
        }

        .nav-links a:hover {
            color: #ffd3c2;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
        }

        .login-btn,
        .register-btn {
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-weight: bold;
        }

        .login-btn {
            color: white;
            border: 1px solid #d2aa49;
        }

        .register-btn {
            background: linear-gradient(135deg, #d4ac43, #f4d47a);
            color: #1a2d31;
            box-shadow: 0 10px 20px rgba(212, 172, 67, 0.25);
        }

        /* HERO */

        .hero {
            min-height: 650px;
            background:
                linear-gradient(
                    rgba(0,0,0,0.65),
                    rgba(0,0,0,0.65)
                ),
                url("images/restaurant.jpg");

            background-size: cover;
            background-position: center;

            display: flex;
            align-items: center;
            justify-content: center;

            text-align: center;
            color: white;
            padding: 30px;
        }

        .hero-content {
            max-width: 800px;
        }

        .hero h1 {
            font-size: 58px;
            margin-bottom: 20px;
        }

        .hero h1 span {
            color: #f5c76a;
        }

        .hero p {
            font-size: 20px;
            line-height: 1.7;
            margin-bottom: 30px;
            color: rgba(255,255,255,0.96);
            font-weight: 500;
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .hero-btn {
            padding: 15px 28px;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
        }

        .primary-btn {
            background: linear-gradient(135deg, #d7af49, #f6d98d);
            color: #1a2d31;
        }

        .secondary-btn {
            border: 2px solid white;
            color: white;
        }

        /* ABOUT */

        .section {
            padding: 80px 7%;
            text-align: center;
            background: linear-gradient(180deg, rgba(255,255,255,0.72), rgba(255,247,243,0.92));
        }

        .section-title {
            font-size: 35px;
            color: #1a2d31;
            margin-bottom: 15px;
        }

        .section-title span {
            color: #ef6d4b;
        }

        .section-description {
            max-width: 700px;
            margin: auto;
            color: #2f4851;
            line-height: 1.7;
            font-weight: 500;
        }

        /* FEATURES */

        .features {
            margin-top: 45px;
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
        }

        .feature-card {
            background: linear-gradient(180deg, #ffffff 0%, #fff8f5 100%);
            padding: 35px 25px;
            border: 1px solid #f0d7cc;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(20, 41, 37, 0.08);
            transition: 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 18px 36px rgba(20, 41, 37, 0.12);
        }

        .feature-icon {
            font-size: 45px;
            margin-bottom: 15px;
        }

        .feature-card h3 {
            margin-bottom: 10px;
            color: #173c38;
        }

        .feature-card p {
            color: #2f4851;
            line-height: 1.6;
            font-weight: 500;
        }

        /* MENU PREVIEW */

        .menu-section {
            background: #f3f4f6;
        }

        .menu-items {
            margin-top: 45px;
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
        }

        .food-card {
            background: linear-gradient(180deg, #ffffff 0%, #fffaf7 100%);
            border: 1px solid #f0d7cc;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 16px 30px rgba(20, 41, 37, 0.08);
        }

        .food-image {
            height: 180px;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 65px;
        }

        .food-content {
            padding: 20px;
            text-align: left;
        }

        .food-content h3 {
            margin-bottom: 8px;
            color: #163c38;
        }

        .food-content p {
            color: #2f4851;
            font-weight: 500;
        }

        .food-price {
            color: #df683f;
            font-weight: bold;
            font-size: 18px;
        }

        /* CTA */

        .cta {
            background: linear-gradient(135deg, #123d38 0%, #1d4d48 100%);
            color: white;
            text-align: center;
            padding: 75px 20px;
        }

        .cta h2 {
            font-size: 38px;
            margin-bottom: 15px;
            color: #fffaf6;
        }

        .cta p {
            color: rgba(255,255,255,0.82);
            margin-bottom: 25px;
        }

        /* FOOTER */

        footer {
            background: #030712;
            color: #9ca3af;
            padding: 30px 7%;
            text-align: center;
        }

        /* MOBILE */

        @media (max-width: 800px) {

            .navbar {
                flex-wrap: wrap;
                gap: 15px;
            }

            .nav-links {
                display: none;
            }

            .nav-buttons {
                margin-left: auto;
            }

            .hero {
                min-height: 550px;
            }

            .hero h1 {
                font-size: 40px;
            }

            .hero p {
                font-size: 17px;
            }

        }

        @media (max-width: 500px) {

            .nav-buttons {
                width: 100%;
                justify-content: center;
            }

            .hero h1 {
                font-size: 34px;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .hero-btn {
                display: block;
            }

            .section-title {
                font-size: 28px;
            }

        }

    </style>

</head>

<body class="landing-body">

<!-- =========================
     NAVIGATION
========================= -->

<nav class="navbar">

    <div class="logo">
        🍽️ Golden <span>Plate</span>
    </div>

    <ul class="nav-links">

        <li>
            <a href="#home">Home</a>
        </li>

        <li>
            <a href="#about">About</a>
        </li>

        <li>
            <a href="#menu">Menu</a>
        </li>

        <li>
            <a href="#contact">Contact</a>
        </li>

    </ul>

    <div class="nav-buttons">

        <a href="login.php" class="login-btn">
            Login
        </a>

        <a href="register.php" class="register-btn">
            Register
        </a>

    </div>

</nav>


<!-- =========================
     HERO
========================= -->

<section class="hero" id="home">

    <div class="hero-content">

        <h1>
            Welcome to
            <span>Golden Plate</span>
        </h1>

        <p>
            Experience delicious meals, beautiful surroundings
            and exceptional service. Reserve your table today
            and enjoy an unforgettable dining experience.
        </p>

        <div class="hero-buttons">

            <a href="register.php" class="hero-btn primary-btn">Reserve your table</a>
            <a href="#menu" class="hero-btn secondary-btn">Explore the menu</a>

        </div>

    </div>

</section>

<section class="section landing-about" id="about">
    <div class="landing-section-inner">
        <p class="landing-eyebrow">A little more than dinner</p>
        <h2 class="section-title">Good food. <span>Better company.</span></h2>
        <p class="section-description">From an easy lunch to a once-in-a-lifetime celebration, Golden Plate brings thoughtful cooking and warm Ghanaian hospitality to every table.</p>

        <div class="features">
            <article class="feature-card">
                <div class="feature-icon">✦</div>
                <h3>Made for gathering</h3>
                <p>Book a table for two, bring the whole family, or plan a special occasion with our team.</p>
            </article>
            <article class="feature-card">
                <div class="feature-icon">⌂</div>
                <h3>Two places to meet</h3>
                <p>Visit Royale in Spintex or Grande in Ahodwo and find the setting that feels right.</p>
            </article>
            <article class="feature-card">
                <div class="feature-icon">♡</div>
                <h3>Made with care</h3>
                <p>Explore local favorites and familiar classics, prepared with care and served with a smile.</p>
            </article>
        </div>
    </div>
</section>

<section class="section menu-section" id="menu">
    <div class="landing-section-inner">
        <p class="landing-eyebrow">From our kitchen</p>
        <h2 class="section-title">A taste of <span>Golden Plate</span></h2>
        <p class="section-description">A few guest favorites to get you started. Find the full menu after you sign in.</p>

        <div class="menu-items">
            <article class="food-card">
                <img class="food-image" src="https://images.unsplash.com/photo-1473093295043-cdd812d0e601?auto=format&fit=crop&w=800&q=85" alt="Fresh pasta with herbs" loading="lazy">
                <div class="food-content">
                    <p class="landing-eyebrow">Continental</p>
                    <h3>Pasta with sauce and cheese</h3>
                    <p>Comforting, generous, and made for a long lunch.</p>
                </div>
            </article>
            <article class="food-card">
                <img class="food-image" src="https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=800&q=85" alt="Freshly baked pizza" loading="lazy">
                <div class="food-content">
                    <p class="landing-eyebrow">A crowd favorite</p>
                    <h3>Stone-baked pizza</h3>
                    <p>Share a slice, stay for another round.</p>
                </div>
            </article>
            <article class="food-card">
                <img class="food-image" src="https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?auto=format&fit=crop&w=800&q=85" alt="Grilled fish served with fresh sides" loading="lazy">
                <div class="food-content">
                    <p class="landing-eyebrow">Local favorite</p>
                    <h3>Banku with tilapia</h3>
                    <p>A beloved classic, served the Golden Plate way.</p>
                </div>
            </article>
        </div>
    </div>
</section>

<section class="landing-cta" id="contact">
    <div class="landing-cta-content">
        <p class="landing-eyebrow">Your table is waiting</p>
        <h2>Make tonight feel like an occasion.</h2>
        <p>Choose your venue, find your favorite dishes, and let us take care of the rest.</p>
        <div class="hero-buttons">
            <a href="register.php" class="hero-btn primary-btn">Join us</a>
            <a href="login.php" class="hero-btn secondary-btn">Sign in</a>
        </div>
    </div>
</section>

<footer class="landing-footer">
    <span>🍽️ Golden Plate</span>
    <span>Royale · Spintex &nbsp; | &nbsp; Grande · Ahodwo</span>
    <span>&copy; <?php echo date("Y"); ?> Golden Plate Restaurant</span>
</footer>

<script src="app.js" defer></script>
</body>
</html>
