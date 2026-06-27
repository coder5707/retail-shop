<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$message = "";

/* ── CLEAR ALL DATA (admin only) ── */
if (isset($_POST['clear_all']) && $_SESSION['role'] === 'admin') {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    foreach (['shop_sale_items','shop_sales','shop_customers','shop_products',
              'exp_entries','exp_categories'] as $tbl) {
        $conn->query("TRUNCATE TABLE $tbl");
    }
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    $message = "All data cleared successfully!";
}

/* ── DASHBOARD METRICS ── */
$totalProducts = $conn->query("SELECT COUNT(*) c FROM shop_products WHERE prod_status=1")->fetch_assoc()['c'];
$totalStock    = $conn->query("SELECT COALESCE(SUM(stock),0) s FROM shop_products WHERE prod_status=1")->fetch_assoc()['s'];

$today = date('Y-m-d');
$stDay = $conn->prepare("SELECT COALESCE(SUM(sale_total),0) t FROM shop_sales WHERE DATE(sale_date)=?");
$stDay->bind_param("s", $today);
$stDay->execute();
$todaySales = $stDay->get_result()->fetch_assoc()['t'];
$stDay->close();

$totalBills = $conn->query("SELECT COUNT(*) c FROM shop_sales")->fetch_assoc()['c'];

/* ── GAUGE ── */
$maxCapacity  = 5000;
$stockPercent = $totalStock > 0 ? min(100, round(($totalStock / $maxCapacity) * 100)) : 0;

/* ── EXPENSE SUMMARY ── */
$expMonth = date('Y-m');
$expRow = $conn->query("
    SELECT
        COALESCE(SUM(e.exp_amount), 0)  AS spent,
        COALESCE(SUM(ec.budget), 0)     AS total_budget
    FROM exp_entries e
    JOIN exp_categories ec ON e.category_id = ec.cat_id
    WHERE DATE_FORMAT(e.exp_date, '%Y-%m') = '$expMonth'
")->fetch_assoc();

$allBudget = $conn->query("SELECT COALESCE(SUM(budget),0) b FROM exp_categories")->fetch_assoc()['b'] ?? 0;
$expSpent  = $expRow ? (float)$expRow['spent'] : 0;
$expBudget = (float)$allBudget;
$expPct    = $expBudget > 0 ? min(100, round(($expSpent / $expBudget) * 100)) : 0;
$expEmoji  = $expPct <= 80  ? '😊' : ($expPct <= 100 ? '😐' : '😟');
$expColor  = $expPct <= 80  ? '#27ae60' : ($expPct <= 100 ? '#f3ef12' : '#ff1900');
$expLabel  = $expPct <= 80  ? 'Good' : ($expPct <= 100 ? 'Warning' : 'Overspent');
?>

<div class="card">
    <h2>📊 Dashboard</h2>
    <?php if ($message): ?><div class="alert success"><?= $message ?></div><?php endif; ?>
</div>

<!-- KPI SUMMARY -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:16px;">
    <?php
    $kpis = [
        ['label'=>'Active Products', 'value'=>$totalProducts,                            'icon'=>'👕'],
        ['label'=>'Total Stock',     'value'=>$totalStock,                                'icon'=>'📦'],
        ['label'=>"Today's Sales",  'value'=>'₹ '.number_format($todaySales,2),          'icon'=>'💵'],
        ['label'=>'Total Bills',     'value'=>$totalBills,                                'icon'=>'🧾'],
    ];
    foreach ($kpis as $kpi):
    ?>
    <div style="background:#fff;border-radius:8px;padding:16px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.08);">
        <div style="font-size:28px;"><?= $kpi['icon'] ?></div>
        <div style="font-size:20px;font-weight:bold;color:#2c3e50;margin:4px 0;"><?= $kpi['value'] ?></div>
        <div style="font-size:12px;color:#777;"><?= $kpi['label'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- INVENTORY GAUGE -->
<div class="card">
    <h3 style="text-align:center;">Inventory Level</h3>
    <div class="gauge-grid">
        <div class="gauge-box">
            <canvas id="inventoryGauge"></canvas>
            <div class="gauge-value"><?= $stockPercent ?>%</div>
        </div>
    </div>
    <?php if ($stockPercent < 30): ?>
        <p class="low-stock">⚠ Low Inventory Alert!</p>
    <?php endif; ?>
</div>

<!-- EXPENSE OVERVIEW -->
<div class="card">
    <h3>💰 Expense Overview — <?= date('F Y') ?>
        <a href="<?= $basePath ?>/modules/expenses/expenses.php" class="btn small secondary" style="float:right;">Full Details</a>
    </h3>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:10px;margin-bottom:12px;flex-wrap:wrap;">
        <div style="text-align:center;background:#f9f9f9;border-radius:6px;padding:10px;">
            <div style="font-size:18px;font-weight:bold;">₹ <?= number_format($expSpent,2) ?></div>
            <div style="font-size:11px;color:#777;">Spent</div>
        </div>
        <div style="text-align:center;background:#f9f9f9;border-radius:6px;padding:10px;">
            <div style="font-size:18px;font-weight:bold;">₹ <?= number_format($expBudget,2) ?></div>
            <div style="font-size:11px;color:#777;">Budget</div>
        </div>
        <div style="text-align:center;background:#f9f9f9;border-radius:6px;padding:10px;">
            <div style="font-size:18px;font-weight:bold;"><?= $expPct ?>%</div>
            <div style="font-size:11px;color:#777;">Used</div>
        </div>
        <div style="text-align:center;background:#f9f9f9;border-radius:6px;padding:10px;">
            <div style="font-size:28px;"><?= $expEmoji ?></div>
            <div style="font-size:12px;font-weight:bold;color:<?= $expColor ?>;"><?= $expLabel ?></div>
        </div>
    </div>
    <div style="background:#e0e0e0;border-radius:6px;height:12px;overflow:hidden;">
        <div style="width:<?= $expPct ?>%;height:100%;background:<?= $expColor ?>;border-radius:6px;transition:width .5s;"></div>
    </div>
</div>

<?php if ($_SESSION['role'] === 'admin'): ?>
<div class="card">
    <h3 style="color:red;">⚠ Danger Zone</h3>
    <form method="post" onsubmit="return confirm('Permanently delete ALL data? This cannot be undone.');">
        <button type="submit" name="clear_all" class="btn danger">🔥 Clear All Data</button>
    </form>
</div>
<?php endif; ?>

<script src="chart.min.js"></script>
<script>
const value = <?= $stockPercent ?>;
const gaugeNeedle = {
    id: 'needle',
    afterDatasetDraw(chart) {
        const { ctx } = chart;
        const meta = chart.getDatasetMeta(0).data[0];
        ctx.save();
        const angle = Math.PI + (Math.PI * value / 100);
        ctx.translate(meta.x, meta.y);
        ctx.rotate(angle);
        ctx.beginPath();
        ctx.moveTo(0, -4);
        ctx.lineTo(55, 0);
        ctx.lineTo(0, 4);
        ctx.fillStyle = '#e74c3c';
        ctx.fill();
        ctx.restore();
        ctx.beginPath();
        ctx.arc(meta.x, meta.y, 6, 0, Math.PI * 2);
        ctx.fillStyle = '#2c3e50';
        ctx.fill();
    }
};
const canvas = document.getElementById('inventoryGauge');
canvas.width  = 220;
canvas.height = 140;
new Chart(canvas, {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [value, 100 - value],
            backgroundColor: [
                value <= 30 ? '#e74c3c' : value <= 60 ? '#f39c12' : '#27ae60',
                '#e8e8e8'
            ],
            borderWidth: 0
        }]
    },
    options: {
        rotation: -90,
        circumference: 180,
        cutout: '72%',
        responsive: false,
        plugins: { legend: { display: false }, tooltip: { enabled: false } }
    },
    plugins: [gaugeNeedle]
});
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
