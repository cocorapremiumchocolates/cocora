<?php
session_start();

$orderId = $_SESSION['last_order_id'] ?? null;
$orderTotal = $_SESSION['last_order_total'] ?? null;
$paymentMethod = $_SESSION['last_payment_method'] ?? null;

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Thank You | COCORA Premium Chocolates
</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap"
    rel="stylesheet"
>


<style>

/* =========================================================
   RESET
========================================================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}


/* =========================================================
   BODY
========================================================= */

html{
    scroll-behavior:smooth;
}

body{

    min-height:100vh;

    background:#080402;

    color:#eadfbd;

    font-family:'Cormorant Garamond',serif;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:40px 20px;

    overflow-x:hidden;

    position:relative;

}


/* =========================================================
   BACKGROUND GLOW
========================================================= */

body::before{

    content:"";

    position:fixed;

    inset:0;

    pointer-events:none;

    background:

        radial-gradient(
            circle at 50% 10%,
            rgba(212,175,55,.09),
            transparent 30%
        ),

        radial-gradient(
            circle at 10% 90%,
            rgba(212,175,55,.045),
            transparent 28%
        ),

        radial-gradient(
            circle at 90% 80%,
            rgba(212,175,55,.035),
            transparent 30%
        );

    z-index:-1;

}


/* =========================================================
   ANIMATION
========================================================= */

@keyframes blurFade{

    0%{

        opacity:0;

        filter:blur(14px);

        transform:translateY(18px);

    }

    100%{

        opacity:1;

        filter:blur(0);

        transform:translateY(0);

    }

}


@keyframes softGlow{

    0%{

        opacity:.25;

        transform:scale(.92);

    }

    100%{

        opacity:.7;

        transform:scale(1);

    }

}


/* =========================================================
   MAIN CARD
========================================================= */

.thankyou-card{

    width:100%;

    max-width:900px;

    position:relative;

    overflow:hidden;

    text-align:center;

    padding:
        75px
        70px
        65px;

    background:

        linear-gradient(
            145deg,
            rgba(18,9,4,.97),
            rgba(8,4,2,.99)
        );

    border:
        1px solid
        rgba(212,175,55,.28);

    box-shadow:

        0 30px 100px
        rgba(0,0,0,.55),

        inset 0 0 70px
        rgba(212,175,55,.025);

    animation:
        blurFade
        .9s
        cubic-bezier(.22,1,.36,1)
        both;

}


/* =========================================================
   CARD GOLD CORNERS
========================================================= */

.thankyou-card::before{

    content:"";

    position:absolute;

    width:420px;

    height:420px;

    border-radius:50%;

    background:
        rgba(212,175,55,.055);

    filter:blur(90px);

    top:-250px;

    left:50%;

    transform:translateX(-50%);

    animation:
        softGlow
        1.8s
        ease
        both;

}


.thankyou-card::after{

    content:"";

    position:absolute;

    width:1px;

    height:110px;

    background:

        linear-gradient(
            to bottom,
            transparent,
            rgba(212,175,55,.5),
            transparent
        );

    left:50%;

    bottom:0;

}


/* =========================================================
   CONTENT
========================================================= */

.content{

    position:relative;

    z-index:2;

}


/* =========================================================
   LOGO
========================================================= */

.logo{

    width:82px;

    height:82px;

    object-fit:contain;

    margin-bottom:25px;

    animation:
        blurFade
        .9s
        .1s
        both;

}


/* =========================================================
   BRAND LABEL
========================================================= */

.brand-label{

    color:#9c8a67;

    font-size:10px;

    letter-spacing:5px;

    margin-bottom:22px;

    animation:
        blurFade
        .9s
        .16s
        both;

}


/* =========================================================
   MAIN TITLE
========================================================= */

h1{

    color:#D4AF37;

    font-size:
        clamp(
            38px,
            5.5vw,
            62px
        );

    font-weight:400;

    line-height:1.08;

    letter-spacing:4px;

    margin-bottom:20px;

    animation:
        blurFade
        .9s
        .22s
        both;

}


/* =========================================================
   SUBTITLE
========================================================= */

.subtitle{

    color:#d8c58f;

    font-size:22px;

    line-height:1.5;

    margin-bottom:8px;

    animation:
        blurFade
        .9s
        .28s
        both;

}


.interest{

    color:#9d8a6c;

    font-size:14px;

    margin-bottom:29px;

    animation:
        blurFade
        .9s
        .34s
        both;

}


/* =========================================================
   GOLD LINE
========================================================= */

.gold-line{

    width:65px;

    height:1px;

    background:#D4AF37;

    margin:
        0 auto
        31px;

    box-shadow:
        0 0 12px
        rgba(212,175,55,.25);

    animation:
        blurFade
        .9s
        .4s
        both;

}


/* =========================================================
   MESSAGE
========================================================= */

.message{

    max-width:680px;

    margin:auto;

    color:#a99a80;

    font-family:Arial,sans-serif;

    font-size:13px;

    line-height:2;

    animation:
        blurFade
        .9s
        .46s
        both;

}


.message strong{

    color:#D4AF37;

    font-weight:400;

}


/* =========================================================
   ORDER INFO
========================================================= */

.order-info{

    max-width:450px;

    margin:
        32px auto
        0;

    padding:
        17px 24px;

    border:
        1px solid
        rgba(212,175,55,.2);

    background:
        rgba(212,175,55,.025);

    color:#99896f;

    font-family:Arial,sans-serif;

    font-size:11px;

    line-height:1.9;

    animation:
        blurFade
        .9s
        .52s
        both;

    transition:
        border-color .3s ease,
        background .3s ease;

}


.order-info:hover{

    border-color:
        rgba(212,175,55,.4);

    background:
        rgba(212,175,55,.045);

}


.order-info .order-number{

    color:#D4AF37;

    font-size:14px;

    letter-spacing:1px;

    margin-bottom:2px;

}


/* =========================================================
   CONTACT
========================================================= */

.contact{

    margin-top:34px;

    padding-top:26px;

    border-top:
        1px solid
        rgba(212,175,55,.12);

    animation:
        blurFade
        .9s
        .58s
        both;

}


.contact-title{

    color:#9c8a67;

    font-family:Arial,sans-serif;

    font-size:10px;

    letter-spacing:2.5px;

    margin-bottom:13px;

}


.contact p{

    color:#b7a684;

    font-family:Arial,sans-serif;

    font-size:12px;

    line-height:2;

}


.contact a{

    color:#D4AF37;

    text-decoration:none;

    transition:
        color .3s ease;

}


.contact a:hover{

    color:#f0d477;

}


/* =========================================================
   HOME BUTTON
========================================================= */

.home-button{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    margin-top:35px;

    min-width:190px;

    padding:
        14px
        30px;

    border:
        1px solid
        #D4AF37;

    color:#D4AF37;

    background:transparent;

    font-family:Arial,sans-serif;

    font-size:10px;

    letter-spacing:2px;

    text-decoration:none;

    transition:
        background .35s ease,
        color .35s ease,
        transform .35s ease,
        box-shadow .35s ease;

    animation:
        blurFade
        .9s
        .64s
        both;

}


.home-button:hover{

    background:#D4AF37;

    color:#080402;

    transform:
        translateY(-3px);

    box-shadow:
        0 10px 28px
        rgba(212,175,55,.16);

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width:650px){

    body{

        padding:
            18px 12px;

    }


    .thankyou-card{

        padding:
            52px 22px
            48px;

    }


    .logo{

        width:70px;

        height:70px;

    }


    .brand-label{

        font-size:8px;

        letter-spacing:3px;

    }


    h1{

        font-size:35px;

        letter-spacing:2px;

    }


    .subtitle{

        font-size:18px;

    }


    .interest{

        font-size:12px;

    }


    .message{

        font-size:12px;

        line-height:1.9;

    }


    .order-info{

        padding:
            14px 15px;

    }


    .home-button{

        width:100%;

    }

}

</style>

</head>


<body>


<div class="thankyou-card">

    <div class="content">


        <!-- LOGO -->

        <img
            src="assets/logo/logo.png"
            alt="COCORA"
            class="logo"
        >


        <!-- BRAND -->

        <div class="brand-label">

            COCORA PREMIUM CHOCOLATES

        </div>


        <!-- TITLE -->

        <h1>

            THANK YOU FOR<br>

            CHOOSING COCORA

        </h1>


        <!-- SUBTITLE -->

        <div class="subtitle">

            Your Request Has Been Received

        </div>


        <div class="interest">

            We Appreciate Your Interest

        </div>


        <div class="gold-line"></div>


        <!-- MESSAGE -->

        <div class="message">

            Thank you for choosing COCORA.

            <br>

            Your order has been successfully placed.

            <br><br>

            We will keep you informed about your order
            through your registered email address or
            contact number.

            <br><br>

            For exclusive collections, gifting solutions,
            special requests or any further assistance,
            please feel free to contact COCORA directly.

        </div>


        <!-- ORDER DETAILS -->

        <?php if ($orderId): ?>

            <div class="order-info">

                <div class="order-number">

                    Order #<?php
                    echo e($orderId);
                    ?>

                </div>


                <?php if ($orderTotal !== null): ?>

                    Order Total:
                    ₹<?php
                    echo number_format(
                        (float)$orderTotal,
                        2
                    );
                    ?>

                    <br>

                <?php endif; ?>


                <?php if ($paymentMethod): ?>

                    Payment Method:
                    <?php
                    echo e($paymentMethod);
                    ?>

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- CONTACT -->

        <div class="contact">

            <div class="contact-title">

                FOR FURTHER ASSISTANCE

            </div>


            <p>

                <a
                    href="mailto:CocoraPremiumChocolates@gmail.com"
                >

                    CocoraPremiumChocolates@gmail.com

                </a>

                <br>

                <a
                    href="tel:+919328016467"
                >

                    +91 9328016467

                </a>

            </p>

        </div>


        <!-- HOME -->

        <a
            href="index.php"
            class="home-button"
        >

            RETURN TO HOME

        </a>


    </div>

</div>


</body>

</html>