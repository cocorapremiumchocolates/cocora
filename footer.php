<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   CART COUNT
========================= */

$cartCount = 0;

if (
    isset($_SESSION['cart']) &&
    is_array($_SESSION['cart'])
) {
    foreach ($_SESSION['cart'] as $item) {

        $cartCount += (int)(
            $item['quantity'] ?? 0
        );

    }
}
?>


<!-- =========================
     FLOATING CART ACTIONS
     
     ONLY VISIBLE WHEN CART
     CONTAINS PRODUCTS
========================= -->

<?php if ($cartCount > 0): ?>

<div class="cocora-floating-cart">

    <a
        href="cart.php"
        class="cocora-float-btn"
    >
        VIEW CART
        <span>
            (<?php echo $cartCount; ?>)
        </span>
    </a>


    <a
        href="checkout.php"
        class="cocora-float-btn"
    >
        CHECKOUT
    </a>

</div>

<?php endif; ?>


<style>

/* =========================
   COCORA FLOATING BUTTONS
========================= */

.cocora-floating-cart {

    position: fixed;

    right: 24px;

    bottom: 22px;

    z-index: 9999;

    display: flex;

    flex-direction: column;

    gap: 9px;

    animation:
        cocoraCartAppear .35s ease;

}


.cocora-float-btn {

    display: block;

    padding:
        11px 17px;

    background: #d9a441;

    color: #241208;

    border:
        1px solid
        #d9a441;

    font-family:
        Arial,
        sans-serif;

    font-size: 9px;

    font-weight: bold;

    letter-spacing: 1px;

    text-align: center;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,.3);

    transition:
        .3s ease;

}


.cocora-float-btn:hover {

    background: #efc66c;

    border-color: #efc66c;

    transform:
        translateY(-2px);

}


.cocora-float-btn span {

    font-weight: bold;

}


@keyframes cocoraCartAppear {

    from {

        opacity: 0;

        transform:
            translateY(12px);

    }

    to {

        opacity: 1;

        transform:
            translateY(0);

    }

}


/* MOBILE */

@media (max-width: 600px) {

    .cocora-floating-cart {

        right: 13px;

        bottom: 13px;

    }


    .cocora-float-btn {

        padding:
            10px 13px;

        font-size: 8px;

    }

}

</style>


<!-- =========================
     FOOTER
========================= -->

<footer
    style="
        border-top:1px solid rgba(217,164,65,.15);
        padding:30px 5%;
        display:flex;
        justify-content:space-between;
        align-items:center;
        color:#6f604d;
        font-family:Arial,sans-serif;
        font-size:9px;
    "
>

    <div
        style="
            color:#947b50;
            letter-spacing:2px;
        "
    >
        COCORA PREMIUM CHOCOLATES
    </div>


    <div>
        © <?php echo date('Y'); ?>
        COCORA. All Rights Reserved.
    </div>

</footer>