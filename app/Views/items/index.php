<?php
// $items and $categories are expected from controller; provide safe defaults
$items = $items ?? [];
$categories = $categories ?? [];
$filters = $filters ?? $_GET;
?>
<section class="page-header">
    <div>
        <p class="eyebrow">Marketplace</p>
        <h1>Featured products (MVC)</h1>
    </div>
</section>

<section class="filter-bar">
    <form method="get" class="form-grid" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <label>
            <select name="category">
                <option value="All">All categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= (($filters['category'] ?? 'All') === $cat) ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <input type="number" name="price_min" step="1" placeholder="Min price" value="<?= e((string) ($filters['price_min'] ?? '')) ?>">
        </label>

        <label>
            <input type="number" name="price_max" step="1" placeholder="Max price" value="<?= e((string) ($filters['price_max'] ?? '')) ?>">
        </label>

        <label>
            <input type="date" name="date_from" value="<?= e((string) ($filters['date_from'] ?? '')) ?>">
        </label>

        <label>
            <input type="date" name="date_to" value="<?= e((string) ($filters['date_to'] ?? '')) ?>">
        </label>

        <button type="submit" class="primary-btn">Filter</button>
        <a class="primary-btn" href="/items" style="background:#e5e7eb;color:var(--text);">Reset</a>
    </form>
</section>

<div class="product-grid">
    <?php foreach ($items as $item): ?>
        <article class="product-card">
            <img src="<?= e(itemImageUrl($item['image'])) ?>" alt="<?= e($item['name']) ?>">
            <div class="product-body">
                <h3><?= e($item['name']) ?></h3>
                <p class="product-category"><?= e($item['category'] ?? 'General') ?></p>
                <p><?= e($item['description']) ?></p>
                <div class="product-meta">
                    <span class="price"><?= formatPrice((float) $item['price']) ?></span>
                    <a href="/item.php?id=<?= (int) $item['id'] ?>">View details</a>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>
