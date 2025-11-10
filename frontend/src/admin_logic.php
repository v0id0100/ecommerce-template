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

if (!isset($_SESSION['login_token']) || !isset($_SESSION['usuari']) || $_SESSION['usuari'] !== 'admin') {
    http_response_code(403);
    echo "Accés denegat.";
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once("db_conn.php");
if ($conn instanceof mysqli) {
    $conn->set_charset('utf8mb4');
}

$usersOverview = [];
$sql = "
    SELECT 
        u.id, u.username, u.email, u.created_at,
        COALESCE(COUNT(ph.id), 0) AS purchases_count,
        COALESCE(SUM(ph.price * ph.quantity), 0) AS total_spent
    FROM users u
    LEFT JOIN purchase_history ph ON ph.user_id = u.id
    GROUP BY u.id, u.username, u.email, u.created_at
    ORDER BY u.created_at DESC
";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $usersOverview[] = [
            'id' => (int)$row['id'],
            'username' => $row['username'],
            'email' => $row['email'],
            'created_at' => $row['created_at'],
            'purchases' => (int)$row['purchases_count'],
            'spent' => (float)$row['total_spent']
        ];
    }
}

$userInvoices = [];
if (!empty($usersOverview)) {
    $invStmt = $conn->prepare("SELECT id, number, total, created_at FROM invoices WHERE user_id = ? ORDER BY created_at DESC");
    foreach ($usersOverview as $u) {
        $uid = $u['id'];
        $invStmt->bind_param("i", $uid);
        $invStmt->execute();
        $invStmt->bind_result($inv_id, $inv_number, $inv_total, $inv_created);
        $userInvoices[$uid] = [];
        while ($invStmt->fetch()) {
            $userInvoices[$uid][] = [
                'id' => (int)$inv_id,
                'number' => $inv_number,
                'total' => (float)$inv_total,
                'created_at' => $inv_created
            ];
        }
        $invStmt->free_result();
    }
    $invStmt->close();
}

$conn->close();
