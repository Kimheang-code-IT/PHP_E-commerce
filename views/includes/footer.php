<?php
// views/includes/footer.php
// (assumes session is started and $mysqli + BASE_URL are already defined)

?>
<footer class="bg3 p-t-75 p-b-32">
    <div class="container">
        <div class="row">
            <!-- Categories (dynamic) -->
            <div class="col-sm-6 col-lg-3 p-b-50">
                <h4 class="stext-301 cl0 p-b-30">Categories</h4>
                <ul>
                    <?php
                    $footerCats = $mysqli->query("SELECT name, id FROM categories ORDER BY name LIMIT 4");
                    while ($fc = $footerCats->fetch_assoc()):
                    ?>
                        <li class="p-b-10">
                            <a href="<?= BASE_URL ?>/views/site/product.php?cat_id=<?= $fc['id'] ?>"
                                class="stext-107 cl7 hov-cl1 trans-04">
                                <?= htmlspecialchars($fc['name']) ?>
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>

            <!-- Help -->
            <div class="col-sm-6 col-lg-3 p-b-50">
                <h4 class="stext-301 cl0 p-b-30">Help</h4>
                <ul>
                    <li class="p-b-10"><a href="<?= BASE_URL ?>/views/site/track-order.php" class="stext-107 cl7 hov-cl1 trans-04">Track Order</a></li>
                    <li class="p-b-10"><a href="<?= BASE_URL ?>/views/site/returns.php" class="stext-107 cl7 hov-cl1 trans-04">Returns</a></li>
                    <li class="p-b-10"><a href="<?= BASE_URL ?>/views/site/shipping.php" class="stext-107 cl7 hov-cl1 trans-04">Shipping</a></li>
                    <li class="p-b-10"><a href="<?= BASE_URL ?>/views/site/faq.php" class="stext-107 cl7 hov-cl1 trans-04">FAQs</a></li>
                </ul>
            </div>

            <!-- Get in touch -->
            <div class="col-sm-6 col-lg-3 p-b-50">
                <h4 class="stext-301 cl0 p-b-30">GET IN TOUCH</h4>
                <p class="stext-107 cl7 size-201">
                    Any questions? Let us know at 8th floor, 379 Hudson St, New York, NY 10018 or call us on (+1) 96 716 6879
                </p>
                <div class="p-t-27">
                    <a href="#" class="fs-18 cl7 hov-cl1 trans-04 m-r-16"><i class="fa fa-facebook"></i></a>
                    <a href="#" class="fs-18 cl7 hov-cl1 trans-04 m-r-16"><i class="fa fa-instagram"></i></a>
                    <a href="#" class="fs-18 cl7 hov-cl1 trans-04 m-r-16"><i class="fa fa-pinterest-p"></i></a>
                </div>
            </div>

            <!-- Newsletter -->
            <div class="col-sm-6 col-lg-3 p-b-50">
                <h4 class="stext-301 cl0 p-b-30">Newsletter</h4>
                <form action="<?= BASE_URL ?>/views/site/subscribe.php" method="post">
                    <div class="wrap-input1 w-full p-b-4">
                        <input class="input1 bg-none plh1 stext-107 cl7" type="email" name="email" placeholder="email@example.com" required>
                        <div class="focus-input1 trans-04"></div>
                    </div>
                    <div class="p-t-18">
                        <button type="submit" class="flex-c-m stext-101 cl0 size-103 bg1 bor1 hov-btn2 p-lr-15 trans-04">
                            Subscribe
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="p-t-40">
            <!-- Payment icons -->
            <div class="flex-c-m flex-w p-b-18">
                <a href="#" class="m-all-1"><img src="<?= BASE_URL ?>/assets/images/icons/icon-pay-01.png" alt="ICON-PAY"></a>
                <a href="#" class="m-all-1"><img src="<?= BASE_URL ?>/assets/images/icons/icon-pay-02.png" alt="ICON-PAY"></a>
                <a href="#" class="m-all-1"><img src="<?= BASE_URL ?>/assets/images/icons/icon-pay-03.png" alt="ICON-PAY"></a>
                <a href="#" class="m-all-1"><img src="<?= BASE_URL ?>/assets/images/icons/icon-pay-04.png" alt="ICON-PAY"></a>
                <a href="#" class="m-all-1"><img src="<?= BASE_URL ?>/assets/images/icons/icon-pay-05.png" alt="ICON-PAY"></a>
            </div>

            <!-- Copyright -->
            <p class="stext-107 cl6 txt-center mb-0">
                &copy; <?= date('Y'); ?> All Rights Reserved |
                Made with <i class="fa fa-heart text-danger" aria-hidden="true"></i>
                by <a href="https://your-website.com" class="text-decoration-none">Moeng Kimheang</a>
            </p>
        </div>
    </div>
</footer>

<!-- Back to top -->
<div class="btn-back-to-top" id="myBtn">
    <span class="symbol-btn-back-to-top"><i class="zmdi zmdi-chevron-up"></i></span>
</div>

<!-- Modal1 (quick‐view template) -->
<div class="wrap-modal1 js-modal1 p-t-60 p-b-20">
    <div class="overlay-modal1 js-hide-modal1"></div>
    <!-- …your modal markup… -->
</div>

<!-- Vendor scripts -->
<script src="<?= BASE_URL ?>/assets/vendor/jquery/jquery-3.2.1.min.js"></script>
<script src="<?= BASE_URL ?>/assets/vendor/animsition/js/animsition.min.js"></script>
<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/js/popper.js"></script>
<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/js/bootstrap.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/vendor/slick/slick.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/slick-custom.js"></script>
<script src="<?= BASE_URL ?>/assets/vendor/isotope/isotope.pkgd.min.js"></script>
</body>

</html>