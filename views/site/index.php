<?php
// views/site/index.php

// 1) Page metadata
$pageTitle = 'Home';

// 2) Shared header include (starts session, connects $mysqli, defines BASE_URL, outputs <head>…)
include __DIR__ . '/../includes/header.php';
?>

<!-- Slider -->
<section class="section-slide">
	<div class="wrap-slick1">
		<div class="slick1">
			<?php
			$banners = $mysqli->query("
        SELECT image, headline, subtext
          FROM banners
         ORDER BY created_at DESC
         LIMIT 3
      ");
			while ($b = $banners->fetch_assoc()):
			?>
				<div class="item-slick1"
					style="background-image: url('<?= BASE_URL ?>/assets/<?= htmlspecialchars($b['image']) ?>');"
					data-thumb="<?= BASE_URL ?>/assets/<?= htmlspecialchars($b['image']) ?>"
					data-caption="<?= htmlspecialchars($b['headline']) ?>">
					<div class="container h-full">
						<div class="flex-col-l-m h-full p-t-100 p-b-30 respon5">
							<div class="layer-slick1 animated visible-false"
								data-appear="fadeInDown"
								data-delay="0">
								<span class="ltext-101 cl2 respon2">
									<?= htmlspecialchars($b['headline']) ?>
								</span>
							</div>
							<div class="layer-slick1 animated visible-false"
								data-appear="fadeInUp"
								data-delay="800">
								<h2 class="ltext-201 cl2 p-t-19 p-b-43 respon1">
									<?= htmlspecialchars($b['subtext']) ?>
								</h2>
							</div>
							<div class="layer-slick1 animated visible-false"
								data-appear="zoomIn"
								data-delay="1600">
								<a href="<?= BASE_URL ?>/views/site/product.php"
									class="flex-c-m stext-101 cl0 size-101 bg1 bor1 hov-btn1 p-lr-15 trans-04">
									Shop Now
								</a>
							</div>
						</div>
					</div>
				</div>
			<?php endwhile; ?>
		</div>
	</div>
</section>
<!-- Category Highlights -->
<!-- Category Highlights -->
<div class="sec-banner bg0 p-t-80 p-b-50">
  <div style="width: 95%;" class="container">
    <div class="row">
      <?php
        // grab first 3 categories
        $items = $mysqli->query("
          SELECT id, name, image, description
            FROM categories
           ORDER BY id
           LIMIT 3
        ");
        while ($it = $items->fetch_assoc()):
      ?>
        <div class="col-md-6 col-xl-4 p-b-30 m-lr-auto">
          <div class="block1 wrap-pic-w">
            <!-- now using BASE_URL plus the raw image path -->
            <img src="<?= BASE_URL . '/' . htmlspecialchars($it['image']) ?>"
                 alt="<?= htmlspecialchars($it['name']) ?>">
            <a href="<?= BASE_URL ?>/views/site/product.php?cat_id=<?= $it['id'] ?>"
               class="block1-txt ab-t-l s-full flex-col-l-sb p-lr-38 p-tb-34 trans-03 respon3">
              <div class="block1-txt-child1 flex-col-l">
                <span class="block1-name ltext-102">
                  <?= htmlspecialchars($it['name']) ?>
                </span>
                <span class="block1-info stext-102">
                  <?= htmlspecialchars($it['description']) ?>
                </span>
              </div>
              <div class="block1-txt-child2 p-b-4 trans-05">
                <div class="block1-link stext-101 cl0 trans-09">
                  Shop Now
                </div>
              </div>
            </a>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</div>


<section class="bg0 p-t-23 p-b-140">
	<div class="container">
		<div class="p-b-10">
			<h3 class="ltext-103 cl5">Product Overview</h3>
		</div>

		<!-- Category filters -->
		<div class="flex-w flex-sb-m p-b-52">
			<div class="flex-w flex-l-m filter-tope-group m-tb-10">
				<button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5 how-active1" data-filter="*">
					All Products
				</button>
				<?php
				$cats = $mysqli->query("SELECT id, name FROM categories ORDER BY name");
				while ($c = $cats->fetch_assoc()):
				?>
					<button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5"
						data-filter=".cat-<?= $c['id'] ?>">
						<?= htmlspecialchars($c['name']) ?>
					</button>
				<?php endwhile; ?>
			</div>
		</div>

		<!-- Product grid -->
		<div class="row isotope-grid">
			<?php
			$prods = $mysqli->query("
    SELECT p.id, p.name, p.price_usd, p.image, c.id AS cat_id
      FROM products p
      JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1
  ");
			while ($p = $prods->fetch_assoc()):
			?>
				<div class="col-sm-6 col-md-4 col-lg-3 p-b-35 isotope-item cat-<?= $p['cat_id'] ?>">
					<div class="block2">
						<div class="block2-pic hov-img0">
							<img src="<?= BASE_URL ?>/<?= htmlspecialchars($p['image']) ?>"
								alt="<?= htmlspecialchars($p['name']) ?>">
							<a href="<?= BASE_URL ?>/views/site/product-detail.php?id=<?= $p['id'] ?>"
								class="block2-btn flex-c-m stext-103 cl2 size-102 bg0 bor2 hov-btn1 p-lr-15 trans-04">
								Quick View
							</a>
						</div>
						<div class="block2-txt flex-w flex-t p-t-14">
							<!-- Name & Price -->
							<div class="block2-txt-child1 flex-col-l">
								<a href="<?= BASE_URL ?>/views/site/product-detail.php?id=<?= $p['id'] ?>"
									class="stext-104 cl4 hov-cl1 trans-04 js-name-b2 p-b-6">
									<?= htmlspecialchars($p['name']) ?>
								</a>
								<span class="stext-105 cl3">
									$<?= number_format($p['price_usd'], 2) ?>
								</span>
							</div>
							<!-- Wishlist button -->
							<div class="block2-txt-child2 flex-r p-t-3">
								<a href="#" class="btn-addwish-b2 dis-block pos-relative js-addwish-b2">
									<img class="icon-heart1 dis-block trans-04"
										src="<?= BASE_URL ?>/assets/images/icons/icon-heart-01.png"
										alt="Add to wishlist">
									<img class="icon-heart2 dis-block trans-04 ab-t-l"
										src="<?= BASE_URL ?>/assets/images/icons/icon-heart-02.png"
										alt="Added to wishlist">
								</a>
							</div>
						</div>
					</div>
				</div>
			<?php endwhile; ?>
		</div>

		<!-- Load more button -->
		<div class="flex-c-m flex-w w-full p-t-45">
			<a href="<?= BASE_URL ?>/views/site/product.php"
				class="flex-c-m stext-101 cl5 size-103 bg2 bor1 hov-btn1 p-lr-15 trans-04">
				Load More
			</a>
		</div>
	</div>
</section>


<!-- after loading vendor/isotope/isotope.pkgd.min.js -->
<script>
	jQuery(function($) {
		// 1) initialize
		var $grid = $('.isotope-grid').isotope({
			itemSelector: '.isotope-item',
			layoutMode: 'fitRows'
		});

		// 2) wire up your filter buttons
		$('.filter-tope-group').on('click', 'button', function() {
			var filterValue = $(this).attr('data-filter');
			$grid.isotope({
				filter: filterValue
			});

			// swap the “active” class
			$('.filter-tope-group .how-active1').removeClass('how-active1');
			$(this).addClass('how-active1');
		});
	});
</script>

<?php
// 3) Shared footer include (closes </body></html>, and loads your JS)
include __DIR__ . '/../includes/footer.php';
