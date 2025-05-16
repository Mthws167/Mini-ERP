<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = $_POST['code'];
    $discount_type = $_POST['discount_type'];
    $discount_value = $_POST['discount_value'];
    $min_order_value = $_POST['min_order_value'] ?: null;
    $valid_from = $_POST['valid_from'];
    $valid_until = $_POST['valid_until'];

    $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_order_value, valid_from, valid_until) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$code, $discount_type, $discount_value, $min_order_value, $valid_from, $valid_until]);
    header('Location: coupons.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM coupons");
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Coupons</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h1>Coupons</h1>
    <form method="POST">
        <div class="form-group">
            <label for="code">Code</label>
            <input type="text" class="form-control" id="code" name="code" required>
        </div>
        <div class="form-group">
            <label for="discount_type">Discount Type</label>
            <select class="form-control" id="discount_type" name="discount_type" required>
                <option value="fixed">Fixed</option>
                <option value="percentage">Percentage</option>
            </select>
        </div>
        <div class="form-group">
            <label for="discount_value">Discount Value</label>
            <input type="number" step="0.01" class="form-control" id="discount_value" name="discount_value" required>
        </div>
        <div class="form-group">
            <label for="min_order_value">Minimum Order Value (optional)</label>
            <input type="number" step="0.01" class="form-control" id="min_order_value" name="min_order_value">
        </div>
        <div class="form-group">
            <label for="valid_from">Valid From</label>
            <input type="date" class="form-control" id="valid_from" name="valid_from" required>
        </div>
        <div class="form-group">
            <label for="valid_until">Valid Until</label>
            <input type="date" class="form-control" id="valid_until" name="valid_until" required>
        </div>
        <button type="submit" class="btn btn-primary">Save Coupon</button>
    </form>
    <table class="table mt-4">
        <thead>
            <tr>
                <th>Code</th>
                <th>Type</th>
                <th>Value</th>
                <th>Min Order</th>
                <th>Valid From</th>
                <th>Valid Until</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($coupons as $coupon): ?>
            <tr>
                <td><?php echo $coupon['code']; ?></td>
                <td><?php echo $coupon['discount_type']; ?></td>
                <td><?php echo $coupon['discount_value']; ?></td>
                <td><?php echo $coupon['min_order_value'] ?: 'N/A'; ?></td>
                <td><?php echo $coupon['valid_from']; ?></td>
                <td><?php echo $coupon['valid_until']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>