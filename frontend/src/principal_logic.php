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
    header("Location: index.php");
    exit;
}
require_once("db_conn.php");
$usuari = $_SESSION['usuari'];
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $usuari);
$stmt->execute();
$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();

if (!$user_id) {
    echo "Error d'usuari.";
    exit;
}
$productes = [
    ["nom" => "Producte 1", "preu" => 10.00],
    ["nom" => "Producte 2", "preu" => 15.50]
];

if (isset($_POST['afegir'])) {
    $prod = $_POST['producte'];
    $preu = $_POST['preu'];
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
    header("Location: principal.php");
    exit;
}

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
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

$invoices = [];
$stmt = $conn->prepare("SELECT id, number, total, created_at FROM invoices WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($inv_id, $inv_number, $inv_total, $inv_created);
while ($stmt->fetch()) {
    $invoices[] = ["id" => $inv_id, "number" => $inv_number, "total" => $inv_total, "created_at" => $inv_created];
}
$stmt->close();

$conn->close();
?>