<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Your session has expired. Please try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $stmt = db()->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: dashboard.php');
            exit;
        }

        $error = 'Invalid username or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login | <?= htmlspecialchars(SITE_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Source+Serif+4:wght@600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#f3f5f7;color:#20272e;display:grid;place-items:center;min-height:100vh;padding:24px}
.login{background:#fff;border:1px solid #dfe4e8;border-radius:10px;padding:38px;width:100%;max-width:420px;box-shadow:0 14px 36px rgba(23,50,77,.08)}
h1{font-family:'Source Serif 4',serif;color:#17324d;font-size:26px;margin-bottom:8px}
p{color:#5f6b76;font-size:14.5px;margin-bottom:24px}
label{display:block;font-size:14px;font-weight:600;margin-bottom:6px}
input{width:100%;padding:12px;border:1px solid #c8d0d7;border-radius:6px;font:inherit;margin-bottom:16px}
button{width:100%;padding:13px;border:0;border-radius:6px;background:#17324d;color:#fff;font-weight:600;font-size:15px;cursor:pointer}
button:hover{background:#2f6da4}
.error{background:#fdecea;border:1px solid #f0b6b1;color:#b3261e;padding:12px;border-radius:6px;font-size:14px;margin-bottom:18px}
.note{font-size:12.5px;color:#5f6b76;text-align:center;margin-top:18px}
</style>
</head>
<body>
<div class="login">
  <h1>Admin Login</h1>
  <p>Sign in to view and manage program enquiries.</p>

  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="post" action="login.php">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
    <label for="username">Username</label>
    <input id="username" name="username" type="text" required autocomplete="username">
    <label for="password">Password</label>
    <input id="password" name="password" type="password" required autocomplete="current-password">
    <button type="submit">Sign In</button>
  </form>

  <p class="note"><a href="../index.php">Return to website</a></p>
</div>
</body>
</html>
