<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$conn = $conn ?? ($mysqli ?? null);

function ensureProducts(mysqli $conn): void {
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
    $r=$conn->query("SELECT COUNT(*) AS total FROM cocora_products");
    $count=$r?(int)$r->fetch_assoc()['total']:0;
    if($count===0){
        $seed=[
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
        $s=$conn->prepare("INSERT INTO cocora_products (name,price,image,description,category,stock,is_active) VALUES (?,?,?,?,?,?,1)");
        if($s){foreach($seed as $p){$s->bind_param('sdsssi',$p[0],$p[1],$p[2],$p[3],$p[4],$p[5]);$s->execute();}$s->close();}
    }
}
if($conn instanceof mysqli) ensureProducts($conn);

$id=(int)($_GET['id']??0);
$product=null;
if($conn instanceof mysqli && $id>0){
    $stmt=$conn->prepare("SELECT * FROM cocora_products WHERE id=? AND is_active=1 LIMIT 1");
    if($stmt){$stmt->bind_param('i',$id);$stmt->execute();$r=$stmt->get_result();$product=$r?$r->fetch_assoc():null;$stmt->close();}
}
if(!$product){header("Location: products.php");exit;}

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_to_cart'])){
    if((int)$product['stock']<=0){$addError='This product is currently out of stock.';}
    else{
        if(!isset($_SESSION['cart'])||!is_array($_SESSION['cart']))$_SESSION['cart']=[];
        $key=(int)$product['id'];
        if(isset($_SESSION['cart'][$key]))$_SESSION['cart'][$key]['quantity']++;
        else $_SESSION['cart'][$key]=['id'=>$key,'name'=>$product['name'],'price'=>(float)$product['price'],'image'=>$product['image'],'quantity'=>1];
        header("Location: product.php?id=".$key."&added=1");exit;
    }
}

$cartCount=0;foreach(($_SESSION['cart']??[]) as $item)$cartCount+=(int)($item['quantity']??0);
$isLoggedIn=isset($_SESSION['customer_id']);
$added=isset($_GET['added']);
$addError=$addError??'';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=htmlspecialchars($product['name'])?> | COCORA</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}body{min-height:100vh;background:#080402;color:#ead7ad;font-family:'Cormorant Garamond',Georgia,serif}a{text-decoration:none;color:inherit}
@keyframes cocoraFadeBlur{0%{opacity:0;filter:blur(16px);transform:translateY(20px)}100%{opacity:1;filter:blur(0);transform:none}}
.navbar{height:88px;display:flex;align-items:center;justify-content:center;padding:0 5.5%;background:rgba(8,4,2,.94);border-bottom:1px solid rgba(212,175,55,.18);backdrop-filter:blur(12px);position:sticky;top:0;z-index:1000;animation:cocoraFadeBlur .8s ease both}.nav-links{display:flex;gap:42px}.nav-links a,.account-link{color:#ead7ad;font-size:21px;font-weight:500;letter-spacing:1px}.nav-links a:hover{color:#D4AF37}.account-link{position:absolute;right:5.5%;min-width:118px;text-align:center;padding:10px 18px;border:1px solid #D4AF37;color:#D4AF37}.account-link:hover{background:#D4AF37;color:#080402}
.page{width:min(1150px,88%);margin:0 auto;padding:65px 0 110px}.back-link{display:inline-block;color:#bda477;font-size:18px;margin-bottom:38px;animation:cocoraFadeBlur .8s .1s both}.product-wrapper{display:grid;grid-template-columns:1fr 1fr;gap:70px;align-items:center}.image-box{min-height:540px;border:1px solid rgba(212,175,55,.22);background:rgba(25,11,5,.52);display:flex;align-items:center;justify-content:center;padding:45px;animation:cocoraFadeBlur .9s .2s both}.image-box img{width:100%;height:100%;max-height:500px;object-fit:contain}.product-details{animation:cocoraFadeBlur .9s .35s both}.category{color:#b99a63;font-size:15px;letter-spacing:4px;text-transform:uppercase}.product-title{color:#D4AF37;font-size:clamp(48px,6vw,72px);line-height:1.05;font-weight:500;margin-top:14px}.price{color:#ead7ad;font-size:29px;margin-top:18px;font-weight:600}.divider{width:75px;height:1px;background:#D4AF37;margin:26px 0}.description{color:#c7ae85;font-size:21px;line-height:1.75}.details-row{display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin-top:35px}.detail{border-top:1px solid rgba(212,175,55,.2);padding-top:12px;color:#9f8c70;font-size:17px}.detail strong{display:block;color:#D4AF37;font-size:14px;letter-spacing:2px;margin-bottom:4px}.actions{margin-top:35px;display:flex;gap:12px}.add-button{flex:1;padding:15px;border:1px solid #D4AF37;background:#D4AF37;color:#080402;font:600 19px 'Cormorant Garamond',Georgia,serif;letter-spacing:1px;cursor:pointer;transition:.3s}.add-button:hover{background:#f0c96a;border-color:#f0c96a}.cart-button{padding:15px 20px;border:1px solid #D4AF37;color:#D4AF37;font-size:19px}.cart-button:hover{background:#D4AF37;color:#080402}.message{margin-top:18px;color:#D4AF37;font-size:18px}.error{margin-top:18px;color:#e9aa92;font-size:18px}
.floating-cart{position:fixed;right:25px;bottom:25px;display:flex;gap:10px;z-index:2000;animation:cocoraFadeBlur .7s both}.floating-cart a{padding:12px 18px;border:1px solid #D4AF37;background:#080402;color:#D4AF37;font-size:15px}.floating-cart a:hover{background:#D4AF37;color:#080402}footer{border-top:1px solid rgba(212,175,55,.16);text-align:center;padding:30px;color:#806e55}
@media(max-width:850px){.navbar{height:auto;min-height:88px;padding:15px 5%;flex-wrap:wrap;gap:15px}.nav-links{width:100%;order:2;justify-content:center;gap:22px}.nav-links a{font-size:17px}.account-link{right:5%;font-size:17px;min-width:95px;padding:8px 12px}.product-wrapper{grid-template-columns:1fr;gap:45px}.image-box{min-height:400px}.page{padding-top:45px}}@media(max-width:600px){.page{width:90%}.product-title{font-size:48px}.details-row{grid-template-columns:1fr}.actions{flex-direction:column}.floating-cart{left:12px;right:12px;bottom:12px}.floating-cart a{flex:1;text-align:center;font-size:14px}}@media(max-width:500px){.nav-links{gap:15px;overflow-x:auto;justify-content:flex-start}.nav-links a{font-size:15px;white-space:nowrap}.account-link{font-size:15px;min-width:82px}}
</style>
</head>
<body>
<nav class="navbar"><div class="nav-links"><a href="index.php">Home</a><a href="products.php">Products</a><a href="about.php">About</a><a href="contact.php">Contact</a></div><?php if($isLoggedIn):?><a href="account.php" class="account-link">My Account</a><?php else:?><a href="login.php" class="account-link">Login</a><?php endif;?></nav>
<main class="page"><a href="products.php" class="back-link">← Back To Collection</a>
<div class="product-wrapper">
<div class="image-box"><img src="assets/images/<?=htmlspecialchars($product['image'])?>" alt="<?=htmlspecialchars($product['name'])?>"></div>
<div class="product-details">
<div class="category"><?=htmlspecialchars($product['category'])?></div>
<h1 class="product-title"><?=htmlspecialchars($product['name'])?></h1>
<div class="price">₹<?=number_format((float)$product['price'])?></div>
<div class="divider"></div>
<p class="description"><?=nl2br(htmlspecialchars($product['description']))?></p>
<div class="details-row">
<div class="detail"><strong>QUALITY</strong>Premium</div>
<div class="detail"><strong>CRAFTED</strong>With Passion</div>
<div class="detail"><strong>STOCK</strong><?=((int)$product['stock']>0?'Available':'Sold Out')?></div>
</div>
<?php if($added):?><div class="message">✓ Added to your cart.</div><?php endif;?>
<?php if($addError!==''):?><div class="error"><?=htmlspecialchars($addError)?></div><?php endif;?>
<div class="actions">
<form method="post" style="flex:1"><button type="submit" name="add_to_cart" class="add-button" <?=((int)$product['stock']<=0?'disabled':'')?>><?=((int)$product['stock']<=0?'OUT OF STOCK':'ADD TO CART')?></button></form>
<?php if($cartCount>0):?><a href="cart.php" class="cart-button">VIEW CART (<?=$cartCount?>)</a><?php endif;?>
</div>
</div></div></main>
<?php if($cartCount>0):?><div class="floating-cart"><a href="cart.php">VIEW CART (<?=$cartCount?>)</a><a href="checkout.php">CHECKOUT</a></div><?php endif;?>
<footer>© <?=date('Y')?> COCORA Premium Chocolates. Crafted With Passion.</footer>
</body>
</html>
