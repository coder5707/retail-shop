<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$message = "";
$msgType = "success";

/* ── ADD EXPENSE ─────────────────────────── */
if (isset($_POST['add_expense'])) {
    $cat   = (int)($_POST['category_id']   ?? 0);
    $title = trim($conn->real_escape_string($_POST['title'] ?? ''));
    $amt   = (float)($_POST['amount']      ?? 0);
    $note  = trim($conn->real_escape_string($_POST['note'] ?? ''));
    $date  = $_POST['exp_date'] ?? '';

    if (!$cat || $title === '' || $amt <= 0 || $date === '') {
        $message = "⚠ Fill in all required fields.";
        $msgType = "error";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO exp_entries (category_id, exp_title, exp_amount, exp_note, exp_date)
            VALUES (?,?,?,?,?)
        ");
        $stmt->bind_param("isdss", $cat, $title, $amt, $note, $date);
        $stmt->execute() ? $message = "✅ Expense added!" : $message = "DB Error: " . $stmt->error;
        $stmt->close();
    }
}

/* ── DELETE EXPENSE ──────────────────────── */
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM exp_entries WHERE exp_id=?");
    $stmt->bind_param("i", $did);
    $stmt->execute();
    $stmt->close();
    header("Location: expenses.php?deleted=1");
    exit;
}
if (isset($_GET['deleted'])) $message = "🗑 Expense deleted.";

/* ── FETCH CATEGORIES ────────────────────── */
$categories = $conn->query("SELECT * FROM exp_categories ORDER BY cat_name")->fetch_all(MYSQLI_ASSOC);

/* ── THIS MONTH EXPENSES ─────────────────── */
$month = date('Y-m');
$expenses = $conn->query("
    SELECT e.exp_id, e.exp_title, e.exp_amount, e.exp_note, e.exp_date,
           ec.cat_name, ec.cat_icon, ec.budget
    FROM exp_entries e
    JOIN exp_categories ec ON e.category_id = ec.cat_id
    WHERE DATE_FORMAT(e.exp_date,'%Y-%m') = '$month'
    ORDER BY e.exp_date DESC
")->fetch_all(MYSQLI_ASSOC);

/* ── CATEGORY TOTALS ─────────────────────── */
$catTotals = $conn->query("
    SELECT ec.cat_id, ec.cat_name, ec.cat_icon, ec.budget,
           COALESCE(SUM(e.exp_amount), 0) AS total_spent
    FROM exp_categories ec
    LEFT JOIN exp_entries e
        ON e.category_id = ec.cat_id
        AND DATE_FORMAT(e.exp_date, '%Y-%m') = '$month'
    GROUP BY ec.cat_id
    ORDER BY total_spent DESC
")->fetch_all(MYSQLI_ASSOC);

/* ── TOTALS ──────────────────────────────── */
$grandTotal  = array_sum(array_column($expenses, 'exp_amount'));
$totalBudget = array_sum(array_column($catTotals, 'budget'));
$usedPct     = $totalBudget > 0 ? min(100, round(($grandTotal / $totalBudget) * 100)) : 0;

/* ── REACTION HELPER ─────────────────────── */
function reaction(float $spent, float $budget): array {
    if ($budget <= 0)  return ['emoji'=>'❓','label'=>'No Budget','color'=>'#95a5a6','class'=>'neutral'];
    $p = ($spent / $budget) * 100;
    if ($p <= 80)      return ['emoji'=>'😊','label'=>'Good',     'color'=>'#27ae60','class'=>'good'];
    if ($p <= 100)     return ['emoji'=>'😐','label'=>'Warning',  'color'=>'#f39c12','class'=>'warn'];
                       return ['emoji'=>'😟','label'=>'Overspent','color'=>'#e74c3c','class'=>'bad'];
}

$overall = reaction($grandTotal, $totalBudget);

/* ── CHART DATA ──────────────────────────── */
$chartLabels = $chartSpent = $chartBudget = $chartColors = [];
foreach ($catTotals as $ct) {
    $r = reaction((float)$ct['total_spent'], (float)$ct['budget']);
    $chartLabels[] = $ct['cat_icon'] . ' ' . $ct['cat_name'];
    $chartSpent[]  = (float)$ct['total_spent'];
    $chartBudget[] = (float)$ct['budget'];
    $chartColors[] = $r['color'];
}
?>

<style>
.reaction-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:bold;}
.reaction-badge.good   {background:#d5f5e3;color:#1e8449;}
.reaction-badge.warn   {background:#fef9e7;color:#b7770d;}
.reaction-badge.bad    {background:#fde8e8;color:#c0392b;}
.reaction-badge.neutral{background:#eaf0fb;color:#555;}
.bbar-wrap{background:#e0e0e0;border-radius:6px;height:9px;margin-top:5px;overflow:hidden;}
.bbar-fill{height:100%;border-radius:6px;}
.cat-row{display:flex;align-items:center;background:#fff;border-radius:8px;
         padding:12px 14px;margin-bottom:8px;box-shadow:0 1px 4px rgba(0,0,0,.07);gap:12px;flex-wrap:wrap;}
.cat-info{flex:1;min-width:140px;}
.tab-btns{display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;}
.tab-btn{padding:7px 15px;border-radius:20px;border:none;cursor:pointer;
         font-size:13px;background:#dce3ea;color:#2c3e50;font-weight:bold;}
.tab-btn.active{background:#1abc9c;color:#fff;}
.section{display:none;} .section.active{display:block;}
</style>

<?php if ($message): ?><div class="alert <?= $msgType ?>"><?= $message ?></div><?php endif; ?>

<div class="card">
    <h2>💰 Expense Tracker <small style="font-size:13px;color:#888;font-weight:normal;">— <?= date('F Y') ?></small></h2>

    <!-- KPI row -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:14px;">
        <?php
        $kpis = [
            ['₹ '.number_format($grandTotal,0),         'Spent This Month'],
            ['₹ '.number_format($totalBudget,0),         'Total Budget'],
            ['₹ '.number_format(max(0,$totalBudget-$grandTotal),0), 'Remaining'],
            [count($expenses),                            'Entries'],
        ];
        foreach ($kpis as [$v,$l]):
        ?>
        <div style="background:#f9f9f9;border-radius:8px;padding:12px;text-align:center;">
            <div style="font-size:18px;font-weight:bold;color:#2c3e50;"><?= $v ?></div>
            <div style="font-size:11px;color:#777;margin-top:3px;"><?= $l ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Overall reaction -->
    <div style="background:#f9f9f9;border-radius:8px;padding:14px;text-align:center;">
        <div style="font-size:44px;"><?= $overall['emoji'] ?></div>
        <div style="font-size:15px;font-weight:bold;color:<?= $overall['color'] ?>;margin:4px 0;">
            Overall: <?= $overall['label'] ?>
        </div>
        <div style="max-width:300px;margin:8px auto 0;font-size:12px;color:#555;">
            Budget Used: <?= $usedPct ?>%
            <div class="bbar-wrap"><div class="bbar-fill" style="width:<?= $usedPct ?>%;background:<?= $overall['color'] ?>;"></div></div>
        </div>
    </div>
</div>

<!-- TABS -->
<div class="tab-btns">
    <button class="tab-btn active" onclick="showTab('categories',this)">📊 By Category</button>
    <button class="tab-btn" onclick="showTab('chart',this)">📈 Charts</button>
    <button class="tab-btn" onclick="showTab('list',this)">📋 All Entries</button>
    <button class="tab-btn" onclick="showTab('add',this)">➕ Add Expense</button>
</div>

<!-- TAB: CATEGORIES -->
<div id="tab-categories" class="section active">
<div class="card">
    <h3>Category Breakdown with Reactions</h3>
    <?php foreach ($catTotals as $ct):
        $r   = reaction((float)$ct['total_spent'], (float)$ct['budget']);
        $pct = $ct['budget'] > 0 ? min(100, ($ct['total_spent'] / $ct['budget']) * 100) : 0;
    ?>
    <div class="cat-row">
        <div style="font-size:26px;"><?= $ct['cat_icon'] ?></div>
        <div class="cat-info">
            <div style="font-weight:bold;font-size:14px;margin-bottom:3px;"><?= htmlspecialchars($ct['cat_name']) ?></div>
            <div style="font-size:12px;color:#555;">
                Spent: <strong>₹<?= number_format($ct['total_spent'],2) ?></strong>
                &nbsp;/&nbsp; Budget: ₹<?= number_format($ct['budget'],2) ?>
            </div>
            <div class="bbar-wrap" style="width:200px;">
                <div class="bbar-fill" style="width:<?= $pct ?>%;background:<?= $r['color'] ?>;"></div>
            </div>
        </div>
        <div style="text-align:right;min-width:100px;">
            <div style="font-size:24px;"><?= $r['emoji'] ?></div>
            <span class="reaction-badge <?= $r['class'] ?>"><?= $r['label'] ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
</div>

<!-- TAB: CHARTS -->
<div id="tab-chart" class="section">
<div class="card">
    <h3>Spent vs Budget — Bar Chart</h3>
    <canvas id="barChart" style="max-height:360px;"></canvas>
</div>
<div class="card">
    <h3>Spending Distribution — Doughnut</h3>
    <canvas id="pieChart" style="max-height:300px;max-width:360px;display:block;margin:0 auto;"></canvas>
</div>
</div>

<!-- TAB: LIST -->
<div id="tab-list" class="section">
<div class="card">
    <h3>All Entries — <?= date('F Y') ?></h3>
    <?php if (empty($expenses)): ?>
        <p style="color:#777;">No expenses recorded this month.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>#</th><th>Date</th><th>Category</th><th>Title</th><th>Amount</th><th>Note</th><th>Reaction</th><th>Del</th></tr>
        </thead>
        <tbody>
        <?php foreach ($expenses as $i => $ex):
            $r = reaction((float)$ex['exp_amount'], (float)$ex['budget']);
        ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= $ex['exp_date'] ?></td>
                <td><?= $ex['cat_icon'] ?> <?= htmlspecialchars($ex['cat_name']) ?></td>
                <td><?= htmlspecialchars($ex['exp_title']) ?></td>
                <td><strong>₹<?= number_format($ex['exp_amount'],2) ?></strong></td>
                <td style="color:#777;font-size:12px;"><?= htmlspecialchars($ex['exp_note']) ?></td>
                <td><span class="reaction-badge <?= $r['class'] ?>"><?= $r['emoji'] ?> <?= $r['label'] ?></span></td>
                <td>
                    <a href="expenses.php?delete=<?= $ex['exp_id'] ?>"
                       class="btn danger small"
                       onclick="return confirm('Delete this entry?')">🗑</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</div>

<!-- TAB: ADD -->
<div id="tab-add" class="section">
<div class="card" style="max-width:500px;">
    <h3>➕ Add New Expense</h3>
    <form method="post">
        <label>Category *</label>
        <select name="category_id" required style="margin-bottom:10px;">
            <option value="">-- Select Category --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['cat_id'] ?>"><?= $cat['cat_icon'] ?> <?= htmlspecialchars($cat['cat_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Title *</label>
        <input type="text" name="title" placeholder="e.g. Monthly Rent" required style="margin-bottom:10px;">

        <label>Amount (₹) *</label>
        <input type="number" name="amount" step="0.01" min="0.01" placeholder="0.00" required style="margin-bottom:10px;">

        <label>Date *</label>
        <input type="date" name="exp_date" value="<?= date('Y-m-d') ?>" required style="margin-bottom:10px;">

        <label>Note (optional)</label>
        <input type="text" name="note" placeholder="Remarks..." style="margin-bottom:14px;">

        <button type="submit" name="add_expense" class="btn">💾 Save Expense</button>
    </form>
</div>
</div>

<!-- Chart.js from dashboard folder -->
<script src="<?= $basePath ?>/modules/dashboard/chart.min.js"></script>
<script>
function showTab(name, btn) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

const labels  = <?= json_encode($chartLabels) ?>;
const spent   = <?= json_encode($chartSpent)  ?>;
const budgets = <?= json_encode($chartBudget) ?>;
const colors  = <?= json_encode($chartColors) ?>;

new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels,
        datasets: [
            {
                label: 'Spent (₹)',
                data: spent,
                backgroundColor: colors,
                borderRadius: 5
            },
            {
                label: 'Budget (₹)',
                data: budgets,
                backgroundColor: 'rgba(44,62,80,0.12)',
                borderColor: '#2c3e50',
                borderWidth: 2,
                borderRadius: 5
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: { callbacks: { label: ctx => '₹ ' + ctx.parsed.y.toLocaleString('en-IN') } }
        },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => '₹' + v.toLocaleString('en-IN') } }
        }
    }
});

const nonZero = spent.map((v,i) => ({v, l:labels[i], c:colors[i]})).filter(x => x.v > 0);
if (nonZero.length > 0) {
    new Chart(document.getElementById('pieChart'), {
        type: 'doughnut',
        data: {
            labels: nonZero.map(x => x.l),
            datasets: [{ data: nonZero.map(x => x.v), backgroundColor: nonZero.map(x => x.c), borderWidth:2, borderColor:'#fff' }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'right' },
                tooltip: { callbacks: { label: ctx => ctx.label + ': ₹' + ctx.parsed.toLocaleString('en-IN') } }
            }
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
