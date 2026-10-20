<?php
session_start();

/* Existing database connection */
$dbFile = __DIR__ . '/includes/db.php';
if (is_file($dbFile)) {
    require_once $dbFile;
}
$conn = $conn ?? ($mysqli ?? null);

/* =========================================================
   COCORA ACCOUNT PAGE
   Profile picture is stored inside the existing /profile folder.
   No database structure is changed.
   ========================================================= */

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customerId     = (int)$_SESSION['customer_id'];
$customerName   = $_SESSION['customer_name'] ?? 'COCORA Customer';
$customerEmail  = $_SESSION['customer_email'] ?? 'Not available';
$customerMobile = $_SESSION['customer_mobile'] ?? 'Not available';

$profileDir = __DIR__ . DIRECTORY_SEPARATOR . 'profile';
$profileWeb = 'profile/';

$uploadMessage = '';
$uploadType = '';

/* Create the existing profile folder if it is missing. */
if (!is_dir($profileDir)) {
    @mkdir($profileDir, 0755, true);
}

/* =========================================================
   PROFILE PICTURE UPLOAD
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_profile_picture'])) {

    if (!isset($_FILES['profile_picture']) ||
        $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {

        $uploadMessage = 'Please choose a profile picture.';
        $uploadType = 'error';

    } else {

        $file = $_FILES['profile_picture'];

        if ($file['size'] > 5 * 1024 * 1024) {
            $uploadMessage = 'Profile picture must be 5 MB or smaller.';
            $uploadType = 'error';

        } else {

            $imageInfo = @getimagesize($file['tmp_name']);

            if ($imageInfo === false) {
                $uploadMessage = 'Please select a valid image file.';
                $uploadType = 'error';

            } else {

                $allowedMimeTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                $mimeType = $imageInfo['mime'] ?? '';

                if (!isset($allowedMimeTypes[$mimeType])) {
                    $uploadMessage = 'Only JPG, PNG and WEBP images are allowed.';
                    $uploadType = 'error';

                } else {

                    $extension = $allowedMimeTypes[$mimeType];
                    $newFileName = 'customer_' . $customerId . '.' . $extension;
                    $destination = $profileDir . DIRECTORY_SEPARATOR . $newFileName;

                    /* Remove older profile-picture formats for this customer. */
                    foreach (['jpg', 'jpeg', 'png', 'webp'] as $oldExtension) {
                        $oldFile = $profileDir . DIRECTORY_SEPARATOR . 'customer_' . $customerId . '.' . $oldExtension;

                        if ($oldFile !== $destination && is_file($oldFile)) {
                            @unlink($oldFile);
                        }
                    }

                    if (@move_uploaded_file($file['tmp_name'], $destination)) {
                        $_SESSION['customer_profile'] = $profileWeb . $newFileName;

                        $uploadMessage = 'Profile picture updated successfully.';
                        $uploadType = 'success';
                    } else {
                        $uploadMessage = 'Unable to save the profile picture. Please try again.';
                        $uploadType = 'error';
                    }
                }
            }
        }
    }
}

/* =========================================================
   REMOVE PROFILE PICTURE
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_profile_picture'])) {

    foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
        $oldFile = $profileDir . DIRECTORY_SEPARATOR . 'customer_' . $customerId . '.' . $extension;

        if (is_file($oldFile)) {
            @unlink($oldFile);
        }
    }

    unset($_SESSION['customer_profile']);

    $uploadMessage = 'Profile picture removed.';
    $uploadType = 'success';
}

/* =========================================================
   FIND CURRENT PROFILE PICTURE
   ========================================================= */
$profilePicture = '';

if (!empty($_SESSION['customer_profile'])) {
    $sessionProfile = $_SESSION['customer_profile'];
    $sessionProfilePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $sessionProfile);

    if (is_file($sessionProfilePath)) {
        $profilePicture = $sessionProfile;
    }
}

/* If session does not contain the path, find it from the profile folder. */
if ($profilePicture === '') {
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
        $possibleFile = $profileDir . DIRECTORY_SEPARATOR . 'customer_' . $customerId . '.' . $extension;

        if (is_file($possibleFile)) {
            $profilePicture = $profileWeb . 'customer_' . $customerId . '.' . $extension;
            $_SESSION['customer_profile'] = $profilePicture;
            break;
        }
    }
}

/* Cart count */
$cartCount = 0;

if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)($item['quantity'] ?? 0);
    }
}

$customerInitial = strtoupper(substr(trim($customerName), 0, 1));

/* =========================================================
   ORDER HISTORY
   Reads orders already created by payment.php.
   The existing database column names are detected automatically.
   ========================================================= */
$orderHistory = [];
$orderHistoryError = '';

if ($conn instanceof mysqli) {

    /* Get actual columns from orders table. */
    $orderColumns = [];
    $orderColumnsResult = @$conn->query("SHOW COLUMNS FROM `orders`");

    if ($orderColumnsResult) {
        while ($column = $orderColumnsResult->fetch_assoc()) {
            $orderColumns[] = $column['Field'];
        }
    }

    /* Detect the real primary/order ID column. */
    $findActualColumn = function(array $columns, array $candidates) {
        foreach ($candidates as $candidate) {
            foreach ($columns as $actualColumn) {
                if (strcasecmp($actualColumn, $candidate) === 0) {
                    return $actualColumn;
                }
            }
        }
        return null;
    };

    $orderIdColumn = $findActualColumn(
        $orderColumns,
        ['order_id', 'OrderID', 'orderid', 'orderId', 'id', 'ID']
    );

    $customerIdColumn = $findActualColumn(
        $orderColumns,
        ['customer_id', 'CustomerID', 'customerid', 'customerId']
    );

    $orderDateColumn = $findActualColumn(
        $orderColumns,
        ['created_at', 'order_date', 'created_date', 'date', 'createdAt', 'orderDate']
    );

    $totalColumn = $findActualColumn(
        $orderColumns,
        ['total_amount', 'total', 'grand_total', 'amount']
    );

    $paymentMethodColumn = $findActualColumn(
        $orderColumns,
        ['payment_method', 'paymentMethod', 'payment_type']
    );

    $paymentStatusColumn = $findActualColumn(
        $orderColumns,
        ['payment_status', 'paymentStatus']
    );

    $orderStatusColumn = $findActualColumn(
        $orderColumns,
        ['order_status', 'orderStatus', 'status']
    );

    if ($orderIdColumn === null || $customerIdColumn === null) {
        $orderHistoryError = 'Order history database fields could not be detected.';
    } else {

        $safeOrderIdColumn = $conn->real_escape_string($orderIdColumn);
        $safeCustomerIdColumn = $conn->real_escape_string($customerIdColumn);

        $selectParts = [
            "`{$safeOrderIdColumn}` AS history_order_id"
        ];

        if ($totalColumn !== null) {
            $selectParts[] = "`" . $conn->real_escape_string($totalColumn) . "` AS total_amount";
        } else {
            $selectParts[] = "0 AS total_amount";
        }

        if ($paymentMethodColumn !== null) {
            $selectParts[] = "`" . $conn->real_escape_string($paymentMethodColumn) . "` AS payment_method";
        } else {
            $selectParts[] = "'' AS payment_method";
        }

        if ($paymentStatusColumn !== null) {
            $selectParts[] = "`" . $conn->real_escape_string($paymentStatusColumn) . "` AS payment_status";
        } else {
            $selectParts[] = "'' AS payment_status";
        }

        if ($orderStatusColumn !== null) {
            $selectParts[] = "`" . $conn->real_escape_string($orderStatusColumn) . "` AS order_status";
        } else {
            $selectParts[] = "'' AS order_status";
        }

        if ($orderDateColumn !== null) {
            $selectParts[] = "`" . $conn->real_escape_string($orderDateColumn) . "` AS order_date";
        } else {
            $selectParts[] = "NULL AS order_date";
        }

        $orderSelect = "SELECT " . implode(', ', $selectParts)
                     . " FROM `orders`"
                     . " WHERE `{$safeCustomerIdColumn}` = ?"
                     . " ORDER BY `{$safeOrderIdColumn}` DESC";

        $orderHistoryStmt = @$conn->prepare($orderSelect);

        if ($orderHistoryStmt) {
            $orderHistoryStmt->bind_param('i', $customerId);

            if ($orderHistoryStmt->execute()) {
                $orderHistoryResult = $orderHistoryStmt->get_result();

                /* Detect the real order reference column in order_items. */
                $itemColumns = [];
                $itemColumnsResult = @$conn->query("SHOW COLUMNS FROM `order_items`");

                if ($itemColumnsResult) {
                    while ($column = $itemColumnsResult->fetch_assoc()) {
                        $itemColumns[] = $column['Field'];
                    }
                }

                $itemOrderIdColumn = $findActualColumn(
                    $itemColumns,
                    ['order_id', 'OrderID', 'orderid', 'orderId', 'orderID']
                );

                /* Fallback: find any column whose name contains order + id. */
                if ($itemOrderIdColumn === null) {
                    foreach ($itemColumns as $actualColumn) {
                        $normalized = strtolower(str_replace(['_', '-'], '', $actualColumn));
                        if (strpos($normalized, 'order') !== false && strpos($normalized, 'id') !== false) {
                            $itemOrderIdColumn = $actualColumn;
                            break;
                        }
                    }
                }

                $itemNameColumn = $findActualColumn(
                    $itemColumns,
                    ['product_name', 'ProductName', 'productname', 'name', 'Name']
                );
                $itemPriceColumn = $findActualColumn(
                    $itemColumns,
                    ['price', 'Price', 'product_price', 'ProductPrice']
                );
                $itemQuantityColumn = $findActualColumn(
                    $itemColumns,
                    ['quantity', 'Quantity', 'qty', 'Qty']
                );

                while ($orderRow = $orderHistoryResult->fetch_assoc()) {
                    $orderRow['items'] = [];
                    $historyOrderId = (int)$orderRow['history_order_id'];

                    if ($itemOrderIdColumn !== null &&
                        $itemNameColumn !== null &&
                        $itemPriceColumn !== null &&
                        $itemQuantityColumn !== null) {

                        $safeItemOrderId = $conn->real_escape_string($itemOrderIdColumn);
                        $safeItemName = $conn->real_escape_string($itemNameColumn);
                        $safeItemPrice = $conn->real_escape_string($itemPriceColumn);
                        $safeItemQuantity = $conn->real_escape_string($itemQuantityColumn);

                        $itemSql = "SELECT `{$safeItemName}` AS product_name,
                                           `{$safeItemPrice}` AS price,
                                           `{$safeItemQuantity}` AS quantity
                                    FROM `order_items`
                                    WHERE `{$safeItemOrderId}` = ?";

                        $itemStmt = @$conn->prepare($itemSql);

                        if ($itemStmt) {
                            $itemStmt->bind_param('i', $historyOrderId);

                            if ($itemStmt->execute()) {
                                $itemResult = $itemStmt->get_result();

                                while ($itemRow = $itemResult->fetch_assoc()) {
                                    $orderRow['items'][] = $itemRow;
                                }
                            }

                            $itemStmt->close();
                        }
                    }

                    $orderHistory[] = $orderRow;
                }
            } else {
                $orderHistoryError = 'Unable to load order history right now.';
            }

            $orderHistoryStmt->close();
        } else {
            $orderHistoryError = 'Unable to load order history right now.';
        }
    }
} else {
    $orderHistoryError = 'Database connection is not available.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Account | COCORA Premium Chocolates</title>

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

/* =========================
   ANIMATION
   ========================= */
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
   ACCOUNT PAGE
   ========================= */
.account-page{
    width:min(1120px,90%);
    margin:0 auto;
    padding:78px 0 120px;
}

.account-header{
    text-align:center;
    animation:cocoraFadeBlur .8s .1s ease both;
}

.eyebrow,
.card-label,
.small-label{
    color:#b99752;
    text-transform:uppercase;
    letter-spacing:4px;
    font-size:14px;
    font-weight:600;
}

.account-header h1{
    color:#D4AF37;
    font-size:64px;
    line-height:1;
    font-weight:600;
    letter-spacing:2px;
    margin:14px 0 18px;
}

.account-header p{
    max-width:700px;
    margin:0 auto;
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

/* =========================
   PROFILE HERO
   ========================= */
.profile-card{
    margin-top:48px;
    padding:42px;
    border:1px solid rgba(212,175,55,.28);
    background:rgba(43,19,8,.45);
    display:flex;
    align-items:center;
    gap:38px;
    animation:cocoraFadeBlur .8s .2s ease both;
}

.profile-picture-area{
    flex:0 0 auto;
    text-align:center;
}

.profile-picture,
.profile-placeholder{
    width:150px;
    height:150px;
    border-radius:50%;
    border:1px solid #D4AF37;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    background:#140803;
    color:#D4AF37;
    box-shadow:0 0 0 7px rgba(212,175,55,.04);
}

.profile-picture{
    object-fit:cover;
}

.profile-placeholder{
    font-size:62px;
    font-weight:600;
    letter-spacing:2px;
}

.change-picture{
    display:inline-block;
    margin-top:18px;
    color:#D4AF37;
    font-size:16px;
    letter-spacing:1px;
    cursor:pointer;
    transition:.3s ease;
}

.change-picture:hover{
    color:#f0c96a;
}

.profile-details{
    flex:1;
}

.profile-details .small-label{
    margin-bottom:8px;
}

.profile-details h2{
    color:#D4AF37;
    font-size:42px;
    font-weight:600;
    letter-spacing:1px;
    margin-bottom:7px;
}

.profile-details p{
    color:#a9957b;
    font-size:18px;
    line-height:1.6;
}

.profile-details .profile-note{
    margin-top:15px;
    color:#7f705f;
    font-size:15px;
    letter-spacing:.5px;
}

.profile-upload-form{
    flex:0 0 auto;
}

.file-input{
    display:none;
}

.upload-button{
    display:inline-block;
    padding:13px 24px;
    border:1px solid #D4AF37;
    background:#D4AF37;
    color:#080402;
    font-size:17px;
    font-weight:600;
    letter-spacing:1px;
    cursor:pointer;
    transition:.3s ease;
}

.upload-button:hover{
    background:transparent;
    color:#D4AF37;
}

.upload-message{
    margin-top:22px;
    text-align:center;
    font-size:17px;
    letter-spacing:.5px;
    animation:cocoraFadeBlur .6s ease both;
}

.upload-message.success{
    color:#d6b96c;
}

.upload-message.error{
    color:#d99a8d;
}

/* =========================
   ACCOUNT GRID
   ========================= */
.account-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:22px;
    margin-top:24px;
}

.account-card{
    padding:34px;
    border:1px solid rgba(212,175,55,.24);
    background:rgba(43,19,8,.35);
    animation:cocoraFadeBlur .8s .35s ease both;
}

.account-card:nth-child(2){
    animation-delay:.45s;
}

.card-label{
    margin-bottom:24px;
}

.info-row{
    padding:17px 0;
    border-bottom:1px solid rgba(212,175,55,.12);
}

.info-row:first-of-type{
    padding-top:0;
}

.info-row:last-child{
    border-bottom:0;
    padding-bottom:0;
}

.info-title{
    color:#8f7d66;
    font-size:15px;
    text-transform:uppercase;
    letter-spacing:2px;
    margin-bottom:5px;
}

.info-value{
    color:#ead7ad;
    font-size:20px;
    overflow-wrap:anywhere;
}

.actions{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
}

.action-button{
    display:inline-block;
    padding:13px 22px;
    border:1px solid #D4AF37;
    color:#D4AF37;
    font-size:17px;
    font-weight:600;
    letter-spacing:1px;
    transition:.3s ease;
}

.action-button:hover{
    background:#D4AF37;
    color:#080402;
    transform:translateY(-2px);
}

.action-button.primary{
    background:#D4AF37;
    color:#080402;
}

.action-button.primary:hover{
    background:transparent;
    color:#D4AF37;
}

/* =========================
   REMOVE PHOTO
   ========================= */
.remove-photo-form{
    margin-top:12px;
}

.remove-button{
    border:0;
    background:transparent;
    color:#8f7d66;
    font-size:15px;
    letter-spacing:1px;
    cursor:pointer;
    transition:.3s ease;
}

.remove-button:hover{
    color:#D4AF37;
}

/* =========================
   ORDER HISTORY
   ========================= */
.order-history{
    margin-top:24px;
    padding:34px;
    border:1px solid rgba(212,175,55,.24);
    background:rgba(43,19,8,.35);
    animation:cocoraFadeBlur .8s .55s ease both;
}

.order-history-title{
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap:20px;
    margin-bottom:24px;
}

.order-history-title .card-label{
    margin-bottom:0;
}

.order-history-count{
    color:#8f7d66;
    font-size:15px;
    letter-spacing:1px;
}

.order-card{
    padding:24px 0;
    border-top:1px solid rgba(212,175,55,.14);
}

.order-card:first-of-type{
    border-top:0;
    padding-top:0;
}

.order-top{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:20px;
    margin-bottom:18px;
}

.order-number{
    color:#D4AF37;
    font-size:24px;
    font-weight:600;
    letter-spacing:1px;
}

.order-date{
    color:#8f7d66;
    font-size:16px;
    margin-top:4px;
}

.order-total{
    color:#ead7ad;
    font-size:24px;
    font-weight:600;
    text-align:right;
}

.order-meta{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-bottom:20px;
}

.order-badge{
    display:inline-block;
    padding:7px 12px;
    border:1px solid rgba(212,175,55,.28);
    color:#c7ae85;
    font-size:14px;
    letter-spacing:.8px;
}

.order-items{
    border:1px solid rgba(212,175,55,.12);
    background:rgba(8,4,2,.32);
}

.order-item{
    display:grid;
    grid-template-columns:1fr auto auto;
    gap:20px;
    align-items:center;
    padding:15px 16px;
    border-bottom:1px solid rgba(212,175,55,.10);
}

.order-item:last-child{
    border-bottom:0;
}

.order-item-name{
    color:#ead7ad;
    font-size:18px;
}

.order-item-qty{
    color:#8f7d66;
    font-size:16px;
}

.order-item-price{
    color:#D4AF37;
    font-size:18px;
    min-width:90px;
    text-align:right;
}

.no-orders{
    padding:30px 0 8px;
    color:#8f7d66;
    font-size:18px;
    line-height:1.7;
}

.order-history-error{
    color:#d99a8d;
    font-size:17px;
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

/* =========================
   FOOTER
   ========================= */
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
.order-top{
    flex-direction:column;
}

.order-total{
    text-align:left;
}

@media(max-width:900px){
    .profile-card{
        flex-wrap:wrap;
        justify-content:center;
        text-align:center;
    }

    .profile-details{
        flex:1 1 100%;
    }

    .profile-upload-form{
        flex:0 0 auto;
    }
}

@media(max-width:800px){
    .account-grid{
        grid-template-columns:1fr;
    }
}

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

    .account-page{
        width:90%;
        padding:60px 0 110px;
    }

    .account-header h1{
        font-size:48px;
    }

    .account-header p{
        font-size:18px;
    }

    .profile-card,
    .account-card{
        padding:28px 24px;
    }

    .profile-picture,
    .profile-placeholder{
        width:125px;
        height:125px;
    }

    .profile-placeholder{
        font-size:50px;
    }

    .profile-details h2{
        font-size:34px;
    }

    .info-value{
        font-size:18px;
    }

    .order-history{
        padding:28px 22px;
    }

    .order-item{
        grid-template-columns:1fr auto;
        gap:8px 14px;
    }

    .order-item-price{
        grid-column:2;
        grid-row:1 / span 2;
    }

    .order-item-qty{
        grid-column:1;
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

    .account-header h1{
        font-size:42px;
    }

    .profile-card{
        gap:22px;
    }

    .profile-upload-form,
    .upload-button{
        width:100%;
        text-align:center;
    }

    .actions{
        flex-direction:column;
    }

    .action-button{
        text-align:center;
        width:100%;
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

<main class="account-page">

    <header class="account-header">
        <div class="eyebrow">COCORA MEMBERSHIP</div>
        <h1>My Account</h1>
        <p>
            Welcome to your personal COCORA space.
            Manage your details, profile and collection in one place.
        </p>
        <div class="gold-line"></div>
    </header>

    <section class="profile-card">

        <div class="profile-picture-area">

            <?php if ($profilePicture !== ''): ?>
                <img
                    src="<?= htmlspecialchars($profilePicture) ?>"
                    alt="Profile Picture"
                    class="profile-picture"
                >
            <?php else: ?>
                <div class="profile-placeholder">
                    <?= htmlspecialchars($customerInitial) ?>
                </div>
            <?php endif; ?>

            <label for="profile_picture" class="change-picture">
                Change Profile Picture
            </label>

            <?php if ($profilePicture !== ''): ?>
                <form method="post" class="remove-photo-form">
                    <button type="submit" name="remove_profile_picture" class="remove-button">
                        Remove Picture
                    </button>
                </form>
            <?php endif; ?>

        </div>

        <div class="profile-details">
            <div class="small-label">Your COCORA Profile</div>
            <h2><?= htmlspecialchars($customerName) ?></h2>
            <p><?= htmlspecialchars($customerEmail) ?></p>
            <div class="profile-note">
                JPG, PNG or WEBP · Maximum 5 MB
            </div>
        </div>

        <form
            method="post"
            enctype="multipart/form-data"
            class="profile-upload-form"
        >
            <input
                type="file"
                name="profile_picture"
                id="profile_picture"
                class="file-input"
                accept="image/jpeg,image/png,image/webp"
                onchange="this.form.submit()"
            >

            <input type="hidden" name="upload_profile_picture" value="1">

            <label for="profile_picture" class="upload-button">
                <?= $profilePicture !== '' ? 'CHANGE PHOTO' : 'ADD PHOTO' ?>
            </label>
        </form>

    </section>

    <?php if ($uploadMessage !== ''): ?>
        <div class="upload-message <?= $uploadType ?>">
            <?= htmlspecialchars($uploadMessage) ?>
        </div>
    <?php endif; ?>

    <section class="account-grid">

        <article class="account-card">
            <div class="card-label">Personal Information</div>

            <div class="info-row">
                <div class="info-title">Full Name</div>
                <div class="info-value"><?= htmlspecialchars($customerName) ?></div>
            </div>

            <div class="info-row">
                <div class="info-title">Email</div>
                <div class="info-value"><?= htmlspecialchars($customerEmail) ?></div>
            </div>

            <div class="info-row">
                <div class="info-title">Mobile</div>
                <div class="info-value"><?= htmlspecialchars($customerMobile) ?></div>
            </div>
        </article>

        <article class="account-card">
            <div class="card-label">Quick Actions</div>

            <div class="actions">
                <a href="products.php" class="action-button primary">
                    Explore Collection
                </a>

                <a href="edit-profile.php" class="action-button">
                    Edit Profile
                </a>

                <a href="logout.php" class="action-button">
                    Logout
                </a>
            </div>
        </article>

    </section>

    <section class="order-history">
        <div class="order-history-title">
            <div class="card-label">Order History</div>

            <?php if (!empty($orderHistory)): ?>
                <div class="order-history-count">
                    <?= count($orderHistory) ?> <?= count($orderHistory) === 1 ? 'ORDER' : 'ORDERS' ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($orderHistoryError !== ''): ?>

            <div class="order-history-error">
                <?= htmlspecialchars($orderHistoryError) ?>
            </div>

        <?php elseif (empty($orderHistory)): ?>

            <div class="no-orders">
                You have not placed any COCORA orders yet.
                Your completed orders will appear here automatically.
            </div>

        <?php else: ?>

            <?php foreach ($orderHistory as $order): ?>
                <article class="order-card">

                    <div class="order-top">
                        <div>
                            <div class="order-number">
                                ORDER #<?= (int)$order['history_order_id'] ?>
                            </div>

                            <?php if (!empty($order['order_date'])): ?>
                                <div class="order-date">
                                    <?= htmlspecialchars(date('d M Y, h:i A', strtotime($order['order_date']))) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="order-total">
                            ₹<?= number_format((float)$order['total_amount'], 2) ?>
                        </div>
                    </div>

                    <div class="order-meta">
                        <span class="order-badge">
                            PAYMENT: <?= htmlspecialchars((string)$order['payment_method']) ?>
                        </span>

                        <span class="order-badge">
                            PAYMENT STATUS: <?= htmlspecialchars((string)$order['payment_status']) ?>
                        </span>

                        <span class="order-badge">
                            ORDER STATUS: <?= htmlspecialchars((string)$order['order_status']) ?>
                        </span>
                    </div>

                    <div class="order-items">
                        <?php if (!empty($order['items'])): ?>

                            <?php foreach ($order['items'] as $item): ?>
                                <div class="order-item">
                                    <div class="order-item-name">
                                        <?= htmlspecialchars((string)$item['product_name']) ?>
                                    </div>

                                    <div class="order-item-qty">
                                        × <?= (int)$item['quantity'] ?>
                                    </div>

                                    <div class="order-item-price">
                                        ₹<?= number_format((float)$item['price'], 2) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        <?php else: ?>

                            <div class="order-item">
                                <div class="order-item-name">
                                    COCORA Chocolate
                                </div>
                            </div>

                        <?php endif; ?>
                    </div>

                </article>
            <?php endforeach; ?>

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
