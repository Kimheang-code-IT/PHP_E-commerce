<?php
// views/site/About.php

// 1) Page metadata
//    title will come from the DB
include __DIR__ . '/../includes/header.php';

// 2) Fetch the “about” page
$stmt = $mysqli->prepare("
  SELECT title, banner_image, heading,
         story_title, story_content, story_image,
         mission_title, mission_content, mission_image,
         quote, quote_author
    FROM pages
   WHERE slug = 'about'
  LIMIT 1
");
$stmt->execute();
$page = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$page) {
	echo '<div class="alert alert-warning">About page not found.</div>';
	include __DIR__ . '/../includes/footer.php';
	exit;
}
?>
<div style="margin-top: 80px;" class="main">
	<!-- Hero Banner -->
	<section class="bg-img1 txt-center p-lr-15 p-tb-92"
		style="background-image: url('<?= BASE_URL ?>/<?= htmlspecialchars($page['banner_image']) ?>');">
		<h2 class="ltext-105 cl0 txt-center">
			<?= htmlspecialchars($page['heading']) ?>
		</h2>
	</section>

	<!-- Story + Image -->
	<section class="bg0 p-t-75 p-b-120">
		<div class="container">
			<div class="row p-b-148">
				<div class="col-md-7 col-lg-8">
					<h3 class="mtext-111 cl2 p-b-16">
						<?= htmlspecialchars($page['story_title']) ?>
					</h3>
					<p class="stext-113 cl6 p-b-26">
						<?= nl2br(htmlspecialchars($page['story_content'])) ?>
					</p>
				</div>
				<div class="col-11 col-md-5 col-lg-4 m-lr-auto">
					<div class="how-bor1">
						<div class="hov-img0">
							<img src="<?= BASE_URL ?>/<?= htmlspecialchars($page['story_image']) ?>" alt="">
						</div>
					</div>
				</div>
			</div>

			<!-- Mission + Image -->
			<div class="row">
				<div class="order-md-2 col-md-7 col-lg-8 p-b-30">
					<h3 class="mtext-111 cl2 p-b-16">
						<?= htmlspecialchars($page['mission_title']) ?>
					</h3>
					<p class="stext-113 cl6 p-b-26">
						<?= nl2br(htmlspecialchars($page['mission_content'])) ?>
					</p>
					<div class="bor16 p-l-29 p-b-9 m-t-22">
						<p class="stext-114 cl6 p-r-40 p-b-11">
							<?= htmlspecialchars($page['quote']) ?>
						</p>
						<span class="stext-111 cl8">
							– <?= htmlspecialchars($page['quote_author']) ?>
						</span>
					</div>
				</div>
				<div class="order-md-1 col-11 col-md-5 col-lg-4 m-lr-auto p-b-30">
					<div class="how-bor2">
						<div class="hov-img0">
							<img src="<?= BASE_URL ?>/<?= htmlspecialchars($page['mission_image']) ?>" alt="">
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>