<?php
session_start();

/* =========================
   DATABASE
========================= */

$host = "localhost";
$user = "root";
$pass = "";
$db   = "cocora_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


/* =========================
   LOGIN CHECK
========================= */

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php?redirect=payment.php");
    exit;
}

$customerId = (int) $_SESSION['customer_id'];


/* =========================
   CART CHECK
========================= */

if (
    !isset($_SESSION['cart']) ||
    empty($_SESSION['cart'])
) {
    header("Location: cart.php");
    exit;
}


/* =========================
   CHECKOUT CHECK
========================= */

if (
    !isset($_SESSION['checkout']) ||
    empty($_SESSION['checkout'])
) {
    header("Location: checkout.php");
    exit;
}


/* =========================
   CUSTOMER DETAILS
========================= */

$stmt = $conn->prepare("
    SELECT
        full_name,
        email,
        mobile
    FROM customers
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $customerId);
$stmt->execute();

$result = $stmt->get_result();

$customer = $result->fetch_assoc();

$stmt->close();


if (!$customer) {
    header("Location: login.php");
    exit;
}


$fullName = trim(
    $customer['full_name'] ?? ''
);

$email = trim(
    $customer['email'] ?? ''
);

$phone = trim(
    $customer['mobile'] ?? ''
);


/* =========================
   CHECKOUT DETAILS
========================= */

$checkout = $_SESSION['checkout'];

$address = trim(
    $checkout['address']
    ?? $checkout['address_line']
    ?? $checkout['full_address']
    ?? ''
);

$city = trim(
    $checkout['city'] ?? ''
);

$state = trim(
    $checkout['state'] ?? ''
);

$pincode = trim(
    $checkout['pincode'] ?? ''
);

$landmark = trim(
    $checkout['landmark'] ?? ''
);


/* =========================
   CART
========================= */

$cart = $_SESSION['cart'];


/* =========================
   TOTALS
========================= */

$subtotal = 0;

foreach ($cart as $item) {

    $price = (float) (
        $item['price'] ?? 0
    );

    $quantity = (int) (
        $item['quantity'] ?? 1
    );

    $subtotal +=
        $price * $quantity;
}


$taxAmount = round(
    $subtotal * 0.05,
    2
);

$deliveryCharge = 99;

$grandTotal = round(
    $subtotal +
    $taxAmount +
    $deliveryCharge,
    2
);


/* =========================
   ERROR
========================= */

$error = "";


/* =========================
   PLACE ORDER
========================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['place_order'])
) {

    $paymentMethod =
        $_POST['payment_method'] ?? 'COD';


    if (
        $paymentMethod !== 'COD' &&
        $paymentMethod !== 'UPI'
    ) {
        $paymentMethod = 'COD';
    }


    /* Payment status */

    if ($paymentMethod === 'COD') {
        $paymentStatus = 'COD';
    } else {
        $paymentStatus = 'Pending';
    }


    /* =========================
       VALIDATION
    ========================= */

    if (
        $fullName === '' ||
        $email === '' ||
        $phone === '' ||
        $address === '' ||
        $city === '' ||
        $state === '' ||
        $pincode === ''
    ) {

        $error =
            "Some customer or delivery details are missing.";

    } else {


        /* =========================
           DATABASE TRANSACTION
        ========================= */

        $conn->begin_transaction();


        try {


            /* =========================
               INSERT ORDER
            ========================= */

            $orderSql = "
                INSERT INTO orders
                (
                    customer_id,
                    total_amount,
                    tax_amount,
                    delivery_charge,
                    payment_method,
                    payment_status,
                    order_status,
                    full_name,
                    phone,
                    email,
                    address_line,
                    city,
                    state,
                    pincode,
                    landmark
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'Pending',
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ";


            $orderStmt =
                $conn->prepare($orderSql);


            if (!$orderStmt) {

                throw new Exception(
                    "Order query failed: " .
                    $conn->error
                );
            }


            $orderStmt->bind_param(
                "idddssssssssss",
                $customerId,
                $grandTotal,
                $taxAmount,
                $deliveryCharge,
                $paymentMethod,
                $paymentStatus,
                $fullName,
                $phone,
                $email,
                $address,
                $city,
                $state,
                $pincode,
                $landmark
            );


            if (!$orderStmt->execute()) {

                throw new Exception(
                    "Order could not be saved: " .
                    $orderStmt->error
                );
            }


            $orderId =
                $conn->insert_id;


            $orderStmt->close();


            /* =========================
               INSERT ORDER ITEMS

               product_id intentionally
               left NULL because the
               products are currently
               managed by PHP on the
               Products page.
            ========================= */

            $itemSql = "
                INSERT INTO order_items
                (
                    order_id,
                    product_id,
                    product_name,
                    price,
                    quantity
                )
                VALUES
                (
                    ?,
                    NULL,
                    ?,
                    ?,
                    ?
                )
            ";


            foreach ($cart as $item) {

                $productName =
                    $item['name']
                    ?? 'COCORA Chocolate';

                $price =
                    (float) (
                        $item['price'] ?? 0
                    );

                $quantity =
                    (int) (
                        $item['quantity'] ?? 1
                    );


                $itemStmt =
                    $conn->prepare($itemSql);


                if (!$itemStmt) {

                    throw new Exception(
                        "Order item query failed: " .
                        $conn->error
                    );
                }


                /*
                 * order_id     = integer
                 * product_name = string
                 * price        = decimal
                 * quantity     = integer
                 */

                $itemStmt->bind_param(
                    "isdi",
                    $orderId,
                    $productName,
                    $price,
                    $quantity
                );


                if (!$itemStmt->execute()) {

                    throw new Exception(
                        "Order item could not be saved: " .
                        $itemStmt->error
                    );
                }


                $itemStmt->close();
            }


            /* =========================
               COMMIT
            ========================= */

            $conn->commit();


            /* =========================
               LAST ORDER DETAILS
            ========================= */

            $_SESSION['last_order_id'] =
                $orderId;

            $_SESSION['last_order_total'] =
                $grandTotal;

            $_SESSION['last_payment_method'] =
                $paymentMethod;

            $_SESSION['last_payment_status'] =
                $paymentStatus;


            /* =========================
               CLEAR CART
            ========================= */

            unset($_SESSION['cart']);

            unset($_SESSION['checkout']);

            unset($_SESSION['payment']);


            /* =========================
               THANK YOU
            ========================= */

            header(
                "Location: thankyou.php"
            );

            exit;


        } catch (Exception $e) {

            $conn->rollback();

            $error =
                $e->getMessage();
        }
    }
}


/* =========================
   ESCAPE
========================= */

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Payment | COCORA Premium Chocolates
</title>


<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap');

*{margin:0;padding:0;box-sizing:border-box}

html{scroll-behavior:smooth}

body{
    min-height:100vh;
    background:#080402;
    color:#eadfbd;
    font-family:'Cormorant Garamond',serif;
    overflow-x:hidden;
}

body::before{
    content:"";
    position:fixed;
    inset:0;
    pointer-events:none;
    background:
        radial-gradient(circle at 15% 10%,rgba(212,175,55,.07),transparent 28%),
        radial-gradient(circle at 85% 85%,rgba(212,175,55,.045),transparent 30%);
    z-index:-1;
}

a{text-decoration:none;color:inherit}

/* COCORA HEADER */
.header{
    height:90px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 6%;
    border-bottom:1px solid rgba(212,175,55,.25);
    background:rgba(8,4,2,.96);
    position:relative;
    z-index:10;
    animation:blurFade .75s ease both;
}

.logo{display:flex;align-items:center;gap:12px}
.logo img{width:46px;height:46px;object-fit:contain}
.logo-name{
    color:#D4AF37;
    font-size:24px;
    letter-spacing:4px;
    font-weight:600;
    line-height:1;
}
.logo-sub{
    color:#9c8a67;
    font-size:9px;
    letter-spacing:2.5px;
    margin-top:5px;
}

.back{
    color:#D4AF37;
    border:1px solid #D4AF37;
    padding:11px 20px;
    font-size:14px;
    letter-spacing:.5px;
    transition:.3s ease;
}
.back:hover{background:#D4AF37;color:#080402;transform:translateY(-1px)}

/* MAIN */
.container{
    width:90%;
    max-width:1160px;
    margin:0 auto;
    padding:65px 0 90px;
}

.title{text-align:center;margin-bottom:45px;animation:blurFade .8s .08s ease both}
.title small{
    display:block;
    color:#B99A43;
    font-size:12px;
    letter-spacing:5px;
    margin-bottom:12px;
}
.title h1{
    color:#D4AF37;
    font-weight:500;
    font-size:50px;
    letter-spacing:4px;
    line-height:1;
}
.gold-line{
    width:58px;
    height:1px;
    background:#D4AF37;
    margin:19px auto 0;
}

.error{
    margin:0 auto 25px;
    max-width:1160px;
    padding:15px 18px;
    text-align:center;
    color:#e8b5a7;
    border:1px solid rgba(180,80,65,.45);
    background:rgba(100,25,15,.15);
    font-family:'Cormorant Garamond',serif;
    font-size:14px;
    animation:blurFade .7s .12s ease both;
}

/* LAYOUT */
.layout{
    display:grid;
    grid-template-columns:minmax(0,1fr) 360px;
    gap:28px;
    align-items:start;
}

.panel,.summary{
    border:1px solid rgba(212,175,55,.28);
    background:rgba(17,8,3,.88);
    padding:32px;
    box-shadow:0 18px 55px rgba(0,0,0,.28);
    animation:blurFade .85s .16s ease both;
}
.summary{animation-delay:.23s}

.panel h2,.summary h2{
    color:#D4AF37;
    font-weight:500;
    font-size:25px;
    letter-spacing:.7px;
    margin-bottom:9px;
}
.subtitle{
    color:#9b896c;
    font-size:14px;
    margin-bottom:27px;
}

/* PAYMENT OPTIONS */
.option{display:block;margin-bottom:15px;cursor:pointer}
.option input{display:none}
.option-box{
    border:1px solid rgba(212,175,55,.23);
    padding:21px;
    background:rgba(8,4,2,.58);
    transition:.3s ease;
}
.option:hover .option-box{border-color:rgba(212,175,55,.6);transform:translateY(-1px)}
.option input:checked + .option-box{
    border-color:#D4AF37;
    background:rgba(212,175,55,.08);
    box-shadow:inset 0 0 0 1px rgba(212,175,55,.08),0 8px 25px rgba(0,0,0,.18);
}
.option-title{
    color:#E0C36B;
    font-size:18px;
    font-weight:600;
    letter-spacing:.7px;
    margin-bottom:7px;
}
.option-text{
    color:#a9997e;
    font-size:13px;
    line-height:1.65;
}

/* UPI */
.upi-area{
    margin-top:20px;
    padding:25px;
    text-align:center;
    border:1px solid rgba(212,175,55,.2);
    background:rgba(8,4,2,.5);
    animation:blurFade .7s ease both;
}
.upi-area h3{color:#D4AF37;font-size:20px;font-weight:500;margin-bottom:8px}
.upi-area p{color:#9b896c;font-size:13px;margin-bottom:17px}
.qr-wrapper{display:inline-block;background:#fff;padding:10px;box-shadow:0 8px 30px rgba(0,0,0,.3)}
.qr-code{width:230px;height:230px;display:block;object-fit:contain}
.scan-text{color:#bcae91;font-size:12px;margin-top:14px}

/* COD */
.cod-note{
    margin-top:20px;
    padding:19px;
    border:1px solid rgba(212,175,55,.2);
    color:#aa997e;
    font-size:13px;
    line-height:1.7;
    background:rgba(8,4,2,.42);
    animation:blurFade .7s ease both;
}

/* BUTTON */
.place-order{
    width:100%;
    margin-top:27px;
    border:1px solid #D4AF37;
    background:#D4AF37;
    color:#080402;
    padding:16px;
    cursor:pointer;
    font-family:'Cormorant Garamond',serif;
    font-weight:600;
    font-size:15px;
    letter-spacing:1.5px;
    transition:.3s ease;
}
.place-order:hover{
    background:#edc95b;
    border-color:#edc95b;
    transform:translateY(-2px);
    box-shadow:0 10px 28px rgba(212,175,55,.18);
}

/* SUMMARY */
.summary{position:sticky;top:20px}
.summary-row{
    display:flex;
    justify-content:space-between;
    gap:15px;
    padding:13px 0;
    border-bottom:1px solid rgba(212,175,55,.11);
    color:#9d8b70;
    font-size:13px;
}
.summary-row strong{color:#d8c99f;font-weight:500}
.total{
    display:flex;
    justify-content:space-between;
    padding-top:21px;
    color:#D4AF37;
    font-size:21px;
    font-weight:600;
}
.total strong{color:#e7c768}
.delivery{
    margin-top:24px;
    padding-top:21px;
    border-top:1px solid rgba(212,175,55,.16);
}
.delivery h3{color:#D4AF37;font-size:16px;font-weight:500;margin-bottom:9px}
.delivery p{color:#9b896c;font-size:12px;line-height:1.8}
.security{margin-top:16px;text-align:center;color:#6f624f;font-size:10px}

/* ALL ELEMENT ENTRANCE */
.header *, .container *{animation:blurFade .72s ease both}
.title > *, .panel > *, .summary > *{animation-delay:.08s}

@keyframes blurFade{
    from{opacity:0;filter:blur(9px);transform:translateY(16px)}
    to{opacity:1;filter:blur(0);transform:translateY(0)}
}

@media (max-width:850px){
    .header{height:auto;padding:20px 5%;gap:18px}
    .layout{grid-template-columns:1fr}
    .summary{position:static}
}

@media (max-width:550px){
    .header{padding:18px 4%}
    .logo-name{font-size:19px;letter-spacing:3px}
    .logo-sub{display:none}
    .back{padding:9px 13px;font-size:12px}
    .container{width:94%;padding-top:45px}
    .title h1{font-size:37px}
    .title small{font-size:10px;letter-spacing:3.5px}
    .panel,.summary{padding:22px 18px}
    .qr-code{width:205px;height:205px}
}
</style>

</head>


<body>


<header class="header">

    <a
        href="index.php"
        class="logo"
    >

        <img
            src="assets/logo/logo.png"
            alt="COCORA"
        >

        <div>

            <div class="logo-name">
                COCORA
            </div>

            <div class="logo-sub">
                PREMIUM CHOCOLATES
            </div>

        </div>

    </a>


    <a
        href="checkout.php"
        class="back"
    >
        ← BACK TO CHECKOUT
    </a>

</header>


<main class="container">


    <div class="title">

        <small>
            SECURE CHECKOUT
        </small>

        <h1>
            PAYMENT
        </h1>

        <div class="gold-line"></div>

    </div>


    <?php if ($error !== ''): ?>

        <div class="error">
            <?php echo e($error); ?>
        </div>

    <?php endif; ?>


    <div class="layout">


        <!-- PAYMENT -->

        <section class="panel">

            <h2>
                Choose Payment Method
            </h2>

            <div class="subtitle">
                Select your preferred payment method.
            </div>


            <!-- UPI -->

            <label class="option">

                <input
                    type="radio"
                    name="payment_choice"
                    value="UPI"
                    onclick="selectPayment('UPI')"
                >

                <div class="option-box">

                    <div class="option-title">
                        UPI / QR PAYMENT
                    </div>

                    <div class="option-text">
                        Scan the COCORA QR code using
                        your preferred UPI application.
                    </div>

                </div>

            </label>


            <!-- COD -->

            <label class="option">

                <input
                    type="radio"
                    name="payment_choice"
                    value="COD"
                    checked
                    onclick="selectPayment('COD')"
                >

                <div class="option-box">

                    <div class="option-title">
                        CASH ON DELIVERY
                    </div>

                    <div class="option-text">
                        Pay the complete amount when your
                        COCORA order is delivered.
                    </div>

                </div>

            </label>


            <!-- UPI QR -->

            <div
                id="upiArea"
                class="upi-area"
                style="display:none;"
            >

                <h3>
                    Scan To Pay
                </h3>

                <p>
                    Scan this QR code with your UPI app.
                </p>


                <div class="qr-wrapper">

                    <img
                        src="assets/images/QR.jpg"
                        alt="COCORA UPI QR Code"
                        class="qr-code"
                    >

                </div>


                <div class="scan-text">
                    COCORA UPI PAYMENT
                </div>

            </div>


            <!-- COD NOTE -->

            <div
                id="codNote"
                class="cod-note"
            >

                <strong style="color:#d9a441;">
                    Cash on Delivery
                </strong>

                <br>

                Your order will be placed immediately.
                You can pay when the order is delivered.

            </div>


            <!-- FORM -->

            <form
                method="POST"
                action=""
            >

                <input
                    type="hidden"
                    name="payment_method"
                    id="paymentMethod"
                    value="COD"
                >


                <button
                    type="submit"
                    name="place_order"
                    class="place-order"
                    id="placeButton"
                >
                    PLACE COD ORDER
                </button>

            </form>


            <div class="security">
                Your order details are securely processed by COCORA.
            </div>

        </section>


        <!-- SUMMARY -->

        <aside class="summary">

            <h2>
                Order Summary
            </h2>


            <div class="summary-row">

                <span>
                    Subtotal
                </span>

                <strong>
                    ₹<?php
                    echo number_format(
                        $subtotal,
                        2
                    );
                    ?>
                </strong>

            </div>


            <div class="summary-row">

                <span>
                    GST (5%)
                </span>

                <strong>
                    ₹<?php
                    echo number_format(
                        $taxAmount,
                        2
                    );
                    ?>
                </strong>

            </div>


            <div class="summary-row">

                <span>
                    Delivery
                </span>

                <strong>
                    ₹<?php
                    echo number_format(
                        $deliveryCharge,
                        2
                    );
                    ?>
                </strong>

            </div>


            <div class="total">

                <span>
                    TOTAL
                </span>

                <strong>
                    ₹<?php
                    echo number_format(
                        $grandTotal,
                        2
                    );
                    ?>
                </strong>

            </div>


            <!-- DELIVERY -->

            <div class="delivery">

                <h3>
                    Delivering To
                </h3>


                <p>

                    <strong
                        style="color:#d9a441;"
                    >
                        <?php echo e($fullName); ?>
                    </strong>

                    <br>

                    <?php echo e($phone); ?>

                    <br>

                    <?php echo e($email); ?>

                    <br><br>

                    <?php echo nl2br(e($address)); ?>

                    <br>

                    <?php echo e($city); ?>,
                    <?php echo e($state); ?>

                    -
                    <?php echo e($pincode); ?>


                    <?php if ($landmark !== ''): ?>

                        <br>

                        Landmark:
                        <?php echo e($landmark); ?>

                    <?php endif; ?>

                </p>

            </div>

        </aside>


    </div>

</main>


<script>

function selectPayment(method) {

    const upiArea =
        document.getElementById(
            "upiArea"
        );

    const codNote =
        document.getElementById(
            "codNote"
        );

    const paymentMethod =
        document.getElementById(
            "paymentMethod"
        );

    const placeButton =
        document.getElementById(
            "placeButton"
        );


    paymentMethod.value =
        method;


    if (method === "UPI") {

        upiArea.style.display =
            "block";

        codNote.style.display =
            "none";

        placeButton.innerText =
            "PLACE ORDER";

    } else {

        upiArea.style.display =
            "none";

        codNote.style.display =
            "block";

        placeButton.innerText =
            "PLACE COD ORDER";
    }

}

</script>


</body>

</html>