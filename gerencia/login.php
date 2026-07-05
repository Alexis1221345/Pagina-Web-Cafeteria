<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once dirname(__DIR__) . '/php/config.php';

    $usuario    = trim($_POST['usuario']    ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');

    if ($usuario && $contrasena) {
        try {
            $db   = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE usuario = ? AND activo = 1");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();

            if ($user && password_verify($contrasena, $user['contrasena_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nombre']  = $user['nombre'];
                $_SESSION['rol']     = $user['rol'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (Throwable $e) {
            $error = 'Error del sistema. Intenta de nuevo.';
        }
    } else {
        $error = 'Por favor ingresa usuario y contraseña.';
    }
}
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gerencia — Muna Café</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300&family=Jost:wght@200;300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body class="login-body">
  <div class="login-wrap">
    <div class="login-card">
      <div class="login-brand">
        <img src="../Imagenes/Logo.jpg" alt="Muna Café" class="login-logo">
        <h1 class="login-title">Muna Café</h1>
        <p class="login-sub">Panel de Gerencia</p>
      </div>

      <?php if ($error): ?>
        <div class="login-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" class="login-form">
        <div class="login-field">
          <label for="usuario">Usuario</label>
          <input
            type="text"
            id="usuario"
            name="usuario"
            required
            autocomplete="username"
            placeholder="Tu usuario"
            value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
          >
        </div>
        <div class="login-field">
          <label for="contrasena">Contraseña</label>
          <input
            type="password"
            id="contrasena"
            name="contrasena"
            required
            autocomplete="current-password"
            placeholder="••••••••"
          >
        </div>
        <button type="submit" class="login-btn">Iniciar sesión</button>
      </form>

      <p class="login-back"><a href="../index.html">← Volver al sitio</a></p>
    </div>
  </div>
</body>
</html>
