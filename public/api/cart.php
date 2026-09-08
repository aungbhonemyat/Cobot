<?php
require_once __DIR__ . '/../../app/helpers.php';

// Ensure sessions and consistent JSON-only responses even on errors
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', '0');

set_exception_handler(function ($e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'detail' => $e->getMessage()]);
    exit;
});

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = currentUser();

$result = ['success' => false, 'message' => 'Invalid action'];

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    if ($action === 'add') {
        $id = (int) ($_POST['id'] ?? 0);
        $qty = max(1, (int) ($_POST['quantity'] ?? 1));
        if ($id <= 0) throw new Exception('Invalid item');
        addToCart($id, $qty);
        $result = ['success' => true, 'message' => 'Added to cart', 'cart_count' => array_sum(getCart()), 'cart_total' => cartTotal()];
    } elseif ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $qty = (int) ($_POST['quantity'] ?? 0);
        updateCartItem($id, $qty);
        $result = ['success' => true, 'message' => 'Cart updated', 'cart_count' => array_sum(getCart()), 'cart_total' => cartTotal()];
    } elseif ($action === 'remove') {
        $id = (int) ($_POST['id'] ?? 0);
        removeFromCart($id);
        $result = ['success' => true, 'message' => 'Removed', 'cart_count' => array_sum(getCart()), 'cart_total' => cartTotal()];
    } else {
        throw new Exception('Unsupported action');
    }
} catch (Exception $e) {
    $result = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($result);
