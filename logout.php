<?php

session_start();

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
| Database ki zarurat nahi hai.
| Sirf customer login session clear karna hai.
|--------------------------------------------------------------------------
*/

unset($_SESSION['customer_id']);
unset($_SESSION['customer_name']);
unset($_SESSION['customer_email']);
unset($_SESSION['customer_mobile']);

/*
 * Checkout ki temporary information bhi clear.
 */
unset($_SESSION['checkout']);

/*
 * Session destroy.
 */
session_destroy();

/*
 * Logout ke baad directly homepage.
 */
header("Location: index.php");
exit;

?>