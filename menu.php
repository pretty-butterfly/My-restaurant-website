<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . "/database.php";
$menuPrices = require __DIR__ . "/menu_prices.php";

$createOrdersTable = "CREATE TABLE IF NOT EXISTS menu_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    occasion VARCHAR(60) NULL,
    special_request TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX menu_order_user (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$createOrderItemsTable = "CREATE TABLE IF NOT EXISTS menu_order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    item_key VARCHAR(60) NOT NULL,
    category VARCHAR(80) NOT NULL,
    item_name VARCHAR(120) NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    INDEX menu_order_items_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($createOrdersTable) || !$conn->query($createOrderItemsTable)) {
    die("Could not set up menu orders. Please check your database permissions.");
}

$itemColumns = $conn->query("SHOW COLUMNS FROM menu_order_items");
$hasUnitPrice = false;
while ($column = $itemColumns->fetch_assoc()) {
    if ($column["Field"] === "unit_price") {
        $hasUnitPrice = true;
        break;
    }
}
if (!$hasUnitPrice && !$conn->query("ALTER TABLE menu_order_items ADD COLUMN unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER quantity")) {
    die("Could not add menu prices. Please check your database permissions.");
}

$menuSections = [
    [
        "title" => "Breakfast",
        "items" => [
            ["key" => "millet_porridge", "name" => "Millet porridge with fried bean cakes", "image" => "photo-1517673132405-a56a62b18caf"],
            ["key" => "chai_tea", "name" => "Chai tea", "image" => "photo-1576092768241-dec231879fc3"],
            ["key" => "croissants", "name" => "Croissants", "image" => "photo-1555507036-ab1f4038808a"],
            ["key" => "rice_pudding", "name" => "Rice pudding", "image" => "photo-1571877227200-a0d98ea607e9"],
            ["key" => "doughnuts", "name" => "Doughnuts", "image" => "photo-1551024506-0bccd828d307"],
        ],
    ],
    [
        "title" => "Lunch",
        "items" => [
            ["key" => "burger_fries", "name" => "Burger with fries", "image" => "photo-1568901346375-23c9450c58cd"],
            ["key" => "lunch_pizza", "name" => "Pizza", "image" => "photo-1513104890138-7c749659a591"],
            ["key" => "assorted_sandwich", "name" => "Assorted sandwich", "image" => "photo-1528735602780-2552fd46c7af"],
            ["key" => "plantain_yam", "name" => "Fried plantain and yam", "image" => "photo-1547592180-85f173990554"],
        ],
    ],
    [
        "title" => "Dinner",
        "subsections" => [
            [
                "title" => "Continental meals",
                "items" => [
                    ["key" => "pasta_sauce_cheese", "name" => "Pasta with sauce and cheese", "image" => "photo-1473093295043-cdd812d0e601"],
                    ["key" => "chinese_stir_fry_noodles", "name" => "Chinese stir-fry noodles", "image" => "photo-1569718212165-3a8278d5f624"],
                    ["key" => "texas_steak", "name" => "Texas-style steak with sauce", "image" => "photo-1546833999-b9f581a1996d"],
                    ["key" => "dinner_pizza", "name" => "Pizza", "image" => "photo-1571407970349-bc81e7e96d47"],
                    ["key" => "fried_rice_chicken", "name" => "Fried rice with chicken", "image" => "photo-1603133872878-684f208fb84b"],
                    ["key" => "continental_salad", "name" => "Salad", "image" => "photo-1512621776951-a57141f2eefd"],
                ],
            ],
            [
                "title" => "Local meals",
                "items" => [
                    ["key" => "yam_egg_stew", "name" => "Boiled yam with egg stew", "image" => "photo-1547592180-85f173990554"],
                    ["key" => "banku_okro", "name" => "Banku and okro stew", "image" => "photo-1559847844-5315695dadae"],
                    ["key" => "banku_tilapia", "name" => "Banku with tilapia", "image" => "photo-1519708227418-c8fd9a32b7a2"],
                    ["key" => "fufu_goat_soup", "name" => "Fufu and goat soup", "image" => "photo-1547592180-85f173990554"],
                    ["key" => "fufu_palm_nut_soup", "name" => "Fufu with palm nut soup", "image" => "photo-1547592180-85f173990554"],
                    ["key" => "ghanaian_salad", "name" => "Ghanaian-style salad", "image" => "photo-1546069901-ba9599a7e63c"],
                ],
            ],
        ],
    ],
    [
        "title" => "Dessert",
        "items" => [
            ["key" => "ice_cream", "name" => "Ice cream", "image" => "photo-1563805042-7684c019e1cb"],
            ["key" => "pie", "name" => "Pie", "image" => "photo-1562007908-17c67e878c7a"],
            ["key" => "tiramisu", "name" => "Tiramisu", "image" => "photo-1571877227200-a0d98ea607e9"],
            ["key" => "cake", "name" => "Cake", "image" => "photo-1578985545062-69928b1d9587"],
        ],
    ],
    [
        "title" => "Drinks",
        "items" => [
            ["key" => "wine", "name" => "Wine", "image" => "photo-1510812431401-41d2bd2722f3"],
            ["key" => "tequila", "name" => "Tequila", "image" => "photo-1514362545857-3bc16c4c7d1b"],
            ["key" => "orange_juice", "name" => "Orange juice", "image" => "photo-1600271886742-f049cd451bba"],
            ["key" => "mango_juice", "name" => "Mango juice", "image" => "photo-1622597467836-f3285f2131b8"],
            ["key" => "pineapple_juice", "name" => "Pineapple juice", "image" => "photo-1579954115545-a95591f28bfc"],
            ["key" => "mixed_fruit_juice", "name" => "Mixed fruit juice", "image" => "photo-1546173159-315724a31696"],
            ["key" => "whiskey", "name" => "Whiskey", "image" => "photo-1513558161293-cdaf765edfd4"],
            ["key" => "scotch", "name" => "Scotch", "image" => "photo-1514362545857-3bc16c4c7d1b"],
        ],
    ],
];

$menuItems = [];
foreach ($menuSections as $section) {
    if (isset($section["subsections"])) {
        foreach ($section["subsections"] as $subsection) {
            foreach ($subsection["items"] as $item) {
                $item["category"] = $section["title"] . " - " . $subsection["title"];
                $menuItems[$item["key"]] = $item;
            }
        }
    } else {
        foreach ($section["items"] as $item) {
            $item["category"] = $section["title"];
            $menuItems[$item["key"]] = $item;
        }
    }
}

$occasionOptions = ["Christmas", "Birthday", "Wedding", "Anniversary", "Graduation", "Other"];
$quantities = array_fill_keys(array_keys($menuItems), 0);
$occasion = "";
$specialRequest = "";
$message = "";
$confirmation = $_SESSION["menu_order_confirmation"] ?? null;
unset($_SESSION["menu_order_confirmation"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedQuantities = $_POST["quantities"] ?? [];
    $occasion = $_POST["occasion"] ?? "";
    $specialRequest = trim($_POST["special_request"] ?? "");
    $selectedItems = [];

    if (!is_array($postedQuantities)) {
        $postedQuantities = [];
    }

    foreach ($menuItems as $itemKey => $item) {
        $quantityInput = $postedQuantities[$itemKey] ?? "0";
        $quantity = filter_var(
            $quantityInput,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 0, "max_range" => 20]]
        );

        if ($quantity === false) {
            $message = "Enter a quantity from 0 to 20 for each selected item.";
            break;
        }

        $quantities[$itemKey] = $quantity;
        if ($quantity > 0) {
            $selectedItems[$itemKey] = ["item" => $item, "quantity" => $quantity];
        }
    }

    if ($message === "" && $occasion !== "" && !in_array($occasion, $occasionOptions, true)) {
        $message = "Choose a valid occasion.";
    } elseif ($message === "" && strlen($specialRequest) > 2000) {
        $message = "Keep your special request under 2,000 characters.";
    } elseif ($message === "" && count($selectedItems) === 0) {
        $message = "Choose at least one menu item before placing your order.";
    }

    if ($message === "") {
        $savedOccasion = $occasion === "" ? null : $occasion;
        $savedRequest = $specialRequest === "" ? null : $specialRequest;
        $userId = (int) $_SESSION["user_id"];
        $itemCount = array_sum(array_column($selectedItems, "quantity"));

        try {
            $conn->begin_transaction();
            $insertOrder = $conn->prepare(
                "INSERT INTO menu_orders (user_id, occasion, special_request) VALUES (?, ?, ?)"
            );
            $insertOrder->bind_param("iss", $userId, $savedOccasion, $savedRequest);
            $insertOrder->execute();
            $orderId = $conn->insert_id;

            $insertItem = $conn->prepare(
                "INSERT INTO menu_order_items (order_id, item_key, category, item_name, quantity, unit_price)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            foreach ($selectedItems as $itemKey => $selection) {
                $item = $selection["item"];
                $quantity = $selection["quantity"];
                $unitPrice = $menuPrices[$itemKey];
                $insertItem->bind_param(
                    "isssid",
                    $orderId,
                    $itemKey,
                    $item["category"],
                    $item["name"],
                    $quantity,
                    $unitPrice
                );
                $insertItem->execute();
            }

            $conn->commit();
            $_SESSION["menu_order_confirmation"] = [
                "order_id" => $orderId,
                "item_count" => $itemCount,
                "occasion" => $occasion,
            ];
            header("Location: menu.php?submitted=1");
            exit();
        } catch (Throwable $error) {
            $conn->rollback();
            $message = "We could not submit your order. Please try again.";
        }
    }
}

function menuEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

function renderMenuItems(array $items, array $quantities, array $menuPrices): void
{
    foreach ($items as $item):
        $key = $item["key"];
        $imageUrl = "https://images.unsplash.com/" . $item["image"] . "?auto=format&fit=crop&w=500&h=360&q=80";
        ?>
        <article class="menu-item">
            <img src="<?php echo menuEscape($imageUrl); ?>" alt="<?php echo menuEscape($item["name"]); ?>" loading="lazy">
            <div class="menu-item-details">
                <h3><?php echo menuEscape($item["name"]); ?></h3>
                <p class="menu-item-price">GHS <?php echo number_format($menuPrices[$key], 2); ?></p>
                <label for="quantity-<?php echo menuEscape($key); ?>">Quantity</label>
                <input id="quantity-<?php echo menuEscape($key); ?>" name="quantities[<?php echo menuEscape($key); ?>]" type="number" min="0" max="20" step="1" value="<?php echo (int) ($quantities[$key] ?? 0); ?>" aria-label="Quantity for <?php echo menuEscape($item["name"]); ?>">
            </div>
        </article>
        <?php
    endforeach;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu | Golden Plate Restaurant</title>
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
            <li><a href="menu.php" aria-current="page">🍽️ Menu</a></li>
            <li><a href="payment.php">💳 Payment</a></li>
            <li><a href="reviews.php">⭐ Reviews</a></li>
            <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="topbar">
            <h2>Restaurant Menu</h2>
            <div class="user-info">👤 <?php echo menuEscape($_SESSION["full_name"] ?? "Customer"); ?></div>
        </div>

        <section class="welcome">
            <h1>Explore the menu</h1>
            <p>Choose quantities for any of your favorite meals and drinks.</p>
        </section>

        <section class="menu-content">
            <?php if ($confirmation !== null): ?>
                <div class="success-message">Order #<?php echo (int) $confirmation["order_id"]; ?> placed with <?php echo (int) $confirmation["item_count"]; ?> item(s)<?php echo $confirmation["occasion"] !== "" ? " for " . menuEscape($confirmation["occasion"]) : ""; ?>. The restaurant will confirm your order. <a href="payment.php?order_id=<?php echo (int) $confirmation["order_id"]; ?>">Continue to demo checkout</a></div>
            <?php endif; ?>

            <?php if ($message !== ""): ?>
                <div class="error-message"><?php echo menuEscape($message); ?></div>
            <?php endif; ?>

            <form class="menu-order-form" method="POST" action="menu.php">
                <?php foreach ($menuSections as $section): ?>
                    <section class="menu-section">
                        <div class="menu-section-heading">
                            <h2><?php echo menuEscape($section["title"]); ?></h2>
                        </div>

                        <?php if (isset($section["subsections"])): ?>
                            <?php foreach ($section["subsections"] as $subsection): ?>
                                <div class="menu-subsection">
                                    <h3><?php echo menuEscape($subsection["title"]); ?></h3>
                                    <div class="menu-item-grid">
                                        <?php renderMenuItems($subsection["items"], $quantities, $menuPrices); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="menu-item-grid">
                                <?php renderMenuItems($section["items"], $quantities, $menuPrices); ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>

                <section class="menu-occasion-section">
                    <div class="menu-section-heading">
                        <h2>Special occasion</h2>
                        <p>Let us know if your order is for a celebration.</p>
                    </div>

                    <div class="menu-occasion-fields">
                        <div class="form-group">
                            <label for="occasion">Occasion</label>
                            <select id="occasion" name="occasion">
                                <option value="">No special occasion</option>
                                <?php foreach ($occasionOptions as $occasionOption): ?>
                                    <option value="<?php echo menuEscape($occasionOption); ?>" <?php echo $occasion === $occasionOption ? "selected" : ""; ?>><?php echo menuEscape($occasionOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="special_request">Special request</label>
                            <textarea id="special_request" name="special_request" rows="4" maxlength="2000" placeholder="Add celebration details or any requests for the kitchen."><?php echo menuEscape($specialRequest); ?></textarea>
                        </div>
                    </div>
                </section>

                <div class="menu-order-actions">
                    <p>Choose at least one item. Quantities can be set from 0 to 20. Prices are illustrative GHS demo prices.</p>
                    <button class="btn btn-primary" type="submit">Place order</button>
                </div>
            </form>
        </section>
    </main>
</div>
<script src="app.js" defer></script>
</body>
</html>
