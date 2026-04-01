<?php
/*
    SERTAEJ S.A. - Central de Despacho (Dispatcher)
    UI responsive inspirada en TaxiCaller, con colores de marca SERTAEJ.

    IMPORTANTE:
    1) Reemplaza YOUR_GOOGLE_MAPS_API_KEY por tu clave real.
    2) Para pruebas puedes usar DEMO_MAP_ID.
    3) Para producción crea tu propio Map ID en Google Cloud.
*/

$config = require __DIR__ . '/config/dispatcher_config.php';
$empresa = $config['company'];
$ciudad = $config['city'];
$googleMapsApiKey = $config['google_maps_api_key'];
$googleMapsMapId = $config['google_maps_map_id'];
$pollingMs = (int) $config['polling_ms'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Central de Despacho - <?php echo $empresa; ?></title>
    <style>
        :root {
            --brand-blue: #2f43a8;
            --brand-blue-dark: #1e2d80;
            --brand-yellow: #f9cb2f;
            --brand-orange: #f7a43a;
            --brand-bg: #1a213c;

            --bg: #1d233a;
            --bg-soft: #242d4f;
            --bg-soft-2: #2e3762;
            --panel: #f4f6fb;
            --panel-line: #d9def0;
            --text-dark: #1f2742;
            --text-mid: #5f6788;
            --white: #ffffff;
            --green: #2eb05f;
            --red: #da4e4e;
            --orange: #f39a3c;
            --blue: #5c7dff;
            --cyan: #1abdc9;
            --purple: #b36bff;
            --shadow: 0 10px 28px rgba(19, 31, 78, 0.28);
            --radius: 14px;
            --radius-sm: 10px;
            --topbar-h: 64px;
            --leftbar-w: 58px;
            --rightbar-w: 368px;
            --bottom-h: 185px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        html,
        body {
            height: 100%;
            background: var(--bg);
            color: var(--text-dark);
            overflow: hidden;
        }

        .app-shell {
            display: grid;
            grid-template-columns: var(--leftbar-w) 1fr var(--rightbar-w);
            grid-template-rows: var(--topbar-h) 1fr var(--bottom-h);
            grid-template-areas:
                "top top top"
                "left center right"
                "left bottom right";
            width: 100%;
            height: 100vh;
            background: #dde3f2;
        }

        .topbar {
            grid-area: top;
            background: linear-gradient(90deg, var(--brand-blue-dark) 0%, var(--brand-blue) 65%, #3d5bd2 100%);
            border-bottom: 1px solid rgba(255,255,255,0.18);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px 0 10px;
            color: var(--white);
            z-index: 30;
            gap: 10px;
        }

        .topbar-left,
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 0;
            overflow: hidden;
        }

        .brand-square {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: radial-gradient(circle at 30% 25%, var(--brand-yellow) 0%, #f6bf17 65%, #e2a812 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1b1b1b;
            flex: 0 0 auto;
            box-shadow: inset 0 0 0 2px rgba(0,0,0,0.12);
        }

        .pill {
            border-radius: 999px;
            padding: 9px 14px;
            border: 1px solid rgba(249, 203, 47, 0.6);
            color: var(--white);
            font-size: 12px;
            line-height: 1;
            background: rgba(255,255,255,0.06);
            white-space: nowrap;
        }

        .pill-active {
            background: var(--brand-yellow);
            color: #252525;
            border-color: transparent;
            font-weight: bold;
        }

        .topbar-divider {
            width: 1px;
            height: 34px;
            background: rgba(255, 255, 255, 0.25);
            margin: 0 4px;
        }

        .topbar-clock {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 0.4px;
        }

        .topbar-signal {
            font-size: 11px;
            padding: 5px 8px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.28);
            background: rgba(0,0,0,0.15);
        }

        .user-box {
            display: flex;
            align-items: center;
            gap: 9px;
            font-weight: bold;
        }

        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--brand-orange);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
        }

        .leftbar {
            grid-area: left;
            background: linear-gradient(180deg, #1f2950 0%, #172042 100%);
            border-right: 1px solid #2f3e78;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 0;
            gap: 8px;
            z-index: 20;
        }

        .nav-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ced7ff;
            position: relative;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .nav-icon:hover,
        .nav-icon.active {
            background: rgba(249, 203, 47, 0.14);
            color: var(--white);
        }

        .nav-badge {
            position: absolute;
            right: -2px;
            bottom: -2px;
            min-width: 18px;
            height: 18px;
            border-radius: 999px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--brand-orange);
            color: var(--white);
            padding: 0 5px;
            font-weight: bold;
            border: 2px solid #1f2023;
        }

        .center-panel {
            grid-area: center;
            position: relative;
            background: #cfd8dc;
            overflow: hidden;
        }

        #map {
            width: 100%;
            height: 100%;
        }

        .floating-topbar {
            position: absolute;
            top: 12px;
            left: 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            z-index: 15;
        }

        .map-toolbox,
        .stats-box {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid rgba(47,67,168,0.16);
        }

        .map-tool-btn {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #e7e7e7;
            cursor: pointer;
            background: var(--white);
        }

        .map-tool-btn:last-child { border-bottom: none; }

        .stats-box { display: flex; align-items: stretch; }

        .stats-label-box {
            width: 85px;
            padding: 8px 10px;
            border-right: 1px solid #e6e6e6;
            background: #f3f6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: var(--text-mid);
            text-transform: lowercase;
        }

        .stat-item {
            min-width: 76px;
            padding: 7px 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-right: 1px solid #ececec;
        }

        .stat-item span { font-size: 12px; color: #6f737a; margin-bottom: 3px; text-transform: lowercase; }
        .stat-item strong { font-size: 18px; line-height: 1; }
        .stat-total strong { color: #202124; }
        .stat-free strong { color: var(--green); }
        .stat-busy strong { color: var(--red); }
        .stat-off strong { color: #cfad00; }

        .map-right-tools {
            position: absolute;
            top: 12px;
            right: 14px;
            display: flex;
            gap: 8px;
            z-index: 15;
        }

        .square-control {
            width: 34px;
            height: 34px;
            border-radius: 7px;
            background: rgba(35, 49, 102, 0.94);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow);
            cursor: pointer;
        }

        .map-bottom-strip {
            position: absolute;
            right: 12px;
            bottom: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            z-index: 15;
            max-width: calc(100% - 24px);
        }

        .bottom-chip {
            background: rgba(255, 255, 255, 0.93);
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 12px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: var(--shadow);
        }

        .bottom-chip strong { font-size: 13px; }

        .right-panel {
            grid-area: right;
            background: #edf1fb;
            border-left: 1px solid #c6d0ee;
            display: grid;
            grid-template-rows: 52px 1fr 290px;
            min-height: 0;
        }

        .right-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            background: #f3f5fb;
            border-bottom: 1px solid #d4d9e8;
        }

        .right-tab {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #5a5f67;
            border-right: 1px solid #d7d7d7;
            cursor: pointer;
            text-align: center;
            padding: 0 6px;
        }

        .right-tab:last-child { border-right: none; }

        .right-tab.active {
            background: #ffffff;
            color: #1f2124;
            font-weight: bold;
            border-bottom: 2px solid var(--brand-yellow);
        }

        .service-list-wrap {
            overflow-y: auto;
            padding: 8px;
            background: #edf1fb;
        }

        .service-card {
            background: #d6e6ff;
            border: 1px solid #9fbde9;
            margin-bottom: 8px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.35);
        }

        .service-row {
            display: grid;
            grid-template-columns: 78px 1fr 24px;
            gap: 8px;
            align-items: center;
            padding: 5px 8px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            font-size: 12px;
        }

        .service-row:last-child { border-bottom: none; }
        .service-label { color: #476273; text-transform: capitalize; }
        .service-value { color: #173545; font-size: 12px; line-height: 1.25; }
        .service-action { display: flex; align-items: center; justify-content: center; color: #16394e; cursor: pointer; }

        .queue-board {
            border-top: 1px solid #d0d0d0;
            background: #f5f5f5;
            display: grid;
            grid-template-rows: 42px 1fr;
            min-height: 0;
        }

        .queue-toolbar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 10px;
            border-bottom: 1px solid #d7d7d7;
            color: #565b63;
            font-size: 12px;
        }

        .queue-toolbar select,
        .queue-toolbar input,
        .bottom-topline input {
            height: 28px;
            border: 1px solid #cfd4da;
            background: #ffffff;
            border-radius: 4px;
            padding: 0 8px;
            outline: none;
            color: #30343a;
        }

        .queue-grid {
            display: grid;
            grid-template-columns: 46px 1fr;
            font-size: 12px;
            min-height: 0;
            overflow: auto;
        }

        .queue-head,
        .queue-body { display: contents; }

        .queue-cell-head {
            background: #efefef;
            border-bottom: 1px solid #d8d8d8;
            padding: 8px 8px;
            font-weight: bold;
            color: #4f535a;
        }

        .queue-row-id,
        .queue-row-values {
            border-bottom: 1px solid #ebebeb;
            background: #ffffff;
            padding: 7px 8px;
            min-height: 34px;
        }

        .queue-row-values {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            align-items: center;
        }

        .queue-tag {
            min-width: 28px;
            padding: 4px 7px;
            text-align: center;
            border-radius: 3px;
            color: #101214;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .tag-green { background: #7fe27f; }
        .tag-orange { background: #ffa449; }
        .tag-blue { background: #7c8cff; }
        .tag-red { background: #ff7777; }
        .tag-purple { background: #eb77ff; }
        .tag-lime { background: #9cf18a; }

        .bottom-panel {
            grid-area: bottom;
            background: #ecf0fb;
            border-top: 1px solid #c6d0ee;
            display: grid;
            grid-template-rows: 32px 1fr;
            min-height: 0;
        }

        .bottom-topline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 12px;
            font-size: 12px;
            color: #5e626a;
            border-bottom: 1px solid #d7d7d7;
            gap: 8px;
        }

        .bottom-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .bottom-actions a {
            color: var(--brand-blue);
            text-decoration: none;
            font-size: 12px;
        }

        .table-wrap { overflow: auto; background: #f3f3f3; }

        table.dispatch-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1180px;
            font-size: 12px;
        }

        .dispatch-table thead th {
            background: #eff2fa;
            color: #50545b;
            text-align: left;
            padding: 7px 8px;
            border-right: 1px solid #dde4f7;
            border-bottom: 1px solid #d0d0d0;
            font-weight: 600;
        }

        .dispatch-table tbody td {
            background: #ffffff;
            color: #2c3136;
            padding: 7px 8px;
            border-right: 1px solid #ececec;
            border-bottom: 1px solid #ececec;
            white-space: nowrap;
        }

        .load-cell { font-weight: bold; text-align: right; }
        .load-green { background: #83dd7d; }
        .load-yellow { background: #f7ef00; }
        .load-orange { background: #ffa347; }
        .load-blue { background: #5fd3d7; }

        .status-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 6px;
            vertical-align: middle;
        }

        .status-free { background: var(--green); }
        .status-busy { background: var(--red); }
        .status-pending { background: var(--orange); }
        .status-offline { background: #8c91a3; }

        .map-popup { min-width: 220px; font-size: 12px; color: #1f252a; }
        .map-popup h4 { font-size: 14px; margin-bottom: 6px; }
        .map-popup-row { margin-bottom: 4px; }

        .vehicle-marker {
            width: 40px;
            height: 24px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .vehicle-marker .car-shape {
            width: 34px;
            height: 18px;
            border-radius: 12px 12px 8px 8px;
            border: 2px solid rgba(0,0,0,0.15);
            box-shadow: 0 2px 8px rgba(0,0,0,0.28);
            position: relative;
            transform: rotate(-8deg);
        }

        .vehicle-marker .car-shape::before,
        .vehicle-marker .car-shape::after {
            content: '';
            position: absolute;
            bottom: -5px;
            width: 8px;
            height: 8px;
            background: #1b1b1b;
            border-radius: 50%;
        }

        .vehicle-marker .car-shape::before { left: 4px; }
        .vehicle-marker .car-shape::after { right: 4px; }

        .vehicle-marker .vehicle-id {
            position: absolute;
            top: -13px;
            left: 50%;
            transform: translateX(-50%);
            min-width: 22px;
            height: 18px;
            border-radius: 999px;
            background: rgba(255,255,255,0.96);
            color: #111;
            font-size: 11px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
            border: 1px solid rgba(0,0,0,0.15);
        }

        .service-marker {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #ffffffcc;
            border: 2px solid rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.25);
        }

        .service-marker-inner {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--brand-orange);
        }

        .support-btn {
            position: absolute;
            right: 12px;
            bottom: 10px;
            z-index: 15;
            background: var(--brand-yellow);
            color: #282828;
            border: none;
            border-radius: 999px;
            height: 36px;
            padding: 0 24px;
            font-weight: bold;
            box-shadow: var(--shadow);
            cursor: pointer;
        }



        .admin-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 22, 49, 0.55);
            z-index: 60;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 12px;
        }

        .admin-modal {
            width: min(900px, 100%);
            max-height: 90vh;
            background: #fff;
            border-radius: 14px;
            box-shadow: var(--shadow);
            display: grid;
            grid-template-rows: 56px 1fr;
            overflow: hidden;
        }

        .admin-head { display:flex; justify-content:space-between; align-items:center; padding:0 14px; background:#eef2ff; border-bottom:1px solid #d9e1fb; }
        .admin-body { padding:12px; overflow:auto; }
        .admin-form { display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:8px; margin-bottom:10px; }
        .admin-form input { height:34px; border:1px solid #ccd5ef; border-radius:6px; padding:0 8px; }
        .admin-form button, .btn-close-admin { border:none; border-radius:8px; padding:8px 12px; background:var(--brand-blue); color:#fff; cursor:pointer; }
        .admin-table { width:100%; border-collapse: collapse; font-size:12px; }
        .admin-table th, .admin-table td { border:1px solid #e6eaf8; padding:6px; text-align:left; }
        .empty-map { position:absolute; inset:0; display:none; align-items:center; justify-content:center; flex-direction:column; gap:8px; background:#e9edf7; color:#283154; z-index:12; }

        @media (max-width: 1480px) {
            :root { --rightbar-w: 332px; --bottom-h: 170px; }
        }

        @media (max-width: 1220px) {
            html, body { overflow: auto; }
            .app-shell {
                height: auto;
                min-height: 100vh;
                grid-template-columns: 54px 1fr;
                grid-template-rows: var(--topbar-h) 60vh auto auto;
                grid-template-areas:
                    "top top"
                    "left center"
                    "left right"
                    "left bottom";
            }
            .right-panel { grid-template-rows: 52px minmax(240px, auto) 320px; }
            .bottom-panel { min-height: 280px; }
        }

        @media (max-width: 900px) {
            .topbar { flex-direction: column; align-items: stretch; justify-content: center; gap: 6px; height: auto; padding: 8px; }
            .topbar-left { overflow-x: auto; }
            .app-shell {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto 52vh auto auto;
                grid-template-areas: "top" "left" "center" "right" "bottom";
            }
            .leftbar { flex-direction: row; justify-content: flex-start; overflow-x: auto; padding: 8px; }
            .floating-topbar { flex-direction: column; }
            .bottom-topline { flex-direction: column; align-items: stretch; padding: 8px; }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <div class="topbar-left">
            <div class="brand-square" aria-label="Logo">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M5 13h14l-1.2-5.1A2 2 0 0 0 15.86 6H8.14a2 2 0 0 0-1.94 1.9L5 13Z" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M6 13v4a1 1 0 0 0 1 1h1m9-5v4a1 1 0 0 1-1 1h-1M7 18v1m10-1v1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <circle cx="8" cy="15" r="1.1" fill="currentColor"/>
                    <circle cx="16" cy="15" r="1.1" fill="currentColor"/>
                </svg>
            </div>
            <div class="pill">ADMIN</div>
            <div class="pill pill-active">CENTRAL DE DESPACHO</div>
            <div class="pill pill-active">PRINCIPAL</div>
            <div class="pill">RESUMEN DE SERVICIOS</div>
            <div class="topbar-divider"></div>
            <div class="pill"><?php echo $empresa; ?></div>
        </div>
        <div class="topbar-right">
            <div class="topbar-signal" id="connectionState">CONECTADO</div>
            <div class="topbar-clock" id="currentClock">--/-- --:--:--</div>
            <div class="user-box">
                <span>Operador SERTAEJ</span>
                <div class="user-avatar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M5 20c1.4-3.5 4.1-5 7-5s5.6 1.5 7 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
        </div>
    </header>

    <aside class="leftbar" aria-label="Navegación principal">
        <div class="nav-icon active" title="Buscar">🔎</div>
        <div class="nav-icon" data-module="reports" title="Servicios y reportes">📋</div>
        <div class="nav-icon" data-module="users" title="Usuarios">👥</div>
        <div class="nav-icon" title="Alertas">🔔</div>
        <div class="nav-icon" data-module="reports" title="Mensajes">💬<span class="nav-badge">17</span></div>
        <div class="nav-icon" title="Zonas">🗺️<span class="nav-badge">30</span></div>
        <div class="nav-icon" data-module="vehicles" title="Vehículos">🚕</div>
        <div class="nav-icon" data-module="users" title="Usuarios">🧑</div>
        <div class="nav-icon" data-module="reports" title="Reportes">🧾</div>
    </aside>

    <section class="center-panel">
        <div id="map"></div>
        <div class="empty-map" id="emptyMap"><h3>Mapa no disponible</h3><p>Configura GOOGLE_MAPS_API_KEY válida para habilitar mapa y clics.</p></div>

        <div class="floating-topbar">
            <div class="map-toolbox">
                <div class="map-tool-btn" id="zoomInBtn" title="Acercar">+</div>
                <div class="map-tool-btn" id="zoomOutBtn" title="Alejar">−</div>
            </div>
            <div class="stats-box">
                <div class="stats-label-box">general</div>
                <div class="stat-item stat-total"><span>todos</span><strong id="statTotal">0</strong></div>
                <div class="stat-item stat-free"><span>libre</span><strong id="statFree">0</strong></div>
                <div class="stat-item stat-busy"><span>ocupado</span><strong id="statBusy">0</strong></div>
                <div class="stat-item stat-off"><span>no disponible</span><strong id="statOffline">0</strong></div>
            </div>
        </div>

        <div class="map-right-tools">
            <div class="square-control" id="toggleTrafficBtn" title="Tráfico">T</div>
            <div class="square-control" id="recenterBtn" title="Centrar mapa">◎</div>
        </div>

        <div class="map-bottom-strip">
            <div class="bottom-chip">Cancelado <strong id="statCancelled">0</strong></div>
            <div class="bottom-chip">Sin vehículos <strong id="statNoVehicles">0</strong></div>
            <div class="bottom-chip">Despachado <strong id="statDispatched">0</strong></div>
            <div class="bottom-chip">Ingresado/Creado <strong id="statCreated">0</strong></div>
            <div class="bottom-chip">Conectados <strong id="statConnected">0</strong></div>
        </div>
    </section>

    <aside class="right-panel">
        <div class="right-tabs">
            <div class="right-tab active">No asignado (<span id="unassignedCount">0</span>)</div>
            <div class="right-tab">Asignado (0)</div>
            <div class="right-tab">Activo (<span id="activeCount">0</span>)</div>
        </div>

        <div class="service-list-wrap" id="serviceList"></div>

        <div class="queue-board">
            <div class="queue-toolbar"><span>mostrar empresa:</span><select><option>Todos</option><option><?php echo $empresa; ?></option></select></div>
            <div class="queue-grid" id="queueBoard"><div class="queue-head"><div class="queue-cell-head">ID</div><div class="queue-cell-head">colas de zona</div></div></div>
        </div>
    </aside>

    <section class="bottom-panel">
        <div class="bottom-topline">
            <div class="bottom-actions"><a href="#">Cambiar columnas</a></div>
            <div class="bottom-actions"><label><input type="checkbox" checked> sólo activo</label><input type="text" placeholder="Buscar"></div>
        </div>

        <div class="table-wrap">
            <table class="dispatch-table">
                <thead>
                    <tr><th>#ID</th><th>Cargar</th><th>Indicativo</th><th>Servicios</th><th>Espera</th><th>Libre</th><th>Conductor</th><th>Origen</th><th>Destino</th><th>Pasajero</th><th>Estado</th><th>Conexión</th></tr>
                </thead>
                <tbody id="driversTableBody"></tbody>
            </table>
        </div>
    </section>
</div>


<div class="admin-modal-backdrop" id="adminModalBackdrop">
    <div class="admin-modal">
        <div class="admin-head">
            <strong id="adminTitle">Administración</strong>
            <button class="btn-close-admin" id="closeAdminModal">Cerrar</button>
        </div>
        <div class="admin-body">
            <form class="admin-form" id="adminForm"></form>
            <table class="admin-table">
                <thead><tr id="adminHeadRow"></tr></thead>
                <tbody id="adminTableBody"></tbody>
            </table>
        </div>
    </div>
</div>

<button class="support-btn">SUPPORT</button>

<script>
const appState = {
    map: null,
    infoWindow: null,
    trafficLayer: null,
    trafficEnabled: true,
    vehicleMarkers: [],
    serviceMarkers: [],
    metrics: { cancelled: 0, noVehicles: 0, dispatched: 0, created: 0 },
    drivers: [],
    services: [],
    zoneQueues: [],
};

function getStatusColor(status) {
    if (status === 'free') return '#2eb05f';
    if (status === 'busy') return '#da4e4e';
    if (status === 'pending') return '#f39a3c';
    return '#7e869a';
}
function getStatusLabel(status) {
    if (status === 'free') return 'Libre';
    if (status === 'busy') return 'Ocupado';
    if (status === 'pending') return 'Pendiente';
    return 'No disponible';
}
function getLoadClass(index) {
    const classes = ['load-green', 'load-yellow', 'load-orange', 'load-blue'];
    return classes[index % classes.length];
}

function updateClock() {
    const now = new Date();
    const text = `${now.getDate()}/${now.getMonth() + 1} ${now.toLocaleTimeString('es-EC', { hour12: false })}`;
    document.getElementById('currentClock').textContent = text;
}

function renderStats() {
    const total = appState.drivers.length;
    const free = appState.drivers.filter(d => d.status === 'free' && d.connected).length;
    const busy = appState.drivers.filter(d => d.status === 'busy' && d.connected).length;
    const offline = appState.drivers.filter(d => !d.connected || d.status === 'offline').length;
    const unassigned = appState.services.filter(s => s.status === 'unassigned').length;
    const active = appState.services.filter(s => s.status === 'active').length;
    const connected = appState.drivers.filter(d => d.connected).length;

    document.getElementById('statTotal').textContent = total;
    document.getElementById('statFree').textContent = free;
    document.getElementById('statBusy').textContent = busy;
    document.getElementById('statOffline').textContent = offline;
    document.getElementById('unassignedCount').textContent = unassigned;
    document.getElementById('activeCount').textContent = active;
    document.getElementById('statConnected').textContent = connected;

    document.getElementById('statCancelled').textContent = appState.metrics.cancelled;
    document.getElementById('statNoVehicles').textContent = appState.metrics.noVehicles;
    document.getElementById('statDispatched').textContent = appState.metrics.dispatched;
    document.getElementById('statCreated').textContent = appState.metrics.created;

    const signal = document.getElementById('connectionState');
    signal.textContent = connected > 0 ? `CONECTADO (${connected})` : 'SIN CONEXIÓN';
    signal.style.background = connected > 0 ? 'rgba(46,176,95,0.22)' : 'rgba(218,78,78,0.22)';
}

function renderServiceList() {
    const container = document.getElementById('serviceList');
    const data = appState.services.filter(s => s.status === 'unassigned' || s.status === 'active');
    container.innerHTML = data.map(item => `
        <div class="service-card">
            <div class="service-row"><div class="service-label">Cuando</div><div class="service-value">${item.when} • ${item.duration} • ${item.km}</div><div class="service-action">↻</div></div>
            <div class="service-row"><div class="service-label">Pasajero</div><div class="service-value">${item.passenger} • ${item.phone}</div><div class="service-action"></div></div>
            <div class="service-row"><div class="service-label">Origen</div><div class="service-value">${item.origin}</div><div class="service-action"></div></div>
            <div class="service-row"><div class="service-label">Destino</div><div class="service-value">${item.destination}</div><div class="service-action"></div></div>
            <div class="service-row"><div class="service-label">INFORMACIÓN</div><div class="service-value">Prioridad ${item.priority}</div><div class="service-action">▣</div></div>
            <div class="service-row"><div class="service-label">Bkd. by</div><div class="service-value">${item.operator}</div><div class="service-action">⋮</div></div>
        </div>
    `).join('');
}

function renderQueueBoard() {
    const board = document.getElementById('queueBoard');
    board.innerHTML = `<div class="queue-head"><div class="queue-cell-head">ID</div><div class="queue-cell-head">colas de zona</div></div>` +
        appState.zoneQueues.map(row => `
            <div class="queue-row-id">${row.id}</div>
            <div class="queue-row-values">${row.values.map(v => `<span class="queue-tag ${v.cls}">${v.text}</span>`).join('')}</div>
        `).join('');
}

function renderDriversTable() {
    const body = document.getElementById('driversTableBody');
    body.innerHTML = appState.drivers.map((driver, index) => {
        const loadClass = getLoadClass(index);
        const statusClass = driver.status === 'free' ? 'status-free' : driver.status === 'busy' ? 'status-busy' : driver.status === 'pending' ? 'status-pending' : 'status-offline';
        return `
            <tr>
                <td>${driver.id}</td>
                <td class="load-cell ${loadClass}">${driver.freeUnits}</td>
                <td>${driver.plate}</td>
                <td>${driver.services}</td>
                <td>${driver.wait}</td>
                <td>${driver.status === 'free' ? driver.freeUnits : 0}</td>
                <td>${driver.name}</td>
                <td>${driver.origin}</td>
                <td>${driver.destination}</td>
                <td>${driver.passenger}</td>
                <td><span class="status-dot ${statusClass}"></span>${getStatusLabel(driver.status)}</td>
                <td>${driver.connected ? 'online' : 'offline'}</td>
            </tr>
        `;
    }).join('');
}

function makeVehicleMarker(driver) {
    const wrapper = document.createElement('div'); wrapper.className = 'vehicle-marker';
    const car = document.createElement('div'); car.className = 'car-shape'; car.style.background = getStatusColor(driver.status);
    const id = document.createElement('div'); id.className = 'vehicle-id'; id.textContent = driver.code;
    wrapper.appendChild(id); wrapper.appendChild(car); return wrapper;
}
function makeServiceMarker() { const w = document.createElement('div'); w.className='service-marker'; const i=document.createElement('div'); i.className='service-marker-inner'; w.appendChild(i); return w; }

function buildPopupForDriver(driver) {
    return `<div class="map-popup"><h4>${driver.name}</h4><div class="map-popup-row"><strong>Unidad:</strong> ${driver.code}</div><div class="map-popup-row"><strong>Placa:</strong> ${driver.plate}</div><div class="map-popup-row"><strong>Estado:</strong> ${getStatusLabel(driver.status)}</div><div class="map-popup-row"><strong>Teléfono:</strong> ${driver.phone}</div><div class="map-popup-row"><strong>Conexión:</strong> ${driver.connected ? 'Online' : 'Offline'}</div></div>`;
}
function buildPopupForService(service) {
    return `<div class="map-popup"><h4>${service.id}</h4><div class="map-popup-row"><strong>Pasajero:</strong> ${service.passenger}</div><div class="map-popup-row"><strong>Origen:</strong> ${service.origin}</div><div class="map-popup-row"><strong>Destino:</strong> ${service.destination}</div><div class="map-popup-row"><strong>Prioridad:</strong> ${service.priority}</div><div class="map-popup-row"><strong>Estado:</strong> ${service.status}</div></div>`;
}

function clearMarkers(list) { list.forEach(marker => marker.map = null); }

function renderMapMarkers() {
    if (!appState.map) return;
    clearMarkers(appState.vehicleMarkers); clearMarkers(appState.serviceMarkers);
    appState.vehicleMarkers = []; appState.serviceMarkers = [];

    appState.drivers.filter(d => d.connected).forEach(driver => {
        const marker = new google.maps.marker.AdvancedMarkerElement({
            map: appState.map,
            position: { lat: driver.lat, lng: driver.lng },
            title: `${driver.code} - ${driver.name}`,
            content: makeVehicleMarker(driver)
        });
        marker.addListener('click', () => { appState.infoWindow.setContent(buildPopupForDriver(driver)); appState.infoWindow.open({ map: appState.map, anchor: marker }); });
        appState.vehicleMarkers.push(marker);
    });

    appState.services.filter(s => s.status === 'unassigned').forEach(service => {
        const marker = new google.maps.marker.AdvancedMarkerElement({
            map: appState.map,
            position: { lat: service.lat, lng: service.lng },
            title: service.id,
            content: makeServiceMarker()
        });
        marker.addListener('click', () => { appState.infoWindow.setContent(buildPopupForService(service)); appState.infoWindow.open({ map: appState.map, anchor: marker }); });
        appState.serviceMarkers.push(marker);
    });
}


async function fetchServerState() {
    try {
        const response = await fetch('api/state.php', { cache: 'no-store' });
        if (!response.ok) throw new Error('No se pudo leer estado del servidor');
        const data = await response.json();

        appState.drivers = data.drivers || [];
        appState.services = data.services || [];
        appState.zoneQueues = data.zoneQueues || [];
        appState.metrics = data.metrics || appState.metrics;

        renderStats();
        renderServiceList();
        renderQueueBoard();
        renderDriversTable();
        renderMapMarkers();
    } catch (error) {
        console.error(error);
    }
}



const adminModules = {
    users: { title: 'Usuarios', fields: ['name', 'email', 'role'] },
    vehicles: { title: 'Vehículos', fields: ['unit', 'plate', 'brand'] },
    reports: { title: 'Reportes', fields: ['title', 'type', 'note'] }
};

function showMapFallback(message) {
    const fallback = document.getElementById('emptyMap');
    fallback.style.display = 'flex';
    if (message) fallback.querySelector('p').textContent = message;
}

async function fetchModuleData(module) {
    const response = await fetch(`api/admin.php?module=${module}`, { cache: 'no-store' });
    if (!response.ok) throw new Error('No se pudo cargar módulo');
    return response.json();
}

function renderAdminTable(module, items) {
    const def = adminModules[module];
    document.getElementById('adminTitle').textContent = `Administración de ${def.title}`;
    const head = document.getElementById('adminHeadRow');
    head.innerHTML = def.fields.map(f => `<th>${f}</th>`).join('') + '<th>acción</th>';

    const form = document.getElementById('adminForm');
    form.innerHTML = def.fields.map(f => `<input name="${f}" placeholder="${f}" required>`).join('') + '<button type="submit">Guardar</button>';
    form.onsubmit = async (event) => {
        event.preventDefault();
        const formData = new FormData(form);
        const item = {};
        def.fields.forEach(f => item[f] = (formData.get(f) || '').toString());
        await fetch(`api/admin.php?module=${module}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'create', item })
        });
        await openAdminModal(module);
    };

    document.getElementById('adminTableBody').innerHTML = (items || []).map(row => `
        <tr>
            ${def.fields.map(f => `<td>${row[f] || '-'}</td>`).join('')}
            <td><button data-del="${row.id}" style="border:none;background:#da4e4e;color:#fff;border-radius:4px;padding:4px 8px;">Eliminar</button></td>
        </tr>
    `).join('');

    document.querySelectorAll('[data-del]').forEach(btn => {
        btn.addEventListener('click', async () => {
            await fetch(`api/admin.php?module=${module}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: Number(btn.dataset.del) })
            });
            await openAdminModal(module);
        });
    });
}

async function openAdminModal(module) {
    try {
        const payload = await fetchModuleData(module);
        renderAdminTable(module, payload.items || []);
        document.getElementById('adminModalBackdrop').style.display = 'flex';
    } catch (error) {
        console.error(error);
        alert('No se pudo abrir el módulo administrativo.');
    }
}

function bindUiEvents() {
    document.getElementById('zoomInBtn').addEventListener('click', () => {
        if (!appState.map) return;
        appState.map.setZoom(appState.map.getZoom() + 1);
    });
    document.getElementById('zoomOutBtn').addEventListener('click', () => {
        if (!appState.map) return;
        appState.map.setZoom(appState.map.getZoom() - 1);
    });
    document.getElementById('recenterBtn').addEventListener('click', () => {
        if (!appState.map) return;
        appState.map.setCenter({ lat: -2.170998, lng: -79.922359 });
        appState.map.setZoom(12.6);
    });
    document.getElementById('toggleTrafficBtn').addEventListener('click', () => {
        appState.trafficEnabled = !appState.trafficEnabled;
        if (appState.trafficLayer) appState.trafficLayer.setMap(appState.trafficEnabled ? appState.map : null);
    });

    document.querySelectorAll('.nav-icon[data-module]').forEach(item => {
        item.addEventListener('click', () => openAdminModal(item.dataset.module));
    });

    document.getElementById('closeAdminModal').addEventListener('click', () => {
        document.getElementById('adminModalBackdrop').style.display = 'none';
    });
}

let uiBootstrapped = false;

function bootstrapUi() {
    if (uiBootstrapped) return;
    uiBootstrapped = true;

    bindUiEvents();
    updateClock();
    setInterval(updateClock, 1000);
    fetchServerState();
    setInterval(fetchServerState, <?php echo $pollingMs; ?>);
}

async function initGoogleDispatchMap() {
    bootstrapUi();

    try {
        const { Map, InfoWindow, TrafficLayer } = await google.maps.importLibrary('maps');
        await google.maps.importLibrary('marker');

        appState.map = new Map(document.getElementById('map'), {
            center: { lat: -2.170998, lng: -79.922359 },
            zoom: 12.6,
            mapId: '<?php echo htmlspecialchars($googleMapsMapId, ENT_QUOTES, 'UTF-8'); ?>',
            disableDefaultUI: true,
            clickableIcons: false,
            gestureHandling: 'greedy',
            keyboardShortcuts: true,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
            zoomControl: false
        });

        appState.infoWindow = new InfoWindow();
        appState.trafficLayer = new TrafficLayer();
        appState.trafficLayer.setMap(appState.map);
        renderMapMarkers();
    } catch (error) {
        console.error(error);
        showMapFallback('No se pudo cargar Google Maps. Revisa tu API Key y restricciones.');
    }
}

function handleMapScriptError() {
    bootstrapUi();
    showMapFallback('No se pudo cargar script de Google Maps.');
}

window.addEventListener('DOMContentLoaded', bootstrapUi);
window.initGoogleDispatchMap = initGoogleDispatchMap;
window.handleMapScriptError = handleMapScriptError;
</script>
<script async onerror="handleMapScriptError()" src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($googleMapsApiKey, ENT_QUOTES, 'UTF-8'); ?>&loading=async&libraries=marker&callback=initGoogleDispatchMap"></script>
</body>
</html>
