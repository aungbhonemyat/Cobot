<?php
require_once __DIR__ . '/../app/helpers.php';
requireLogin();

$categories = getItemCategories();

// Collect filter inputs from query string
$filters = [
    'category' => $_GET['category'] ?? 'All',
    'price_min' => $_GET['price_min'] ?? '',
    'price_max' => $_GET['price_max'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
];

$items = searchItems($filters);

$title = 'Catalog';
$currentPage = basename(__FILE__);
include __DIR__ . '/../app/partials/header.php';
?>
<section class="page-header">
    <div>
        <p class="eyebrow">Marketplace</p>
        <h1>Featured products</h1>
    </div>
</section>

<section class="filter-bar">
    <form method="get" class="form-grid" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <label>
            <select name="category">
                <option value="All">All categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= ($filters['category'] === $cat) ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <input type="number" name="price_min" step="1" placeholder="Min price" value="<?= $filters['price_min'] !== '' ? e((string) (int) $filters['price_min']) : '' ?>">
        </label>

        <label>
            <input type="number" name="price_max" step="1" placeholder="Max price" value="<?= $filters['price_max'] !== '' ? e((string) (int) $filters['price_max']) : '' ?>">
        </label>

        <label>
            <input type="date" name="date_from" value="<?= e($filters['date_from']) ?>">
        </label>

        <label>
            <input type="date" name="date_to" value="<?= e($filters['date_to']) ?>">
        </label>

        <button type="submit" class="primary-btn">Filter</button>
        <a class="primary-btn" href="/items.php" style="background:#e5e7eb;color:var(--text);">Reset</a>
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
                    <?php $cu = currentUser(); if (($cu['role'] ?? '') !== 'admin'): ?>
                    <form method="post" action="/api/cart.php" class="add-to-cart-form" style="display:inline;margin-left:8px">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="small-btn">Add</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/../app/partials/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function submitAdd(e) {
        e.preventDefault();
        const form = e.currentTarget;
        const data = new FormData(form);
        const url = form.getAttribute('action') || form.action;
        fetch(url, { method: 'POST', body: data, credentials: 'same-origin' })
            .then(r => r.text().then(text => ({ status: r.status, ok: r.ok, text })))
            .then(resp => {
                if (!resp.ok) {
                    console.error('Cart API returned non-OK status', resp.status, resp.text);
                    alert('Server error: ' + resp.status + '. See console for details.');
                    return;
                }
                let js = null;
                try { js = JSON.parse(resp.text); } catch (err) {
                    console.error('Failed to parse JSON from cart API', resp.text);
                    alert('Unexpected response from server. See console for details.');
                    return;
                }
                if (js && js.success) {
                    const t = document.createElement('div');
                    t.textContent = js.message || 'Added';
                    t.className = 'alert success';
                    t.style.position = 'fixed';
                    t.style.right = '22px';
                    t.style.bottom = '22px';
                    t.style.zIndex = 9999;
                    document.body.appendChild(t);
                    setTimeout(() => t.remove(), 2000);
                    if (window.updateCartCount && typeof js.cart_count !== 'undefined') {
                        window.updateCartCount(js.cart_count);
                    }
                } else {
                    alert((js && js.message) ? js.message : 'Could not add to cart.');
                }
            }).catch(err => {
                console.error('Fetch error when calling cart API', err);
                alert('Network error: ' + (err && err.message ? err.message : 'See console'));
            });
    }

    document.querySelectorAll('form.add-to-cart-form').forEach(f => f.addEventListener('submit', submitAdd));
});
</script>
