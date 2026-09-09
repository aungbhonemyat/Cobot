<?php
require_once __DIR__ . '/../helpers.php';

$loggedUser = currentUser();
$currentPage = $currentPage ?? basename($_SERVER['SCRIPT_NAME']);
$successMessage = flash('success');
$errorMessage = flash('error');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Shop Admin'; ?></title>
    <link rel="icon" type="image/png" href="/uploads/items/ABM.png">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <header class="topbar">
        <div class="container nav-wrap">
            <div class="brand">
                <a href="/dashboard.php" class="brand-link">
                    <img src="/uploads/items/ABM.png" alt="ABM MarketBoard" class="brand-logo">
                    <span class="brand-text">ABM MarketBoard</span>
                </a>
            </div>
            <button class="mobile-menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
            <nav class="main-nav" id="mainNav">
                <?php if ($loggedUser): ?>
                    <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="/dashboard.php">Dashboard</a>
                    <a class="<?= $currentPage === 'items.php' ? 'active' : '' ?>" href="/items.php">Catalog</a>
                    <?php if (($loggedUser['role'] ?? '') !== 'admin'): ?>
                        <a class="<?= $currentPage === 'cart.php' ? 'active' : '' ?> cart-link" href="/cart.php">Cart <span id="cart-count" style="display:inline-block;margin-left:6px;min-width:20px;text-align:center">0</span></a>
                        <a class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>" href="/orders.php">Orders</a>
                    <?php else: ?>
                        <a class="<?= $currentPage === 'admin/users.php' ? 'active' : '' ?>" href="/admin/users.php">Users</a>
                        <a class="<?= $currentPage === 'admin/categories.php' ? 'active' : '' ?>" href="/admin/categories.php">Categories</a>
                        <a class="<?= $currentPage === 'admin/items.php' ? 'active' : '' ?>" href="/admin/items.php">Manage Items</a>
                        <a class="<?= $currentPage === 'admin/orders.php' ? 'active' : '' ?>" href="/admin/orders.php">Orders</a>
                    <?php endif; ?>
                    <a href="/logout.php">Logout</a>
                <?php else: ?>
                    <a class="<?= $currentPage === 'login.php' ? 'active' : '' ?>" href="/login.php">Login</a>
                    <a class="<?= $currentPage === 'register.php' ? 'active' : '' ?>" href="/register.php">Register</a>
                <?php endif; ?>
            </nav>
            <?php if ($loggedUser): ?>
                <div class="user-pill">
                    <?= e($loggedUser['name']) ?>
                    <span>(<?= e($loggedUser['role']) ?>)</span>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <script>
    window.updateCartCount = function (n) {
        var el = document.getElementById('cart-count');
        if (!el) return;
        el.textContent = Number(n || 0);
    };
    document.addEventListener('DOMContentLoaded', function () {
        try {
            updateCartCount(<?= (int) (function_exists('cartCount') ? cartCount() : 0) ?>);
        } catch (e) {
            // ignore
        }

        var toggle = document.querySelector('.mobile-menu-toggle');
        var nav = document.getElementById('mainNav');
        if (toggle && nav) {
            toggle.addEventListener('click', function () {
                var isOpen = nav.classList.toggle('open');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        }

        var chatToggle = document.querySelector('.chat-toggle');
        var chatPanel = document.getElementById('chatPanel');
        if (chatToggle && chatPanel) {
            chatToggle.addEventListener('click', function () {
                var isCollapsed = chatPanel.classList.toggle('collapsed');
                chatToggle.textContent = isCollapsed ? 'Maximize' : 'Minimize';
                chatToggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
                chatToggle.setAttribute('aria-label', isCollapsed ? 'Maximize chat' : 'Minimize chat');
            });
        }
    });
    </script>

    <main class="container page-content">
        <?php if ($successMessage): ?>
            <div class="alert success"><?= e($successMessage) ?></div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert error"><?= e($errorMessage) ?></div>
        <?php endif; ?>
