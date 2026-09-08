<?php
require_once __DIR__ . '/../../app/helpers.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_item'])) {
        $id = (int) $_POST['delete_item'];
        if ($id > 0) {
            deleteItem($id);
            flash('success', 'Item deleted successfully.');
        }
        redirect('/admin/items.php');
    }

    if (isset($_POST['save_item'])) {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $details = trim($_POST['details'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? 'General');
        $categories = getItemCategories();

        if (!in_array($category, $categories, true)) {
            $category = 'General';
        }

        if ($name === '' || $description === '' || $details === '' || $price <= 0) {
            flash('error', 'Please complete all item fields with a valid price.');
            redirect('/admin/items.php');
        }

        $existing = $id > 0 ? findItemById($id) : null;
        $imageName = $existing['image'] ?? null;

        if (!empty($_FILES['image']['name'])) {
            $uploaded = uploadItemImage($_FILES['image']);
            if ($uploaded) {
                if ($imageName && is_string($imageName) && $imageName !== '' && file_exists(UPLOAD_DIR . $imageName)) {
                    unlink(UPLOAD_DIR . $imageName);
                }
                $imageName = $uploaded;
            } else {
                flash('error', 'Image upload failed. Please upload a valid JPG, PNG, GIF, WEBP, or SVG image.');
                redirect('/admin/items.php');
            }
        }

        $saveData = [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'details' => $details,
            'price' => $price,
            'image' => $imageName,
            'category' => $category,
        ];

        $savedId = saveItem($saveData);
        if ($savedId > 0) {
            $barcode = generateItemBarcodeValue($savedId, $name);
            setItemBarcode($savedId, $barcode);
        }

        flash('success', $id > 0 ? 'Item updated successfully.' : 'Item created successfully.');
        redirect('/admin/items.php');
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editingItem = $editId > 0 ? findItemById($editId) : null;
$items = getAllItems();
$categories = getItemCategories();
$totalCount = count(getAllItems());
$totalValueUsd = getItemsTotal();

$title = 'Item Management';
$currentPage = 'items.php';
include __DIR__ . '/../../app/partials/header.php';
?>
<section class="page-header">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Manage items</h1>
    </div>
    <div style="display:flex;gap:12px;align-items:center">
        <div style="text-align:right">
            <div style="color:var(--muted);font-weight:700">Total items</div>
            <div style="font-size:1.2rem;font-weight:800"><?= (int) $totalCount ?></div>
        </div>
        <div style="text-align:right">
            <div style="color:var(--muted);font-weight:700">Total value (<?= defined('CURRENCY') ? CURRENCY : 'MMK' ?>)</div>
            <div style="font-size:1.1rem;font-weight:800"><?= formatPrice((float) $totalValueUsd) ?></div>
        </div>
        <a class="primary-btn" href="/admin/items.php">Add item</a>
    </div>
</section>

<div class="admin-layout">
    <aside class="admin-form-card">
        <h2><?= $editingItem ? 'Edit item' : 'Add item' ?></h2>
        <form method="post" class="form-grid" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= (int) ($editingItem['id'] ?? 0) ?>">

            <label>
                <span>Name</span>
                <input type="text" name="name" value="<?= e($editingItem['name'] ?? '') ?>" required>
            </label>

            <label>
                <span>Category</span>
                <select name="category" required>
                    <?php foreach ($categories as $categoryOption): ?>
                        <option value="<?= e($categoryOption) ?>" <?= (($editingItem['category'] ?? 'General') === $categoryOption) ? 'selected' : '' ?>><?= e($categoryOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                <span>Short description</span>
                <textarea name="description" rows="3" required><?= e($editingItem['description'] ?? '') ?></textarea>
            </label>

            <label>
                <span>Details</span>
                <textarea name="details" rows="5" required><?= e($editingItem['details'] ?? '') ?></textarea>
            </label>

            <label>
                <span>Price (MMK)</span>
                <input type="number" name="price" step="1" min="0" value="<?= e((string) ($editingItem['price'] ?? 0)) ?>" required>
            </label>

            <label>
                <span>Image</span>
                <input type="file" name="image" accept="image/*">
            </label>

            <button type="submit" name="save_item" class="primary-btn">Save item</button>
        </form>
    </aside>

    <section class="admin-table-card">
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price (MMK)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><img class="mini-thumb" src="<?= e(itemImageUrl($item['image'])) ?>" alt="<?= e($item['name']) ?>"></td>
                    <td><?= e($item['name']) ?></td>
                    <td><?= e($item['category'] ?? 'General') ?></td>
                    <td><?= formatPrice((float) $item['price']) ?></td>
                    <td class="action-buttons">
                        <a href="/admin/items.php?edit=<?= (int) $item['id'] ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Delete this item?');">
                            <input type="hidden" name="delete_item" value="<?= (int) $item['id'] ?>">
                            <button type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
<?php include __DIR__ . '/../../app/partials/footer.php'; ?>
