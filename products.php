<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$conn = $conn ?? ($mysqli ?? null);

function ensureCocoraProducts(mysqli $conn): void {
    $conn->query("
        CREATE TABLE IF NOT EXISTS cocora_products (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(150) NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            image VARCHAR(255) NOT NULL DEFAULT '',
            description TEXT NOT NULL,
            category VARCHAR(100) NOT NULL DEFAULT 'COCORA COLLECTION',
            stock INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $r = $conn->query("SELECT COUNT(*) AS total FROM cocora_products");
    $count = $r ? (int)$r->fetch_assoc()['total'] : 0;
    if ($count === 0) {
        $seed = [
            ['Almond Indulgence',699,'Almond-indulgence.png','A luxurious almond chocolate creation with a rich, elegant finish.','COCORA COLLECTION',50],
            ['Caramel Crème',749,'Caramel-crème.png','Silky premium chocolate with smooth caramel notes for a refined indulgence.','COCORA COLLECTION',50],
            ['Dark Collection',799,'Dark-collection.png','Deep, sophisticated dark chocolate crafted for an intense cocoa experience.','COCORA COLLECTION',50],
            ['Golden Truffle',899,'Golden-truffle.png','An elegant golden truffle creation with a rich and decadent chocolate centre.','COCORA COLLECTION',50],
            ['Hazelnut Praline',749,'Hazelnut-praline.png','Premium chocolate layered with delicate hazelnut praline for a smooth finish.','COCORA COLLECTION',50],
            ['Luxury Gift',1499,'Luxury-gift.png','A premium COCORA gift selection designed for celebrations and special moments.','COCORA COLLECTION',50],
            ['Mocha Bliss',699,'Mocha-bliss.png','A delightful combination of premium chocolate and smooth mocha notes.','COCORA COLLECTION',50],
            ['Pistachio Dream',849,'Pistachio-dream.png','Premium chocolate paired with the delicate richness of pistachio.','COCORA COLLECTION',50],
            ['Raspberry Silk',799,'Raspberry-silk.png','Smooth luxurious chocolate complemented by refined raspberry notes.','COCORA COLLECTION',50],
            ['Velvet Cocoa',699,'Velvet-cocoa.png','A beautifully smooth cocoa experience with a velvety chocolate character.','COCORA COLLECTION',50]
        ];
        $stmt = $conn->prepare("INSERT INTO cocora_products (name,price,image,description,category,stock,is_active) VALUES (?,?,?,?,?,?,1)");
        if ($stmt) {
            foreach ($seed as $p) {
                $stmt->bind_param('sdsssi',$p[0],$p[1],$p[2],$p[3],$p[4],$p[5]);
                $stmt->execute();
            }
            $stmt->close();
        }
    }
}

if ($conn instanceof mysqli) ensureCocoraProducts($conn);

/* AJAX add-to-cart uses the same live database product data. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_add_to_cart'])) {
    header('Content-Type: application/json');

    $productId = (int)($_POST['product_id'] ?? 0);
    $product = null;

    if ($conn instanceof mysqli) {
        $stmt = $conn->prepare("SELECT * FROM cocora_products WHERE id=? AND is_active=1 LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i',$productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result ? $result->fetch_assoc() : null;
            $stmt->close();
        }
    }

    if (!$product) {
        echo json_encode(['success'=>false,'message'=>'Product not found or unavailable.']);
        exit;
    }

    if ((int)$product['stock'] <= 0) {
        echo json_encode(['success'=>false,'message'=>'This product is currently out of stock.']);
        exit;
    }

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];

    $key = (int)$product['id'];
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity']++;
    } else {
        $_SESSION['cart'][$key] = [
            'id'=>(int)$product['id'],
            'name'=>$product['name'],
            'price'=>(float)$product['price'],
            'image'=>$product['image'],
            'quantity'=>1
        ];
    }

    $cartCount = 0;
    foreach ($_SESSION['cart'] as $item) $cartCount += (int)($item['quantity'] ?? 0);

    echo json_encode(['success'=>true,'product_name'=>$product['name'],'cart_count'=>$cartCount]);
    exit;
}

$cartCount = 0;
foreach (($_SESSION['cart'] ?? []) as $item) $cartCount += (int)($item['quantity'] ?? 0);
$isLoggedIn = isset($_SESSION['customer_id']);

$products = [];
if ($conn instanceof mysqli) {
    $result = $conn->query("SELECT * FROM cocora_products WHERE is_active=1 ORDER BY id ASC");
    if ($result) while ($row=$result->fetch_assoc()) $products[]=$row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Products | COCORA Premium Chocolates</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}body{min-height:100vh;background:#080402;color:#ead7ad;font-family:'Cormorant Garamond',Georgia,serif;overflow-x:hidden}a{text-decoration:none;color:inherit}
@keyframes cocoraFadeBlur{0%{opacity:0;filter:blur(16px);transform:translateY(20px)}100%{opacity:1;filter:blur(0);transform:translateY(0)}}
.navbar{height:88px;display:flex;align-items:center;justify-content:center;padding:0 5.5%;background:rgba(8,4,2,.94);border-bottom:1px solid rgba(212,175,55,.18);backdrop-filter:blur(12px);position:sticky;top:0;z-index:1000;animation:cocoraFadeBlur .8s ease both}.nav-links{display:flex;gap:42px;align-items:center}.nav-links a,.login-link{color:#ead7ad;font-size:21px;font-weight:500;letter-spacing:1px;transition:.3s}.nav-links a:hover,.nav-links a.active{color:#D4AF37}.nav-links a.active{border-bottom:1px solid #D4AF37;padding-bottom:6px}.login-link{position:absolute;right:5.5%;min-width:118px;text-align:center;padding:10px 18px;border:1px solid #D4AF37;color:#D4AF37}.login-link:hover{background:#D4AF37;color:#080402}
.products-page{width:min(1200px,88%);margin:0 auto;padding:82px 0 110px}.products-header{text-align:center;margin-bottom:65px}.eyebrow{color:#b99a63;font-size:16px;letter-spacing:5px;text-transform:uppercase;animation:cocoraFadeBlur .9s .15s ease both}.products-header h1{color:#D4AF37;font-size:clamp(52px,7vw,78px);font-weight:500;letter-spacing:3px;line-height:1.05;animation:cocoraFadeBlur .9s .3s ease both}.products-header p{max-width:720px;margin:24px auto 0;color:#d5c09a;font-size:21px;line-height:1.7;animation:cocoraFadeBlur .9s .45s ease both}.gold-line{width:65px;height:1px;background:#D4AF37;margin:30px auto 0;animation:cocoraFadeBlur .9s .6s ease both}
.products-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px}.product-card{background:rgba(25,11,5,.62);border:1px solid rgba(212,175,55,.25);backdrop-filter:blur(8px);overflow:hidden;cursor:pointer;transition:.3s;opacity:0;animation:cocoraFadeBlur .9s ease both}.product-card:nth-child(1){animation-delay:.75s}.product-card:nth-child(2){animation-delay:.85s}.product-card:nth-child(3){animation-delay:.95s}.product-card:nth-child(4){animation-delay:1.05s}.product-card:nth-child(5){animation-delay:1.15s}.product-card:nth-child(6){animation-delay:1.25s}.product-card:nth-child(7){animation-delay:1.35s}.product-card:nth-child(8){animation-delay:1.45s}.product-card:nth-child(9){animation-delay:1.55s}.product-card:nth-child(10){animation-delay:1.65s}.product-card:hover{transform:translateY(-7px);border-color:rgba(212,175,55,.65);box-shadow:0 20px 45px rgba(0,0,0,.4)}.product-image{height:255px;padding:20px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.015);border-bottom:1px solid rgba(212,175,55,.13)}.product-image img{width:100%;height:100%;object-fit:contain;transition:transform .4s}.product-card:hover .product-image img{transform:scale(1.05)}.product-info{padding:25px 22px 24px;text-align:center}.product-name{min-height:55px;display:flex;align-items:center;justify-content:center;color:#D4AF37;font-size:27px;line-height:1.12;font-weight:500}.product-price{margin-top:8px;color:#d5c09a;font-size:22px;font-weight:600}.product-category{margin-top:6px;color:#806e55;font-size:14px;letter-spacing:1px}.add-cart-form{margin-top:19px}.add-cart-button{width:100%;min-height:46px;border:1px solid #D4AF37;background:transparent;color:#D4AF37;cursor:pointer;font-family:'Cormorant Garamond',Georgia,serif;font-size:17px;font-weight:600;letter-spacing:1px;transition:.3s}.add-cart-button:hover{background:#D4AF37;color:#080402}
.floating-cart{position:fixed;right:25px;bottom:25px;display:flex;gap:10px;z-index:2000;animation:cocoraFadeBlur .7s ease both}.floating-cart.hidden{display:none}.floating-cart a{padding:12px 18px;border:1px solid #D4AF37;background:#080402;color:#D4AF37;font-size:15px;letter-spacing:1px;transition:.3s}.floating-cart a:hover{background:#D4AF37;color:#080402}
footer{border-top:1px solid rgba(212,175,55,.16);text-align:center;padding:30px 20px;color:#806e55;font-size:16px}
@media(max-width:1050px){.products-grid{grid-template-columns:repeat(2,1fr)}.nav-links{gap:28px}}@media(max-width:850px){.navbar{height:auto;min-height:88px;padding:15px 5%;flex-wrap:wrap;gap:15px}.nav-links{width:100%;order:2;justify-content:center;gap:22px}.nav-links a{font-size:17px}.login-link{right:5%;font-size:17px;min-width:95px;padding:8px 12px}.products-page{padding-top:55px}}@media(max-width:600px){.products-page{width:90%;padding-bottom:90px}.products-header h1{font-size:48px}.products-header p{font-size:18px}.products-grid{grid-template-columns:1fr;gap:20px}.product-image{height:280px}.product-name{font-size:25px}.floating-cart{left:12px;right:12px;bottom:12px}.floating-cart a{flex:1;text-align:center;padding:12px 8px;font-size:14px}}@media(max-width:500px){.nav-links{gap:15px;overflow-x:auto;justify-content:flex-start;padding:0 2px}.nav-links a{font-size:15px;white-space:nowrap}.login-link{font-size:15px;min-width:82px}}
</style>
</head>
<body>
<nav class="navbar"><div class="nav-links"><a href="index.php">Home</a><a href="products.php" class="active">Products</a><a href="about.php">About</a><a href="contact.php">Contact</a></div><?php if($isLoggedIn):?><a href="account.php" class="login-link">My Account</a><?php else:?><a href="login.php" class="login-link">Login</a><?php endif;?></nav>
<main class="products-page">
<header class="products-header"><div class="eyebrow">The COCORA Collection</div><h1>Our Collection</h1><p>Discover the COCORA collection of premium chocolates, crafted for elegant moments and unforgettable indulgence.</p><div class="gold-line"></div></header>
<section class="products-grid">
<?php foreach($products as $product): ?>
<article class="product-card" onclick="openProduct(<?= (int)$product['id'] ?>)" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openProduct(<?= (int)$product['id'] ?>)}">
<div class="product-image"><img src="assets/images/<?=htmlspecialchars($product['image'])?>" alt="<?=htmlspecialchars($product['name'])?>"></div>
<div class="product-info"><div class="product-name"><?=htmlspecialchars($product['name'])?></div><div class="product-price">₹<?=number_format((float)$product['price'])?></div><div class="product-category"><?=htmlspecialchars($product['category'])?></div>
<form class="add-cart-form" data-product-id="<?= (int)$product['id'] ?>" onclick="event.stopPropagation()"><button type="submit" class="add-cart-button">ADD TO CART</button></form></div>
</article>
<?php endforeach; ?>
</section>
</main>
<div class="floating-cart <?=$cartCount>0?'':'hidden'?>" id="floatingCart"><a href="cart.php">VIEW CART (<span id="floatingCartCount"><?=$cartCount?></span>)</a><a href="checkout.php">CHECKOUT</a></div>
<footer>© <?=date('Y')?> COCORA Premium Chocolates. Crafted With Passion.</footer>
<script>
function openProduct(id){window.location.href='product.php?id='+id}
const forms=document.querySelectorAll('.add-cart-form'),floatingCart=document.getElementById('floatingCart'),floatingCartCount=document.getElementById('floatingCartCount');
forms.forEach(function(form){form.addEventListener('submit',function(event){event.preventDefault();event.stopPropagation();const fd=new FormData();fd.append('ajax_add_to_cart','1');fd.append('product_id',form.dataset.productId);fetch('products.php',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{if(!data.success){alert(data.message||'Unable to add product.');return}floatingCartCount.textContent=data.cart_count;floatingCart.classList.remove('hidden')}).catch(()=>alert('Something went wrong while adding the product.'))})});
</script>
</body>
</html>
