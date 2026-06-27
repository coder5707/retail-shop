<?php
require_once __DIR__ . '/../auth/auth.php';
$basePath = WEB_BASE; // auto-detected from config.php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retail Clothes Shop</title>
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css">
</head>
<body>
<div class="topbar">
    <div class="logo">🛍️ Retail Clothes Shop</div>
    <div class="topbar-right">
        <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
        <span class="role-badge"><?= ucfirst($_SESSION['role']) ?></span>
        <a href="<?= $basePath ?>/auth/logout.php" class="btn small">Logout</a>
    </div>
</div>

<div class="wrapper">
    <div class="sidebar">
        <a href="<?= $basePath ?>/modules/dashboard/dashboard.php">📊 Dashboard</a>
        <a href="<?= $basePath ?>/modules/products/products.php">👕 Products</a>
        <a href="<?= $basePath ?>/modules/sales/new_sale.php">🧾 New Sale</a>
        <a href="<?= $basePath ?>/modules/sales/sales_list.php">📋 Sales History</a>
        <a href="<?= $basePath ?>/modules/customers/customers.php">👥 Customers</a>
        <a href="<?= $basePath ?>/modules/expenses/expenses.php">💰 Expense Tracker</a>
        <a href="<?= $basePath ?>/modules/reports/daily_report.php">📅 Daily Report</a>
        <a href="<?= $basePath ?>/modules/reports/monthly_report.php">📆 Monthly Report</a>
        <a href="<?= $basePath ?>/modules/reports/stock_report.php">📦 Stock Report</a>
        <a href="<?= $basePath ?>/modules/publish/publish.php">🌐 Publish Products</a>
    </div>
    <div class="content">
