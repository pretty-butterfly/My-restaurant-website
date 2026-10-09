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
$createDemoPaymentsTable = "CREATE TABLE IF NOT EXISTS demo_menu_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method VARCHAR(30) NOT NULL,
    mobile_network VARCHAR(20) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Demo successful',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_demo_payment_per_order (order_id),
    INDEX demo_payment_user (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($createOrdersTable)
    || !$conn->query($createOrderItemsTable)
    || !$conn->query($createDemoPaymentsTable)) {
    die("Could not set up demo payments. Please check your database permissions.");
}

$itemColumns = $conn->query("SHOW COLUMNS FROM menu_order_items");
if (!$itemColumns) {
    die("Could not check menu prices. Please check your database permissions.");
}
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

$methods = [
    "mastercard" => "Mastercard",
    "visa" => "Visa",
    "mobile_money" => "Mobile Money",
    "paypal" => "PayPal",
    "venmo" => "Venmo",
];
$networks = ["MTN", "Telecel"];
$userId = (int) $_SESSION["user_id"];
$orderQuery = $conn->prepare(
    "SELECT orders.id, orders.created_at
     FROM menu_orders AS orders
     LEFT JOIN demo_menu_payments AS payments
       ON payments.order_id = orders.id AND payments.status = 'Demo successful'
     WHERE orders.user_id = ? AND payments.id IS NULL
     ORDER BY orders.created_at DESC"
);
$orderQuery->bind_param("i", $userId);
$orderQuery->execute();
$orderResult = $orderQuery->get_result();
$orders = [];

while ($order = $orderResult->fetch_assoc()) {
    $orderId = (int) $order["id"];
    $itemsQuery = $conn->prepare(
        "SELECT item_key, item_name, quantity, unit_price FROM menu_order_items WHERE order_id = ?"
    );
    $itemsQuery->bind_param("i", $orderId);
    $itemsQuery->execute();
    $itemsResult = $itemsQuery->get_result();
    $order["total"] = 0.0;
    $order["item_count"] = 0;
    $itemNames = [];

    while ($item = $itemsResult->fetch_assoc()) {
        $quantity = (int) $item["quantity"];
        $unitPrice = (float) $item["unit_price"];
        if ($unitPrice <= 0) {
            $unitPrice = (float) ($menuPrices[$item["item_key"]] ?? 0);
        }
        $order["total"] += $unitPrice * $quantity;
        $order["item_count"] += $quantity;
        $itemNames[] = $item["item_name"] . " x" . $quantity;
    }

    if ($order["item_count"] > 0) {
        $order["items"] = implode(", ", $itemNames);
        $orders[$orderId] = $order;
    }
}

$message = "";
$methodChoice = "";
$networkChoice = "";
$orderChoice = filter_input(INPUT_GET, "order_id", FILTER_VALIDATE_INT) ?: 0;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $orderChoice = filter_var($_POST["order_id"] ?? "", FILTER_VALIDATE_INT) ?: 0;
    $methodChoice = $_POST["method"] ?? "";
    $networkChoice = $_POST["mobile_network"] ?? "";

    if (!isset($orders[$orderChoice])) {
        $message = "Choose one of your unpaid menu orders.";
    } elseif (!isset($methods[$methodChoice])) {
        $message = "Choose a payment option.";
    } elseif ($methodChoice === "mobile_money" && !in_array($networkChoice, $networks, true)) {
        $message = "Choose MTN or Telecel for the demo mobile money payment.";
    } elseif (($_POST["demo_confirm"] ?? "") !== "yes") {
        $message = "Confirm that you understand this is a demo and no money will be moved.";
    } else {
        $selectedOrder = $orders[$orderChoice];
        $amount = $selectedOrder["total"];
        $mobileNetwork = $methodChoice === "mobile_money" ? $networkChoice : null;
        $status = "Demo successful";
        $insertPayment = $conn->prepare(
            "INSERT INTO demo_menu_payments (user_id, order_id, amount, method, mobile_network, status)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $insertPayment->bind_param(
            "iidsss",
            $userId,
            $orderChoice,
            $amount,
            $methodChoice,
            $mobileNetwork,
            $status
        );

        if ($insertPayment->execute()) {
            $_SESSION["demo_payment_confirmation"] = [
                "order_id" => $orderChoice,
                "amount" => number_format($amount, 2),
                "method" => $methods[$methodChoice],
                "network" => $mobileNetwork,
            ];
            header("Location: payment.php?completed=1");
            exit();
        }

        $message = $conn->errno === 1062
            ? "This order already has a demo payment recorded."
            : "The demo payment could not be recorded. Please try again.";
    }
}

$confirmation = $_SESSION["demo_payment_confirmation"] ?? null;
unset($_SESSION["demo_payment_confirmation"]);

function paymentEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Checkout | Golden Plate Restaurant</title>
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
            <li><a href="payment.php" aria-current="page">💳 Payment</a></li>
            <li><a href="reviews.php">⭐ Reviews</a></li>
            <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="topbar">
            <h2>Checkout</h2>
            <div class="user-info">👤 <?php echo paymentEscape($_SESSION["full_name"] ?? "Customer"); ?></div>
        </div>

        <section class="welcome">
            <h1>Payment options</h1>
            <p>Choose an unpaid menu order and a preferred payment method.</p>
        </section>

        <section class="payment-content">
            <div class="payment-demo-warning">
                <strong>Demo checkout only</strong>
                <span>No real payment will be made. Never enter or share a real card number, account number, or payment PIN here. A live checkout must be completed through a payment provider.</span>
            </div>

            <?php if ($confirmation !== null): ?>
                <div class="payment-success" role="status">
                    <span class="payment-smile" aria-hidden="true">☺</span>
                    <h2>Thank you!</h2>
                    <p>Demo payment for order #<?php echo (int) $confirmation["order_id"]; ?> was successful.</p>
                    <p>Amount: GHS <?php echo paymentEscape($confirmation["amount"]); ?> via <?php echo paymentEscape($confirmation["method"] . ($confirmation["network"] !== null ? " (" . $confirmation["network"] . ")" : "")); ?>.</p>
                    <p class="payment-success-note">This confirmation is simulated. No money was moved.</p>
                </div>
            <?php elseif (count($orders) === 0): ?>
                <div class="payment-empty-state">
                    <h2>No unpaid menu orders</h2>
                    <p>Place a menu order first to see its demo total here.</p>
                    <a class="btn btn-primary" href="menu.php">Browse the menu</a>
                </div>
            <?php else: ?>
                <?php if ($message !== ""): ?>
                    <div class="error-message"><?php echo paymentEscape($message); ?></div>
                <?php endif; ?>

                <form class="payment-form" method="POST" action="payment.php">
                    <div class="form-group">
                        <label for="order_id">Menu order</label>
                        <select id="order_id" name="order_id" required>
                            <option value="">Choose an unpaid order</option>
                            <?php foreach ($orders as $orderId => $order): ?>
                                <option value="<?php echo (int) $orderId; ?>" <?php echo (int) $orderChoice === (int) $orderId ? "selected" : ""; ?>>Order #<?php echo (int) $orderId; ?> · <?php echo (int) $order["item_count"]; ?> items · GHS <?php echo number_format($order["total"], 2); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="payment-order-summary" class="payment-order-summary" aria-live="polite"></div>

                    <fieldset class="payment-methods">
                        <legend>Select a payment option</legend>
                        <label class="payment-method-option">
                            <input type="radio" name="method" value="mastercard" <?php echo $methodChoice === "mastercard" ? "checked" : ""; ?> required>
                            <span>Mastercard</span>
                        </label>
                        <label class="payment-method-option">
                            <input type="radio" name="method" value="visa" <?php echo $methodChoice === "visa" ? "checked" : ""; ?> required>
                            <span>Visa</span>
                        </label>
                        <label class="payment-method-option">
                            <input type="radio" name="method" value="mobile_money" <?php echo $methodChoice === "mobile_money" ? "checked" : ""; ?> required>
                            <span>Mobile Money</span>
                        </label>
                        <label class="payment-method-option">
                            <input type="radio" name="method" value="paypal" <?php echo $methodChoice === "paypal" ? "checked" : ""; ?> required>
                            <span>PayPal</span>
                        </label>
                        <label class="payment-method-option">
                            <input type="radio" name="method" value="venmo" <?php echo $methodChoice === "venmo" ? "checked" : ""; ?> required>
                            <span>Venmo</span>
                        </label>
                    </fieldset>

                    <div id="mobile-network-group" class="form-group" <?php echo $methodChoice !== "mobile_money" ? "hidden" : ""; ?>>
                        <label for="mobile_network">Mobile money network</label>
                        <select id="mobile_network" name="mobile_network">
                            <option value="">Choose a network</option>
                            <?php foreach ($networks as $network): ?>
                                <option value="<?php echo paymentEscape($network); ?>" <?php echo $networkChoice === $network ? "selected" : ""; ?>><?php echo paymentEscape($network); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="payment-demo-confirm">
                        <label>
                            <input type="checkbox" name="demo_confirm" value="yes" required>
                            <span>I understand this is a demonstration only and no money will be charged.</span>
                        </label>
                    </div>

                    <div class="booking-actions">
                        <button class="btn btn-primary" type="submit">Confirm demo payment</button>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </main>
</div>
<script>
    const paymentForm = document.querySelector(".payment-form");

    if (paymentForm) {
        const orderSelect = paymentForm.querySelector("#order_id");
        const orderSummary = paymentForm.querySelector("#payment-order-summary");
        const networkGroup = paymentForm.querySelector("#mobile-network-group");
        const networkSelect = paymentForm.querySelector("#mobile_network");
        const orderDetails = <?php echo json_encode(array_map(static function ($order) {
            return [
                "items" => $order["items"],
                "total" => number_format($order["total"], 2),
            ];
        }, $orders), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        function updatePaymentForm() {
            const details = orderDetails[orderSelect.value];
            orderSummary.textContent = details
                ? details.items + " · Total: GHS " + details.total
                : "Select an order to view its items and total.";

            const selectedMethod = paymentForm.querySelector('input[name="method"]:checked');
            const isMobileMoney = selectedMethod && selectedMethod.value === "mobile_money";
            networkGroup.hidden = !isMobileMoney;
            networkSelect.required = Boolean(isMobileMoney);
        }

        orderSelect.addEventListener("change", updatePaymentForm);
        paymentForm.querySelectorAll('input[name="method"]').forEach((method) => {
            method.addEventListener("change", updatePaymentForm);
        });
        updatePaymentForm();
    }
</script>
<script src="app.js" defer></script>
</body>
</html>
