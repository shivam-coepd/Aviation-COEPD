<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($username === '' || strlen($username) < 4) {
        $error = 'Username must be at least 4 characters.';
    } elseif (strlen($password) < 10) {
        $error = 'Password must be at least 10 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $stmt = db()->prepare('SELECT id FROM admin_users WHERE username = :username LIMIT 1');
            $stmt->execute([':username' => $username]);

            if ($stmt->fetch()) {
                $error = 'This username already exists.';
            } else {
                $insert = db()->prepare('INSERT INTO admin_users (username, password_hash) VALUES (:username, :password_hash)');
                $insert->execute([
                    ':username' => $username,
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                $message = 'Admin account created successfully. Delete this file from the server now.';
            }
        } catch (Throwable $e) {
            $error = ENVIRONMENT === 'local' ? $e->getMessage() : 'Unable to create admin account.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Create Admin Account</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#f3f5f7;display:grid;place-items:center;min-height:100vh;padding:24px;color:#20272e}
.box{background:#fff;border:1px solid #dfe4e8;border-radius:10px;padding:36px;width:100%;max-width:440px}
h1{font-family:'Source Serif 4',serif;color:#17324d;font-size:25px;margin-bottom:10px}
p{color:#5f6b76;font-size:14.5px;margin-bottom:22px}
label{display:block;font-weight:600;font-size:14px;margin-bottom:6px}
input{width:100%;padding:12px;border:1px solid #c8d0d7;border-radius:6px;font:inherit;margin-bottom:16px}
button{width:100%;padding:13px;border:0;border-radius:6px;background:#17324d;color:#fff;font-weight:600;cursor:pointer}
.error{background:#fdecea;border:1px solid #f0b6b1;color:#b3261e;padding:12px;border-radius:6px;margin-bottom:18px;font-size:14px}
.success{background:#eef6ee;border:1px solid #b7d3b8;color:#275c31;padding:12px;border-radius:6px;margin-bottom:18px;font-size:14px}
</style>
</head>
<body>
<div class="box">
  <h1>Create Admin Account</h1>
  <p>Create the first administrator account for the leads dashboard. Delete this file immediately after creating the account.</p>

  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($message): ?><div class="success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

  <form method="post">
    <label for="username">Admin username</label>
    <input id="username" name="username" required>
    <label for="password">Password</label>
    <input id="password" name="password" type="password" required>
    <label for="confirm_password">Confirm password</label>
    <input id="confirm_password" name="confirm_password" type="password" required>
    <button type="submit">Create Admin Account</button>
  </form>
</div>
</body>
</html>
