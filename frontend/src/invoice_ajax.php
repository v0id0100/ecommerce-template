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
header('Content-Type: application/json');

if (!isset($_SESSION['login_token'], $_SESSION['usuari'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticat']); exit;
}
require_once("db_conn.php");

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'error' => 'CSRF']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'error'=>'Mètode invàlid']); exit;
}

$invoice_id = isset($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : 0;
if ($invoice_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invàlid']); exit;
}

$isAdmin = ($_SESSION['usuari'] === 'admin');

$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['usuari']);
$stmt->execute();
$stmt->bind_result($current_user_id);
$stmt->fetch();
$stmt->close();
if (!$current_user_id) { echo json_encode(['success' => false, 'error' => 'Usuari invàlid']); exit; }

if ($isAdmin) {
    $stmt = $conn->prepare("SELECT user_id, number, total, created_at FROM invoices WHERE id = ?");
    $stmt->bind_param("i", $invoice_id);
} else {
    $stmt = $conn->prepare("SELECT user_id, number, total, created_at FROM invoices WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $invoice_id, $current_user_id);
}
$stmt->execute();
$stmt->bind_result($invoice_user_id, $number, $total, $created_at);
if (!$stmt->fetch()) {
    $stmt->close();
    echo json_encode(['success' => false, 'error' => 'Factura no trobada']); exit;
}
$stmt->close();

$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $invoice_user_id);
$stmt->execute();
$stmt->bind_result($owner_username, $owner_email);
$stmt->fetch();
$stmt->close();

$items = [];
$stmt = $conn->prepare("SELECT product_name, price, quantity FROM purchase_history WHERE invoice_id = ? AND user_id = ?");
$stmt->bind_param("ii", $invoice_id, $invoice_user_id);
$stmt->execute();
$stmt->bind_result($pname, $pprice, $pqty);
while ($stmt->fetch()) {
    $items[] = [
        'name' => $pname,
        'price' => (float)$pprice,
        'qty' => (int)$pqty,
        'subtotal' => (float)$pprice * (int)$pqty
    ];
}
$stmt->close();
$conn->close();

$company = [
    'name' => 'Empresa Demo, S.L.',
    'vat' => 'B-12345678',
    'address' => 'Carrer Exemple 123, 08000 Barcelona',
    'email' => 'info@empresademo.cat'
];

echo json_encode([
    'success' => true,
    'invoice' => [
        'id' => $invoice_id,
        'number' => $number,
        'total' => (float)$total,
        'created_at' => $created_at
    ],
    'customer' => [
        'name' => $owner_username,
        'email' => $owner_email
    ],
    'company' => $company,
    'items' => $items
]);
