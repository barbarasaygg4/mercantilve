<?php
/* Panel · Login */
session_start();
require __DIR__ . '/config.php';
if (panel_logueado()) { header('Location: dashboard.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['user'] ?? '');
    $p = (string)($_POST['pass'] ?? '');
    $ip = client_ip();
    // Anti brute-force: max 8 intentos fallidos por IP en 15 min
    $lr = json_read(F_LOGIN);
    $now = time();
    $lr = array_filter($lr, fn($t) => ($now - (int)$t) < 900);
    $fallos = 0;
    foreach ($lr as $k => $t) { if (strpos($k, $ip . '_') === 0) $fallos++; }
    if ($fallos >= 8) {
        $err = 'Demasiados intentos. Espera unos minutos.';
        log_accion('login_bloqueado_ip', $u . ' @ ' . $ip);
    } else {
        $usuarios = json_read(F_USUARIOS);
        $ok = false;
        foreach ($usuarios as $usr) {
            if (($usr['user'] ?? '') === $u && password_verify($p, $usr['pass'] ?? '')) { $ok = true; break; }
        }
        if ($ok) {
            $_SESSION['panel_user'] = $u;
            json_write(F_LOGIN, array_filter($lr, fn($t) => true));
            log_accion('login_panel', $u);
            header('Location: dashboard.php'); exit;
        }
        $lr[$ip . '_' . microtime(true)] = $now;
        json_write(F_LOGIN, $lr);
        $err = 'Usuario o clave incorrectos';
        log_accion('login_fallido', $u . ' @ ' . $ip);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Acceso · <?= htmlspecialchars(json_read(F_CONFIG)['panel_nombre'] ?? 'Panel') ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons|Material+Icons+Outlined">
<style>
  *{box-sizing:border-box}
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
    font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
    background:linear-gradient(135deg,#0b2a4a,#1976d2 75%);}
  .card{background:#fff;border-radius:18px;box-shadow:0 24px 60px rgba(0,0,0,.3);padding:40px 38px;width:370px;max-width:92vw}
  .logo{font-size:19px;font-weight:800;color:#004e9b;letter-spacing:.4px;margin-bottom:4px}
  .sub{color:#7b8a9b;font-size:13px;margin-bottom:26px}
  label{display:block;font-size:12px;font-weight:700;color:#5b6b7f;margin:14px 0 6px}
  input{width:100%;border:1px solid #e3e9f0;border-radius:11px;padding:12px 14px;font-size:14px;color:#1f466d;background:#f7f9fc}
  input:focus{outline:none;border-color:#1976d2;box-shadow:0 0 0 3px rgba(25,118,210,.13);background:#fff}
  button{width:100%;margin-top:22px;border:0;border-radius:11px;padding:13px;font-size:14px;font-weight:800;color:#fff;
    background:linear-gradient(135deg,#1976d2,#2196f3);cursor:pointer;box-shadow:0 6px 16px rgba(25,118,210,.35)}
  button:hover{filter:brightness(1.06)}
  .err{background:#fdeceb;color:#c0392b;border:1px solid #f6c9c4;border-radius:10px;padding:10px 13px;font-size:13px;margin-top:16px}
</style>
</head>
<body>
  <form class="card" method="post">
    <div class="logo">🔒 Panel de control</div>
    <div class="sub">Acceso restringido</div>
    <label>Usuario</label>
    <input name="user" autocomplete="username" required autofocus>
    <label>Clave</label>
    <input name="pass" type="password" autocomplete="current-password" required>
    <button type="submit">Ingresar</button>
    <?php if ($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  </form>
</body>
</html>
