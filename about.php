<?php
session_start();

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
<title>About | COCORA Premium Chocolates</title>
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
.navbar{
 height:88px;display:flex;align-items:center;justify-content:center;
 padding:0 5.5%;background:rgba(8,4,2,.94);
 border-bottom:1px solid rgba(212,175,55,.18);backdrop-filter:blur(12px);
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
.about-page{width:min(1120px,88%);margin:0 auto;padding:82px 0 110px}
.about-header{text-align:center;margin-bottom:70px}
.about-header .eyebrow{
 color:#b99a63;font-size:16px;letter-spacing:5px;text-transform:uppercase;margin-bottom:18px;
 animation:cocoraFadeBlur .9s .15s ease both;
}
.about-header h1{
 color:#D4AF37;font-size:clamp(52px,7vw,78px);font-weight:500;letter-spacing:3px;line-height:1.05;
 animation:cocoraFadeBlur .9s .3s ease both;
}
.about-header p{
 max-width:720px;margin:24px auto 0;color:#d5c09a;font-size:21px;line-height:1.7;
 animation:cocoraFadeBlur .9s .45s ease both;
}
.gold-line{width:65px;height:1px;background:#D4AF37;margin:30px auto 0;animation:cocoraFadeBlur .9s .6s ease both}
.about-content{display:grid;grid-template-columns:1.05fr .95fr;gap:55px;align-items:stretch}
.about-story,.vision-panel,.feature-card{
 background:rgba(25,11,5,.62);border:1px solid rgba(212,175,55,.25);
 backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
}
.about-story{padding:48px 45px;animation:cocoraFadeBlur .9s .75s ease both}
.about-story h2,.vision-panel h2{color:#D4AF37;font-size:38px;font-weight:500;letter-spacing:1px;margin-bottom:22px}
.about-story p,.vision-panel p{color:#c7ae85;font-size:19px;line-height:1.85;margin-bottom:18px}
.about-story p:last-child,.vision-panel p:last-child{margin-bottom:0}
.vision-panel{padding:48px 40px;animation:cocoraFadeBlur .9s .9s ease both}
.feature-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;margin-top:55px}
.feature-card{padding:32px 25px;text-align:center;animation:cocoraFadeBlur .9s ease both}
.feature-card:nth-child(1){animation-delay:1.05s}.feature-card:nth-child(2){animation-delay:1.2s}.feature-card:nth-child(3){animation-delay:1.35s}
.feature-card h3{color:#D4AF37;font-size:27px;font-weight:500;margin-bottom:12px}
.feature-card p{color:#a7957c;font-size:17px;line-height:1.7}
.floating-cart{
 position:fixed;right:24px;bottom:24px;display:flex;gap:10px;z-index:2000;
 animation:cocoraFadeBlur .8s 1.5s ease both;
}
.floating-cart a{text-decoration:none;padding:13px 21px;font-size:16px;font-weight:600;letter-spacing:.8px;transition:.3s ease;border:1px solid #D4AF37}
.view-cart{background:#D4AF37;color:#080402}.checkout{background:#080402;color:#D4AF37}
.floating-cart a:hover{transform:translateY(-3px);background:#D4AF37;color:#080402}
footer{border-top:1px solid rgba(212,175,55,.15);text-align:center;padding:30px 20px;color:#8f795d;font-size:15px;letter-spacing:1px;animation:cocoraFadeBlur .8s 1.45s ease both}
@keyframes cocoraFadeBlur{
 0%{opacity:0;filter:blur(16px);transform:translateY(20px)}
 100%{opacity:1;filter:blur(0);transform:translateY(0)}
}
@media(max-width:850px){
 .navbar{height:auto;min-height:88px;padding:15px 5%;flex-wrap:wrap;gap:15px}
 .nav-links{width:100%;order:2;justify-content:center;gap:22px}
 .nav-links a{font-size:17px}
 .login-link{right:5%;font-size:17px;min-width:95px;padding:8px 12px}
 .about-page{padding-top:55px}
 .about-content{grid-template-columns:1fr;gap:25px}
 .feature-grid{grid-template-columns:1fr}
}
@media(max-width:500px){
 .nav-links{gap:15px;overflow-x:auto;justify-content:flex-start;padding:0 2px}
 .nav-links a{font-size:15px;white-space:nowrap}
 .login-link{font-size:15px;min-width:82px}
 .about-page{width:90%;padding-bottom:80px}
 .about-header h1{font-size:48px}
 .about-header p{font-size:18px}
 .about-story,.vision-panel{padding:32px 25px}
 .about-story h2,.vision-panel h2{font-size:32px}
 .about-story p,.vision-panel p{font-size:17px}
 .floating-cart{left:12px;right:12px;bottom:12px}
 .floating-cart a{flex:1;text-align:center;padding:12px 8px;font-size:14px}
}
</style>
</head>
<body>
<nav class="navbar">
 <div class="nav-links">
  <a href="index.php">Home</a>
  <a href="products.php">Products</a>
  <a href="about.php" class="active">About</a>
  <a href="contact.php">Contact</a>
 </div>
 <?php if ($isLoggedIn): ?>
  <a href="account.php" class="login-link">My Account</a>
 <?php else: ?>
  <a href="login.php" class="login-link">Login</a>
 <?php endif; ?>
</nav>

<main class="about-page">
 <header class="about-header">
  <div class="eyebrow">The COCORA Story</div>
  <h1>About COCORA</h1>
  <p>A premium chocolate brand created around elegance, craftsmanship and the simple pleasure of truly exceptional chocolate.</p>
  <div class="gold-line"></div>
 </header>

 <section class="about-content">
  <article class="about-story">
   <h2>Our Story</h2>
   <p>COCORA is a premium chocolate brand website designed to bring an elegant and memorable chocolate experience to every visitor.</p>
   <p>Our collection is inspired by refined taste, beautiful presentation and the belief that chocolate can turn ordinary moments into something special.</p>
   <p>Every part of COCORA reflects our passion for quality, sophistication and indulgence.</p>
  </article>

  <article class="vision-panel">
   <h2>Our Vision</h2>
   <p>Our vision is to create a premium chocolate experience where quality, elegance and customer satisfaction come together.</p>
   <p>From discovering a collection to placing an order, COCORA is designed to feel smooth, simple and luxurious.</p>
  </article>
 </section>

 <section class="feature-grid">
  <article class="feature-card"><h3>Premium Quality</h3><p>Carefully presented chocolates with a focus on a refined and luxurious experience.</p></article>
  <article class="feature-card"><h3>Crafted With Passion</h3><p>A brand built around attention to detail, elegant presentation and love for chocolate.</p></article>
  <article class="feature-card"><h3>Customer First</h3><p>A simple, user-friendly experience designed to make every interaction feel effortless.</p></article>
 </section>
</main>

<?php if ($cartCount > 0): ?>
<div class="floating-cart">
 <a href="cart.php" class="view-cart">View Cart (<?= $cartCount ?>)</a>
 <a href="checkout.php" class="checkout">Checkout</a>
</div>
<?php endif; ?>

<footer>© <?= date('Y') ?> COCORA Premium Chocolates. Crafted With Passion.</footer>
</body>
</html>
