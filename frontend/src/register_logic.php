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

$missatge = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $servername = "db";
    $username = "root";
    $password = "rootpassword";
    $dbname = "ecommerce_db";
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connexió fallida: " . $conn->connect_error);
    }

    if (isset($_POST["usuari"], $_POST["email"], $_POST["contrasenya"])) {
        $usuari = trim($_POST["usuari"]);
        $email = trim($_POST["email"]);
        $contrasenya = $_POST["contrasenya"];

        if (empty($usuari) || empty($email) || empty($contrasenya)) {
            $missatge = "Tots els camps són obligatoris.";
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $missatge = "Email no vàlid.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $usuari, $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $missatge = "L'usuari o email ja existeix.";
            } else {
                $hash = password_hash($contrasenya, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $usuari, $email, $hash);

                if ($stmt->execute()) {
                    $token = bin2hex(random_bytes(32));
                    $_SESSION['login_token'] = $token;
                    $_SESSION['usuari'] = $usuari;
                    header("Location: principal.php");
                    exit;
                } else {
                    $missatge = "Error en registrar l'usuari.";
                }
            }
            $stmt->close();
        }
    }

    if (isset($_POST['login'], $_POST['login_password'])) {
        $login = trim($_POST['login']);
        $password = $_POST['login_password'];

        $stmt = $conn->prepare("SELECT username, password_hash FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($usuari_bd, $hash_bd);
            $stmt->fetch();
            if (password_verify($password, $hash_bd)) {
                $token = bin2hex(random_bytes(32));
                $_SESSION['login_token'] = $token;
                $_SESSION['usuari'] = $usuari_bd;
                header("Location: principal.php");
                exit;
            } else {
                $missatge = "Contrasenya incorrecta.";
            }
        } else {
            $missatge = "Usuari o email no trobat.";
        }
        $stmt->close();
    }

    $conn->close();
}
?>