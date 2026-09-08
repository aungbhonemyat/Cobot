<?php
require_once __DIR__ . '/../app/helpers.php';
requireLogin();

$user = currentUser();
if (($user['role'] ?? '') === 'admin') {
    redirect('/dashboard.php');
}

$orders = getOrdersByUser((int) $user['id']);
$title = 'My Orders';
$currentPage = 'orders.php';
include __DIR__ . '/../app/partials/header.php';
?>
<section class="page-header">
    <div>
        <p class="eyebrow">Account</p>
        <h1>My orders</h1>
    </div>
</section>

<div class="section-block">
    <?php if (empty($orders)): ?>
        <p>You have not placed any orders yet.</p>
        <a class="primary-btn" href="/items.php">Browse items</a>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Placed</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= (int) $order['id'] ?></td>
                        <td><?= formatPrice((float) $order['total']) ?></td>
                        <td><?= e($order['status']) ?></td>
                        <td><?= e($order['created_at']) ?></td>
                        <td><a href="/order-confirmation.php?order_id=<?= (int) $order['id'] ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../app/partials/footer.php'; ?>
