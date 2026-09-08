<?php
require_once __DIR__ . '/config.php';

// Clean single-file DB layer with `approved` support.
function getDb(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        initializeDatabase($pdo);
    }
    return $pdo;
}

function initializeDatabase(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT "user",
        approved INTEGER NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT NOT NULL,
        details TEXT NOT NULL,
        price REAL NOT NULL DEFAULT 0,
        image TEXT,
        category TEXT NOT NULL DEFAULT "General",
        barcode TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sender_id INTEGER,
        sender_name TEXT NOT NULL,
        message TEXT NOT NULL,
        is_announcement INTEGER NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL
    )');

    // Orders tables
    $pdo->exec('CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        total INTEGER NOT NULL DEFAULT 0,
        status TEXT NOT NULL DEFAULT "pending",
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        item_id INTEGER NOT NULL,
        price INTEGER NOT NULL,
        quantity INTEGER NOT NULL DEFAULT 1,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
    )');

    try {
        $pdo->query('SELECT is_announcement FROM messages LIMIT 1');
    } catch (Exception $e) {
        try {
            $pdo->exec('ALTER TABLE messages ADD COLUMN is_announcement INTEGER NOT NULL DEFAULT 0');
        } catch (Exception $ignored) {
        }
    }

    try {
        $pdo->query('SELECT approved FROM users LIMIT 1');
    } catch (Exception $e) {
        try {
            $pdo->exec('ALTER TABLE users ADD COLUMN approved INTEGER NOT NULL DEFAULT 0');
        } catch (Exception $ignored) {
        }
    }

    try {
        $pdo->query('SELECT barcode FROM items LIMIT 1');
    } catch (Exception $e) {
        try {
            $pdo->exec('ALTER TABLE items ADD COLUMN barcode TEXT');
        } catch (Exception $ignored) {
        }
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE email = "admin@demo.com"')->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, approved) VALUES (?, ?, ?, "admin", 1)');
        $stmt->execute(['Administrator', 'admin@demo.com', password_hash('admin123', PASSWORD_DEFAULT)]);
    } else {
        $pdo->exec('UPDATE users SET approved = 1 WHERE email = "admin@demo.com"');
    }
}

function getAllMessages(int $limit = 50): array
{
    $stmt = getDb()->prepare('SELECT m.*, u.role FROM messages m LEFT JOIN users u ON u.id = m.sender_id ORDER BY m.created_at ASC LIMIT ?');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Orders helpers
function createOrder(int $userId, int $total, string $status = 'pending'): int
{
    $stmt = getDb()->prepare('INSERT INTO orders (user_id, total, status) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $total, $status]);
    return (int) getDb()->lastInsertId();
}

function addOrderItem(int $orderId, int $itemId, int $price, int $quantity): bool
{
    $stmt = getDb()->prepare('INSERT INTO order_items (order_id, item_id, price, quantity) VALUES (?, ?, ?, ?)');
    return (bool) $stmt->execute([$orderId, $itemId, $price, $quantity]);
}

function getOrdersByUser(int $userId): array
{
    $stmt = getDb()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getAllOrders(): array
{
    $stmt = getDb()->query('SELECT o.*, u.email, u.name, u.role FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC');
    return $stmt->fetchAll();
}

function getOrderItems(int $orderId): array
{
    $stmt = getDb()->prepare('SELECT oi.*, i.name FROM order_items oi LEFT JOIN items i ON i.id = oi.item_id WHERE oi.order_id = ?');
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

function getOrderById(int $orderId): ?array
{
    $stmt = getDb()->prepare('SELECT o.*, u.email, u.name FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function createOrderFromCart(int $userId, array $cartItems): int
{
    $total = 0;
    foreach ($cartItems as $c) {
        $total += (int) ($c['price'] * $c['quantity']);
    }
    $orderId = createOrder($userId, $total, 'pending');
    foreach ($cartItems as $c) {
        addOrderItem($orderId, (int) $c['id'], (int) $c['price'], (int) $c['quantity']);
    }
    return $orderId;
}

function sendMessage(int $senderId, string $message, bool $isAnnouncement = false): bool
{
    $message = trim($message);
    if ($message === '') {
        return false;
    }

    $user = getUserById($senderId);
    if (!$user) {
        return false;
    }

    $stmt = getDb()->prepare('INSERT INTO messages (sender_id, sender_name, message, is_announcement) VALUES (?, ?, ?, ?)');
    return $stmt->execute([$senderId, $user['name'], $message, $isAnnouncement ? 1 : 0]);
}

function clearMessages(): void
{
    $stmt = getDb()->prepare('DELETE FROM messages');
    $stmt->execute();
}

function findUserByEmail(string $email): ?array
{
    $stmt = getDb()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function getUserById(int $id): ?array
{
    $stmt = getDb()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function getAllUsers(): array
{
    $stmt = getDb()->query('SELECT * FROM users ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function registerUser(string $name, string $email, string $password, int $approved = 0): bool
{
    $email = strtolower(trim($email));
    $name = trim($name);

    if ($name === '' || $email === '' || $password === '') {
        return false;
    }

    if (findUserByEmail($email)) {
        return false;
    }

    $stmt = getDb()->prepare('INSERT INTO users (name, email, password, role, approved) VALUES (?, ?, ?, "user", ?)');
    return $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $approved]);
}

function updateUser(int $id, string $name, string $email, string $role, int $approved = 1, ?string $password = null): bool
{
    $email = strtolower(trim($email));
    $name = trim($name);

    if ($name === '' || $email === '') {
        return false;
    }

    $user = getUserById($id);
    if (!$user) {
        return false;
    }

    $query = 'UPDATE users SET name = ?, email = ?, role = ?, approved = ?';
    $params = [$name, $email, $role, $approved];

    if ($password !== null && $password !== '') {
        $query .= ', password = ?';
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    $query .= ' WHERE id = ?';
    $params[] = $id;

    $stmt = getDb()->prepare($query);
    return $stmt->execute($params);
}

function deleteUser(int $id): void
{
    $stmt = getDb()->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);
}

function getCount(string $table): int
{
    $stmt = getDb()->query("SELECT COUNT(*) FROM {$table}");
    return (int) $stmt->fetchColumn();
}

function getAllItems(): array
{
    $stmt = getDb()->query('SELECT * FROM items ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function searchItems(array $filters = []): array
{
    $sql = 'SELECT * FROM items';
    $where = [];
    $params = [];

    if (!empty($filters['category']) && $filters['category'] !== 'All') {
        $where[] = 'category = ?';
        $params[] = $filters['category'];
    }
    if (isset($filters['price_min']) && $filters['price_min'] !== '') {
        $where[] = 'price >= ?';
        $params[] = (int) $filters['price_min'];
    }
    if (isset($filters['price_max']) && $filters['price_max'] !== '') {
        $where[] = 'price <= ?';
        $params[] = (int) $filters['price_max'];
    }
    if (!empty($filters['date_from'])) {
        $where[] = 'date(created_at) >= date(?)';
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $where[] = 'date(created_at) <= date(?)';
        $params[] = $filters['date_to'];
    }

    if (count($where) > 0) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY created_at DESC';
    $stmt = getDb()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getItemsTotal(): float
{
    $stmt = getDb()->query('SELECT COALESCE(SUM(price), 0) as total FROM items');
    $row = $stmt->fetch();
    return (float) ($row['total'] ?? 0.0);
}

function findItemById(int $id): ?array
{
    $stmt = getDb()->prepare('SELECT * FROM items WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    return $item ?: null;
}

function getItemCategories(): array
{
    $stmt = getDb()->query('SELECT name FROM categories ORDER BY name ASC');
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($rows)) {
        return ['General'];
    }
    return $rows;
}

function getAllCategories(): array
{
    $stmt = getDb()->query('SELECT * FROM categories ORDER BY name ASC');
    return $stmt->fetchAll();
}

function createCategory(string $name): bool
{
    $name = trim($name);
    if ($name === '') {
        return false;
    }

    try {
        $stmt = getDb()->prepare('INSERT INTO categories (name) VALUES (?)');
        return (bool) $stmt->execute([$name]);
    } catch (Exception $e) {
        return false;
    }
}

function updateCategory(int $id, string $name): bool
{
    $name = trim($name);
    if ($id <= 0 || $name === '') {
        return false;
    }

    try {
        $stmt = getDb()->prepare('UPDATE categories SET name = ? WHERE id = ?');
        return (bool) $stmt->execute([$name, $id]);
    } catch (Exception $e) {
        return false;
    }
}

function deleteCategory(int $id): bool
{
    $stmt = getDb()->prepare('DELETE FROM categories WHERE id = ?');
    return (bool) $stmt->execute([$id]);
}

function saveItem(array $data): int
{
    $pdo = getDb();

    if (!empty($data['id'])) {
        $stmt = $pdo->prepare('UPDATE items SET name = ?, description = ?, details = ?, price = ?, image = ?, category = ? WHERE id = ?');
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['details'],
            (float) $data['price'],
            $data['image'],
            $data['category'] ?? 'General',
            (int) $data['id'],
        ]);
        return (int) $data['id'];
    }

    $stmt = $pdo->prepare('INSERT INTO items (name, description, details, price, image, category) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['name'],
        $data['description'],
        $data['details'],
        (float) $data['price'],
        $data['image'],
        $data['category'] ?? 'General',
    ]);

    return (int) $pdo->lastInsertId();
}

function deleteItem(int $id): void
{
    $item = findItemById($id);
    if ($item && !empty($item['image'])) {
        $filePath = UPLOAD_DIR . $item['image'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    $stmt = getDb()->prepare('DELETE FROM items WHERE id = ?');
    $stmt->execute([$id]);
}

function uploadItemImage(array $file): ?string
{
    if (!isset($file['name'], $file['tmp_name']) || empty($file['name'])) {
        return null;
    }

    if (!is_uploaded_file($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes, true)) {
        return null;
    }

    $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($file['name']));
    $target = UPLOAD_DIR . $safeName;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $safeName;
    }

    return null;
}

function removeOldItemImage(?string $oldImage): void
{
    if (!$oldImage) {
        return;
    }

    $filePath = UPLOAD_DIR . $oldImage;
    if (file_exists($filePath) && is_file($filePath)) {
        unlink($filePath);
    }
}

function generateItemBarcodeValue(int $id, string $name): string
{
    $cleanName = strtolower(trim($name));
    $cleanName = preg_replace('/[^a-z0-9]+/', '-', $cleanName) ?? 'item';
    $cleanName = trim($cleanName, '-');
    if ($cleanName === '') {
        $cleanName = 'item';
    }

    $suffix = str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    return strtoupper(substr($cleanName, 0, 18) . '-' . $suffix);
}

function setItemBarcode(int $id, string $barcode): bool
{
    $barcode = trim($barcode);
    if ($id <= 0 || $barcode === '') {
        return false;
    }

    $stmt = getDb()->prepare('UPDATE items SET barcode = ? WHERE id = ?');
    return (bool) $stmt->execute([$barcode, $id]);
}

function deleteOrder(int $orderId): bool
{
    $stmt = getDb()->prepare('DELETE FROM orders WHERE id = ?');
    return (bool) $stmt->execute([$orderId]);
}

function clearAllOrders(): int
{
    $stmt = getDb()->prepare('DELETE FROM orders');
    $stmt->execute();
    return (int) $stmt->rowCount();
}

function clearOrdersByDateRange(?string $dateFrom = null, ?string $dateTo = null, ?int $userId = null): int
{
    $sql = 'DELETE FROM orders WHERE 1 = 1';
    $params = [];

    if ($dateFrom) {
        $sql .= ' AND date(created_at) >= date(?)';
        $params[] = $dateFrom;
    }

    if ($dateTo) {
        $sql .= ' AND date(created_at) <= date(?)';
        $params[] = $dateTo;
    }

    if ($userId) {
        $sql .= ' AND user_id = ?';
        $params[] = $userId;
    }

    $stmt = getDb()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->rowCount();
}
