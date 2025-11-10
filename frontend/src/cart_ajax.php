<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

if (!isset($_SESSION['login_token'])) {
    echo json_encode(['success' => false, 'cart_html' => '<p>No autenticat.</p>']);
    exit;
}
require_once("db_conn.php");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'cart_html' => '<p>Error CSRF.</p>']);
        exit;
    }
}

$usuari = $_SESSION['usuari'];
$stmt = $conn->prepare("SELECT id, email FROM users WHERE username = ?");
$stmt->bind_param("s", $usuari);
$stmt->execute();
$stmt->bind_result($user_id, $user_email);
$stmt->fetch();
$stmt->close();

if (!$user_id) {
    echo json_encode(['success' => false, 'cart_html' => '<p>Error d\'usuari.</p>']);
    exit;
}

if (isset($_POST['afegir'], $_POST['producte'], $_POST['preu'])) {
    $prod = trim($_POST['producte']);
    if (strlen($prod) > 100) $prod = substr($prod,0,100);
    $preu = (float)$_POST['preu'];
    if ($preu < 0) $preu = 0;
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_name = ?");
    $stmt->bind_param("is", $user_id, $prod);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($cart_id, $qty);
        $stmt->fetch();
        $qty++;
        $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $update->bind_param("ii", $qty, $cart_id);
        $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO cart (user_id, product_name, price, quantity) VALUES (?, ?, ?, 1)");
        $insert->bind_param("isd", $user_id, $prod, $preu);
        $insert->execute();
        $insert->close();
    }
    $stmt->close();
}

if (isset($_POST['treure'], $_POST['producte'])) {
    $prod = $_POST['producte'];
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_name = ?");
    $stmt->bind_param("is", $user_id, $prod);
    $stmt->execute();
    $stmt->bind_result($cart_id, $qty);
    $found = false;
    if ($stmt->fetch()) {
        $found = true;
    }
    $stmt->close();
    if ($found) {
        if ($qty > 1) {
            $update = $conn->prepare("UPDATE cart SET quantity = quantity - 1 WHERE id = ?");
            $update->bind_param("i", $cart_id);
            $update->execute();
            $update->close();
        } else {
            $del = $conn->prepare("DELETE FROM cart WHERE id = ?");
            $del->bind_param("i", $cart_id);
            $del->execute();
            $del->close();
        }
    }
}

if (isset($_POST['checkout'])) {
    $items = [];
    $q = $conn->prepare("SELECT product_name, price, quantity FROM cart WHERE user_id = ?");
    $q->bind_param("i", $user_id);
    $q->execute();
    $q->bind_result($pn, $pr, $qt);
    $totalValue = 0;
    while ($q->fetch()) {
        $items[] = [$pn, $pr, $qt];
        $totalValue += $pr * $qt;
    }
    $q->close();

    if (!empty($items)) {
        $number = 'INV-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        $insInv = $conn->prepare("INSERT INTO invoices (user_id, number, total) VALUES (?, ?, ?)");
        $insInv->bind_param("isd", $user_id, $number, $totalValue);
        $insInv->execute();
        $invoice_id = $insInv->insert_id;
        $insInv->close();

        $ins = $conn->prepare("INSERT INTO purchase_history (user_id, product_name, price, quantity, invoice_id) VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $it) {
            [$pn, $pr, $qt] = $it;
            $ins->bind_param("isdii", $user_id, $pn, $pr, $qt, $invoice_id);
            $ins->execute();
        }
        $ins->close();

        $del = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $del->bind_param("i", $user_id);
        $del->execute();
        $del->close();
    }
}

$cart = [];
$stmt = $conn->prepare("SELECT product_name, price, quantity FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($c_nom, $c_preu, $c_qty);
while ($stmt->fetch()) {
    $cart[] = ["nom" => $c_nom, "preu" => $c_preu, "qty" => $c_qty];
}
$stmt->close();

$totalValue = 0;
foreach ($cart as $item) {
    $totalValue += $item['preu'] * $item['qty'];
}

$invoices = [];
$hs = $conn->prepare("SELECT id, number, total, created_at FROM invoices WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$hs->bind_param("i", $user_id);
$hs->execute();
$hs->bind_result($inv_id, $inv_number, $inv_total, $inv_created);
while ($hs->fetch()) {
    $invoices[] = ["id" => $inv_id, "number" => $inv_number, "total" => $inv_total, "created_at" => $inv_created];
}
$hs->close();

$conn->close();

ob_start();
if (count($cart) == 0): ?>
    <p style="color:#888; margin-bottom:0;">El carretó és buit.</p>
<?php else: ?>
    <ul class="cart-list">
        <?php foreach ($cart as $item): ?>
            <li>
                <b><?php echo htmlspecialchars($item['nom'], ENT_QUOTES, 'UTF-8'); ?></b>
                <span class="qty">x<?php echo (int)$item['qty']; ?></span>
                <span class="price"><?php echo number_format($item['preu'],2); ?> €</span>
                <button class="remove-from-cart-btn" data-producte="<?php echo htmlspecialchars($item['nom'], ENT_QUOTES, 'UTF-8'); ?>">-</button>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="cart-total">
        Total:
        <?php echo number_format($totalValue, 2) . " €"; ?>
    </div>
    <div id="paypal-button-container" style="margin-top:1.5rem; text-align:center;"></div>
    <input type="hidden" id="paypal-total" value="<?php echo number_format($totalValue, 2, '.', ''); ?>">
<?php endif;
$cart_html = ob_get_clean();

ob_start();
if (empty($invoices)): ?>
    <p style="color:#888;">Encara no tens factures.</p>
<?php else: ?>
    <ul class="cart-list">
        <?php foreach ($invoices as $inv): ?>
            <li>
                <b><?php echo htmlspecialchars($inv['number'], ENT_QUOTES, 'UTF-8'); ?></b>
                <span class="price"><?php echo number_format($inv['total'],2); ?> €</span>
                <button class="download-invoice-btn" data-invoice-id="<?php echo (int)$inv['id']; ?>" style="margin-left:0.7em; background:#2b5876; color:#fff; border:none; border-radius:5px; padding:0.25rem 0.6rem; cursor:pointer;">Descarregar PDF</button>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif;
$history_html = ob_get_clean();

echo json_encode([
    'success' => true,
    'cart_html' => $cart_html,
    'history_html' => $history_html,
    'csrf_token' => $_SESSION['csrf_token']
]);
