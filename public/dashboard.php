<?php
require_once __DIR__ . '/../app/helpers.php';
requireLogin();

$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['clear_messages']) && (($user['role'] ?? '') === 'admin')) {
        clearMessages();
        flash('success', 'All messages cleared.');
        redirect('/dashboard.php');
    }

    if (isset($_POST['chat_message'])) {
        $message = trim($_POST['chat_message'] ?? '');
        $isAnnouncement = !empty($_POST['announce']) && (($user['role'] ?? '') === 'admin');

        if ($message === '') {
            flash('error', 'Message cannot be empty.');
            redirect('/dashboard.php');
        }

        $sent = sendMessage((int) $user['id'], $message, $isAnnouncement);
        flash($sent ? 'success' : 'error', $sent ? ($isAnnouncement ? 'Announcement sent to all users.' : 'Message sent successfully.') : 'Unable to send message.');
        redirect('/dashboard.php');
    }
}

$stats = [
    'users' => getCount('users'),
    'items' => getCount('items'),
];
$recentItems = array_slice(getAllItems(), 0, 3);
$messages = getAllMessages(30);

$title = 'Dashboard';
$currentPage = basename(__FILE__);
include __DIR__ . '/../app/partials/header.php';
?>
<section class="page-header">
    <div>
        <p class="eyebrow">Overview</p>
        <h1>Welcome back, <?= e($user['name']) ?>!</h1>
    </div>
</section>

<div class="dashboard-layout">
    <main class="dashboard-main">
        <?php if (($user['role'] ?? '') === 'admin'): ?>
            <div class="stats-grid">
                <article class="stat-card">
                    <span>Total users</span>
                    <strong><?= $stats['users'] ?></strong>
                </article>
                <article class="stat-card">
                    <span>Total items</span>
                    <strong><?= $stats['items'] ?></strong>
                </article>
                <article class="stat-card">
                    <span>Access</span>
                    <strong>Admin</strong>
                </article>
            </div>
        <?php else: ?>
            <div class="welcome-panel">
                <p>Explore our catalog, compare products, and discover items that match your lifestyle.</p>
                <a class="primary-btn" href="/items.php">Browse catalog</a>
            </div>
        <?php endif; ?>

        <section class="section-block">
            <div class="section-heading">
                <h2>Spotlight items</h2>
                <a href="/items.php">View all</a>
            </div>

            <div class="product-grid">
                <?php foreach ($recentItems as $item): ?>
                    <article class="product-card">
                        <img src="<?= e(itemImageUrl($item['image'])) ?>" alt="<?= e($item['name']) ?>">
                        <div class="product-body">
                            <h3><?= e($item['name']) ?></h3>
                            <p><?= e($item['description']) ?></p>
                            <div class="product-meta">
                                <span class="price"><?= formatPrice((float) $item['price']) ?></span>
                                <a href="/item.php?id=<?= (int) $item['id'] ?>">Details</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <aside class="dashboard-sidebar">
        <section class="chat-panel">
            <div class="chat-header">
                <h2>Conversation</h2>
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                    <form method="post" class="inline-form">
                        <button type="submit" name="clear_messages" class="clear-btn">Clear</button>
                    </form>
                <?php endif; ?>
            </div>

            <form method="post" class="chat-form">
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                    <label class="checkbox-row">
                        <input type="checkbox" name="announce" value="1">
                        <span>Send as announcement</span>
                    </label>
                <?php endif; ?>

                <textarea name="chat_message" rows="3" placeholder="<?= (($user['role'] ?? '') === 'admin') ? 'Type an announcement or message...' : 'Write a message to the team...' ?>" required></textarea>
                <button type="submit" class="primary-btn">Send</button>
            </form>

            <div class="chat-box">
                <?php foreach ($messages as $message): ?>
                    <article class="chat-message <?= ((int) ($message['is_announcement'] ?? 0)) === 1 ? 'announcement' : '' ?> <?= ((int) ($message['sender_id'] ?? 0) === (int) $user['id']) ? 'mine' : '' ?>">
                        <div class="chat-meta">
                            <strong><?= e($message['sender_name']) ?></strong>
                            <?php if ((int) ($message['is_announcement'] ?? 0) === 1): ?>
                                <span class="chat-tag">Admin</span>
                            <?php endif; ?>
                            <span class="chat-time"><?= date('M d, Y h:i A', strtotime($message['created_at'])) ?></span>
                        </div>
                        <p><?= nl2br(e($message['message'])) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </aside>
</div>
<?php include __DIR__ . '/../app/partials/footer.php'; ?>
