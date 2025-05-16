<?php
require_once 'db.php';

$successMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $couponCode = $_POST['code'] ?? '';
    $discountType = $_POST['discount_type'] ?? '';
    $discountValue = floatval($_POST['discount_value'] ?? 0);
    $minOrderValue = !empty($_POST['min_order_value']) ? floatval($_POST['min_order_value']) : null;
    $validFrom = $_POST['valid_from'] ?? '';
    $validUntil = $_POST['valid_until'] ?? '';

    if ($discountValue <= 0) {
        $errorMessage = "O valor do desconto deve ser maior que zero.";
    } else {
        $couponQuery = $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_order_value, valid_from, valid_until) VALUES (?, ?, ?, ?, ?, ?)");
        $couponQuery->execute([$couponCode, $discountType, $discountValue, $minOrderValue, $validFrom, $validUntil]);
        $successMessage = "Cupom salvo com sucesso!";
    }
}

$couponQuery = $pdo->query("SELECT * FROM coupons");
$couponList = $couponQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Gerenciar Cupons</title>
    <link rel="stylesheet" href="styles/custom.css">
</head>
<body>
    <div class="coupons-container">
        <h1>Gerenciar Cupons</h1>
        <?php if ($successMessage): ?>
            <p class="success-message"><?php echo $successMessage; ?></p>
        <?php elseif (isset($errorMessage)): ?>
            <p class="error-message"><?php echo $errorMessage; ?></p>
        <?php endif; ?>
        <form method="POST" class="coupon-form">
            <div class="form-field">
                <label for="coupon_code">Código do Cupom</label>
                <input type="text" id="coupon_code" name="code" required>
            </div>
            <div class="form-field">
                <label for="discount_type">Tipo de Desconto</label>
                <select id="discount_type" name="discount_type" required>
                    <option value="fixed">Valor Fixo</option>
                    <option value="percentage">Porcentagem</option>
                </select>
            </div>
            <div class="form-field">
                <label for="discount_value">Valor do Desconto</label>
                <input type="number" step="0.01" id="discount_value" name="discount_value" required>
            </div>
            <div class="form-field">
                <label for="min_order_value">Valor Mínimo do Pedido (Opcional)</label>
                <input type="number" step="0.01" id="min_order_value" name="min_order_value">
            </div>
            <div class="form-field">
                <label for="valid_from">Válido a Partir de</label>
                <input type="date" id="valid_from" name="valid_from" required>
            </div>
            <div class="form-field">
                <label for="valid_until">Válido Até</label>
                <input type="date" id="valid_until" name="valid_until" required>
            </div>
            <button type="submit" class="submit-btn">Salvar Cupom</button>
        </form>
        <table class="coupons-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Valor Mínimo</th>
                    <th>Início</th>
                    <th>Fim</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($couponList as $coupon): ?>
                    <tr>
                        <td><?php echo $coupon['code']; ?></td>
                        <td><?php echo $coupon['discount_type'] === 'fixed' ? 'Fixo' : 'Porcentagem'; ?></td>
                        <td><?php echo number_format($coupon['discount_value'], 2); ?></td>
                        <td><?php echo $coupon['min_order_value'] ? number_format($coupon['min_order_value'], 2) : 'N/A'; ?></td>
                        <td><?php echo $coupon['valid_from']; ?></td>
                        <td><?php echo $coupon['valid_until']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>