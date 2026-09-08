<?php
require_once __DIR__ . '/../../app/helpers.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user'])) {
        $id = (int) $_POST['delete_user'];
        if ($id > 0 && (int) currentUser()['id'] !== $id) {
            deleteUser($id);
            flash('success', 'User deleted successfully.');
        }
        redirect('/admin/users.php');
    }

    if (isset($_POST['toggle_approve'])) {
        $id = (int) $_POST['toggle_approve'];
        $u = getUserById($id);
        if ($u) {
            $new = (int) (!(int) $u['approved']);
            updateUser($id, $u['name'], $u['email'], $u['role'], $new);
            flash('success', 'User approval updated.');
        }
        redirect('/admin/users.php');
    }

    if (isset($_POST['save_user'])) {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $role = $_POST['role'] ?? 'user';
        $approved = isset($_POST['approved']) ? 1 : 0;
        $password = $_POST['password'] ?? '';

        if ($name === '' || $email === '') {
            flash('error', 'Name and email are required.');
            redirect('/admin/users.php');
        }

            if ($id > 0) {
            $existing = getUserById($id);
            if (!$existing) {
                flash('error', 'User not found.');
                redirect('/admin/users.php');
            }

            $otherUser = findUserByEmail($email);
            if ($otherUser && (int) $otherUser['id'] !== $id) {
                flash('error', 'That email is already in use.');
                redirect('/admin/users.php');
            }

            $result = updateUser($id, $name, $email, $role, $approved, $password);
            flash($result ? 'success' : 'error', $result ? 'User updated successfully.' : 'Unable to update user.');
        } else {
            if (registerUser($name, $email, $password, $approved)) {
                flash('success', 'User created successfully.');
            } else {
                flash('error', 'User could not be created.');
            }
        }

        redirect('/admin/users.php');
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editingUser = $editId > 0 ? getUserById($editId) : null;
$users = getAllUsers();

$title = 'User Management';
$currentPage = 'users.php';
include __DIR__ . '/../../app/partials/header.php';
?>
<section class="page-header">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Manage users</h1>
    </div>
    <a class="primary-btn" href="/admin/users.php">New user</a>
</section>

<div class="admin-layout">
    <aside class="admin-form-card">
        <h2><?= $editingUser ? 'Edit user' : 'Add user' ?></h2>
        <form method="post" class="form-grid">
            <input type="hidden" name="id" value="<?= (int) ($editingUser['id'] ?? 0) ?>">

            <label>
                <span>Name</span>
                <input type="text" name="name" value="<?= e($editingUser['name'] ?? '') ?>" required>
            </label>

            <label>
                <span>Email</span>
                <input type="email" name="email" value="<?= e($editingUser['email'] ?? '') ?>" required>
            </label>

            <label>
                <span>Role</span>
                <select name="role">
                    <option value="user" <?= (($editingUser['role'] ?? 'user') === 'user') ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= (($editingUser['role'] ?? 'user') === 'admin') ? 'selected' : '' ?>>Admin</option>
                </select>
            </label>

            <label>
                <span>Approved</span>
                <input type="checkbox" name="approved" value="1" <?= ((int) ($editingUser['approved'] ?? 0)) === 1 ? 'checked' : '' ?> >
            </label>

            <label>
                <span>Password <?= $editingUser ? '(leave blank to keep current)' : '' ?></span>
                <input type="password" name="password" <?= $editingUser ? '' : 'required' ?>>
            </label>

            <button type="submit" name="save_user" class="primary-btn">Save user</button>
        </form>
    </aside>

    <section class="admin-table-card">
        <table>
            <thead>
                <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Approved</th>
                        <th>Actions</th>
                    </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= e($user['name']) ?></td>
                    <td><?= e($user['email']) ?></td>
                    <td><?= e($user['role']) ?></td>
                        <td><?= ((int) ($user['approved'] ?? 0)) === 1 ? 'Yes' : 'No' ?></td>
                    <td class="action-buttons">
                        <a href="/admin/users.php?edit=<?= (int) $user['id'] ?>">Edit</a>
                        <?php if ((int) currentUser()['id'] !== (int) $user['id']): ?>
                                <form method="post" onsubmit="return confirm('Delete this user?');">
                                    <input type="hidden" name="delete_user" value="<?= (int) $user['id'] ?>">
                                    <button type="submit">Delete</button>
                                </form>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="toggle_approve" value="<?= (int) $user['id'] ?>">
                                    <button type="submit"><?php echo ((int) ($user['approved'] ?? 0)) === 1 ? 'Unapprove' : 'Approve'; ?></button>
                                </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
<?php include __DIR__ . '/../../app/partials/footer.php'; ?>
