<?php
session_start();

/* =========================================================
   COCORA PREMIUM CHOCOLATES
   CART PAGE
========================================================= */

$cart = $_SESSION['cart'] ?? [];


/* =========================================================
   UPDATE QUANTITY
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $productId = $_POST['product_id'] ?? '';
    $action    = $_POST['action'] ?? '';

    if ($productId !== '' && isset($_SESSION['cart'][$productId])) {

        if ($action === 'plus') {
            $_SESSION['cart'][$productId]['quantity']++;
        }

        if ($action === 'minus') {

            $_SESSION['cart'][$productId]['quantity']--;

            if ($_SESSION['cart'][$productId]['quantity'] <= 0) {
                unset($_SESSION['cart'][$productId]);
            }
        }
    }

    header("Location: cart.php");
    exit;
}


/* =========================================================
   REMOVE PRODUCT
========================================================= */

if (isset($_GET['remove'])) {

    $removeId = $_GET['remove'];

    if (isset($_SESSION['cart'][$removeId])) {
        unset($_SESSION['cart'][$removeId]);
    }

    header("Location: cart.php");
    exit;
}


/* =========================================================
   CART CALCULATION
========================================================= */

$cart = $_SESSION['cart'] ?? [];

$subtotal   = 0;
$totalItems = 0;

foreach ($cart as $item) {

    $quantity = max(
        1,
        (int)($item['quantity'] ?? 1)
    );

    $price = (float)(
        $item['price'] ?? 0
    );

    $subtotal += $price * $quantity;

    $totalItems += $quantity;
}


/* =========================================================
   TAX + DELIVERY
========================================================= */

/*
   Demo GST:
   5% of product subtotal

   Delivery:
   ₹99

   These values can be changed later.
*/

$taxRate = 0.05;

$taxAmount = $subtotal * $taxRate;

$deliveryCharge = $subtotal > 0 ? 99 : 0;

$grandTotal =
    $subtotal
    + $taxAmount
    + $deliveryCharge;

$isLoggedIn = isset($_SESSION['customer_id']);

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&display=swap" rel="stylesheet">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Your Cart | COCORA Premium Chocolates</title>

<style>
*{margin:0;padding:0;box-sizing:border-box}
html,body{min-height:100%;}
body{
 font-family:'Cormorant Garamond',Georgia,serif;
 background:#080402;
 color:#ead7ad;
 overflow-x:hidden;
}

body::before{
 content:"";position:fixed;inset:0;background:#080402;z-index:9999;pointer-events:none;
 animation:pageReveal 1.05s ease forwards;
}
@keyframes pageReveal{0%{opacity:1}100%{opacity:0;visibility:hidden}}

.navbar{
 height:88px;display:flex;align-items:center;justify-content:center;
 padding:0 5.5%;background:rgba(8,4,2,.94);
 border-bottom:1px solid rgba(212,175,55,.18);backdrop-filter:blur(12px);
 position:sticky;top:0;z-index:1000;
 animation:cocoraFadeBlur .8s ease both;
}
.navbar nav{display:flex;align-items:center;justify-content:center}
.nav-links{display:flex;gap:42px;align-items:center;list-style:none}
.nav-links a{
 color:#ead7ad;text-decoration:none;font-size:21px;font-weight:500;
 letter-spacing:1px;transition:.3s ease;position:relative;
}
.nav-links a:hover,.nav-links a.active{color:#D4AF37}
.nav-links a.active{border-bottom:1px solid #D4AF37;padding-bottom:6px}
.login-link{
 position:absolute;right:5.5%;min-width:118px;text-align:center;
 padding:10px 18px;border:1px solid #D4AF37;color:#D4AF37;
 text-decoration:none;font-size:21px;font-weight:500;letter-spacing:1px;
 transition:.3s ease;
}
.login-link:hover{background:#D4AF37;color:#080402}

.cart-header{text-align:center;padding:82px 20px 70px}
.cart-header>*{animation:cocoraFadeBlur .9s ease both}
.cart-label{
 color:#b99a63;font-size:16px;letter-spacing:5px;text-transform:uppercase;margin-bottom:18px;
 animation-delay:.15s!important;
}
.cart-title{
 color:#D4AF37;font-size:clamp(52px,7vw,78px);font-weight:500;letter-spacing:3px;line-height:1.05;
 animation-delay:.3s!important;
}
.cart-subtitle{
 max-width:720px;margin:24px auto 0;color:#d5c09a;font-size:21px;line-height:1.7;
 animation-delay:.45s!important;
}
.header-line{width:65px;height:1px;background:#D4AF37;margin:30px auto 0;animation-delay:.6s!important}

.cart-container{
 width:min(1120px,88%);margin:0 auto;padding:0 0 110px;
 display:grid;grid-template-columns:1.05fr .95fr;gap:55px;align-items:start;
}
.cart-items{
 background:rgba(25,11,5,.62);border:1px solid rgba(212,175,55,.25);
 backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
 padding:8px 35px;
 animation:cocoraFadeBlur .9s .75s ease both;
}
.cart-item{
 display:grid;grid-template-columns:125px minmax(0,1fr) auto;gap:25px;align-items:center;
 padding:25px 0;border-bottom:1px solid rgba(212,175,55,.15);
 opacity:0;animation:cocoraFadeBlur .8s ease both;
}
.cart-item:last-child{border-bottom:none}
.cart-item:nth-child(1){animation-delay:.9s}.cart-item:nth-child(2){animation-delay:1.05s}.cart-item:nth-child(3){animation-delay:1.2s}.cart-item:nth-child(4){animation-delay:1.35s}.cart-item:nth-child(5){animation-delay:1.5s}
.cart-image{width:125px;height:125px;object-fit:cover;display:block;border:1px solid rgba(212,175,55,.2);transition:transform .5s ease,border-color .5s ease}
.cart-item:hover .cart-image{transform:scale(1.035);border-color:rgba(212,175,55,.55)}
.item-name{color:#D4AF37;font-size:22px;font-weight:500;letter-spacing:1.2px}
.item-price{margin-top:9px;color:#c7ae85;font-size:16px;letter-spacing:1px}
.quantity-control{display:inline-flex;align-items:center;margin-top:18px;border:1px solid rgba(212,175,55,.35)}
.quantity-button{width:35px;height:33px;border:none;background:transparent;color:#D4AF37;font-family:'Cormorant Garamond',Georgia,serif;font-size:20px;cursor:pointer;transition:.3s ease}
.quantity-button:hover{background:#D4AF37;color:#080402}
.quantity-number{width:38px;text-align:center;color:#D4AF37;font-size:16px}
.item-right{min-width:115px;text-align:right}
.item-total{color:#D4AF37;font-size:19px;letter-spacing:1px}
.remove-link{display:inline-block;margin-top:13px;color:#a7957c;text-decoration:none;text-transform:uppercase;font-size:11px;letter-spacing:1.5px;transition:.3s ease}
.remove-link:hover{color:#D4AF37}

.summary{
 position:sticky;top:110px;padding:38px 35px;background:rgba(25,11,5,.62);
 border:1px solid rgba(212,175,55,.25);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
 animation:cocoraFadeBlur .9s 1.05s ease both;
}
.summary-title{color:#D4AF37;font-size:32px;font-weight:500;letter-spacing:1px;margin-bottom:25px}
.summary-row{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:14px 0;border-bottom:1px solid rgba(212,175,55,.12);color:#c7ae85;font-size:17px;letter-spacing:.7px}
.summary-row strong{color:#D4AF37;font-weight:500;text-align:right}
.tax-note{margin-top:13px;color:#8f795d;font-size:12px;line-height:1.6;letter-spacing:.4px}
.summary-total{display:flex;align-items:center;justify-content:space-between;margin-top:23px;padding-top:20px;border-top:1px solid rgba(212,175,55,.28);color:#D4AF37;font-size:22px;letter-spacing:1px}
.summary-total strong{font-weight:500;color:#D4AF37}
.summary-buttons{display:flex;flex-direction:column;gap:10px;margin-top:28px}
.cart-button{width:100%;min-height:50px;display:flex;align-items:center;justify-content:center;text-decoration:none;text-transform:uppercase;font-size:12px;letter-spacing:2px;border:1px solid #D4AF37;color:#D4AF37;background:transparent;transition:.35s ease}
.cart-button.primary{background:#D4AF37;color:#080402;border-color:#D4AF37}
.cart-button:hover{background:#D4AF37;color:#080402;transform:translateY(-2px)}
.cart-button.primary:hover{background:transparent;color:#D4AF37}

.empty-cart{
 width:min(700px,88%);margin:0 auto 130px;padding:85px 30px;text-align:center;
 background:rgba(25,11,5,.62);border:1px solid rgba(212,175,55,.25);
 backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
 animation:cocoraFadeBlur .9s .75s ease both;
}
.empty-symbol{color:rgba(212,175,55,.65);font-size:32px;margin-bottom:25px;animation:cocoraFadeBlur .8s .9s ease both}
.empty-title{color:#D4AF37;font-size:34px;font-weight:500;letter-spacing:2px;animation:cocoraFadeBlur .8s 1.05s ease both}
.empty-text{max-width:520px;margin:17px auto 0;color:#c7ae85;font-size:19px;line-height:1.85;animation:cocoraFadeBlur .8s 1.2s ease both}
.empty-button{display:inline-flex;align-items:center;justify-content:center;margin-top:30px;height:48px;padding:0 30px;border:1px solid #D4AF37;color:#D4AF37;text-decoration:none;font-size:12px;letter-spacing:2px;text-transform:uppercase;transition:.35s ease;animation:cocoraFadeBlur .8s 1.35s ease both}
.empty-button:hover{background:#D4AF37;color:#080402;transform:translateY(-2px)}


footer{border-top:1px solid rgba(212,175,55,.15);text-align:center;padding:30px 20px;color:#8f795d;font-size:15px;letter-spacing:1px;animation:cocoraFadeBlur .8s 1.45s ease both}
footer p{color:#8f795d;font-size:15px;letter-spacing:1px;text-transform:none}

@keyframes cocoraFadeBlur{0%{opacity:0;filter:blur(16px);transform:translateY(20px)}100%{opacity:1;filter:blur(0);transform:translateY(0)}}

@media(max-width:900px){
 .cart-container{grid-template-columns:1fr;gap:25px;width:min(760px,90%)}
 .summary{position:static}
}
@media(max-width:850px){
 .navbar{height:auto;min-height:88px;padding:15px 5%;flex-wrap:wrap;gap:15px}
 .nav-links{width:100%;order:2;justify-content:center;gap:22px}
 .nav-links a{font-size:17px}
 .login-link{right:5%;font-size:17px;min-width:95px;padding:8px 12px}
 .cart-header{padding-top:55px}
}
@media(max-width:650px){
 .nav-links{gap:15px;overflow-x:auto;justify-content:flex-start;padding:0 2px}
 .nav-links a{font-size:15px;white-space:nowrap}
 .login-link{font-size:15px;min-width:82px}
 .cart-title{font-size:48px}
 .cart-subtitle{font-size:18px}
 .cart-container{width:90%}
 .cart-items{padding:5px 20px}
 .cart-item{grid-template-columns:88px minmax(0,1fr);gap:16px}
 .cart-image{width:88px;height:88px}
 .item-name{font-size:19px}
 .item-price{font-size:14px}
 .item-right{grid-column:2;text-align:left;min-width:0;margin-top:-6px}
 .item-total{font-size:17px}
 .summary{padding:30px 22px}
}
@media(max-width:500px){
 .nav-links{gap:12px}
 .nav-links a{font-size:14px}
 .login-link{font-size:14px;min-width:80px}
 .cart-title{font-size:42px}
 .cart-header{padding:50px 15px 55px}
 .cart-label{font-size:13px;letter-spacing:4px}
 .cart-subtitle{font-size:17px}
 .empty-cart{width:90%;padding:65px 22px}
 .empty-title{font-size:29px}
 .empty-text{font-size:17px}
}
</style>

</head>

<body>


<!-- =========================================================
     NAVIGATION
========================================================= -->

<header class="navbar">

    <nav>

        <ul class="nav-links">

            <li>
                <a href="index.php">Home</a>
            </li>

            <li>
                <a href="products.php">Products</a>
            </li>

            <li>
                <a href="about.php">About</a>
            </li>

            <li>
                <a href="contact.php">Contact</a>
            </li>

        </ul>

    </nav>


    <?php if ($isLoggedIn): ?>
        <a href="account.php" class="login-link">My Account</a>
    <?php else: ?>
        <a href="login.php" class="login-link">Login</a>
    <?php endif; ?>

</header>



<!-- =========================================================
     CART HEADER
========================================================= -->

<section class="cart-header">

    <div class="cart-label">
        COCORA
    </div>

    <h1 class="cart-title">
        Your Cart
    </h1>

    <p class="cart-subtitle">
        A collection chosen for your indulgence
    </p>

    <div class="header-line"></div>

</section>



<?php if (empty($cart)): ?>


<!-- =========================================================
     EMPTY CART
========================================================= -->

<section class="empty-cart">

    <div class="empty-symbol">
        ◇
    </div>

    <h2 class="empty-title">
        Your Cart Is Empty
    </h2>

    <p class="empty-text">
        Your Cocora collection is waiting.
        Explore our signature chocolate creations
        and choose something beautiful.
    </p>

    <a
        href="products.php"
        class="empty-button"
    >
        Explore Collection
    </a>

</section>


<?php else: ?>


<!-- =========================================================
     CART
========================================================= -->

<main class="cart-container">


<section class="cart-items">


<?php foreach ($cart as $id => $item): ?>

<?php

$quantity = max(
    1,
    (int)($item['quantity'] ?? 1)
);

$price = (float)(
    $item['price'] ?? 0
);

$lineTotal =
    $price * $quantity;

?>


<article class="cart-item">


<img
    src="assets/images/<?php
        echo htmlspecialchars(
            $item['image']
        );
    ?>"
    alt="<?php
        echo htmlspecialchars(
            $item['name']
        );
    ?>"
    class="cart-image"
>


<div>

    <h2 class="item-name">

        <?php
            echo htmlspecialchars(
                $item['name']
            );
        ?>

    </h2>


    <div class="item-price">

        ₹<?php
            echo number_format($price);
        ?>

        each

    </div>


    <form
        method="POST"
        class="quantity-control"
    >

        <input
            type="hidden"
            name="product_id"
            value="<?php
                echo htmlspecialchars($id);
            ?>"
        >


        <button
            type="submit"
            name="action"
            value="minus"
            class="quantity-button"
        >
            −
        </button>


        <span class="quantity-number">
            <?php echo $quantity; ?>
        </span>


        <button
            type="submit"
            name="action"
            value="plus"
            class="quantity-button"
        >
            +
        </button>

    </form>

</div>


<div class="item-right">

    <div class="item-total">

        ₹<?php
            echo number_format($lineTotal);
        ?>

    </div>


    <a
        href="cart.php?remove=<?php
            echo urlencode($id);
        ?>"
        class="remove-link"
    >
        Remove
    </a>

</div>


</article>


<?php endforeach; ?>


</section>



<!-- =========================================================
     ORDER SUMMARY
========================================================= -->

<aside class="summary">

    <h2 class="summary-title">
        Order Summary
    </h2>


    <div class="summary-row">

        <span>
            Items
        </span>

        <strong>
            <?php echo $totalItems; ?>
        </strong>

    </div>


    <div class="summary-row">

        <span>
            Subtotal
        </span>

        <strong>
            ₹<?php echo number_format($subtotal); ?>
        </strong>

    </div>


    <div class="summary-row">

        <span>
            GST (5%)
        </span>

        <strong>
            ₹<?php echo number_format($taxAmount); ?>
        </strong>

    </div>


    <div class="summary-row">

        <span>
            Delivery
        </span>

        <strong>
            ₹<?php echo number_format($deliveryCharge); ?>
        </strong>

    </div>


    <div class="tax-note">

        Taxes are calculated on the product subtotal.
        Final charges will be confirmed at checkout.

    </div>


    <div class="summary-total">

        <span>
            Grand Total
        </span>

        <strong>
            ₹<?php echo number_format($grandTotal); ?>
        </strong>

    </div>


    <div class="summary-buttons">

        <a
            href="checkout.php"
            class="cart-button primary"
        >
            Proceed To Checkout
        </a>


        <a
            href="products.php"
            class="cart-button"
        >
            Continue Shopping
        </a>

    </div>

</aside>


</main>


<?php endif; ?>




<?php if ($totalItems > 0): ?>
<?php endif; ?>


<!-- =========================================================
     FOOTER
========================================================= -->

<footer>

    <p>
        © <?php echo date('Y'); ?> Cocora Premium Chocolates
    </p>

</footer>


</body>
</html>