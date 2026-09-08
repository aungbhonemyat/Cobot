<?php
require_once __DIR__ . '/db.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(): void
{
    if (!currentUser()) {
        redirect('/login.php');
    }
}

function requireAdmin(): void
{
    requireLogin();

    if ((currentUser()['role'] ?? '') !== 'admin') {
        redirect('/dashboard.php');
    }
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value === null) {
        $message = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);
        return $message;
    }

    $_SESSION[$key] = $value;
    return $value;
}

function formatPrice(float $amount): string
{
    return 'MMK ' . number_format((int) round($amount), 0, '.', ',');
}

function formatPriceMMK(float $amountUsd): string
{
    return 'MMK ' . number_format((int) round($amountUsd), 0, '.', ',');
}

function itemImageUrl(?string $imageName): string
{
    if (!$imageName || $imageName === 'placeholder.svg') {
        return 'https://placehold.co/600x400/1f2937/ffffff?text=Product';
    }

    if (preg_match('/^https?:\/\//', $imageName)) {
        return $imageName;
    }

    $imageName = ltrim($imageName, '/');
    $imageName = preg_replace('#^uploads/items/#', '', $imageName) ?? $imageName;

    $publicPath = APP_ROOT . '/public/uploads/items/' . $imageName;
    $legacyPath = APP_ROOT . '/uploads/items/' . $imageName;

    if (file_exists($publicPath) || file_exists($legacyPath)) {
        return '/uploads/items/' . rawurlencode($imageName);
    }

    return 'https://placehold.co/600x400/1f2937/ffffff?text=Product';
}

// Cart helpers (session-based)
function getCart(): array
{
    return $_SESSION['cart'] ?? [];
}

function addToCart(int $itemId, int $quantity = 1): void
{
    $cart = &$_SESSION['cart'];
    if (!isset($cart) || !is_array($cart)) $cart = [];
    $cart[$itemId] = max(0, (int) ($cart[$itemId] ?? 0) + $quantity);
    if ($cart[$itemId] <= 0) unset($cart[$itemId]);
}

function updateCartItem(int $itemId, int $quantity): void
{
    $cart = &$_SESSION['cart'];
    if (!isset($cart) || !is_array($cart)) $cart = [];
    if ($quantity <= 0) {
        unset($cart[$itemId]);
    } else {
        $cart[$itemId] = (int) $quantity;
    }
}

function removeFromCart(int $itemId): void
{
    $cart = &$_SESSION['cart'];
    if (isset($cart[$itemId])) unset($cart[$itemId]);
}

function emptyCart(): void
{
    unset($_SESSION['cart']);
}

function cartItemsDetailed(): array
{
    $cart = getCart();
    $items = [];
    foreach ($cart as $id => $qty) {
        $it = findItemById((int) $id);
        if ($it) {
            $it['quantity'] = (int) $qty;
            $it['subtotal'] = (int) ($it['price'] * $it['quantity']);
            $items[] = $it;
        }
    }
    return $items;
}

function cartTotal(): int
{
    $total = 0;
    foreach (cartItemsDetailed() as $i) $total += (int) $i['subtotal'];
    return $total;
}

function cartCount(): int
{
    $cart = getCart();
    if (!is_array($cart) || empty($cart)) return 0;
    return array_sum(array_map('intval', $cart));
}

function renderItemBarcodeSvg(string $barcodeText): string
{
    $barcodeText = trim($barcodeText);
    if ($barcodeText === '') {
        return '';
    }

    $patterns = [
        '0' => '00110', '1' => '10001', '2' => '01001', '3' => '11000', '4' => '00101',
        '5' => '10100', '6' => '01100', '7' => '00011', '8' => '10010', '9' => '01010',
        'A' => '10001', 'B' => '01001', 'C' => '11000', 'D' => '00101', 'E' => '10100',
        'F' => '01100', 'G' => '00011', 'H' => '10010', 'I' => '01010', 'J' => '11010',
        'K' => '00111', 'L' => '10110', 'M' => '01110', 'N' => '11100', 'O' => '00001',
        'P' => '10001', 'Q' => '00101', 'R' => '10101', 'S' => '01101', 'T' => '11111',
        'U' => '10000', 'V' => '01000', 'W' => '11000', 'X' => '00100', 'Y' => '10100',
        'Z' => '01100', '-' => '00010', '.' => '10010', ' ' => '01010', '_' => '11010'
    ];

    $bars = '';
    $startStop = '101';
    $bars .= $startStop;

    foreach (str_split($barcodeText) as $char) {
        $pattern = $patterns[$char] ?? $patterns['0'];
        for ($i = 0; $i < strlen($pattern); $i++) {
            $bars .= $pattern[$i] === '1' ? '1' : '0';
        }
    }

    $bars .= $startStop;

    $svgWidth = 500;
    $svgHeight = 150;
    $barHeight = 80;
    $marginLeft = 28;
    $marginTop = 24;
    $barWidth = 3;
    $totalBars = strlen($bars);
    $barUnit = ($svgWidth - ($marginLeft * 2)) / max(1, $totalBars);

    $rects = [];
    for ($i = 0; $i < strlen($bars); $i++) {
        $isBar = $bars[$i] === '1';
        if (!$isBar) {
            continue;
        }

        $x = $marginLeft + ($i * $barUnit);
        $height = $barHeight;
        $y = $marginTop;
        $rects[] = '<rect x="' . number_format($x, 2, '.', '') . '" y="' . $y . '" width="' . number_format(max(1, $barUnit), 2, '.', '') . '" height="' . $height . '" fill="#111827" />';
    }

    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $svgWidth . '" height="' . $svgHeight . '" viewBox="0 0 ' . $svgWidth . ' ' . $svgHeight . '" role="img" aria-label="Barcode: ' . e($barcodeText) . '"><g>' . implode('', $rects) . '</g><text x="50%" y="130" text-anchor="middle" font-size="20" font-family="Arial, sans-serif" fill="#111827" letter-spacing="1">' . e($barcodeText) . '</text></svg>';
}
