<?php
require_once __DIR__ . '/../app/helpers.php';

unset($_SESSION['user']);
flash('success', 'You have been logged out.');
redirect('/login.php');
