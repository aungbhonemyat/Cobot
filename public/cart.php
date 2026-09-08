<?php
require_once __DIR__ . '/../app/helpers.php';
requireLogin();

$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $id = (int) ($_POST['id'] ?? 0);
        $qty = max(1, (int) ($_POST['quantity'] ?? 1));
        addToCart($id, $qty);
        flash('success', 'Added to cart.');
        redirect('/cart.php');
    }

    if (isset($_POST['update'])) {
        foreach ($_POST['qty'] as $iid => $q) {
            updateCartItem((int) $iid, (int) $q);
        }
        flash('success', 'Cart updated.');
        redirect('/cart.php');
    }

    if (isset($_POST['remove'])) {
        $id = (int) ($_POST['remove'] ?? 0);
        removeFromCart($id);
        flash('success', 'Removed from cart.');
        redirect('/cart.php');
    }

    if (isset($_POST['place_order'])) {
        $items = cartItemsDetailed();
        if (empty($items)) {
            flash('error', 'Cart is empty.');
            redirect('/cart.php');
        }
        $orderId = createOrderFromCart((int) $user['id'], $items);
        emptyCart();
        flash('success', 'Order placed. Order ID: ' . $orderId);
        redirect('/order-confirmation.php?order_id=' . (int) $orderId);
    }
}

$items = cartItemsDetailed();
$total = cartTotal();

$title = 'Your cart';
$currentPage = 'cart.php';
include __DIR__ . '/../app/partials/header.php';
?>
<section class="page-header">
    <div>
        <p class="eyebrow">Cart</p>
        <h1>Your shopping cart</h1>
    </div>
</section>

<div class="section-block">
    <?php if (empty($items)): ?>
        <p>Your cart is empty. <a href="/items.php">Browse items</a></p>
    <?php else: ?>
        <form method="post">
            <table>
                <thead>
                    <tr><th>Item</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= e($it['name']) ?></td>
                        <td><?= formatPrice((float) $it['price']) ?></td>
                        <td><input type="number" name="qty[<?= (int) $it['id'] ?>]" value="<?= (int) $it['quantity'] ?>" min="1"></td>
                        <td><?= formatPrice((float) $it['subtotal']) ?></td>
                        <td>
                            <button type="submit" name="remove" value="<?= (int) $it['id'] ?>">Remove</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top:12px;display:flex;gap:12px;align-items:center">
                <button type="submit" name="update">Update cart</button>
                <strong style="margin-left:auto">Total: <?= formatPrice((float) $total) ?></strong>
                <button type="submit" name="place_order" class="primary-btn">Place order</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../app/partials/footer.php'; ?>
