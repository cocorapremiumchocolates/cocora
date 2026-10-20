<?php
session_start();

require_once __DIR__ . '/includes/db.php';

$conn->query("
    CREATE TABLE IF NOT EXISTS contact_messages (
        message_id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(190) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (message_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$success = '';
$error = '';
$name = '';
$email = '';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $subject === '' || $message === '') {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO contact_messages (name, email, subject, message)
            VALUES (?, ?, ?, ?)
        ");

        if ($stmt) {
            $stmt->bind_param('ssss', $name, $email, $subject, $message);

            if ($stmt->execute()) {
                $success = 'Thank you. Your message has been sent successfully.';
                $name = '';
                $email = '';
                $subject = '';
                $message = '';
            } else {
                $error = 'Unable to send your message. Please try again.';
            }

            $stmt->close();
        } else {
            $error = 'Unable to process your message. Please try again.';
        }
    }
}


$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)($item['quantity'] ?? 0);
    }
}

$isLoggedIn = isset($_SESSION['customer_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact | COCORA Premium Chocolates</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

html,body{
    min-height:100%;
}

body{
    font-family:'Cormorant Garamond',Georgia,serif;
    background:#080402;
    color:#ead7ad;
    overflow-x:hidden;
}

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

.contact-page{
    width:min(1120px,88%);
    margin:0 auto;
    padding:82px 0 105px;
}

.contact-header{
    text-align:center;
    margin-bottom:65px;
}

.contact-header .eyebrow{
    color:#b99a63;
    font-size:16px;
    letter-spacing:5px;
    text-transform:uppercase;
    margin-bottom:18px;
    animation:cocoraFadeBlur .9s .15s ease both;
}

.contact-header h1{
    color:#D4AF37;
    font-size:clamp(52px,7vw,78px);
    font-weight:500;
    letter-spacing:3px;
    line-height:1.05;
    animation:cocoraFadeBlur .9s .3s ease both;
}

.contact-header p{
    max-width:720px;
    margin:24px auto 0;
    color:#d5c09a;
    font-size:21px;
    line-height:1.7;
    animation:cocoraFadeBlur .9s .45s ease both;
}

.gold-line{
    width:65px;
    height:1px;
    background:#D4AF37;
    margin:30px auto 0;
    animation:cocoraFadeBlur .9s .6s ease both;
}

.form-alert{
    width:100%;
    margin:0 auto 28px;
    padding:13px 18px;
    text-align:center;
    font-size:18px;
    border:1px solid rgba(212,175,55,.25);
    background:rgba(25,11,5,.62);
    animation:cocoraFadeBlur .8s .65s ease both;
}
.success-alert{color:#D4AF37;}
.error-alert{
    color:#c98f7d;
    border-color:rgba(170,80,60,.35);
}

.contact-content{
    display:grid;
    grid-template-columns:.9fr 1.1fr;
    gap:35px;
    align-items:stretch;
}

.contact-info,
.contact-form{
    background:rgba(25,11,5,.62);
    border:1px solid rgba(212,175,55,.25);
    backdrop-filter:blur(8px);
    -webkit-backdrop-filter:blur(8px);
}

.contact-info{
    padding:45px 40px;
    animation:cocoraFadeBlur .9s .75s ease both;
}

.contact-form{
    padding:45px 40px;
    animation:cocoraFadeBlur .9s .9s ease both;
}

.contact-info h2,
.contact-form h2{
    color:#D4AF37;
    font-size:38px;
    font-weight:500;
    letter-spacing:1px;
    margin-bottom:22px;
}

.contact-info > p{
    color:#c7ae85;
    font-size:19px;
    line-height:1.85;
    margin-bottom:30px;
}

.contact-item{
    padding:18px 0;
    border-top:1px solid rgba(212,175,55,.16);
    animation:cocoraFadeBlur .8s ease both;
}

.contact-item:nth-child(2){animation-delay:1.05s}
.contact-item:nth-child(3){animation-delay:1.2s}
.contact-item:nth-child(4){animation-delay:1.35s}

.contact-item strong{
    display:block;
    color:#D4AF37;
    font-size:21px;
    font-weight:500;
    margin-bottom:4px;
}

.contact-item span{
    color:#c7ae85;
    font-size:18px;
}

.form-group{
    margin-bottom:20px;
    animation:cocoraFadeBlur .8s ease both;
}

.form-group:nth-child(1){animation-delay:1.05s}
.form-group:nth-child(2){animation-delay:1.2s}
.form-group:nth-child(3){animation-delay:1.35s}
.form-group:nth-child(4){animation-delay:1.5s}

.form-group label{
    display:block;
    color:#D4AF37;
    font-size:18px;
    margin-bottom:8px;
}

.form-group input,
.form-group textarea{
    width:100%;
    padding:13px 15px;
    background:#1d0d06;
    border:1px solid rgba(212,175,55,.35);
    color:#ead7ad;
    outline:none;
    font-family:'Cormorant Garamond',Georgia,serif;
    font-size:18px;
    transition:.3s ease;
}

.form-group textarea{
    min-height:145px;
    resize:vertical;
}

.form-group input:focus,
.form-group textarea:focus{
    border-color:#D4AF37;
    box-shadow:0 0 15px rgba(212,175,55,.08);
}

.form-group input::placeholder,
.form-group textarea::placeholder{
    color:#806d55;
}

.send-button{
    display:inline-block;
    padding:14px 30px;
    border:1px solid #D4AF37;
    background:transparent;
    color:#D4AF37;
    font-family:'Cormorant Garamond',Georgia,serif;
    font-size:18px;
    font-weight:600;
    letter-spacing:1px;
    cursor:pointer;
    transition:.3s ease;
    animation:cocoraFadeBlur .8s 1.65s ease both;
}

.send-button:hover{
    background:#D4AF37;
    color:#080402;
    transform:translateY(-2px);
}

.floating-cart{
    position:fixed;
    right:24px;
    bottom:24px;
    display:flex;
    gap:10px;
    z-index:2000;
    animation:cocoraFadeBlur .8s 1.75s ease both;
}

.floating-cart a{
    text-decoration:none;
    padding:13px 21px;
    font-size:16px;
    font-weight:600;
    letter-spacing:.8px;
    transition:.3s ease;
    border:1px solid #D4AF37;
}

.view-cart{
    background:#D4AF37;
    color:#080402;
}

.checkout{
    background:#080402;
    color:#D4AF37;
}

.floating-cart a:hover{
    transform:translateY(-3px);
    background:#D4AF37;
    color:#080402;
}

footer{
    border-top:1px solid rgba(212,175,55,.15);
    text-align:center;
    padding:30px 20px;
    color:#8f795d;
    font-size:15px;
    letter-spacing:1px;
    animation:cocoraFadeBlur .8s 1.8s ease both;
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

@media(max-width:850px){
    .navbar{
        height:auto;
        min-height:88px;
        padding:15px 5%;
        flex-wrap:wrap;
        gap:15px;
    }

    .nav-links{
        width:100%;
        order:2;
        justify-content:center;
        gap:22px;
    }

    .nav-links a{
        font-size:17px;
    }

    .login-link{
        right:5%;
        font-size:17px;
        min-width:95px;
        padding:8px 12px;
    }

    .contact-page{
        padding-top:55px;
    }

    .contact-content{
        grid-template-columns:1fr;
        gap:25px;
    }
}

@media(max-width:500px){
    .nav-links{
        gap:15px;
        overflow-x:auto;
        justify-content:flex-start;
        padding:0 2px;
    }

    .nav-links a{
        font-size:15px;
        white-space:nowrap;
    }

    .login-link{
        font-size:15px;
        min-width:82px;
    }

    .contact-page{
        width:90%;
        padding-bottom:80px;
    }

    .contact-header h1{
        font-size:48px;
    }

    .contact-header p{
        font-size:18px;
    }

    .contact-info,
    .contact-form{
        padding:32px 25px;
    }

    .contact-info h2,
    .contact-form h2{
        font-size:32px;
    }

    .contact-info > p{
        font-size:17px;
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
</style>
</head>

<body>

<nav class="navbar">
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="products.php">Products</a>
        <a href="about.php">About</a>
        <a href="contact.php" class="active">Contact</a>
    </div>

    <?php if ($isLoggedIn): ?>
        <a href="account.php" class="login-link">My Account</a>
    <?php else: ?>
        <a href="login.php" class="login-link">Login</a>
    <?php endif; ?>
</nav>

<main class="contact-page">

    <header class="contact-header">
        <div class="eyebrow">Connect With COCORA</div>
        <h1>Contact COCORA</h1>
        <p>
            Have a question about our chocolates or your order?
            We would love to hear from you.
        </p>
        <div class="gold-line"></div>
    </header>

    <?php if ($success !== ''): ?>
        <div class="form-alert success-alert"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="form-alert error-alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <section class="contact-content">

        <article class="contact-info">
            <h2>Get In Touch</h2>

            <p>
                For questions, feedback or information about COCORA,
                please use the details below or send us a message.
            </p>

            <div class="contact-item">
                <strong>Email</strong>
                <span>contact@cocora.com</span>
            </div>

            <div class="contact-item">
                <strong>Phone</strong>
                <span>+91 00000 00000</span>
            </div>

            <div class="contact-item">
                <strong>Business</strong>
                <span>COCORA Premium Chocolates</span>
            </div>
        </article>

        <article class="contact-form">
            <h2>Send A Message</h2>

            <form action="contact.php" method="post">

                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Enter your name"
                        value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Your Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        placeholder="What would you like to ask?"
                        value="<?= htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea
                        id="message"
                        name="message"
                        placeholder="Write your message..."
                        required
                    ></textarea>
                </div>

                <button type="submit" class="send-button">
                    SEND MESSAGE
                </button>

            </form>
        </article>

    </section>

</main>

<?php if ($cartCount > 0): ?>
    <div class="floating-cart">
        <a href="cart.php" class="view-cart">
            View Cart (<?= $cartCount ?>)
        </a>
        <a href="checkout.php" class="checkout">
            Checkout
        </a>
    </div>
<?php endif; ?>

<footer>
    © <?= date('Y') ?> COCORA Premium Chocolates. Crafted With Passion.
</footer>

</body>
</html>
