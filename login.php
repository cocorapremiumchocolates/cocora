<?php
session_start();

/* =========================================================
   COCORA LOGIN
   Authentication is attempted through the existing
   includes/db.php connection when available.
   ========================================================= */

if (isset($_SESSION['customer_id'])) {
    header("Location: account.php");
    exit;
}

$error = '';
$success = '';
$showForgot = isset($_GET['forgot']) || isset($_POST['forgot_submit']);

/* =========================================================
   PASSWORD UPDATE
   User must provide the registered email, old password,
   new password and confirmation before the password is changed.
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_submit'])) {

    $forgotEmail = trim($_POST['forgot_email'] ?? '');
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($forgotEmail === '' || $oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = 'Please fill in all password update fields.';
    } elseif (!filter_var($forgotEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must contain at least 6 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New password and confirm password do not match.';
    } else {

        $dbFile = __DIR__ . '/includes/db.php';

        if (is_file($dbFile)) {
            require_once $dbFile;
        }

        $conn = $conn ?? ($mysqli ?? null);

        if ($conn instanceof mysqli) {
            $tableCandidates = ['customers', 'customer', 'users', 'user'];
            $table = null;

            foreach ($tableCandidates as $candidate) {
                $safeCandidate = $conn->real_escape_string($candidate);
                $check = @$conn->query("SHOW TABLES LIKE '{$safeCandidate}'");
                if ($check && $check->num_rows > 0) {
                    $table = $candidate;
                    break;
                }
            }

            if ($table !== null) {
                $columns = [];
                $safeTable = $conn->real_escape_string($table);
                $columnResult = @$conn->query("SHOW COLUMNS FROM `{$safeTable}`");

                if ($columnResult) {
                    while ($column = $columnResult->fetch_assoc()) {
                        $columns[] = $column['Field'];
                    }
                }

                $findColumn = function(array $possible) use ($columns) {
                    foreach ($possible as $name) {
                        foreach ($columns as $column) {
                            if (strcasecmp($column, $name) === 0) {
                                return $column;
                            }
                        }
                    }
                    return null;
                };

                $emailColumn = $findColumn(['customer_email', 'CustomerEmail', 'email', 'Email']);
                $passwordColumn = $findColumn(['password', 'Password', 'customer_password', 'CustomerPassword']);

                if ($emailColumn && $passwordColumn) {
                    $safeEmailColumn = $conn->real_escape_string($emailColumn);
                    $safePasswordColumn = $conn->real_escape_string($passwordColumn);

                    $sql = "SELECT `{$safePasswordColumn}` FROM `{$safeTable}` WHERE `{$safeEmailColumn}` = ? LIMIT 1";
                    $stmt = @$conn->prepare($sql);

                    if ($stmt) {
                        $stmt->bind_param('s', $forgotEmail);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $user = $result ? $result->fetch_assoc() : null;
                        $stmt->close();

                        if ($user) {
                            $storedPassword = (string)($user[$passwordColumn] ?? '');
                            $passwordMatches = password_verify($oldPassword, $storedPassword);

                            /* Support projects that stored a plain password. */
                            if (!$passwordMatches && hash_equals($storedPassword, $oldPassword)) {
                                $passwordMatches = true;
                            }

                            if ($passwordMatches) {
                                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                                $updateSql = "UPDATE `{$safeTable}` SET `{$safePasswordColumn}` = ? WHERE `{$safeEmailColumn}` = ? LIMIT 1";
                                $updateStmt = @$conn->prepare($updateSql);

                                if ($updateStmt) {
                                    $updateStmt->bind_param('ss', $hashedPassword, $forgotEmail);

                                    if ($updateStmt->execute()) {
                                        $success = 'Password updated successfully. Please login with your new password.';
                                        $showForgot = false;
                                    } else {
                                        $error = 'Unable to update password right now.';
                                    }

                                    $updateStmt->close();
                                } else {
                                    $error = 'Unable to process password update right now.';
                                }
                            } else {
                                $error = 'Old password is incorrect.';
                            }
                        } else {
                            $error = 'No account found with this email address.';
                        }
                    } else {
                        $error = 'Unable to process password update right now.';
                    }
                } else {
                    $error = 'Password database fields could not be detected.';
                }
            } else {
                $error = 'Password database table could not be detected.';
            }
        } else {
            $error = 'Database connection is not available.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {

        $dbFile = __DIR__ . '/includes/db.php';

        if (is_file($dbFile)) {
            require_once $dbFile;
        }

        $conn = $conn ?? ($mysqli ?? null);

        if ($conn instanceof mysqli) {

            /*
             * Detect a common customer/user table and its columns.
             * This keeps the login page flexible without changing
             * the database structure.
             */
            $tableCandidates = ['customers', 'customer', 'users', 'user'];
            $table = null;

            foreach ($tableCandidates as $candidate) {
                $safeTable = $conn->real_escape_string($candidate);
                $check = @$conn->query("SHOW TABLES LIKE '{$safeTable}'");

                if ($check && $check->num_rows > 0) {
                    $table = $candidate;
                    break;
                }
            }

            if ($table !== null) {

                $columns = [];
                $safeTable = $conn->real_escape_string($table);
                $columnResult = @$conn->query("SHOW COLUMNS FROM `{$safeTable}`");

                if ($columnResult) {
                    while ($column = $columnResult->fetch_assoc()) {
                        $columns[] = $column['Field'];
                    }
                }

                $findColumn = function(array $possible) use ($columns) {
                    foreach ($possible as $name) {
                        foreach ($columns as $column) {
                            if (strcasecmp($column, $name) === 0) {
                                return $column;
                            }
                        }
                    }
                    return null;
                };

                $idColumn = $findColumn(['customer_id', 'CustomerID', 'id', 'ID']);
                $nameColumn = $findColumn(['customer_name', 'CustomerName', 'name', 'Name', 'fullname', 'full_name']);
                $emailColumn = $findColumn(['customer_email', 'CustomerEmail', 'email', 'Email']);
                $mobileColumn = $findColumn(['customer_mobile', 'CustomerMobile', 'mobile', 'phone', 'Phone']);
                $passwordColumn = $findColumn(['password', 'Password', 'customer_password', 'CustomerPassword']);

                if ($idColumn && $emailColumn && $passwordColumn) {

                    $sql = "SELECT * FROM `{$safeTable}` WHERE `"
                         . $conn->real_escape_string($emailColumn)
                         . "` = ? LIMIT 1";

                    $stmt = @$conn->prepare($sql);

                    if ($stmt) {
                        $stmt->bind_param('s', $email);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $user = $result ? $result->fetch_assoc() : null;
                        $stmt->close();

                        if ($user) {
                            $storedPassword = (string)($user[$passwordColumn] ?? '');
                            $passwordMatches = password_verify($password, $storedPassword);

                            /* Support projects that stored a plain password. */
                            if (!$passwordMatches && hash_equals($storedPassword, $password)) {
                                $passwordMatches = true;
                            }

                            if ($passwordMatches) {
                                $_SESSION['customer_id'] = (int)$user[$idColumn];
                                $_SESSION['customer_name'] = $nameColumn ? (string)($user[$nameColumn] ?? 'COCORA Customer') : 'COCORA Customer';
                                $_SESSION['customer_email'] = $email;
                                $_SESSION['customer_mobile'] = $mobileColumn ? (string)($user[$mobileColumn] ?? '') : '';

                                header("Location: account.php");
                                exit;
                            }
                        }

                        $error = 'Invalid email or password.';
                    } else {
                        $error = 'Unable to process login right now.';
                    }
                } else {
                    $error = 'Login database fields could not be detected.';
                }
            } else {
                $error = 'Login database table could not be detected.';
            }

        } else {
            $error = 'Database connection is not available.';
        }
    }
}

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

<title>Login | COCORA Premium Chocolates</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

html,
body{
    min-height:100%;
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

.nav-links a:hover{
    color:#D4AF37;
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

.login-page{
    width:min(520px,90%);
    margin:0 auto;
    padding:95px 0 110px;
    min-height:calc(100vh - 88px);
    display:flex;
    align-items:center;
    justify-content:center;
}

.login-card{
    width:100%;
    padding:52px 50px;
    background:rgba(43,19,8,.45);
    border:1px solid rgba(212,175,55,.32);
    animation:cocoraFadeBlur .9s .15s ease both;
}

.login-header{
    text-align:center;
    animation:cocoraFadeBlur .8s .25s ease both;
}

.eyebrow{
    color:#D4AF37;
    font-size:15px;
    letter-spacing:4px;
    text-transform:uppercase;
    margin-bottom:13px;
}

.login-header h1{
    color:#D4AF37;
    font-size:56px;
    line-height:1;
    font-weight:600;
    letter-spacing:1px;
}

.login-header p{
    margin-top:17px;
    color:#c7ae85;
    font-size:19px;
    line-height:1.5;
}

.gold-line{
    width:80px;
    height:1px;
    background:#D4AF37;
    margin:25px auto 34px;
}

.login-form{
    animation:cocoraFadeBlur .8s .35s ease both;
}

.form-group{
    margin-bottom:23px;
}

.form-group label{
    display:block;
    color:#e0b254;
    font-size:18px;
    margin-bottom:8px;
    letter-spacing:.5px;
}

.form-group input{
    width:100%;
    height:52px;
    padding:0 15px;
    background:rgba(20,8,3,.72);
    color:#ead7ad;
    border:1px solid rgba(212,175,55,.42);
    outline:none;
    border-radius:0;
    font-size:18px;
    transition:.3s ease;
}

.form-group input::placeholder{
    color:#806e55;
}

.form-group input:focus{
    border-color:#D4AF37;
    box-shadow:0 0 0 2px rgba(212,175,55,.08);
}

.error-message{
    margin-bottom:22px;
    padding:12px 15px;
    border:1px solid rgba(190,80,55,.65);
    color:#e9aa92;
    background:rgba(90,25,15,.22);
    text-align:center;
    font-size:17px;
}

.success-message{
    margin-bottom:22px;
    padding:12px 15px;
    border:1px solid rgba(212,175,55,.55);
    color:#e6cb82;
    background:rgba(212,175,55,.08);
    text-align:center;
    font-size:17px;
}

.forgot-title{
    color:#D4AF37;
    text-align:center;
    font-size:30px;
    font-weight:600;
    letter-spacing:1px;
    margin-bottom:25px;
}

.back-login{
    display:block;
    text-align:center;
    margin-top:22px;
    color:#bda477;
    font-size:17px;
    transition:.3s ease;
}

.back-login:hover{
    color:#D4AF37;
}

.login-button{
    width:100%;
    height:54px;
    border:1px solid #D4AF37;
    background:#D4AF37;
    color:#080402;
    font-size:19px;
    font-weight:600;
    letter-spacing:2px;
    cursor:pointer;
    transition:.3s ease;
}

.login-button:hover{
    background:#f0c96a;
    border-color:#f0c96a;
    transform:translateY(-2px);
}

.login-footer{
    text-align:center;
    margin-top:28px;
    color:#9f8c70;
    font-size:17px;
    animation:cocoraFadeBlur .8s .45s ease both;
}

.login-footer a{
    color:#D4AF37;
    border-bottom:1px solid rgba(212,175,55,.55);
    padding-bottom:2px;
}

.forgot{
    display:block;
    text-align:right;
    margin-top:-10px;
    margin-bottom:23px;
    color:#bda477;
    font-size:16px;
    transition:.3s ease;
}

.forgot:hover{
    color:#D4AF37;
}

footer{
    border-top:1px solid rgba(212,175,55,.16);
    text-align:center;
    padding:28px 20px;
    color:#806e55;
    font-size:16px;
    animation:cocoraFadeBlur .8s .55s ease both;
}

.floating-cart{
    position:fixed;
    right:25px;
    bottom:25px;
    display:flex;
    gap:10px;
    z-index:2000;
    animation:cocoraFadeBlur .7s ease both;
}

.floating-cart a{
    padding:12px 18px;
    border:1px solid #D4AF37;
    background:#080402;
    color:#D4AF37;
    font-size:15px;
    letter-spacing:1px;
    transition:.3s ease;
}

.floating-cart a:hover{
    background:#D4AF37;
    color:#080402;
}

@media(max-width:700px){
    .navbar{
        height:auto;
        min-height:88px;
        padding:18px 5%;
    }

    .nav-links{
        gap:22px;
    }

    .nav-links a,
    .login-link{
        font-size:17px;
    }

    .login-link{
        right:5%;
        min-width:95px;
        padding:8px 12px;
    }

    .login-page{
        padding:65px 0 90px;
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

    .login-card{
        padding:38px 25px;
    }

    .login-header h1{
        font-size:47px;
    }

    .login-header p{
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


/* =========================
   PASSWORD VIEW / HIDE
   ========================= */
.password-field{
    position:relative;
    width:100%;
}

.password-field input{
    width:100%;
    padding-right:58px !important;
}

.password-toggle{
    position:absolute;
    top:50%;
    right:14px;
    transform:translateY(-50%);
    width:34px;
    height:34px;
    padding:0;
    border:0;
    background:transparent;
    color:#D4AF37;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    z-index:2;
    transition:.25s ease;
}

.password-toggle:hover{
    color:#f0c96a;
    transform:translateY(-50%) scale(1.08);
}

.password-toggle svg{
    width:20px;
    height:20px;
    display:block;
    fill:none;
    stroke:currentColor;
    stroke-width:1.7;
    stroke-linecap:round;
    stroke-linejoin:round;
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

    <a href="login.php" class="login-link">Login</a>
</nav>

<main class="login-page">

    <section class="login-card">

        <header class="login-header">
            <div class="eyebrow">Welcome Back</div>

            <h1>Login</h1>

            <p>
                Enter your details to continue your COCORA experience.
            </p>

            <div class="gold-line"></div>
        </header>

        <?php if ($error !== ''): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="success-message">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!$showForgot): ?>

            <form method="post" action="login.php" class="login-form">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-field">
<input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >
</div>
                </div>

                <a href="?forgot=1" class="forgot">
                    Forgot Password?
                </a>

                <button type="submit" name="login_submit" class="login-button">
                    LOGIN
                </button>

            </form>

        <?php else: ?>

            <form method="post" action="login.php" class="login-form">

                <div class="forgot-title">Forgot Password</div>

                <div class="form-group">
                    <label for="forgot_email">Username / Email</label>
                    <input
                        type="text"
                        id="forgot_email"
                        name="forgot_email"
                        placeholder="Enter your username or email"
                        value="<?= htmlspecialchars($_POST['forgot_email'] ?? '') ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="old_password">Old Password</label>
                    <div class="password-field">
<input
                        type="password"
                        id="old_password"
                        name="old_password"
                        placeholder="Enter your old password"
                        autocomplete="current-password"
                        required
                    >
</div>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="password-field">
<input
                        type="password"
                        id="new_password"
                        name="new_password"
                        placeholder="Enter your new password"
                        autocomplete="new-password"
                        minlength="6"
                        required
                    >
</div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-field">
<input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm your new password"
                        autocomplete="new-password"
                        minlength="6"
                        required
                    >
</div>
                </div>

                <button type="submit" name="forgot_submit" class="login-button">
                    UPDATE PASSWORD
                </button>

                <a href="login.php" class="back-login">
                    ← Back to Login
                </a>

            </form>

        <?php endif; ?>

        <div class="login-footer">
            Don't have an account?
            <a href="register.php">Create Account</a>
        </div>

    </section>

</main>

<?php if ($cartCount > 0): ?>
    <div class="floating-cart">
        <a href="cart.php">VIEW CART (<?= $cartCount ?>)</a>
        <a href="checkout.php">CHECKOUT</a>
    </div>
<?php endif; ?>

<footer>
    © <?= date('Y') ?> COCORA Premium Chocolates. Crafted With Passion.
</footer>



<script>
(function(){
    const eyeOpen = `
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path>
            <circle cx="12" cy="12" r="2.7"></circle>
        </svg>`;

    const eyeClosed = `
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M3 3l18 18"></path>
            <path d="M10.6 5.1A10.7 10.7 0 0 1 12 5c6 0 9.5 7 9.5 7a18.8 18.8 0 0 1-3.1 3.9"></path>
            <path d="M6.2 6.3C3.8 8 2.5 12 2.5 12s3.5 7 9.5 7c1.7 0 3.2-.5 4.5-1.2"></path>
            <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path>
        </svg>`;

    document.querySelectorAll('input[type="password"]').forEach(function(input){
        let wrapper = input.closest('.password-field');

        /* If the input is not already wrapped, create the wrapper. */
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'password-field';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
        }

        /* Prevent duplicate eye buttons. */
        if (wrapper.querySelector('.password-toggle')) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'password-toggle';
        button.setAttribute('aria-label', 'Show password');
        button.setAttribute('title', 'Show password');
        button.innerHTML = eyeOpen;

        button.addEventListener('click', function(){
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';

            if (isHidden) {
                /* Password is visible -> show closed-eye symbol. */
                button.innerHTML = eyeClosed;
                button.setAttribute('aria-label', 'Hide password');
                button.setAttribute('title', 'Hide password');
            } else {
                /* Password is hidden -> show open-eye symbol. */
                button.innerHTML = eyeOpen;
                button.setAttribute('aria-label', 'Show password');
                button.setAttribute('title', 'Show password');
            }
        });

        wrapper.appendChild(button);
    });
})();
</script>

</body>
</html>
