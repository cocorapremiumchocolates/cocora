<?php
session_start();

/* =========================================================
   COCORA CREATE ACCOUNT
   Uses the existing database connection and detects the
   available customer/user table and common column names.
   ========================================================= */

if (isset($_SESSION['customer_id'])) {
    header("Location: account.php");
    exit;
}

$error = '';
$success = '';

$name = '';
$email = '';
$mobile = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
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
                $safeTableCandidate = $conn->real_escape_string($candidate);
                $check = @$conn->query("SHOW TABLES LIKE '{$safeTableCandidate}'");

                if ($check && $check->num_rows > 0) {
                    $table = $candidate;
                    break;
                }
            }

            if ($table !== null) {

                $safeTable = $conn->real_escape_string($table);
                $columns = [];

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

                if (!$emailColumn || !$passwordColumn) {
                    $error = 'Registration database fields could not be detected.';
                } elseif ($nameColumn === null) {
                    $error = 'Customer name field could not be detected.';
                } else {

                    /* Check whether the email is already registered. */
                    $emailSql = "SELECT 1 FROM `{$safeTable}` WHERE `"
                              . $conn->real_escape_string($emailColumn)
                              . "` = ? LIMIT 1";

                    $emailStmt = @$conn->prepare($emailSql);

                    if (!$emailStmt) {
                        $error = 'Unable to process registration right now.';
                    } else {

                        $emailStmt->bind_param('s', $email);
                        $emailStmt->execute();
                        $emailResult = $emailStmt->get_result();
                        $emailExists = $emailResult && $emailResult->num_rows > 0;
                        $emailStmt->close();

                        if ($emailExists) {
                            $error = 'An account with this email already exists.';
                        } else {

                            /*
                             * Build INSERT dynamically using only columns
                             * that are actually present in the existing table.
                             */
                            $insertColumns = [$nameColumn, $emailColumn, $passwordColumn];
                            $insertValues = [$name, $email, password_hash($password, PASSWORD_DEFAULT)];
                            $types = 'sss';

                            if ($mobileColumn !== null) {
                                $insertColumns[] = $mobileColumn;
                                $insertValues[] = $mobile;
                                $types .= 's';
                            }

                            $columnSql = [];
                            foreach ($insertColumns as $column) {
                                $columnSql[] = '`' . $conn->real_escape_string($column) . '`';
                            }

                            $placeholders = implode(', ', array_fill(0, count($insertValues), '?'));

                            $insertSql = "INSERT INTO `{$safeTable}` ("
                                       . implode(', ', $columnSql)
                                       . ") VALUES ({$placeholders})";

                            $insertStmt = @$conn->prepare($insertSql);

                            if (!$insertStmt) {
                                $error = 'Unable to create the account right now.';
                            } else {

                                $bindArgs = [$types];

                                foreach ($insertValues as $key => $value) {
                                    $bindArgs[] = &$insertValues[$key];
                                }

                                call_user_func_array([$insertStmt, 'bind_param'], $bindArgs);

                                if ($insertStmt->execute()) {
                                    $newCustomerId = (int)$conn->insert_id;

                                    $insertStmt->close();

                                    $success = 'Account created successfully. Redirecting to login...';

                                    /*
                                     * Keep the flow simple:
                                     * create account -> login page.
                                     */
                                    header("Refresh: 1.2; url=login.php");
                                } else {
                                    $error = 'Unable to create the account. Please try again.';
                                    $insertStmt->close();
                                }
                            }
                        }
                    }
                }
            } else {
                $error = 'Registration database table could not be detected.';
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

<title>Create Account | COCORA Premium Chocolates</title>

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

.register-page{
    width:min(560px,90%);
    margin:0 auto;
    padding:72px 0 95px;
    min-height:calc(100vh - 88px);
    display:flex;
    align-items:center;
    justify-content:center;
}

.register-card{
    width:100%;
    padding:48px 50px;
    background:rgba(43,19,8,.45);
    border:1px solid rgba(212,175,55,.32);
    animation:cocoraFadeBlur .9s .15s ease both;
}

.register-header{
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

.register-header h1{
    color:#D4AF37;
    font-size:56px;
    line-height:1;
    font-weight:600;
    letter-spacing:1px;
}

.register-header p{
    margin-top:17px;
    color:#c7ae85;
    font-size:19px;
    line-height:1.5;
}

.gold-line{
    width:80px;
    height:1px;
    background:#D4AF37;
    margin:25px auto 30px;
}

.register-form{
    animation:cocoraFadeBlur .8s .35s ease both;
}

.form-group{
    margin-bottom:19px;
}

.form-group label{
    display:block;
    color:#e0b254;
    font-size:18px;
    margin-bottom:7px;
    letter-spacing:.5px;
}

.form-group input{
    width:100%;
    height:49px;
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

.form-note{
    margin:-5px 0 21px;
    color:#806e55;
    font-size:15px;
}

.error-message,
.success-message{
    margin-bottom:22px;
    padding:12px 15px;
    text-align:center;
    font-size:17px;
}

.error-message{
    border:1px solid rgba(190,80,55,.65);
    color:#e9aa92;
    background:rgba(90,25,15,.22);
}

.success-message{
    border:1px solid rgba(212,175,55,.55);
    color:#D4AF37;
    background:rgba(80,50,5,.18);
}

.register-button{
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

.register-button:hover{
    background:#f0c96a;
    border-color:#f0c96a;
    transform:translateY(-2px);
}

.register-footer{
    text-align:center;
    margin-top:27px;
    color:#9f8c70;
    font-size:17px;
    animation:cocoraFadeBlur .8s .45s ease both;
}

.register-footer a{
    color:#D4AF37;
    border-bottom:1px solid rgba(212,175,55,.55);
    padding-bottom:2px;
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

    .register-page{
        padding:55px 0 85px;
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

    .register-card{
        padding:38px 25px;
    }

    .register-header h1{
        font-size:46px;
    }

    .register-header p{
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

<main class="register-page">

    <section class="register-card">

        <header class="register-header">
            <div class="eyebrow">Join The COCORA Experience</div>

            <h1>Create Account</h1>

            <p>
                Create your account and discover the world of premium chocolate.
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

        <form method="post" action="register.php" class="register-form">

            <div class="form-group">
                <label for="name">Full Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Enter your full name"
                    value="<?= htmlspecialchars($name) ?>"
                    autocomplete="name"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    value="<?= htmlspecialchars($email) ?>"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="form-group">
                <label for="mobile">Mobile Number</label>
                <input
                    type="tel"
                    id="mobile"
                    name="mobile"
                    placeholder="Enter your mobile number"
                    value="<?= htmlspecialchars($mobile) ?>"
                    autocomplete="tel"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
<input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Create a password"
                    autocomplete="new-password"
                    minlength="6"
                    required
                >
</div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-field">
<input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Confirm your password"
                    autocomplete="new-password"
                    minlength="6"
                    required
                >
</div>
            </div>

            <div class="form-note">
                Password must contain at least 6 characters.
            </div>

            <button type="submit" name="register_submit" class="register-button">
                CREATE ACCOUNT
            </button>

        </form>

        <div class="register-footer">
            Already have an account?
            <a href="login.php">Login</a>
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
