<?php
require_once("src/principal_logic.php");
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <title>Principal - Productes</title>
    <link rel="stylesheet" href="src/styles.css">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
    <script src="src/paypal-sandbox.js" defer></script>
</head>
<body class="principal-page">
    <div class="logout-bar">
        <div class="logout-form">
            <form method="post">
                <button type="submit" name="logout" class="btn-logout">Tancar Sessió</button>
            </form>
        </div>
        <div class="welcome-center">
            <h2>Benvingut, <?php echo htmlspecialchars($usuari); ?></h2>
        </div>
        <div class="spacer-160">
            <?php if ($usuari === 'admin'): ?>
                <a href="admin.php" class="btn-logout admin-link">Admin</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="main-layout">
        <div class="left-panel">
        </div>
        <div class="center-panel">
            <h3 class="section-title">Productes destacats</h3>
            <div class="product-list">
                <?php foreach ($productes as $p): ?>
                    <div class="product-card">
                        <div class="product-title"><?php echo htmlspecialchars($p['nom']); ?></div>
                        <div class="product-price"><?php echo number_format($p['preu'],2); ?> €</div>
                        <button class="add-to-cart-btn" 
                            data-producte="<?php echo htmlspecialchars($p['nom']); ?>" 
                            data-preu="<?php echo $p['preu']; ?>">
                            Afegir al carretó
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="right-panel">
            <aside class="cart-sidebar" id="cart-sidebar">
                <div class="cart-title">El teu carretó</div>
                <div id="cart-content">
                <?php if (count($cart) == 0): ?>
                    <p style="color:#888; margin-bottom:0;">El carretó és buit.</p>
                <?php else: ?>
                    <ul class="cart-list">
                        <?php foreach ($cart as $item): ?>
                            <li>
                                <b><?php echo htmlspecialchars($item['nom']); ?></b>
                                <span class="qty">x<?php echo $item['qty']; ?></span>
                                <span class="price"><?php echo number_format($item['preu'],2); ?> €</span>
                                <button class="remove-from-cart-btn" data-producte="<?php echo htmlspecialchars($item['nom']); ?>">-</button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="cart-total">
                        Total: 
                        <?php
                            $total = 0;
                            foreach ($cart as $item) $total += $item['preu'] * $item['qty'];
                            echo number_format($total, 2) . " €";
                        ?>
                    </div>
                    <div id="paypal-button-container" class="paypal-container"></div>
                    <input type="hidden" id="paypal-total" value="<?php echo number_format($total, 2, '.', ''); ?>">
                <?php endif; ?>
                </div>
            </aside>

            <aside class="cart-sidebar mt-1">
                <div class="cart-title">Historial de factures</div>
                <div id="history-content">
                    <?php if (empty($invoices)): ?>
                        <p style="color:#888;">Encara no tens factures.</p>
                    <?php else: ?>
                        <ul class="cart-list">
                            <?php foreach ($invoices as $inv): ?>
                                <li>
                                    <b><?php echo htmlspecialchars($inv['number']); ?></b>
                                    <span class="price"><?php echo number_format($inv['total'],2); ?> €</span>
                                    <button class="download-invoice-btn" data-invoice-id="<?php echo (int)$inv['id']; ?>" style="margin-left:0.7em; background:#2b5876; color:#fff; border:none; border-radius:5px; padding:0.25rem 0.6rem; cursor:pointer;">Descarregar PDF</button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
    <script src="src/cart.js"></script>
    <script src="src/invoice.js"></script>
</body>
</html>