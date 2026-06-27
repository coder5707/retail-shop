<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$message = "";
$error   = "";

// Fetch ACTIVE products only for dropdown
$productsRes = $conn->query(
    "SELECT prod_id AS id, prod_name AS name, price, stock FROM shop_products WHERE prod_status = 1 ORDER BY prod_name"
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name  = trim($_POST['customer_name']  ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');

    $product_ids = $_POST['product_id'] ?? [];
    $quantities  = $_POST['quantity']   ?? [];
    $prices      = $_POST['price']      ?? [];

    // Filter out empty/unselected rows
    $validRows = [];
    for ($i = 0; $i < count($product_ids); $i++) {
        $pid = (int)($product_ids[$i] ?? 0);
        $qty = (int)($quantities[$i]  ?? 0);
        $prc = (float)($prices[$i]    ?? 0);
        if ($pid > 0 && $qty > 0 && $prc >= 0) {
            $validRows[] = ['pid' => $pid, 'qty' => $qty, 'prc' => $prc];
        }
    }

    if (empty($validRows)) {
        $error = "Please add at least one valid product.";
    } else {
        // Insert or find customer
        $customer_id = null;
        if ($customer_name !== '' || $customer_phone !== '' || $customer_email !== '') {
            $stmt = $conn->prepare(
                "INSERT INTO shop_customers (cust_name, cust_phone, cust_email) VALUES (?,?,?)"
            );
            $stmt->bind_param("sss", $customer_name, $customer_phone, $customer_email);
            $stmt->execute();
            $customer_id = $stmt->insert_id;
            $stmt->close();
        }

        // Calculate total (check stock first)
        $total       = 0;
        $stockErrors = [];
        foreach ($validRows as &$row) {
            $st = $conn->prepare("SELECT stock FROM shop_products WHERE prod_id = ? AND prod_status = 1");
            $st->bind_param("i", $row['pid']);
            $st->execute();
            $stockRow  = $st->get_result()->fetch_assoc();
            $st->close();
            $available = $stockRow ? (int)$stockRow['stock'] : 0;
            if ($row['qty'] > $available) {
                $stockErrors[] = "Product ID {$row['pid']}: requested {$row['qty']}, available $available.";
                $row['qty']    = $available;
            }
            $total += $row['qty'] * $row['prc'];
        }
        unset($row);

        if ($total <= 0 && empty($stockErrors)) {
            $error = "Sale total cannot be zero.";
        } else {
            // Insert sale header
            $stmt = $conn->prepare(
                "INSERT INTO shop_sales (customer_id, sale_total) VALUES (?, ?)"
            );
            $stmt->bind_param("id", $customer_id, $total);
            $stmt->execute();
            $sale_id = $stmt->insert_id;
            $stmt->close();

            // Insert line items + reduce stock
            foreach ($validRows as $row) {
                if ($row['qty'] <= 0) continue;

                $si = $conn->prepare(
                    "INSERT INTO shop_sale_items (sale_id, product_id, qty, unit_price) VALUES (?,?,?,?)"
                );
                $si->bind_param("iiid", $sale_id, $row['pid'], $row['qty'], $row['prc']);
                $si->execute();
                $si->close();

                $up = $conn->prepare(
                    "UPDATE shop_products SET stock = stock - ? WHERE prod_id = ?"
                );
                $up->bind_param("ii", $row['qty'], $row['pid']);
                $up->execute();
                $up->close();
            }

            if (!empty($stockErrors)) {
                $error = "⚠ Stock warning: " . implode('; ', $stockErrors) . " Sale saved with adjusted quantities.";
            } else {
                $message = "✅ Sale #$sale_id created! <a href='view_sale.php?id=$sale_id'>View Bill</a>";
            }
        }
    }
}
?>

<div class="card">
    <h2>🧾 New Sale (Billing)</h2>
    <?php if ($error):   ?><div class="alert error"><?= $error ?></div><?php endif; ?>
    <?php if ($message): ?><div class="alert success"><?= $message ?></div><?php endif; ?>

    <form method="post" id="saleForm">
        <h3>Customer Details <small style="color:#999;">(optional)</small></h3>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:14px;">
            <div>
                <label>Name</label>
                <input type="text" name="customer_name" placeholder="Walk-in customer">
            </div>
            <div>
                <label>Phone</label>
                <input type="text" name="customer_phone" placeholder="Mobile number">
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="customer_email" placeholder="Email address">
            </div>
        </div>

        <h3>Sale Items</h3>
        <table id="itemsTable">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price (₹)</th>
                    <th>Qty</th>
                    <th>Line Total</th>
                    <th><button type="button" class="btn small secondary" onclick="addRow()">+ Add Row</button></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <select name="product_id[]" onchange="updatePrice(this)" style="min-width:160px;">
                            <option value="">-- Select Product --</option>
                            <?php
                            $productsRes->data_seek(0);
                            while ($p = $productsRes->fetch_assoc()):
                            ?>
                                <option value="<?= $p['id'] ?>"
                                        data-price="<?= $p['price'] ?>"
                                        <?= $p['stock'] == 0 ? 'style="color:#aaa;"' : '' ?>>
                                    <?= htmlspecialchars($p['name']) ?> — Stock: <?= $p['stock'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </td>
                    <td><input type="number" step="0.01" name="price[]" value="0.00" min="0" oninput="calcLine(this)"></td>
                    <td><input type="number" name="quantity[]" value="1" min="1" oninput="calcLine(this)"></td>
                    <td><span class="line-total">0.00</span></td>
                    <td><button type="button" class="btn small danger" onclick="removeRow(this)">✕</button></td>
                </tr>
            </tbody>
        </table>

        <h3 style="margin-top:16px;">Grand Total: ₹ <span id="grandTotal">0.00</span></h3>
        <button type="submit" class="btn">💾 Save Sale</button>
    </form>
</div>

<script>
/* Store product options HTML once for cloning */
const productOptionsHTML = document.querySelector('select[name="product_id[]"]').innerHTML;

function updatePrice(sel) {
    const opt = sel.options[sel.selectedIndex];
    const price = parseFloat(opt.getAttribute('data-price')) || 0;
    const row = sel.closest('tr');
    row.querySelector('input[name="price[]"]').value = price.toFixed(2);
    calcLine(row.querySelector('input[name="price[]"]'));
}

function calcLine(inp) {
    const row   = inp.closest('tr');
    const price = parseFloat(row.querySelector('input[name="price[]"]').value) || 0;
    const qty   = parseInt(row.querySelector('input[name="quantity[]"]').value)  || 0;
    row.querySelector('.line-total').innerText = (price * qty).toFixed(2);
    calcGrand();
}

function calcGrand() {
    let total = 0;
    document.querySelectorAll('.line-total').forEach(el => {
        total += parseFloat(el.innerText) || 0;
    });
    document.getElementById('grandTotal').innerText = total.toFixed(2);
}

function addRow() {
    const tbody  = document.querySelector('#itemsTable tbody');
    const tpl    = tbody.querySelector('tr');
    const newRow = tpl.cloneNode(true);
    newRow.querySelector('select').innerHTML   = productOptionsHTML;
    newRow.querySelector('select').selectedIndex = 0;
    newRow.querySelector('input[name="price[]"]').value    = '0.00';
    newRow.querySelector('input[name="quantity[]"]').value = '1';
    newRow.querySelector('.line-total').innerText          = '0.00';
    tbody.appendChild(newRow);
}

function removeRow(btn) {
    const tbody = document.querySelector('#itemsTable tbody');
    if (tbody.rows.length > 1) {
        btn.closest('tr').remove();
        calcGrand();
    } else {
        alert('At least one product row is required.');
    }
}

calcGrand();
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
