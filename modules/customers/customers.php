<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$message = "";
$error   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']  ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($name === '') {
        $error = "Customer name is required.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO shop_customers (cust_name, cust_phone, cust_email) VALUES (?,?,?)"
        );
        $stmt->bind_param("sss", $name, $phone, $email);
        $stmt->execute() ? $message = "✅ Customer added." : $error = "Failed: " . $stmt->error;
        $stmt->close();
    }
}

$result = $conn->query("
    SELECT c.cust_id, c.cust_name, c.cust_phone, c.cust_email,
           COUNT(s.sale_id) AS total_purchases,
           COALESCE(SUM(s.sale_total), 0) AS total_spent
    FROM shop_customers c
    LEFT JOIN shop_sales s ON s.customer_id = c.cust_id
    GROUP BY c.cust_id
    ORDER BY c.cust_id DESC
");
?>
<div class="card">
    <h2>👥 Customers</h2>
    <?php if ($error):   ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($message): ?><div class="alert success"><?= $message ?></div><?php endif; ?>
</div>

<div class="card">
    <h3>Add Customer</h3>
    <form method="post">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:12px;">
            <div><label>Name *</label><input type="text" name="name" required></div>
            <div><label>Phone</label><input type="text" name="phone"></div>
            <div><label>Email</label><input type="email" name="email"></div>
        </div>
        <button class="btn" type="submit">Add Customer</button>
    </form>
</div>

<div class="card">
    <h3>Customer List</h3>
    <?php if ($result->num_rows === 0): ?>
        <p style="color:#777;">No customers yet.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>#</th><th>Name</th><th>Phone</th><th>Email</th><th>Purchases</th><th>Total Spent</th></tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['cust_id'] ?></td>
                <td><?= htmlspecialchars($row['cust_name']) ?></td>
                <td><?= htmlspecialchars($row['cust_phone']) ?></td>
                <td><?= htmlspecialchars($row['cust_email']) ?></td>
                <td><?= $row['total_purchases'] ?></td>
                <td>₹ <?= number_format($row['total_spent'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
