<?php
// views/site/product-detail.php

// 1) Page metadata
$pageTitle = 'Product Detail';

// 2) Shared header (starts session, defines $mysqli, BASE_URL, outputs <head>…)
include __DIR__ . '/../includes/header.php';

// 3) Get & validate product ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) {
	header('Location: ' . BASE_URL . '/views/site/product.php');
	exit;
}

// 4) Fetch product + category
$stmt = $mysqli->prepare("
    SELECT 
      p.id,
      p.name,
      p.price_usd,
      p.image,
      p.description,
      c.name AS category
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.id = ?
    LIMIT 1
");
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
	echo '<div class="alert alert-warning">Product not found.</div>';
	include __DIR__ . '/../includes/footer.php';
	exit;
}

// 5) Fetch product_meta (Weight, Dimensions, etc.)
$metaStmt = $mysqli->prepare("
    SELECT meta_key, meta_value
      FROM product_meta
     WHERE product_id = ?
     ORDER BY FIELD(meta_key,'Weight','Dimensions','Materials','Color','Size')
");
$metaStmt->bind_param('i', $id);
$metaStmt->execute();
$metaRows = $metaStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$metaStmt->close();

$sizes = $colors = [];
foreach ($metaRows as $m) {
	if ($m['meta_key'] === 'Size') {
		// split on comma and trim whitespace
		$sizes = array_map('trim', explode(',', $m['meta_value']));
	}
	if ($m['meta_key'] === 'Color') {
		$colors = array_map('trim', explode(',', $m['meta_value']));
	}
}

// 6) Prepare display values
$imgUrl    = BASE_URL . '/' . ltrim($product['image'], '/');
$name      = htmlspecialchars($product['name']);
$price     = number_format($product['price_usd'], 2);
$desc      = nl2br(htmlspecialchars($product['description']));
$category  = htmlspecialchars($product['category']);
?>
<div style="margin-top: 80px;" class="main">
	<!-- breadcrumb -->
	<div class="container">
		<div class="bread-crumb flex-w p-l-25 p-r-15 p-t-30 p-lr-0-lg">
			<a href="<?= BASE_URL ?>/index.php" class="stext-109 cl8 hov-cl1 trans-04">
				Home <i class="fa fa-angle-right m-l-9 m-r-10"></i>
			</a>
			<a href="<?= BASE_URL ?>/views/site/product.php" class="stext-109 cl8 hov-cl1 trans-04">
				Shop <i class="fa fa-angle-right m-l-9 m-r-10"></i>
			</a>
			<span class="stext-109 cl4"><?= $name ?></span>
		</div>
	</div>

	<!-- Product Detail -->
	<section class="sec-product-detail bg0 p-t-65 p-b-60">
		<div class="container">
			<div class="row">
				<!-- IMAGES -->
				<div class="col-md-6 col-lg-7 p-b-30">
					<div class="p-l-25 p-r-30 p-lr-0-lg">
						<div class="wrap-slick3 flex-sb flex-w">
							<div class="slick3 gallery-lb">
								<div class="item-slick3" data-thumb="<?= $imgUrl ?>">
									<div class="wrap-pic-w pos-relative">
										<img src="<?= $imgUrl ?>" alt="IMG-PRODUCT">
										<a class="flex-c-m size-108 how-pos1 bor0 fs-16 cl10 bg0 hov-btn3 trans-04"
											href="<?= $imgUrl ?>">
											<i class="fa fa-expand"></i>
										</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- DETAILS -->
				<div class="col-md-6 col-lg-5 p-b-30">
					<div class="p-r-50 p-t-5 p-lr-0-lg">
						<h4 class="mtext-105 cl2 js-name-detail p-b-14"><?= $name ?></h4>
						<span class="mtext-106 cl2">$<?= $price ?></span>
						<p class="stext-102 cl3 p-t-23"><?= $desc ?></p>

						<!-- Add to cart with Size & Color selects -->
						<div class="p-t-33">
							<?php if (isLoggedIn()): ?>
								<form
									class="js-addcart-detail"
									method="post"
									action="<?= BASE_URL ?>/views/site/cart/add.php">
									<input type="hidden" name="product_id" value="<?= $id ?>">

									<?php if (!empty($sizes)): ?>
										<label>Size</label>
										<select name="size" class="form-control mb-3" required>
											<option value="">Choose size</option>
											<?php foreach ($sizes as $sz): ?>
												<option><?= htmlspecialchars($sz) ?></option>
											<?php endforeach; ?>
										</select>
									<?php endif; ?>

									<?php if (!empty($colors)): ?>
										<label>Color</label>
										<select name="color" class="form-control mb-3" required>
											<option value="">Choose color</option>
											<?php foreach ($colors as $cl): ?>
												<option><?= htmlspecialchars($cl) ?></option>
											<?php endforeach; ?>
										</select>
									<?php endif; ?>

									<label>Quantity</label>
									<div class="input-group mb-4" style="max-width:120px">
										<button type="button" class="btn btn-outline-secondary btn-minus">−</button>
										<input
											type="number"
											name="quantity"
											class="form-control text-center"
											value="1"
											min="1"
											required>
										<button type="button" class="btn btn-outline-secondary btn-plus">+</button>
									</div>

									<button type="submit" class="btn btn-primary w-100">
										<i class="bi bi-cart-plus-fill me-1"></i> Add to Cart
									</button>
								</form>
							<?php else: ?>
								<a
									href="<?= BASE_URL ?>/views/site/login.php"
									class="btn btn-outline-secondary w-100">
									<i class="bi bi-lock-fill me-1"></i> Login to Buy
								</a>
							<?php endif; ?>
						</div>
					</div>
					</form>
				</div>
				<!-- Category footer -->
			
			</div>
		</div>
</div>

<!-- TABS -->
<div class="bor10 m-t-50 p-t-43 p-b-40 tab01">
	<ul class="nav nav-tabs" role="tablist">
		<li class="nav-item p-b-10">
			<a class="nav-link active" data-toggle="tab" href="#description" role="tab">Description</a>
		</li>
		<li class="nav-item p-b-10">
			<a class="nav-link" data-toggle="tab" href="#information" role="tab">Additional information</a>
		</li>
		<li class="nav-item p-b-10">
			<a class="nav-link" data-toggle="tab" href="#reviews" role="tab">Reviews</a>
		</li>
	</ul>

	<div class="tab-content p-t-43">
		<!-- DESCRIPTION -->
		<div class="tab-pane fade show active" id="description" role="tabpanel">
			<div class="how-pos2 p-lr-15-md">
				<p class="stext-102 cl6"><?= $desc ?></p>
			</div>
		</div>

		<!-- ADDITIONAL INFORMATION -->
		<div class="tab-pane fade" id="information" role="tabpanel">
			<div class="row">
				<div class="col-sm-10 col-md-8 col-lg-6 m-lr-auto">
					<ul class="p-lr-28 p-lr-15-sm">
						<?php if (count($metaRows)): ?>
							<?php foreach ($metaRows as $row): ?>
								<li class="flex-w flex-t p-b-7">
									<span class="stext-102 cl3 size-205">
										<?= htmlspecialchars($row['meta_key']) ?>
									</span>
									<span class="stext-102 cl6 size-206">
										<?= htmlspecialchars($row['meta_value']) ?>
									</span>
								</li>
							<?php endforeach; ?>
						<?php else: ?>
							<li class="stext-102 cl6">No additional information.</li>
						<?php endif; ?>
					</ul>
				</div>
			</div>
		</div>

		<!-- REVIEWS (stub) -->
		<div class="tab-pane fade" id="reviews" role="tabpanel">
			<p class="stext-102 cl6">No reviews yet.</p>
		</div>
	</div>
</div>
</div>
</section>
</div>
<script>
	document.addEventListener('DOMContentLoaded', () => {
		const form = document.querySelector('.js-addcart-detail');
		form.addEventListener('submit', async e => {
			e.preventDefault();
			const data = new FormData(form);

			try {
				const resp = await fetch(form.action, {
					method: 'POST',
					body: data,
					headers: {
						'Accept': 'application/json'
					}
				});
				const json = await resp.json();
				if (json.success) {
					Swal.fire({
						icon: 'success',
						title: 'Added to cart!',
						text: 'Your item has been added.',
						confirmButtonText: 'OK',
						allowOutsideClick: false,
						allowEscapeKey: false,
						showClass: {
							popup: ''
						},
						hideClass: {
							popup: ''
						},
					}).then(() => {
						// automatically reload the product-detail page
						location.reload();
					});
				} else {
					throw new Error(json.error || 'Add failed');
				}
			} catch (err) {
				Swal.fire({
					icon: 'error',
					title: 'Oops',
					text: err.message
				});
			}
		});
	});
	document.querySelectorAll('.btn-minus').forEach(btn => {
		btn.addEventListener('click', () => {
			const inp = btn.nextElementSibling;
			if (+inp.value > 1) inp.value = +inp.value - 1;
		});
	});
	document.querySelectorAll('.btn-plus').forEach(btn => {
		btn.addEventListener('click', () => {
			const inp = btn.previousElementSibling;
			inp.value = +inp.value + 1;
		});
	});
</script>


<?php include __DIR__ . '/../includes/footer.php'; ?>