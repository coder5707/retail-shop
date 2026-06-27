<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$year  = (int)($_GET['year']  ?? date('Y'));
$month = (int)($_GET['month'] ?? date('m'));
// Clamp month
$month = max(1, min(12, $month));

$stmt = $conn->prepare("
    SELECT DATE(sale_date) AS day, SUM(sale_total) AS total, COUNT(*) AS bills
    FROM shop_sales
    WHERE YEAR(sale_date) = ? AND MONTH(sale_date) = ?
    GROUP BY DATE(sale_date)
    ORDER BY day
");
$stmt->bind_param("ii", $year, $month);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$sumStmt = $conn->prepare("
    SELECT SUM(sale_total) AS t, COUNT(*) AS cnt
    FROM shop_sales
    WHERE YEAR(sale_date) = ? AND MONTH(sale_date) = ?
");
$sumStmt->bind_param("ii", $year, $month);
$sumStmt->execute();
$sumRow   = $sumStmt->get_result()->fetch_assoc();
$sumStmt->close();
$monthTotal = $sumRow['t']   ?? 0;
$billCount  = $sumRow['cnt'] ?? 0;

$monthName = date('F', mktime(0, 0, 0, $month, 1));
?>
<div class="card">
    <h2>📆 Monthly Sales Report</h2>
    <form method="get" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <label>Year</label>
        <input type="number" name="year" value="<?= $year ?>" style="width:90px;">
        <label>Month</label>
        <select name="month" style="width:auto;">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
            <?php endfor; ?>
        </select>
        <button class="btn small" type="submit">View</button>
    </form>
</div>

<div class="card">
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px;">
        <div style="background:#fff;border-radius:8px;padding:14px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.08);">
            <div style="font-size:22px;font-weight:bold;color:#2c3e50;">₹ <?= number_format($monthTotal, 2) ?></div>
            <div style="font-size:12px;color:#777;">Month Total</div>
        </div>
        <div style="background:#fff;border-radius:8px;padding:14px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.08);">
            <div style="font-size:22px;font-weight:bold;color:#2c3e50;"><?= $billCount ?></div>
            <div style="font-size:12px;color:#777;">Total Bills</div>
        </div>
        <div style="background:#fff;border-radius:8px;padding:14px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.08);">
            <div style="font-size:22px;font-weight:bold;color:#2c3e50;">₹ <?= $billCount > 0 ? number_format($monthTotal / $billCount, 2) : '0.00' ?></div>
            <div style="font-size:12px;color:#777;">Avg per Bill</div>
        </div>
    </div>

    <h3><?= $monthName ?> <?= $year ?></h3>
    <?php if ($result->num_rows === 0): ?>
        <p style="color:#777;">No sales in this month.</p>
    <?php else: ?>
    <table>
        <thead><tr><th>Date</th><th>Bills</th><th>Total (₹)</th></tr></thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['day'] ?></td>
                <td><?= $row['bills'] ?></td>
                <td>₹ <?= number_format($row['total'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr><th style="text-align:right;" colspan="2">Month Total</th><th>₹ <?= number_format($monthTotal, 2) ?></th></tr>
        </tfoot>
    </table>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
