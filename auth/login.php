<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$error       = "";
$selectedRole = $_POST['login_type'] ?? 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Customer — no DB check needed
    if ($selectedRole === 'user') {
        header("Location: ../customer/index.php");
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = "Please enter username and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id, username, user_pass, user_role FROM shop_users WHERE username = ?"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && $row['user_pass'] === md5($password)) {
            $_SESSION['user_id']  = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role']     = $row['user_role'];
            header("Location: ../modules/dashboard/dashboard.php");
            exit;
        }
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Retail Shop — Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-body">
<div class="login-container">
    <h2>🛍️ Retail Shop Login</h2>
    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <label>Login As</label>
        <select name="login_type" onchange="toggleFields(this)">
            <option value="admin" <?= $selectedRole==='admin' ? 'selected':'' ?>>Admin / Staff</option>
            <option value="user"  <?= $selectedRole==='user'  ? 'selected':'' ?>>Customer</option>
        </select>
        <div id="adminFields">
            <label>Username</label>
            <input type="text" name="username" autocomplete="username">
            <label>Password</label>
            <input type="password" name="password" autocomplete="current-password">
            <p class="hint">Default: <strong>admin / admin123</strong></p>
        </div>
        <br>
        <button type="submit" class="btn" style="width:100%;">Login</button>
    </form>
</div>
<script>
function toggleFields(sel) {
    document.getElementById('adminFields').style.display =
        sel.value === 'user' ? 'none' : 'block';
}
toggleFields(document.querySelector("select[name='login_type']"));
</script>
</body>
</html>
