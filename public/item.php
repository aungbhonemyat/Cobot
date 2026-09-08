<?php
require_once __DIR__ . '/../app/helpers.php';
requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$item = findItemById($id);

if (!$item) {
    flash('error', 'Item not found.');
    redirect('/items.php');
}

if (empty($item['barcode'])) {
    $barcode = generateItemBarcodeValue((int) $item['id'], (string) $item['name']);
    setItemBarcode((int) $item['id'], $barcode);
    $item['barcode'] = $barcode;
}

$title = $item['name'];
$currentPage = 'items.php';
include __DIR__ . '/../app/partials/header.php';
?>
<section class="item-detail">
    <div class="item-image-wrap">
        <img src="<?= e(itemImageUrl($item['image'])) ?>" alt="<?= e($item['name']) ?>">
    </div>
    <div class="item-info">
        <p class="eyebrow">Product</p>
        <h1><?= e($item['name']) ?></h1>
        <p class="product-category detail-category"><?= e($item['category'] ?? 'General') ?></p>
        <p class="price large"><?= formatPrice((float) $item['price']) ?></p>
        <p class="description"><?= e($item['description']) ?></p>
        <div class="detail-box">
            <h3>Barcode</h3>
            <div class="barcode-wrap"><?= renderItemBarcodeSvg((string) $item['barcode']) ?></div>
            <p class="barcode-code"><?= e($item['barcode']) ?></p>
        </div>
        <div class="detail-box">
            <h3>Details</h3>
            <p><?= nl2br(e($item['details'])) ?></p>
        </div>

        <?php $cu = currentUser(); if (($cu['role'] ?? '') !== 'admin'): ?>
        <form method="post" action="/api/cart.php" class="add-to-cart-form" style="margin-top:12px;display:flex;gap:8px;align-items:center">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
            <label>
                Qty: <input type="number" name="quantity" value="1" min="1" style="width:72px;margin-left:6px">
            </label>
            <button type="submit" class="small-btn">Add</button>
            <a class="primary-btn" href="/items.php" style="background:#e5e7eb;color:var(--text);">Back to catalog</a>
        </form>
        <?php else: ?>
            <a class="primary-btn" href="/items.php" style="background:#e5e7eb;color:var(--text);">Back to catalog</a>
        <?php endif; ?>
    </div>
</section>
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
