<?php
require_once __DIR__ . '/../app/helpers.php';

if (currentUser()) {
    redirect('/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = findUserByEmail($email);

    if ($user && password_verify($password, $user['password'])) {
        if (isset($user['approved']) && (int) $user['approved'] !== 1) {
            flash('error', 'Your account is not approved yet. Please wait for an administrator to approve it.');
        } else {
            $_SESSION['user'] = $user;
            redirect('/dashboard.php');
        }
    } else {
        flash('error', 'Invalid email or password.');
    }
}

$title = 'Login';
$currentPage = basename(__FILE__);
include __DIR__ . '/../app/partials/header.php';
?>
<section class="auth-wrap">
    <div class="auth-card">
        <h1>Welcome back</h1>
        <p class="subtext">Sign in to continue to your dashboard.</p>

        <form method="post" class="auth-form">
            <label>
                <span>Email</span>
                <input type="email" name="email" required>
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" required>
            </label>
            <button type="submit">Login</button>
        </form>

        <p class="meta-link">Need an account? <a href="/register.php">Create one</a></p>
        <p class="demo-credentials">Demo admin: admin@demo.com / admin123</p>
    </div>
</section>
<?php include __DIR__ . '/../app/partials/footer.php'; ?>
