<?php
require_once __DIR__ . '/_session.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$nombre = htmlspecialchars($_SESSION['nombre']);
$rol    = htmlspecialchars($_SESSION['rol']);
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Muna Café</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300&family=Jost:wght@200;300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
<div class="dash-layout">

  <!-- ── Sidebar ─────────────────────────────────────── -->
  <aside class="dash-sidebar">
    <div class="dash-brand-wrap">
      <img src="../Imagenes/Logo.jpg" alt="Muna Café" class="dash-logo-img">
      <div>
        <div class="dash-brand-name">Muna Café</div>
        <div class="dash-brand-label">Panel de Gerencia</div>
      </div>
    </div>

    <nav class="dash-nav">
      <button class="dash-nav-item active" data-section="reservaciones">
        <span class="dash-nav-icon">📅</span> Reservaciones
      </button>
      <button class="dash-nav-item" data-section="pedidos">
        <span class="dash-nav-icon">🧾</span> Pedidos
      </button>
      <?php if ($_SESSION['rol'] === 'gerente'): ?>
      <button class="dash-nav-item" data-section="menu">
        <span class="dash-nav-icon">🍽️</span> Menú
      </button>
      <button class="dash-nav-item" data-section="usuarios">
        <span class="dash-nav-icon">👥</span> Usuarios
      </button>
      <?php endif; ?>
    </nav>

    <div class="dash-user-block">
      <div class="dash-user-name"><?= $nombre ?></div>
      <div class="dash-user-rol"><?= ucfirst($rol) ?></div>
      <a href="logout.php" class="dash-logout-btn">Cerrar sesión</a>
    </div>
  </aside>

  <!-- ── Main ────────────────────────────────────────── -->
  <main class="dash-main">

    <!-- Stats -->
    <div class="dash-stats-row">
      <div class="dash-stat">
        <div class="dash-stat-val" id="stat-res-hoy">—</div>
        <div class="dash-stat-lbl">Reservaciones hoy</div>
      </div>
      <div class="dash-stat">
        <div class="dash-stat-val" id="stat-res-total">—</div>
        <div class="dash-stat-lbl">Reservaciones totales</div>
      </div>
      <div class="dash-stat">
        <div class="dash-stat-val" id="stat-ped-pendientes">—</div>
        <div class="dash-stat-lbl">Pedidos pendientes</div>
      </div>
      <div class="dash-stat">
        <div class="dash-stat-val" id="stat-ped-hoy">—</div>
        <div class="dash-stat-lbl">Pedidos hoy</div>
      </div>
    </div>

    <!-- ── Sección Reservaciones ── -->
    <section class="dash-section" id="section-reservaciones">
      <div class="dash-section-header">
        <h2>Reservaciones</h2>
        <div class="dash-filters">
          <input type="date" id="filter-res-fecha" title="Filtrar por fecha">
          <select id="filter-res-estado">
            <option value="">Todos los estados</option>
            <option value="confirmada">Confirmada</option>
            <option value="cancelada">Cancelada</option>
          </select>
          <button class="dash-btn-refresh" onclick="loadReservaciones()">↻ Actualizar</button>
        </div>
      </div>
      <div class="dash-table-wrap">
        <table class="dash-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Fecha</th>
              <th>Hora</th>
              <th>Personas</th>
              <th>Teléfono</th>
              <th>Peticiones</th>
              <th>Fuente</th>
              <th>Estado</th>
              <th>Registrada</th>
            </tr>
          </thead>
          <tbody id="tbody-reservaciones">
            <tr><td colspan="10" class="dash-loading">Cargando...</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- ── Sección Pedidos ── -->
    <section class="dash-section" id="section-pedidos" style="display:none">
      <div class="dash-section-header">
        <h2>Pedidos</h2>
        <div class="dash-filters">
          <input type="date" id="filter-ped-fecha" title="Filtrar por fecha">
          <select id="filter-ped-estado">
            <option value="">Todos los estados</option>
            <option value="pendiente">Pendiente</option>
            <option value="en_proceso">En proceso</option>
            <option value="listo">Listo</option>
            <option value="entregado">Entregado</option>
            <option value="cancelado">Cancelado</option>
          </select>
          <button class="dash-btn-refresh" onclick="loadPedidos()">↻ Actualizar</button>
        </div>
      </div>
      <div class="dash-table-wrap">
        <table class="dash-table">
          <thead>
            <tr>
              <th>ID Pedido</th>
              <th>Productos</th>
              <th>Total</th>
              <th>Fuente</th>
              <th>Cliente</th>
              <th>Estado</th>
              <th>Hora</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="tbody-pedidos">
            <tr><td colspan="8" class="dash-loading">Cargando...</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- ── Sección Menú (solo gerente) ── -->
    <?php if ($_SESSION['rol'] === 'gerente'): ?>
    <section class="dash-section" id="section-menu" style="display:none">
      <div class="dash-section-header">
        <h2>Menú — Productos</h2>
        <button class="dash-btn-refresh" onclick="toggleFormProducto()">+ Nuevo producto</button>
      </div>

      <!-- Formulario agregar producto -->
      <div id="form-producto-wrap" class="usr-form-wrap" style="display:none">
        <form id="form-producto" class="usr-form">

          <div class="usr-form-grid">
            <div class="usr-field">
              <label class="usr-label">Categoría</label>
              <select id="prod-categoria" class="usr-input">
                <option value="">Cargando categorías…</option>
              </select>
              <span class="usr-help">Elige una existente o crea una nueva.</span>
            </div>
            <div class="usr-field">
              <label class="usr-label">Nombre del producto *</label>
              <input type="text" id="prod-nombre" class="usr-input" placeholder="Ej. Latte de Vainilla" maxlength="120" required>
            </div>
            <div class="usr-field">
              <label class="usr-label">Precio (MXN) *</label>
              <input type="number" id="prod-precio" class="usr-input" placeholder="Ej. 62" min="0" step="0.5" required>
            </div>
          </div>

          <!-- Campos de categoría nueva (ocultos hasta elegir "Nueva categoría") -->
          <div id="prod-nueva-cat" class="usr-form-grid" style="display:none">
            <div class="usr-field">
              <label class="usr-label">Nombre de la nueva categoría *</label>
              <input type="text" id="prod-cat-nombre" class="usr-input" placeholder="Ej. Bebidas de Temporada" maxlength="60">
            </div>
            <div class="usr-field">
              <label class="usr-label">Etiqueta corta *</label>
              <input type="text" id="prod-cat-etiqueta" class="usr-input" placeholder="Ej. Temporada" maxlength="20">
              <span class="usr-help">Aparece junto al número: "05 / Temporada". El número se asigna solo.</span>
            </div>
            <div class="usr-field">
              <label class="usr-label">Foto de la categoría (opcional)</label>
              <input type="text" id="prod-cat-foto" class="usr-input" placeholder="Archivo en Imagenes/ o URL" maxlength="300">
            </div>
          </div>

          <div class="usr-form-grid">
            <div class="usr-field">
              <label class="usr-label">Descripción</label>
              <input type="text" id="prod-descripcion" class="usr-input" placeholder="Ej. Leche sedosa, vainilla natural" maxlength="200">
            </div>
            <div class="usr-field">
              <label class="usr-label">Imagen del producto (opcional)</label>
              <input type="text" id="prod-imagen" class="usr-input" placeholder="Archivo en Imagenes/, URL o link de Drive" maxlength="300">
              <span class="usr-help">Ej. "Latte Caliente.jpeg" (ya subido a Imagenes/) o un link de Google Drive.</span>
            </div>
            <div class="usr-field">
              <label class="usr-label">Disponible</label>
              <label class="usr-check"><input type="checkbox" id="prod-disponible" checked> Mostrar en la página</label>
            </div>
          </div>

          <div class="usr-form-grid">
            <div class="usr-field">
              <label class="usr-label">Extras (opcional)</label>
              <input type="text" id="prod-extras" class="usr-input" placeholder="Ej. leche de avena, shot extra" maxlength="200">
              <span class="usr-help">Separados por comas. El cliente puede agregarlos a su pedido.</span>
            </div>
            <div class="usr-field">
              <label class="usr-label">Se puede pedir sin… (opcional)</label>
              <input type="text" id="prod-sin" class="usr-input" placeholder="Ej. nuez, crema" maxlength="200">
              <span class="usr-help">Ingredientes que el cliente puede quitar, separados por comas.</span>
            </div>
          </div>

          <div id="prod-error" class="usr-feedback usr-error" style="display:none"></div>
          <div id="prod-ok"    class="usr-feedback usr-ok"    style="display:none"></div>
          <div class="usr-form-actions">
            <button type="submit" class="dash-btn-primary" id="prod-submit">Agregar al menú</button>
            <button type="button" class="dash-btn-secondary" onclick="toggleFormProducto()">Cancelar</button>
          </div>
        </form>
      </div>

      <!-- Tabla del menú actual -->
      <div class="dash-table-wrap">
        <table class="dash-table">
          <thead>
            <tr>
              <th>Categoría</th>
              <th>Producto</th>
              <th>Precio</th>
              <th>Descripción</th>
              <th>Disponible</th>
              <th>Extras</th>
              <th>Sin</th>
            </tr>
          </thead>
          <tbody id="tbody-menu">
            <tr><td colspan="7" class="dash-loading">Cargando...</td></tr>
          </tbody>
        </table>
      </div>
    </section>
    <?php endif; ?>

    <!-- ── Sección Usuarios (solo gerente) ── -->
    <?php if ($_SESSION['rol'] === 'gerente'): ?>
    <section class="dash-section" id="section-usuarios" style="display:none">
      <div class="dash-section-header">
        <h2>Usuarios — Meseros</h2>
        <button class="dash-btn-refresh" onclick="toggleFormUsuario()">+ Nuevo mesero</button>
      </div>

      <!-- Formulario crear usuario -->
      <div id="form-usuario-wrap" class="usr-form-wrap" style="display:none">
        <form id="form-usuario" class="usr-form">
          <div class="usr-form-grid">
            <div class="usr-field">
              <label class="usr-label">Nombre completo</label>
              <input type="text" id="usr-nombre" class="usr-input" placeholder="Nombre del mesero" maxlength="80" required>
            </div>
            <div class="usr-field">
              <label class="usr-label">Usuario (para iniciar sesión)</label>
              <input type="text" id="usr-usuario" class="usr-input" placeholder="Nombre de usuario" maxlength="80" required autocomplete="off">
            </div>
            <div class="usr-field">
              <label class="usr-label">Contraseña</label>
              <input type="password" id="usr-password" class="usr-input" placeholder="Mínimo 6 caracteres" minlength="6" required autocomplete="new-password">
            </div>
          </div>
          <div id="usr-error" class="usr-feedback usr-error" style="display:none"></div>
          <div id="usr-ok"    class="usr-feedback usr-ok"    style="display:none"></div>
          <div class="usr-form-actions">
            <button type="submit" class="dash-btn-primary">Crear mesero</button>
            <button type="button" class="dash-btn-secondary" onclick="toggleFormUsuario()">Cancelar</button>
          </div>
        </form>
      </div>

      <!-- Tabla usuarios -->
      <div class="dash-table-wrap">
        <table class="dash-table">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Usuario</th>
              <th>Rol</th>
              <th>Estado</th>
              <th>Creado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="tbody-usuarios">
            <tr><td colspan="6" class="dash-loading">Cargando...</td></tr>
          </tbody>
        </table>
      </div>
    </section>
    <?php endif; ?>

  </main>
</div>

<!-- Modal ticket de impresión -->
<div id="ticket-modal" class="ticket-modal" style="display:none">
  <div class="ticket-modal-inner">
    <div id="ticket-content"></div>
    <div class="ticket-actions">
      <button onclick="window.print()" class="dash-btn-primary">🖨️ Imprimir</button>
      <button onclick="cerrarTicket()" class="dash-btn-secondary">Cerrar</button>
    </div>
  </div>
</div>

<script>
var currentSection = 'reservaciones';
var reservaciones  = [];
var pedidos        = [];
var usuarios       = [];

// ── Navegación ──────────────────────────────────────────────────
document.querySelectorAll('.dash-nav-item').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var sec = this.dataset.section;
    currentSection = sec;
    document.querySelectorAll('.dash-nav-item').forEach(function(b) { b.classList.remove('active'); });
    this.classList.add('active');
    document.getElementById('section-reservaciones').style.display = sec === 'reservaciones' ? '' : 'none';
    document.getElementById('section-pedidos').style.display       = sec === 'pedidos'       ? '' : 'none';
    var secUsr = document.getElementById('section-usuarios');
    if (secUsr) secUsr.style.display = sec === 'usuarios' ? '' : 'none';
    var secMenu = document.getElementById('section-menu');
    if (secMenu) secMenu.style.display = sec === 'menu' ? '' : 'none';
    if (sec === 'reservaciones') loadReservaciones();
    else if (sec === 'pedidos')  loadPedidos();
    else if (sec === 'usuarios') loadUsuarios();
    else if (sec === 'menu')     loadMenu();
  });
});

// ── Filtros ──────────────────────────────────────────────────────
['filter-res-fecha','filter-res-estado'].forEach(function(id) {
  document.getElementById(id).addEventListener('change', renderReservaciones);
});
['filter-ped-fecha','filter-ped-estado'].forEach(function(id) {
  document.getElementById(id).addEventListener('change', renderPedidos);
});

// ── Reservaciones ────────────────────────────────────────────────
function loadReservaciones() {
  var tbody = document.getElementById('tbody-reservaciones');
  tbody.innerHTML = '<tr><td colspan="10" class="dash-loading">Cargando...</td></tr>';
  fetch('api/get_reservaciones.php')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      reservaciones = data.reservaciones || [];
      updateStats(data.stats || {});
      renderReservaciones();
    })
    .catch(function() {
      tbody.innerHTML = '<tr><td colspan="10" class="dash-error">Error al cargar</td></tr>';
    });
}

function renderReservaciones() {
  var fecha  = document.getElementById('filter-res-fecha').value;
  var estado = document.getElementById('filter-res-estado').value;
  var filtered = reservaciones.filter(function(r) {
    if (fecha  && r.fecha  !== fecha)  return false;
    if (estado && r.estado !== estado) return false;
    return true;
  });

  var tbody = document.getElementById('tbody-reservaciones');
  if (!filtered.length) {
    tbody.innerHTML = '<tr><td colspan="10" class="dash-empty">No hay reservaciones</td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(function(r) {
    var fuente = r.fuente === 'whatsapp'
      ? '<span class="badge badge-wa">WhatsApp</span>'
      : '<span class="badge badge-web">Web</span>';
    var estado = r.estado === 'cancelada'
      ? '<span class="badge badge-cancel">Cancelada</span>'
      : '<span class="badge badge-ok">Confirmada</span>';
    return '<tr>' +
      '<td><span class="mono">' + esc(r.reservation_id) + '</span></td>' +
      '<td>' + esc(r.nombre) + '</td>' +
      '<td>' + esc(r.fecha) + '</td>' +
      '<td>' + esc(r.hora) + '</td>' +
      '<td class="center">' + esc(r.personas) + '</td>' +
      '<td>' + esc(r.telefono) + '</td>' +
      '<td class="text-sm">' + esc(r.peticiones || '—') + '</td>' +
      '<td>' + fuente + '</td>' +
      '<td>' + estado + '</td>' +
      '<td class="text-sm">' + esc(r.created_at.substring(0,16)) + '</td>' +
    '</tr>';
  }).join('');
}

// ── Pedidos ──────────────────────────────────────────────────────
function loadPedidos() {
  var tbody = document.getElementById('tbody-pedidos');
  tbody.innerHTML = '<tr><td colspan="8" class="dash-loading">Cargando...</td></tr>';
  fetch('api/get_pedidos.php')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      pedidos = data.pedidos || [];
      updateStats(data.stats || {});
      renderPedidos();
    })
    .catch(function() {
      tbody.innerHTML = '<tr><td colspan="8" class="dash-error">Error al cargar</td></tr>';
    });
}

function renderPedidos() {
  var fecha  = document.getElementById('filter-ped-fecha').value;
  var estado = document.getElementById('filter-ped-estado').value;
  var filtered = pedidos.filter(function(p) {
    if (fecha  && p.created_at.substring(0,10) !== fecha) return false;
    if (estado && p.estado !== estado) return false;
    return true;
  });

  var tbody = document.getElementById('tbody-pedidos');
  if (!filtered.length) {
    tbody.innerHTML = '<tr><td colspan="8" class="dash-empty">No hay pedidos</td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(function(p) {
    var items = [];
    try { items = JSON.parse(p.items); } catch(e) {}
    var itemsHtml = items.map(function(i) {
      return esc(i.cantidad + 'x ' + i.nombre);
    }).join(', ');

    var fuente = p.fuente === 'whatsapp'
      ? '<span class="badge badge-wa">WhatsApp</span>'
      : '<span class="badge badge-web">Web</span>';

    var estadoBadge = {
      pendiente:  '<span class="badge badge-pending">Pendiente</span>',
      en_proceso: '<span class="badge badge-process">En proceso</span>',
      listo:      '<span class="badge badge-ready">Listo</span>',
      entregado:  '<span class="badge badge-done">Entregado</span>',
      cancelado:  '<span class="badge badge-cancel">Cancelado</span>',
    }[p.estado] || '<span class="badge">' + esc(p.estado) + '</span>';

    return '<tr>' +
      '<td><span class="mono">' + esc(p.order_id) + '</span></td>' +
      '<td class="text-sm">' + itemsHtml + '</td>' +
      '<td class="bold">$' + parseFloat(p.total).toFixed(2) + '</td>' +
      '<td>' + fuente + '</td>' +
      '<td>' + esc(p.nombre_cliente || '—') + '</td>' +
      '<td>' +
        '<select class="estado-select" onchange="actualizarEstado(' + p.id + ', this.value)">' +
          ['pendiente','en_proceso','listo','entregado','cancelado'].map(function(s) {
            return '<option value="' + s + '"' + (p.estado === s ? ' selected' : '') + '>' + s.replace('_',' ') + '</option>';
          }).join('') +
        '</select>' +
      '</td>' +
      '<td class="text-sm">' + esc(p.created_at.substring(11,16)) + '</td>' +
      '<td>' +
        '<button class="dash-btn-ticket" onclick="imprimirTicket(' + p.id + ')">🖨️ Ticket</button>' +
      '</td>' +
    '</tr>';
  }).join('');
}

// ── Actualizar estado pedido ─────────────────────────────────────
function actualizarEstado(id, estado) {
  fetch('api/update_estado.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: id, estado: estado }),
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (!data.ok) alert('Error al actualizar estado');
  })
  .catch(function() { alert('Error de conexión'); });
}

// ── Ticket de impresión ──────────────────────────────────────────
function imprimirTicket(id) {
  var p = pedidos.find(function(x) { return x.id === id; });
  if (!p) return;
  var items = [];
  try { items = JSON.parse(p.items); } catch(e) {}

  var html = '<div class="ticket">' +
    '<div class="ticket-header">' +
      '<div class="ticket-logo">Muna Café</div>' +
      '<div class="ticket-subtitle">Café de Especialidad · CDMX</div>' +
    '</div>' +
    '<div class="ticket-divider"></div>' +
    '<div class="ticket-id">Pedido: ' + esc(p.order_id) + '</div>' +
    '<div class="ticket-meta">Fecha: ' + esc(p.created_at.substring(0,16)) + '</div>' +
    '<div class="ticket-meta">Fuente: ' + esc(p.fuente) + '</div>' +
    (p.nombre_cliente ? '<div class="ticket-meta">Cliente: ' + esc(p.nombre_cliente) + '</div>' : '') +
    '<div class="ticket-divider"></div>' +
    '<table class="ticket-items">' +
      items.map(function(i) {
        var mods = [];
        if (i.extras && i.extras.length) mods.push('+ ' + esc(i.extras.join(', ')));
        if (i.sin    && i.sin.length)    mods.push('sin ' + esc(i.sin.join(', ')));
        if (i.nota)                       mods.push(esc(i.nota));
        var qty   = esc(String(parseInt(i.cantidad, 10) || 0));
        var price = esc((parseFloat(i.precio || 0) * parseInt(i.cantidad || 0, 10)).toFixed(2));
        return '<tr>' +
          '<td class="tk-qty">' + qty + 'x</td>' +
          '<td class="tk-name">' + esc(i.nombre) + (mods.length ? '<br><small>' + mods.join(' · ') + '</small>' : '') + '</td>' +
          '<td class="tk-price">$' + price + '</td>' +
        '</tr>';
      }).join('') +
    '</table>' +
    '<div class="ticket-divider"></div>' +
    '<div class="ticket-total">Total: $' + parseFloat(p.total).toFixed(2) + '</div>' +
    '<div class="ticket-footer">¡Gracias por tu pedido!</div>' +
  '</div>';

  document.getElementById('ticket-content').innerHTML = html;
  document.getElementById('ticket-modal').style.display = 'flex';
}

function cerrarTicket() {
  document.getElementById('ticket-modal').style.display = 'none';
}

// ── Stats ────────────────────────────────────────────────────────
function updateStats(stats) {
  if (stats.res_hoy      !== undefined) document.getElementById('stat-res-hoy').textContent       = stats.res_hoy;
  if (stats.res_total    !== undefined) document.getElementById('stat-res-total').textContent     = stats.res_total;
  if (stats.ped_pendientes!==undefined) document.getElementById('stat-ped-pendientes').textContent = stats.ped_pendientes;
  if (stats.ped_hoy      !== undefined) document.getElementById('stat-ped-hoy').textContent       = stats.ped_hoy;
}

function esc(s) {
  return String(s || '')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Usuarios ─────────────────────────────────────────────────────
function loadUsuarios() {
  var tbody = document.getElementById('tbody-usuarios');
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="6" class="dash-loading">Cargando...</td></tr>';
  fetch('api/get_usuarios.php')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      usuarios = data.usuarios || [];
      renderUsuarios();
    })
    .catch(function() {
      tbody.innerHTML = '<tr><td colspan="6" class="dash-error">Error al cargar</td></tr>';
    });
}

function renderUsuarios() {
  var tbody = document.getElementById('tbody-usuarios');
  if (!tbody) return;
  if (!usuarios.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="dash-empty">No hay usuarios registrados</td></tr>';
    return;
  }
  tbody.innerHTML = usuarios.map(function(u) {
    var rolBadge = u.rol === 'gerente'
      ? '<span class="badge badge-process">Gerente</span>'
      : '<span class="badge badge-web">Mesero</span>';
    var activoBadge = parseInt(u.activo)
      ? '<span class="badge badge-ok">Activo</span>'
      : '<span class="badge badge-cancel">Inactivo</span>';
    var accionBtn = u.rol === 'gerente'
      ? '<span class="text-sm" style="color:var(--text-light)">—</span>'
      : (parseInt(u.activo)
          ? '<button class="dash-btn-ticket" onclick="toggleUsuario(' + u.id + ',0)">Desactivar</button>'
          : '<button class="dash-btn-ticket" onclick="toggleUsuario(' + u.id + ',1)">Activar</button>');
    return '<tr>' +
      '<td>' + esc(u.nombre) + '</td>' +
      '<td><span class="mono">' + esc(u.usuario) + '</span></td>' +
      '<td>' + rolBadge + '</td>' +
      '<td>' + activoBadge + '</td>' +
      '<td class="text-sm">' + esc(u.created_at.substring(0,10)) + '</td>' +
      '<td>' + accionBtn + '</td>' +
    '</tr>';
  }).join('');
}

function toggleUsuario(id, activo) {
  fetch('api/toggle_usuario.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: id, activo: activo }),
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.ok) loadUsuarios();
    else alert(data.error || 'Error al actualizar usuario');
  })
  .catch(function() { alert('Error de conexión'); });
}

function toggleFormUsuario() {
  var wrap = document.getElementById('form-usuario-wrap');
  var visible = wrap.style.display !== 'none';
  wrap.style.display = visible ? 'none' : '';
  if (!visible) {
    document.getElementById('form-usuario').reset();
    document.getElementById('usr-error').style.display = 'none';
    document.getElementById('usr-ok').style.display    = 'none';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  var form = document.getElementById('form-usuario');
  if (!form) return;
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var nombre     = document.getElementById('usr-nombre').value.trim();
    var usuario    = document.getElementById('usr-usuario').value.trim();
    var contrasena = document.getElementById('usr-password').value;
    var errEl      = document.getElementById('usr-error');
    var okEl       = document.getElementById('usr-ok');

    errEl.style.display = 'none';
    okEl.style.display  = 'none';

    fetch('api/crear_usuario.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nombre: nombre, usuario: usuario, contrasena: contrasena }),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.ok) {
        okEl.textContent    = 'Mesero "' + nombre + '" creado correctamente.';
        okEl.style.display  = '';
        form.reset();
        loadUsuarios();
      } else {
        errEl.textContent   = data.error || 'Error al crear usuario';
        errEl.style.display = '';
      }
    })
    .catch(function() {
      errEl.textContent   = 'Error de conexión';
      errEl.style.display = '';
    });
  });
});

// ── Menú (Google Sheets) ─────────────────────────────────────────
var menuCategorias = [];
var menuProductos  = [];

function loadMenu() {
  var tbody = document.getElementById('tbody-menu');
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="7" class="dash-loading">Cargando...</td></tr>';
  fetch('api/get_menu.php')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.error) throw new Error(data.error);
      menuCategorias = data.categorias || [];
      menuProductos  = data.productos  || [];
      fillCategoriaSelect();
      renderMenuTable();
    })
    .catch(function(err) {
      tbody.innerHTML = '<tr><td colspan="7" class="dash-error">' +
        esc(err.message || 'Error al cargar el menú') + '</td></tr>';
    });
}

function fillCategoriaSelect() {
  var sel = document.getElementById('prod-categoria');
  if (!sel) return;
  var current = sel.value;
  sel.innerHTML =
    menuCategorias.map(function(c) {
      return '<option value="' + esc(c.num) + '">' + esc(c.num + ' — ' + c.nombre) + '</option>';
    }).join('') +
    '<option value="__nueva__">➕ Nueva categoría…</option>';
  if (current) sel.value = current;
  toggleNuevaCategoria();
}

function toggleNuevaCategoria() {
  var sel  = document.getElementById('prod-categoria');
  var wrap = document.getElementById('prod-nueva-cat');
  if (!sel || !wrap) return;
  wrap.style.display = sel.value === '__nueva__' ? '' : 'none';
}

function renderMenuTable() {
  var tbody = document.getElementById('tbody-menu');
  if (!tbody) return;
  if (!menuProductos.length) {
    tbody.innerHTML = '<tr><td colspan="7" class="dash-empty">No hay productos en el menú</td></tr>';
    return;
  }
  tbody.innerHTML = menuProductos.map(function(p) {
    var disp = p.disponible
      ? '<span class="badge badge-ok">Sí</span>'
      : '<span class="badge badge-cancel">No</span>';
    return '<tr>' +
      '<td class="text-sm">' + esc(p.categoria_num ? p.categoria_num + ' — ' + p.categoria : p.categoria) + '</td>' +
      '<td>' + esc(p.nombre) + '</td>' +
      '<td class="bold">$' + esc(p.precio) + '</td>' +
      '<td class="text-sm">' + esc(p.descripcion || '—') + '</td>' +
      '<td>' + disp + '</td>' +
      '<td class="text-sm">' + esc(p.extras || '—') + '</td>' +
      '<td class="text-sm">' + esc(p.sin_opciones || '—') + '</td>' +
    '</tr>';
  }).join('');
}

function toggleFormProducto() {
  var wrap = document.getElementById('form-producto-wrap');
  if (!wrap) return;
  var visible = wrap.style.display !== 'none';
  wrap.style.display = visible ? 'none' : '';
  if (!visible) {
    document.getElementById('form-producto').reset();
    document.getElementById('prod-error').style.display = 'none';
    document.getElementById('prod-ok').style.display    = 'none';
    toggleNuevaCategoria();
  }
}

document.addEventListener('DOMContentLoaded', function() {
  var sel = document.getElementById('prod-categoria');
  if (sel) sel.addEventListener('change', toggleNuevaCategoria);

  var form = document.getElementById('form-producto');
  if (!form) return;
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var errEl = document.getElementById('prod-error');
    var okEl  = document.getElementById('prod-ok');
    var btn   = document.getElementById('prod-submit');
    errEl.style.display = 'none';
    okEl.style.display  = 'none';

    var catValue = document.getElementById('prod-categoria').value;
    var payload = {
      categoria_modo: catValue === '__nueva__' ? 'nueva' : 'existente',
      categoria_num:  catValue === '__nueva__' ? '' : catValue,
      cat_nombre:     document.getElementById('prod-cat-nombre').value.trim(),
      cat_etiqueta:   document.getElementById('prod-cat-etiqueta').value.trim(),
      cat_foto:       document.getElementById('prod-cat-foto').value.trim(),
      nombre:         document.getElementById('prod-nombre').value.trim(),
      precio:         document.getElementById('prod-precio').value,
      descripcion:    document.getElementById('prod-descripcion').value.trim(),
      imagen:         document.getElementById('prod-imagen').value.trim(),
      extras:         document.getElementById('prod-extras').value.trim(),
      sin_opciones:   document.getElementById('prod-sin').value.trim(),
      disponible:     document.getElementById('prod-disponible').checked,
    };

    if (payload.categoria_modo === 'nueva' && (!payload.cat_nombre || !payload.cat_etiqueta)) {
      errEl.textContent   = 'Para una categoría nueva escribe su nombre y su etiqueta corta.';
      errEl.style.display = '';
      return;
    }

    btn.disabled    = true;
    btn.textContent = 'Guardando…';

    fetch('api/crear_producto.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.ok) {
        okEl.textContent = '"' + data.producto + '" agregado a ' + data.categoria.num +
          ' — ' + data.categoria.nombre + '. Puede tardar 1–2 minutos en verse en la página.';
        okEl.style.display = '';
        form.reset();
        loadMenu();
      } else {
        errEl.textContent   = data.error || 'Error al agregar el producto';
        errEl.style.display = '';
      }
    })
    .catch(function() {
      errEl.textContent   = 'Error de conexión';
      errEl.style.display = '';
    })
    .finally(function() {
      btn.disabled    = false;
      btn.textContent = 'Agregar al menú';
    });
  });
});

// ── Inicio ───────────────────────────────────────────────────────
loadReservaciones();
loadPedidos(); // para las stats
</script>
</body>
</html>
