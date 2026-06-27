<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$date = $_GET['date'] ?? date('Y-m-d');

$stmt = $conn->prepare("
    SELECT s.sale_id, s.sale_date, s.sale_total, c.cust_name
    FROM shop_sales s
    LEFT JOIN shop_customers c ON s.customer_id = c.cust_id
    WHERE DATE(s.sale_date) = ?
    ORDER BY s.sale_date DESC
");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$sumStmt = $conn->prepare("SELECT SUM(sale_total) AS t, COUNT(*) AS cnt FROM shop_sales WHERE DATE(sale_date) = ?");
$sumStmt->bind_param("s", $date);
$sumStmt->execute();
$sumRow = $sumStmt->get_result()->fetch_assoc();
$sumStmt->close();
$dayTotal = $sumRow['t']   ?? 0;
$billCount= $sumRow['cnt'] ?? 0;
?>
<div class="card">
    <h2>📅 Daily Sales Report</h2>
    <form method="get" style="display:flex;align-items:center;gap:10px;">
        <label>Select Date</label>
        <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" style="width:auto;">
        <button class="btn small" type="submit">View</button>
    </form>
</div>

<div class="card">
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px;">
        <div style="background:#fff;border-radius:8px;padding:14px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.08);">
            <div style="font-size:22px;font-weight:bold;color:#2c3e50;">₹ <?= number_format($dayTotal, 2) ?></div>
            <div style="font-size:12px;color:#777;">Day Total</div>
        </div>
        <div style="background:#fff;border-radius:8px;padding:14px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.08);">
            <div style="font-size:22px;font-weight:bold;color:#2c3e50;"><?= $billCount ?></div>
            <div style="font-size:12px;color:#777;">Bills</div>
        </div>
        <div style="background:#fff;border-radius:8px;padding:14px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.08);">
            <div style="font-size:22px;font-weight:bold;color:#2c3e50;">₹ <?= $billCount > 0 ? number_format($dayTotal / $billCount, 2) : '0.00' ?></div>
            <div style="font-size:12px;color:#777;">Avg per Bill</div>
        </div>
    </div>

    <h3>Sales on <?= htmlspecialchars($date) ?></h3>
    <?php if ($result->num_rows === 0): ?>
        <p style="color:#777;">No sales recorded on this date.</p>
    <?php else: ?>
    <table>
        <thead><tr><th>Bill ID</th><th>Time</th><th>Customer</th><th>Total (₹)</th><th>Action</th></tr></thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td>#<?= $row['sale_id'] ?></td>
                <td><?= date('H:i', strtotime($row['sale_date'])) ?></td>
                <td><?= htmlspecialchars($row['cust_name'] ?? 'Walk-in') ?></td>
                <td>₹ <?= number_format($row['sale_total'], 2) ?></td>
                <td><a class="btn small secondary" href="../sales/view_sale.php?id=<?= $row['sale_id'] ?>">View</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr><th colspan="3" style="text-align:right;">Day Total</th><th colspan="2">₹ <?= number_format($dayTotal, 2) ?></th></tr>
        </tfoot>
    </table>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
