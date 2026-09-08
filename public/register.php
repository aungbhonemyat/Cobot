<?php
require_once __DIR__ . '/../app/helpers.php';

if (currentUser()) {
    redirect('/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        flash('error', 'Please fill in all fields.');
    } elseif (registerUser($name, $email, $password)) {
        flash('success', 'Account created. Awaiting admin approval before you can login.');
        redirect('/login.php');
    } else {
        flash('error', 'Email already exists or data is invalid.');
    }
}

$title = 'Register';
$currentPage = basename(__FILE__);
include __DIR__ . '/../app/partials/header.php';
?>
<section class="auth-wrap">
    <div class="auth-card">
        <h1>Create account</h1>
        <p class="subtext">Start exploring products and managing orders.</p>

        <form method="post" class="auth-form">
            <label>
                <span>Full name</span>
                <input type="text" name="name" required>
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" required>
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" required>
            </label>
            <button type="submit">Register</button>
        </form>

        <p class="meta-link">Already have an account? <a href="/login.php">Login</a></p>
    </div>
</section>
<?php include __DIR__ . '/../app/partials/footer.php'; ?>
