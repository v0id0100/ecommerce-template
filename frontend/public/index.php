<?php
require_once("src/register_logic.php");
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <title>Registre i Inici de Sessió</title>
    <link rel="stylesheet" href="src/styles.css">
</head>
<body>
    <div id="formularis">
        <h2 id="titolFormulari">Registre d'Usuari</h2>
        <?php if ($missatge) echo "<p>$missatge</p>"; ?>
        <form id="registreForm" method="post" action="">
            <label>Nom d'usuari:</label><br>
            <input type="text" name="usuari" required><br>
            <label>Email:</label><br>
            <input type="email" name="email" required><br>
            <label>Contrasenya:</label><br>
            <input type="password" name="contrasenya" required><br><br>
            <input type="submit" value="Registrar">
        </form>

        <form id="loginForm" method="post" action="" style="display:none;">
            <label>Nom d'usuari o Email:</label><br>
            <input type="text" name="login" required><br>
            <label>Contrasenya:</label><br>
            <input type="password" name="login_password" required><br><br>
            <input type="submit" value="Iniciar Sessió">
        </form>

        <button id="toggleFormBtn">Tens un compta? Inicia sessió</button>
    </div>
    <script src="src/index.js"></script>
</body>
</html>
