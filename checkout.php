<?php
session_start();

if (empty($_SESSION['cart'])) {
    header("Location: products.php");
    exit;
}

$subtotal = 0;

foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$taxRate = 0.05;
$taxAmount = $subtotal * $taxRate;
$deliveryCharge = 99;
$grandTotal = $subtotal + $taxAmount + $deliveryCharge;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $_SESSION['checkout'] = [
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone']),
        'address' => trim($_POST['address']),
        'city' => trim($_POST['city']),
        'state' => trim($_POST['state']),
        'pincode' => trim($_POST['pincode']),
        'landmark' => trim($_POST['landmark'])
    ];

    header("Location: payment.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">

<title>Checkout | COCORA Premium Chocolates</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: "Cormorant Garamond", serif;
    background: #080402;
    color: #eadfc5;
    min-height: 100vh;
}

body::before {
    content: "";
    position: fixed;
    inset: 0;
    background:
        radial-gradient(circle at 20% 20%, rgba(184,134,11,0.08), transparent 30%),
        radial-gradient(circle at 80% 80%, rgba(184,134,11,0.06), transparent 30%);
    pointer-events: none;
}

.navbar {
    height: 96px;
    border-bottom: 1px solid rgba(212,175,55,0.25);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 6%;
    position: relative;
    z-index: 10;
}

.logo-area {
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo-area img {
    width: 48px;
    height: 48px;
    object-fit: contain;
}

.logo-text {
    font-size: 25px;
    letter-spacing: 4px;
    color: #d4af37;
}

.nav-links {
    display: flex;
    gap: 46px;
}

.nav-links a {
    color: #ead8a0;
    text-decoration: none;
    font-size: 20px;
    transition: 0.3s;
}

.nav-links a:hover {
    color: #d4af37;
}

.login-btn {
    color: #d4af37;
    text-decoration: none;
    border: 1px solid #d4af37;
    padding: 12px 22px;
    transition: 0.3s;
}

.login-btn:hover {
    background: #d4af37;
    color: #080402;
}

.page {
    width: 90%;
    max-width: 1200px;
    margin: 60px auto;
    animation: fadeUp 0.8s ease;
}

.heading {
    text-align: center;
    margin-bottom: 45px;
}

.heading small {
    display: block;
    letter-spacing: 5px;
    font-size: 14px;
    color: #b99538;
    margin-bottom: 12px;
}

.heading h1 {
    font-size: 42px;
    font-weight: normal;
    letter-spacing: 3px;
}

.heading p {
    margin-top: 12px;
    color: #cbb98a;
    font-family: "Cormorant Garamond", serif;
    font-size: 14px;
}

.checkout-grid {
    display: grid;
    grid-template-columns: 1.5fr 0.8fr;
    gap: 35px;
}

.box {
    background: rgba(48, 24, 10, 0.82);
    border: 1px solid rgba(212,175,55,0.28);
    padding: 35px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.25);
}

.box h2 {
    font-size: 22px;
    font-weight: normal;
    color: #d4af37;
    margin-bottom: 28px;
    letter-spacing: 1px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    font-family: "Cormorant Garamond", serif;
    font-size: 12px;
    color: #cdbb91;
    margin-bottom: 8px;
    letter-spacing: 0.5px;
}

input,
textarea {
    width: 100%;
    padding: 14px 15px;
    background: #100805;
    border: 1px solid rgba(212,175,55,0.25);
    color: #f4e4b1;
    outline: none;
    font-family: "Cormorant Garamond", serif;
    font-size: 14px;
    transition: 0.3s;
}

textarea {
    resize: vertical;
    min-height: 90px;
}

input:focus,
textarea:focus {
    border-color: #d4af37;
    box-shadow: 0 0 12px rgba(212,175,55,0.1);
}

.summary-item {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid rgba(212,175,55,0.12);
    font-family: "Cormorant Garamond", serif;
    font-size: 13px;
    color: #d7c89f;
}

.summary-item span:last-child {
    color: #eadb9e;
}

.total {
    display: flex;
    justify-content: space-between;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid rgba(212,175,55,0.4);
    font-size: 21px;
    color: #d4af37;
}

.continue-btn {
    width: 100%;
    margin-top: 30px;
    padding: 16px;
    border: none;
    background: linear-gradient(135deg, #d4af37, #b88a18);
    color: #241208;
    font-family: Georgia, serif;
    font-size: 15px;
    letter-spacing: 1px;
    cursor: pointer;
    transition: 0.3s;
}

.continue-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(212,175,55,0.2);
}

.secure {
    text-align: center;
    margin-top: 18px;
    font-family: "Cormorant Garamond", serif;
    color: #988665;
    font-size: 11px;
}

footer {
    text-align: center;
    padding: 40px 20px;
    margin-top: 70px;
    border-top: 1px solid rgba(212,175,55,0.15);
    color: #897957;
    font-family: "Cormorant Garamond", serif;
    font-size: 12px;
}

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(25px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 850px) {

    .navbar {
        height: auto;
        padding: 20px;
        flex-wrap: wrap;
        gap: 18px;
    }

    .nav-links {
        order: 3;
        width: 100%;
        justify-content: center;
        gap: 20px;
    }

    .checkout-grid {
        grid-template-columns: 1fr;
    }

    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .heading h1 {
        font-size: 32px;
    }
}

@media (max-width: 500px) {

    .page {
        width: 94%;
        margin: 35px auto;
    }

    .box {
        padding: 22px;
    }

    .logo-text {
        font-size: 19px;
    }

    .nav-links a {
        font-size: 13px;
    }
}


/* COCORA entrance animation */
.page > * {
    opacity: 0;
    filter: blur(10px);
    transform: translateY(18px);
    animation: cocoraReveal 0.8s cubic-bezier(.22,.61,.36,1) forwards;
}
.page > *:nth-child(1) { animation-delay: .08s; }
.page > *:nth-child(2) { animation-delay: .18s; }
.checkout-grid > .box:first-child { animation-delay: .28s; }
.checkout-grid > .box:last-child { animation-delay: .40s; }
.checkout-grid .form-group,
.checkout-grid .form-row,
.summary-item,
.total,
.continue-btn,
.secure {
    opacity: 0;
    filter: blur(8px);
    animation: cocoraReveal .65s ease forwards;
}
.checkout-grid .form-row:nth-child(1) { animation-delay: .34s; }
.checkout-grid .form-group:nth-child(2) { animation-delay: .40s; }
.checkout-grid .form-group:nth-child(3) { animation-delay: .46s; }
.checkout-grid .form-row:nth-child(4) { animation-delay: .52s; }
.checkout-grid .form-row:nth-child(5) { animation-delay: .58s; }
.summary-item { animation-delay: .48s; }
.summary-item:nth-of-type(2) { animation-delay: .54s; }
.summary-item:nth-of-type(3) { animation-delay: .60s; }
.summary-item:nth-of-type(4) { animation-delay: .66s; }
.total { animation-delay: .72s; }
.continue-btn { animation-delay: .78s; }
.secure { animation-delay: .84s; }
@keyframes cocoraReveal {
    from { opacity: 0; filter: blur(10px); transform: translateY(18px); }
    to { opacity: 1; filter: blur(0); transform: translateY(0); }
}

</style>
</head>

<body>

<nav class="navbar">

    <div class="logo-area">
        <img src="assets/logo/logo.png" alt="COCORA">
        <div class="logo-text">COCORA</div>
    </div>

    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="products.php">Products</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
    </div>

    <a href="login.php" class="login-btn">Login</a>

</nav>

<main class="page">

    <div class="heading">
        <small>COCORA PREMIUM CHOCOLATES</small>
        <h1>Checkout</h1>
        <p>Complete your details to continue with your order.</p>
    </div>

    <form method="POST">

        <div class="checkout-grid">

            <div class="box">

                <h2>Customer & Delivery Details</h2>

                <div class="form-row">

                    <div class="form-group">
                        <label>FULL NAME *</label>
                        <input type="text" name="name" required>
                    </div>

                    <div class="form-group">
                        <label>PHONE NUMBER *</label>
                        <input type="tel" name="phone" required>
                    </div>

                </div>

                <div class="form-group">
                    <label>EMAIL ADDRESS *</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>FULL ADDRESS *</label>
                    <textarea name="address" required></textarea>
                </div>

                <div class="form-row">

                    <div class="form-group">
                        <label>CITY *</label>
                        <input type="text" name="city" required>
                    </div>

                    <div class="form-group">
                        <label>STATE *</label>
                        <input type="text" name="state" required>
                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">
                        <label>PINCODE *</label>
                        <input type="text" name="pincode" maxlength="6" required>
                    </div>

                    <div class="form-group">
                        <label>LANDMARK</label>
                        <input type="text" name="landmark">
                    </div>

                </div>

            </div>

            <div class="box">

                <h2>Order Summary</h2>

                <?php foreach ($_SESSION['cart'] as $item): ?>

                    <div class="summary-item">
                        <span>
                            <?= htmlspecialchars($item['name']) ?>
                            × <?= (int)$item['quantity'] ?>
                        </span>

                        <span>
                            ₹<?= number_format($item['price'] * $item['quantity'], 2) ?>
                        </span>
                    </div>

                <?php endforeach; ?>

                <div class="summary-item">
                    <span>Subtotal</span>
                    <span>₹<?= number_format($subtotal, 2) ?></span>
                </div>

                <div class="summary-item">
                    <span>GST (5%)</span>
                    <span>₹<?= number_format($taxAmount, 2) ?></span>
                </div>

                <div class="summary-item">
                    <span>Delivery</span>
                    <span>₹<?= number_format($deliveryCharge, 2) ?></span>
                </div>

                <div class="total">
                    <span>Total</span>
                    <span>₹<?= number_format($grandTotal, 2) ?></span>
                </div>

                <button type="submit" class="continue-btn">
                    CONTINUE TO PAYMENT
                </button>

                <div class="secure">
                    Secure checkout • COCORA Premium Chocolates
                </div>

            </div>

        </div>

    </form>

</main>

<footer>
    © <?= date('Y') ?> COCORA Premium Chocolates. All Rights Reserved.
</footer>

</body>
</html>