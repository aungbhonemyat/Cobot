<?php
require_once __DIR__ . '/../app/helpers.php';
requireLogin();

$orderId = (int) ($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    flash('error', 'Invalid order reference.');
    redirect('/items.php');
}

$order = getOrderById($orderId);
if (!$order) {
    flash('error', 'Order not found.');
    redirect('/items.php');
}

// Only allow owner or admin to view
$user = currentUser();
if (($user['role'] ?? '') !== 'admin' && $order['user_id'] !== $user['id']) {
    flash('error', 'You are not authorized to view this order.');
    redirect('/items.php');
}

$items = getOrderItems($orderId);

$title = 'Order #' . $orderId;
$currentPage = 'cart.php';
include __DIR__ . '/../app/partials/header.php';
?>
<section class="page-header">
    <div>
        <p class="eyebrow">Order</p>
        <h1>Order #<?= (int) $orderId ?></h1>
    </div>
</section>

<div class="section-block">
    <p><strong>User:</strong> <?= e($order['name'] ?? $order['email'] ?? 'User') ?></p>
    <p><strong>Total:</strong> <?= formatPrice((float) $order['total']) ?></p>
    <p><strong>Status:</strong> <?= e($order['status']) ?></p>
    <p><strong>Placed:</strong> <?= e($order['created_at']) ?></p>

    <h3>Items</h3>
    <ul>
        <?php foreach ($items as $it): ?>
            <li><?= e($it['name']) ?> — <?= (int)$it['quantity'] ?> × <?= formatPrice((float)$it['price']) ?></li>
        <?php endforeach; ?>
    </ul>

    <a class="primary-btn" href="/items.php">Continue shopping</a>
</div>

<?php include __DIR__ . '/../app/partials/footer.php';
