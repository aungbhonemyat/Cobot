<?php
require_once __DIR__ . '/../../app/helpers.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dateFrom = trim((string) ($_POST['date_from'] ?? ''));
    $dateTo = trim((string) ($_POST['date_to'] ?? ''));
    $filterUser = isset($_POST['user']) ? (int) $_POST['user'] : null;

    if (isset($_POST['delete_order'])) {
        $orderId = (int) $_POST['delete_order'];
        deleteOrder($orderId);
        flash('success', 'Order deleted.');
        redirect('/admin/orders.php?user=' . urlencode((string) $filterUser) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo));
    }

    if (isset($_POST['clear_orders'])) {
        $count = clearOrdersByDateRange($dateFrom !== '' ? $dateFrom : null, $dateTo !== '' ? $dateTo : null, $filterUser ?: null);
        flash('success', $count . ' order(s) cleared.');
        redirect('/admin/orders.php');
    }
}

$filterUser = isset($_GET['user']) ? (int) $_GET['user'] : null;
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));

$allOrders = getAllOrders();
$orders = [];
foreach ($allOrders as $order) {
    $role = $order['role'] ?? '';
    if ($role === 'admin') {
        continue;
    }

    if ($filterUser && (int) ($order['user_id'] ?? 0) !== $filterUser) {
        continue;
    }

    $createdDate = date('Y-m-d', strtotime((string) ($order['created_at'] ?? '')));
    if ($dateFrom !== '' && $createdDate < $dateFrom) {
        continue;
    }
    if ($dateTo !== '' && $createdDate > $dateTo) {
        continue;
    }

    $orders[] = $order;
}

usort($orders, function ($a, $b) {
    return strtotime((string) $b['created_at']) <=> strtotime((string) $a['created_at']);
});

$users = array_values(array_filter(getAllUsers(), function ($u) {
    return ($u['role'] ?? '') !== 'admin';
}));

include __DIR__ . '/../../app/partials/header.php';
?>
<section class="page-header">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Orders</h1>
    </div>
</section>

<div class="section-block">
    <form method="get" style="margin-bottom:12px; display:flex; gap:12px; flex-wrap:wrap; align-items:end;">
        <label>
            User:
            <select name="user">
                <option value="">All users</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= (int) $u['id'] ?>" <?= ($filterUser && (int) $u['id'] === $filterUser) ? 'selected' : '' ?>><?= e($u['name']) ?> (<?= e($u['email']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            From date:
            <input type="date" name="date_from" value="<?= e($dateFrom) ?>">
        </label>

        <label>
            To date:
            <input type="date" name="date_to" value="<?= e($dateTo) ?>">
        </label>

        <button type="submit" class="primary-btn">Filter</button>
        <a class="primary-btn" href="/admin/orders.php" style="background:#e5e7eb;color:var(--text);">Reset</a>
        <?php if (!empty($orders)): ?>
            <button type="submit" form="clear-orders-form" name="clear_orders" value="1" class="clear-btn">Clear visible orders</button>
        <?php endif; ?>
    </form>

    <form id="clear-orders-form" method="post" style="display:none;">
        <input type="hidden" name="user" value="<?= e((string) $filterUser) ?>">
        <input type="hidden" name="date_from" value="<?= e($dateFrom) ?>">
        <input type="hidden" name="date_to" value="<?= e($dateTo) ?>">
    </form>

    <?php if (empty($orders)): ?>
        <p>No orders found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>User</th>
                    <th>Total</th>
                    <th>Created</th>
                    <th>Items</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): $items = getOrderItems((int) $o['id']); ?>
                <tr>
                    <td>#<?= (int) $o['id'] ?></td>
                    <td><?= e($o['name'] ?? $o['email'] ?? 'User') ?></td>
                    <td><?= formatPrice((float) $o['total']) ?></td>
                    <td><?= e($o['created_at']) ?></td>
                    <td>
                        <details>
                            <summary>Items (<?= count($items) ?>)</summary>
                            <ul>
                                <?php foreach ($items as $it): ?>
                                    <li><?= e($it['name']) ?> — <?= (int) $it['quantity'] ?> × <?= formatPrice((float) $it['price']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    </td>
                    <td>
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="user" value="<?= e((string) $filterUser) ?>">
                            <input type="hidden" name="date_from" value="<?= e($dateFrom) ?>">
                            <input type="hidden" name="date_to" value="<?= e($dateTo) ?>">
                            <input type="hidden" name="delete_order" value="<?= (int) $o['id'] ?>">
                            <button type="submit" class="clear-btn">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../app/partials/footer.php'; ?>
