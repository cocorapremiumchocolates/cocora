<?php
session_start();

/* =========================================================
   COCORA EDIT PROFILE
   Updates the currently logged-in customer's session details.
   Existing database/order/cart logic is not changed.
   ========================================================= */

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customerName   = $_SESSION['customer_name'] ?? '';
$customerEmail  = $_SESSION['customer_email'] ?? '';
$customerMobile = $_SESSION['customer_mobile'] ?? '';

$message = '';
$messageType = '';

/* =========================================================
   SAVE PROFILE DETAILS
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {

    $name   = trim($_POST['name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');

    if ($name === '' || $email === '') {
        $message = 'Name and email are required.';
        $messageType = 'error';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';

    } else {

        $_SESSION['customer_name'] = $name;
        $_SESSION['customer_email'] = $email;
        $_SESSION['customer_mobile'] = $mobile;

        /* After saving, return directly to the Account page.
           The Account page reads these updated session values. */
        header("Location: account.php");
        exit;
    }
}

/* Cart count */
$cartCount = 0;

if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)($item['quantity'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile | COCORA Premium Chocolates</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

html{
    min-height:100%;
    scroll-behavior:smooth;
}

body{
    min-height:100vh;
    background:#080402;
    color:#ead7ad;
    font-family:'Cormorant Garamond',Georgia,serif;
    overflow-x:hidden;
}

a{
    color:inherit;
    text-decoration:none;
}

button,
input{
    font-family:inherit;
}

@keyframes cocoraFadeBlur{
    0%{
        opacity:0;
        filter:blur(16px);
        transform:translateY(20px);
    }
    100%{
        opacity:1;
        filter:blur(0);
        transform:translateY(0);
    }
}

/* =========================
   NAVBAR
   ========================= */
.navbar{
    height:88px;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:0 5.5%;
    background:rgba(8,4,2,.94);
    border-bottom:1px solid rgba(212,175,55,.18);
    backdrop-filter:blur(12px);
    position:sticky;
    top:0;
    z-index:1000;
    animation:cocoraFadeBlur .8s ease both;
}

.nav-links{
    display:flex;
    gap:42px;
    align-items:center;
}

.nav-links a,
.login-link{
    color:#ead7ad;
    text-decoration:none;
    font-size:21px;
    font-weight:500;
    letter-spacing:1px;
    transition:.3s ease;
}

.nav-links a:hover,
.nav-links a.active{
    color:#D4AF37;
}

.nav-links a.active{
    border-bottom:1px solid #D4AF37;
    padding-bottom:6px;
}

.login-link{
    position:absolute;
    right:5.5%;
    min-width:118px;
    text-align:center;
    padding:10px 18px;
    border:1px solid #D4AF37;
    color:#D4AF37;
}

.login-link:hover{
    background:#D4AF37;
    color:#080402;
}

/* =========================
   EDIT PROFILE
   ========================= */
.edit-page{
    width:min(760px,90%);
    margin:0 auto;
    padding:78px 0 120px;
}

.edit-header{
    text-align:center;
    animation:cocoraFadeBlur .8s .1s ease both;
}

.eyebrow{
    color:#b99752;
    text-transform:uppercase;
    letter-spacing:4px;
    font-size:14px;
    font-weight:600;
}

.edit-header h1{
    color:#D4AF37;
    font-size:62px;
    line-height:1;
    font-weight:600;
    letter-spacing:2px;
    margin:14px 0 18px;
}

.edit-header p{
    color:#c7ae85;
    font-size:20px;
    line-height:1.7;
}

.gold-line{
    width:100px;
    height:1px;
    background:#D4AF37;
    margin:28px auto 0;
}

.edit-card{
    margin-top:48px;
    padding:42px;
    border:1px solid rgba(212,175,55,.28);
    background:rgba(43,19,8,.45);
    animation:cocoraFadeBlur .8s .25s ease both;
}

.form-group{
    margin-bottom:24px;
}

.form-group:last-of-type{
    margin-bottom:0;
}

label{
    display:block;
    margin-bottom:9px;
    color:#D4AF37;
    font-size:17px;
    font-weight:600;
    letter-spacing:1px;
}

input{
    width:100%;
    padding:15px 16px;
    background:rgba(20,8,3,.72);
    border:1px solid rgba(212,175,55,.35);
    color:#ead7ad;
    outline:none;
    font-size:19px;
    transition:.3s ease;
}

input::placeholder{
    color:#766754;
}

input:focus{
    border-color:#D4AF37;
    box-shadow:0 0 0 2px rgba(212,175,55,.07);
}

.form-actions{
    display:flex;
    gap:12px;
    margin-top:32px;
}

.save-button,
.back-button{
    flex:1;
    padding:14px 22px;
    border:1px solid #D4AF37;
    font-size:18px;
    font-weight:600;
    letter-spacing:1px;
    text-align:center;
    cursor:pointer;
    transition:.3s ease;
}

.save-button{
    background:#D4AF37;
    color:#080402;
}

.save-button:hover{
    background:transparent;
    color:#D4AF37;
}

.back-button{
    background:transparent;
    color:#D4AF37;
}

.back-button:hover{
    background:#D4AF37;
    color:#080402;
}

.message{
    margin-top:22px;
    text-align:center;
    font-size:18px;
    letter-spacing:.5px;
    animation:cocoraFadeBlur .6s ease both;
}

.message.success{
    color:#d6b96c;
}

.message.error{
    color:#d99a8d;
}

/* =========================
   FLOATING CART
   ========================= */
.floating-cart{
    position:fixed;
    right:24px;
    bottom:24px;
    z-index:5000;
    display:flex;
    gap:10px;
    align-items:center;
    animation:cocoraFadeBlur .8s .7s ease both;
}

.floating-cart a{
    display:inline-block;
    padding:11px 18px;
    border:1px solid #D4AF37;
    background:#080402;
    color:#D4AF37;
    font-size:16px;
    font-weight:600;
    letter-spacing:.5px;
    transition:.3s ease;
}

.floating-cart a:hover{
    background:#D4AF37;
    color:#080402;
    transform:translateY(-2px);
}

footer{
    border-top:1px solid rgba(212,175,55,.14);
    padding:30px 5%;
    text-align:center;
    color:#6f604d;
    font-size:15px;
    letter-spacing:1px;
    animation:cocoraFadeBlur .8s .8s ease both;
}

/* =========================
   RESPONSIVE
   ========================= */
@media(max-width:650px){
    .navbar{
        height:auto;
        min-height:88px;
        padding:18px 5%;
        flex-wrap:wrap;
        gap:15px;
    }

    .nav-links{
        width:100%;
        justify-content:center;
        gap:20px;
        order:2;
    }

    .nav-links a{
        font-size:16px;
        white-space:nowrap;
    }

    .login-link{
        right:5%;
        top:18px;
        min-width:95px;
        padding:8px 12px;
        font-size:15px;
    }

    .edit-page{
        width:90%;
        padding:60px 0 110px;
    }

    .edit-header h1{
        font-size:48px;
    }

    .edit-header p{
        font-size:18px;
    }

    .edit-card{
        padding:28px 22px;
    }

    .form-actions{
        flex-direction:column;
    }

    .floating-cart{
        left:12px;
        right:12px;
        bottom:12px;
    }

    .floating-cart a{
        flex:1;
        text-align:center;
        padding:12px 8px;
        font-size:14px;
    }
}

@media(max-width:430px){
    .nav-links{
        gap:12px;
    }

    .nav-links a{
        font-size:14px;
    }

    .login-link{
        min-width:85px;
        font-size:14px;
    }

    .edit-header h1{
        font-size:42px;
    }
}
</style>
</head>

<body>

<nav class="navbar">
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="products.php">Products</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
    </div>

    <a href="account.php" class="login-link active">My Account</a>
</nav>

<main class="edit-page">

    <header class="edit-header">
        <div class="eyebrow">COCORA MEMBERSHIP</div>
        <h1>Edit Profile</h1>
        <p>Update your personal details and keep your COCORA profile current.</p>
        <div class="gold-line"></div>
    </header>

    <section class="edit-card">

        <form method="post">

            <div class="form-group">
                <label for="name">Full Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= htmlspecialchars($customerName) ?>"
                    placeholder="Enter your full name"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($customerEmail) ?>"
                    placeholder="Enter your email"
                    required
                >
            </div>

            <div class="form-group">
                <label for="mobile">Mobile Number</label>
                <input
                    type="tel"
                    id="mobile"
                    name="mobile"
                    value="<?= htmlspecialchars($customerMobile) ?>"
                    placeholder="Enter your mobile number"
                >
            </div>

            <div class="form-actions">
                <button type="submit" name="save_profile" class="save-button">
                    SAVE CHANGES
                </button>

                <a href="account.php" class="back-button">
                    BACK TO ACCOUNT
                </a>
            </div>

        </form>

        <?php if ($message !== ''): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

    </section>

</main>

<?php if ($cartCount > 0): ?>
<div class="floating-cart">
    <a href="cart.php">View Cart (<?= $cartCount ?>)</a>
    <a href="checkout.php">Checkout</a>
</div>
<?php endif; ?>

<footer>
    © <?= date('Y') ?> COCORA Premium Chocolates. Crafted With Passion.
</footer>

</body>
</html>
