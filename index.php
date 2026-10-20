<?php
session_start();

$loggedIn = isset($_SESSION['customer_id']);

$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
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
<title>COCORA | Premium Chocolates</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box}
html,body{min-height:100%;}
body{
 font-family:'Cormorant Garamond',Georgia,serif;
 background:#080402;
 color:#ead7ad;
 overflow-x:hidden;
}

/* NAVBAR — same visual language as About */
.navbar{
 height:88px;display:flex;align-items:center;justify-content:center;
 padding:0 5.5%;background:rgba(8,4,2,.94);
 border-bottom:1px solid rgba(212,175,55,.18);
 backdrop-filter:blur(12px);
 position:sticky;top:0;z-index:1000;
 animation:cocoraFadeBlur .8s ease both;
}
.nav-links{display:flex;gap:42px;align-items:center}
.nav-links a,.login-link{
 color:#ead7ad;text-decoration:none;font-size:21px;font-weight:500;
 letter-spacing:1px;transition:.3s ease;
}
.nav-links a:hover,.nav-links a.active{color:#D4AF37}
.nav-links a.active{border-bottom:1px solid #D4AF37;padding-bottom:6px}
.login-link{
 position:absolute;right:5.5%;min-width:118px;text-align:center;
 padding:10px 18px;border:1px solid #D4AF37;color:#D4AF37;
}
.login-link:hover{background:#D4AF37;color:#080402}

/* HOME */
.home-page{
 width:100%;
 background:#080402;
}
.hero{
 min-height:790px;
 width:100%;
 display:flex;
 justify-content:center;
 align-items:flex-start;
 text-align:center;
 padding:78px 20px 95px;
}
.hero-content{
 width:100%;
 max-width:1200px;
 display:flex;
 flex-direction:column;
 align-items:center;
}
.logo{
 width:390px;
 max-width:70vw;
 height:auto;
 display:block;
 margin:0 auto 28px;
 animation:cocoraFadeBlur .9s .15s ease both;
}
.hero h1{
 color:#D4AF37;
 font-size:clamp(78px,8vw,132px);
 font-weight:500;
 letter-spacing:9px;
 line-height:.88;
 animation:cocoraFadeBlur .9s .3s ease both;
}
.premium-text{
 margin:35px 0 0;
 color:#D4AF37;
 font-size:39px;
 font-weight:500;
 line-height:1;
 animation:cocoraFadeBlur .9s .45s ease both;
}
.tagline{
 margin:25px 0 0;
 color:#c7ae85;
 font-size:25px;
 font-weight:400;
 letter-spacing:1.5px;
 line-height:1.3;
 animation:cocoraFadeBlur .9s .6s ease both;
}
.hero-line{
 width:65px;
 height:1px;
 background:#D4AF37;
 margin:32px 0 28px;
 animation:cocoraFadeBlur .9s .75s ease both;
}
.button{
 display:inline-flex;
 align-items:center;
 justify-content:center;
 width:275px;
 height:62px;
 border:1px solid #D4AF37;
 background:transparent;
 color:#D4AF37;
 text-decoration:none;
 font-size:24px;
 font-weight:500;
 letter-spacing:1px;
 transition:.3s ease;
 animation:cocoraFadeBlur .9s .9s ease both;
}
.button:hover{background:#D4AF37;color:#080402;transform:translateY(-3px)}

.welcome{
 width:min(1000px,88%);
 margin:0 auto;
 padding:80px 20px 110px;
 text-align:center;
}
.welcome h2{
 color:#D4AF37;
 font-size:58px;
 font-weight:500;
 letter-spacing:2px;
 line-height:1.1;
 animation:cocoraFadeBlur .9s 1.05s ease both;
}
.welcome p{
 max-width:780px;
 margin:24px auto 0;
 color:#c7ae85;
 font-size:20px;
 line-height:1.8;
 font-weight:400;
 animation:cocoraFadeBlur .9s 1.2s ease both;
}
.welcome p:nth-of-type(2){animation-delay:1.35s}
.welcome-line{
 width:65px;
 height:1px;
 background:#D4AF37;
 margin:32px auto 0;
 animation:cocoraFadeBlur .9s 1.5s ease both;
}

/* Cart buttons only exist when cart has items */
.floating-cart{
 position:fixed;
 right:24px;
 bottom:24px;
 display:flex;
 gap:10px;
 z-index:2000;
 animation:cocoraFadeBlur .8s 1.55s ease both;
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
.view-cart{background:#D4AF37;color:#080402}
.checkout{background:#080402;color:#D4AF37}
.floating-cart a:hover{transform:translateY(-3px);background:#D4AF37;color:#080402}

footer{
 border-top:1px solid rgba(212,175,55,.15);
 text-align:center;
 padding:30px 20px;
 color:#8f795d;
 font-size:15px;
 letter-spacing:1px;
 animation:cocoraFadeBlur .8s 1.6s ease both;
}

@keyframes cocoraFadeBlur{
 0%{opacity:0;filter:blur(16px);transform:translateY(20px)}
 100%{opacity:1;filter:blur(0);transform:translateY(0)}
}

@media(max-width:850px){
 .navbar{height:auto;min-height:88px;padding:15px 5%;flex-wrap:wrap;gap:15px}
 .nav-links{width:100%;order:2;justify-content:center;gap:22px}
 .nav-links a{font-size:17px}
 .login-link{right:5%;font-size:17px;min-width:95px;padding:8px 12px}
 .hero{min-height:720px;padding-top:60px}
 .logo{width:330px;max-width:68vw}
 .hero h1{font-size:clamp(68px,12vw,108px);letter-spacing:6px}
 .premium-text{font-size:32px}
 .tagline{font-size:21px}
}

@media(max-width:500px){
 .nav-links{gap:15px;overflow-x:auto;justify-content:flex-start;padding:0 2px}
 .nav-links a{font-size:15px;white-space:nowrap}
 .login-link{font-size:15px;min-width:82px}
 .hero{min-height:650px;padding:48px 16px 70px}
 .logo{width:280px;max-width:68vw;margin-bottom:22px}
 .hero h1{font-size:62px;letter-spacing:4px}
 .premium-text{font-size:28px;margin-top:28px}
 .tagline{font-size:19px;margin-top:20px}
 .button{width:225px;height:58px;font-size:21px}
 .welcome{width:90%;padding:65px 15px 85px}
 .welcome h2{font-size:45px}
 .welcome p{font-size:18px}
 .floating-cart{left:12px;right:12px;bottom:12px}
 .floating-cart a{flex:1;text-align:center;padding:12px 8px;font-size:14px}
}
</style>
</head>

<body>

<div class="home-page">

<nav class="navbar">
 <div class="nav-links">
  <a href="index.php" class="active">Home</a>
  <a href="products.php">Products</a>
  <a href="about.php">About</a>
  <a href="contact.php">Contact</a>
 </div>

 <?php if ($loggedIn): ?>
  <a href="account.php" class="login-link">My Account</a>
 <?php else: ?>
  <a href="login.php" class="login-link">Login</a>
 <?php endif; ?>
</nav>

<section class="hero">
 <div class="hero-content">
  <img src="assets/logo/logo.png" alt="Cocora Logo" class="logo">

  <h1>COCORA</h1>

  <p class="premium-text">Premium Chocolates</p>

  <p class="tagline">Crafted With Passion, Made To Indulge</p>

  <div class="hero-line"></div>

  <a href="products.php" class="button">Explore Collection</a>
 </div>
</section>

<section class="welcome">
 <h2>Welcome To Cocora</h2>

 <p>
  Discover handcrafted luxury chocolates inspired by elegance,
  crafted with premium ingredients and designed for unforgettable moments.
 </p>

 <p>
  Every Cocora creation is made to deliver exceptional taste,
  sophistication, and indulgence in every bite.
 </p>

 <div class="welcome-line"></div>
</section>

<?php if ($cartCount > 0): ?>
<div class="floating-cart">
 <a href="cart.php" class="view-cart">View Cart (<?= $cartCount ?>)</a>
 <a href="checkout.php" class="checkout">Checkout</a>
</div>
<?php endif; ?>

<footer>© <?= date('Y') ?> COCORA Premium Chocolates. Crafted With Passion.</footer>

</div>
</body>
</html>
