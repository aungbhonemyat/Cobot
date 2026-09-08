<?php
require_once __DIR__ . '/../../app/helpers.php';
requireAdmin();

$editId = (int) ($_GET['edit'] ?? 0);
$editingCategory = $editId > 0 ? null : null;
foreach (getAllCategories() as $category) {
    if ((int) $category['id'] === $editId) {
        $editingCategory = $category;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_category'])) {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            flash('error', 'Category name is required.');
            redirect('/admin/categories.php');
        }

        if ($id > 0) {
            $updated = updateCategory($id, $name);
            flash($updated ? 'success' : 'error', $updated ? 'Category updated.' : 'Could not update category.');
        } else {
            $created = createCategory($name);
            flash($created ? 'success' : 'error', $created ? 'Category added.' : 'Could not add category. It may already exist.');
        }

        redirect('/admin/categories.php');
    }

    if (isset($_POST['delete_category'])) {
        $id = (int) $_POST['delete_category'];
        if ($id > 0) {
            deleteCategory($id);
            flash('success', 'Category deleted.');
        }
        redirect('/admin/categories.php');
    }
}

$categories = getAllCategories();
$title = 'Categories';
$currentPage = 'categories.php';
include __DIR__ . '/../../app/partials/header.php';
?>
<section class="page-header">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Manage categories</h1>
    </div>
</section>

<div class="admin-layout">
    <aside class="admin-form-card">
        <h2><?= $editingCategory ? 'Edit category' : 'Add category' ?></h2>
        <form method="post" class="form-grid">
            <?php if ($editingCategory): ?>
                <input type="hidden" name="id" value="<?= (int) $editingCategory['id'] ?>">
            <?php endif; ?>

            <label>
                <span>Name</span>
                <input type="text" name="name" value="<?= e($editingCategory['name'] ?? '') ?>" required>
            </label>

            <button type="submit" name="save_category" class="primary-btn"><?= $editingCategory ? 'Update' : 'Add' ?></button>
            <?php if ($editingCategory): ?>
                <a class="secondary-link" href="/admin/categories.php">Cancel edit</a>
            <?php endif; ?>
        </form>
    </aside>

    <section class="admin-table-card">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td><?= e($c['name']) ?></td>
                    <td><?= e($c['created_at']) ?></td>
                    <td class="action-buttons">
                        <a href="/admin/categories.php?edit=<?= (int) $c['id'] ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Delete this category?');">
                            <input type="hidden" name="delete_category" value="<?= (int) $c['id'] ?>">
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
