<!-- 2) Categories -->
<div class="p-t-55">
    <h4 class="mtext-112 cl2 p-b-33">Categories</h4>
    <ul class="list-unstyled">
        <?php
        $cats = $mysqli->query("SELECT id, name, slug FROM categories ORDER BY name ASC");
        while ($cat = $cats->fetch_assoc()):
        ?>
            <li class="bor18 p-tb-4 p-lr-4">
                <a href="<?= BASE_URL ?>/views/site/index.php?category=<?= urlencode($cat['slug']) ?>"
                    class="dis-block stext-115 cl6 hov-cl1 trans-04">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            </li>
        <?php endwhile; ?>
    </ul>
</div>

<!-- 3) Archive -->
<div class="p-t-55">
    <h4 class="mtext-112 cl2 p-b-33">Archive</h4>
    <ul class="list-unstyled">
        <?php
        $archives = $mysqli->query("
            SELECT YEAR(published_at) AS year,
                   MONTH(published_at) AS month,
                   COUNT(*) AS cnt
              FROM posts
             GROUP BY year, month
             ORDER BY year DESC, month DESC
        ");
        while ($a = $archives->fetch_assoc()):
            $dt    = DateTime::createFromFormat('!m', $a['month']);
            $label = $dt->format('F') . ' ' . $a['year'];
        ?>
            <li class="p-b-7">
                <a href="<?= BASE_URL ?>/views/site/index.php?year=<?= $a['year'] ?>&month=<?= $a['month'] ?>"
                    class="flex-w flex-sb-m stext-115 cl6 hov-cl1 trans-04 p-tb-2">
                    <span><?= htmlspecialchars($label) ?></span>
                    <span>(<?= $a['cnt'] ?>)</span>
                </a>
            </li>
        <?php endwhile; ?>
    </ul>
</div>

<!-- 4) Tags -->
<div class="p-t-50">
    <h4 class="mtext-112 cl2 p-b-27">Tags</h4>
    <div class="flex-w m-r--5">
        <?php
        $tags = $mysqli->query("
            SELECT t.id, t.name, t.slug, COUNT(pt.post_id) AS cnt
              FROM tags t
              LEFT JOIN post_tags pt ON pt.tag_id = t.id
             GROUP BY t.id, t.name, t.slug
             ORDER BY t.name ASC
        ");
        while ($t = $tags->fetch_assoc()):
        ?>
            <a href="<?= BASE_URL ?>/views/site/index.php?tag=<?= urlencode($t['slug']) ?>"
                class="flex-c-m stext-107 cl6 size-301 bor7 p-lr-15 hov-tag1 trans-04 m-r-5 m-b-5">
                <?= htmlspecialchars($t['name']) ?> (<?= $t['cnt'] ?>)
            </a>
        <?php endwhile; ?>
    </div>
</div>