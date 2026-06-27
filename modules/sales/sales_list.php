<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$result = $conn->query("
    SELECT s.sale_id, s.sale_date, s.sale_total, c.cust_name
    FROM shop_sales s
    LEFT JOIN shop_customers c ON s.customer_id = c.cust_id
    ORDER BY s.sale_date DESC
");
?>
<div class="card">
    <h2>📋 Sales History</h2>
</div>
<div class="card">
    <?php if ($result->num_rows === 0): ?>
        <p style="color:#777;">No sales recorded yet.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>Bill ID</th><th>Date/Time</th><th>Customer</th><th>Total (₹)</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td>#<?= $row['sale_id'] ?></td>
                <td><?= $row['sale_date'] ?></td>
                <td><?= htmlspecialchars($row['cust_name'] ?? 'Walk-in') ?></td>
                <td>₹ <?= number_format($row['sale_total'], 2) ?></td>
                <td><a class="btn small secondary" href="view_sale.php?id=<?= $row['sale_id'] ?>">View</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
