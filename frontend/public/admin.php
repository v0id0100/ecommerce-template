<?php
require_once("src/admin_logic.php");
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <title>Panell d'administració</title>
    <link rel="stylesheet" href="src/styles.css">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="principal-page">
    <div class="logout-bar">
        <div class="logout-form">
            <a href="principal.php" class="btn-logout">Tornar a Principal</a>
        </div>
        <div class="welcome-center">
            <h2>Panell d'administració</h2>
        </div>
        <div class="spacer-160"></div>
    </div>

    <div class="main-layout" style="overflow:auto;">
        <div class="center-panel" style="width:100%; align-items:stretch;">
            <h3 class="section-title">Usuaris i activitats</h3>

            <div class="cart-sidebar" style="max-width:100%; width:100%;">
                <?php if (empty($usersOverview)): ?>
                    <p style="color:#888;">No hi ha usuaris.</p>
                <?php else: ?>
                    <div style="overflow:auto;">
                        <table style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="text-align:left; border-bottom:1px solid #e3e6ea;">
                                    <th style="padding:8px;">Usuari</th>
                                    <th style="padding:8px;">Email</th>
                                    <th style="padding:8px;">Alta</th>
                                    <th style="padding:8px;"># Compres</th>
                                    <th style="padding:8px;">Total Gastat</th>
                                    <th style="padding:8px;">Factures</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usersOverview as $u): 
                                    $invs = $userInvoices[$u['id']] ?? [];
                                ?>
                                <tr style="border-bottom:1px solid #f0f2f4;">
                                    <td style="padding:8px;"><b><?php echo htmlspecialchars($u['username']); ?></b></td>
                                    <td style="padding:8px;"><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td style="padding:8px;"><?php echo htmlspecialchars($u['created_at']); ?></td>
                                    <td style="padding:8px;"><?php echo (int)$u['purchases']; ?></td>
                                    <td style="padding:8px;"><?php echo number_format($u['spent'], 2); ?> €</td>
                                    <td style="padding:8px;">
                                        <?php if (empty($invs)): ?>
                                            <span style="color:#888;">Sense factures</span>
                                        <?php else: ?>
                                            <button class="toggle-invoices-btn" data-user="<?php echo (int)$u['id']; ?>" style="background:#2b5876; color:#fff; border:none; border-radius:5px; padding:0.25rem 0.6rem; cursor:pointer;">Veure</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr class="invoices-row" id="invoices-<?php echo (int)$u['id']; ?>" style="display:none; background:#fafbfc;">
                                    <td colspan="6" style="padding:10px 8px;">
                                        <?php if (!empty($invs)): ?>
                                            <ul class="cart-list">
                                                <?php foreach ($invs as $inv): ?>
                                                    <li>
                                                        <b><?php echo htmlspecialchars($inv['number']); ?></b>
                                                        <span class="price"><?php echo number_format($inv['total'],2); ?> €</span>
                                                        <span class="qty" style="margin-left:0.7em;"><?php echo htmlspecialchars($inv['created_at']); ?></span>
                                                        <button class="download-invoice-btn" data-invoice-id="<?php echo (int)$inv['id']; ?>" style="margin-left:0.7em; background:#2b5876; color:#fff; border:none; border-radius:5px; padding:0.25rem 0.6rem; cursor:pointer;">Descarregar PDF</button>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.toggle-invoices-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const uid = btn.getAttribute('data-user');
                const row = document.getElementById('invoices-' + uid);
                if (row) {
                    row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
                }
            });
        });
    });
    </script>
    <script src="src/invoice.js"></script>
</body>
</html>
