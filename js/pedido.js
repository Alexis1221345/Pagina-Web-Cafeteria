(function () {
  /* ── Constantes ──────────────────────────────────────────────── */
  var PHONE      = '523121116210';
  var SHEETS_ID  = '16t5lMZ3-KkQgXrfP-OyTfmWKq0hTwI3Ys67eVp0vn5w';
  var GVIZ_URL   = 'https://docs.google.com/spreadsheets/d/' + SHEETS_ID +
                   '/gviz/tq?tqx=out:json&sheet=Menu';

  /* ── Estado ──────────────────────────────────────────────────── */
  var menuGroups  = [];   // [{catNum, catName, items:[…]}]
  var cart        = [];   // [{nombre, precio, cantidad, extras[], sin[], nota}]
  var currentItem = null; // ítem abierto en el modal
  var modalQty    = 1;
  var cartOpen    = false;

  /* ── Helpers DOM ─────────────────────────────────────────────── */
  function $$(id) { return document.getElementById(id); }

  function escHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  /* Convierte valor de columna imagen/foto a src usable:
     - Link de Google Drive  → URL directa con thumbnail
     - URL completa          → se usa tal cual
     - Nombre de archivo     → Imagenes/{nombre} */
  function toImgSrc(value) {
    if (!value) return '';
    var v = String(value).trim();
    var dm = v.match(/\/file\/d\/([a-zA-Z0-9_-]+)/);
    if (dm) return 'https://drive.google.com/thumbnail?id=' + dm[1] + '&sz=w500';
    if (v.indexOf('http') === 0) return v;
    return 'Imagenes/' + v;
  }

  /* ── Carga menú desde Sheets (gviz JSON) ─────────────────────── */
  function cellVal(cell) {
    return (cell && cell.v != null) ? String(cell.v) : '';
  }

  function splitList(str) {
    return str ? str.split(',').map(function (s) { return s.trim(); }).filter(Boolean) : [];
  }

  function parseGviz(text) {
    var json = text.replace(/^[^{]*/, '').replace(/\);\s*$/, '');
    try { return JSON.parse(json); } catch (e) { return null; }
  }

  function buildGroups(rows) {
    var groups = [], idx = {};
    rows.forEach(function (row) {
      var c = row.c || [];
      if (cellVal(c[7]).toUpperCase() === 'FALSE') return;
      var nombre = cellVal(c[3]);
      if (!nombre) return;
      var catNum  = cellVal(c[0]);
      var catName = cellVal(c[1]);
      var key = catNum || catName;
      if (!idx[key]) {
        var g = { catNum: catNum, catName: catName, items: [] };
        groups.push(g);
        idx[key] = g;
      }
      idx[key].items.push({
        nombre:      nombre,
        precio:      Number(cellVal(c[4])) || 0,
        descripcion: cellVal(c[5]),
        imagen:      cellVal(c[6]),
        extras:      splitList(cellVal(c[8])),
        sin:         splitList(cellVal(c[9])),
      });
    });
    return groups;
  }

  /* ── Renderiza el menú ───────────────────────────────────────── */
  function renderMenu(groups) {
    var wrap = $$('ped-menu');
    if (!wrap) return;
    menuGroups = groups;

    if (!groups.length) {
      wrap.innerHTML = '<div class="ped-loading">Menú no disponible por el momento.</div>';
      return;
    }

    var html = groups.map(function (g) {
      var itemsHtml = g.items.map(function (item) {
        var src    = toImgSrc(item.imagen);
        var imgTag = src
          ? '<img class="ped-card-img" src="' + escHtml(src) + '" alt="' + escHtml(item.nombre) + '" loading="lazy">'
          : '<div class="ped-card-img"></div>';
        return (
          '<div class="ped-card" data-nombre="' + escHtml(item.nombre) + '">' +
            imgTag +
            '<div class="ped-card-body">' +
              '<div class="ped-card-name">'  + escHtml(item.nombre)      + '</div>' +
              '<div class="ped-card-desc">'  + escHtml(item.descripcion) + '</div>' +
              '<div class="ped-card-footer">' +
                '<span class="ped-card-price">$' + escHtml(item.precio) + '</span>' +
                '<span class="ped-add-btn" aria-hidden="true">+</span>' +
              '</div>' +
            '</div>' +
          '</div>'
        );
      }).join('');

      return (
        '<section class="ped-section">' +
          '<div class="ped-cat-header">' +
            '<span class="ped-cat-num">'  + escHtml(g.catNum)  + '</span>' +
            '<span class="ped-cat-name">' + escHtml(g.catName) + '</span>' +
          '</div>' +
          '<div class="ped-items">' + itemsHtml + '</div>' +
        '</section>'
      );
    }).join('');

    wrap.innerHTML = html;
    attachCardListeners();
  }

  /* ── Clicks en tarjetas ──────────────────────────────────────── */
  function findItem(nombre) {
    for (var i = 0; i < menuGroups.length; i++) {
      for (var j = 0; j < menuGroups[i].items.length; j++) {
        if (menuGroups[i].items[j].nombre === nombre) return menuGroups[i].items[j];
      }
    }
    return null;
  }

  function attachCardListeners() {
    document.querySelectorAll('.ped-card').forEach(function (card) {
      card.addEventListener('click', function () {
        var item = findItem(card.getAttribute('data-nombre'));
        if (item) openModal(item);
      });
    });
  }

  /* ── MODAL ───────────────────────────────────────────────────── */
  function openModal(item) {
    currentItem = item;
    modalQty    = 1;

    /* Imagen */
    var src      = toImgSrc(item.imagen);
    var modalImg = $$('modal-img');
    var holder   = $$('modal-img-placeholder');
    if (src) {
      modalImg.src          = src;
      modalImg.style.display = 'block';
      holder.style.display   = 'none';
    } else {
      modalImg.style.display = 'none';
      holder.style.display   = 'flex';
    }

    $$('modal-name').textContent  = item.nombre;
    $$('modal-desc').textContent  = item.descripcion;
    $$('modal-price').textContent = '$' + item.precio;
    $$('modal-qty-num').textContent = '1';

    renderOpts('modal-extras-section', 'modal-extras-list', item.extras);
    renderOpts('modal-sin-section',    'modal-sin-list',    item.sin);
    $$('modal-note').value = '';

    $$('ped-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';

    /* Scroll modal al inicio */
    var modal = $$('ped-overlay').querySelector('.ped-modal');
    if (modal) modal.scrollTop = 0;
  }

  function renderOpts(sectionId, listId, options) {
    var section = $$(sectionId);
    var list    = $$(listId);
    if (!options || !options.length) {
      section.style.display = 'none';
      list.innerHTML = '';
      return;
    }
    section.style.display = 'block';
    list.innerHTML = options.map(function (opt, i) {
      var id = listId + '-' + i;
      return (
        '<label class="ped-opt-item" for="' + id + '">' +
          '<input type="checkbox" id="' + id + '" value="' + escHtml(opt) + '">' +
          '<span class="ped-opt-label">' + escHtml(opt) + '</span>' +
        '</label>'
      );
    }).join('');
  }

  function closeModal() {
    $$('ped-overlay').classList.remove('open');
    document.body.style.overflow = '';
    currentItem = null;
  }

  function checkedValues(listId) {
    var vals = [];
    document.querySelectorAll('#' + listId + ' input:checked').forEach(function (el) {
      vals.push(el.value);
    });
    return vals;
  }

  function confirmModal() {
    if (!currentItem) return;
    var extras = checkedValues('modal-extras-list');
    var sin    = checkedValues('modal-sin-list');
    var nota   = $$('modal-note').value.trim();

    /* Fusionar con ítem idéntico ya en carrito */
    var key      = currentItem.nombre + '|' + extras.join(',') + '|' + sin.join(',') + '|' + nota;
    var existing = null;
    for (var i = 0; i < cart.length; i++) {
      var ci = cart[i];
      var ck = ci.nombre + '|' + ci.extras.join(',') + '|' + ci.sin.join(',') + '|' + (ci.nota || '');
      if (ck === key) { existing = ci; break; }
    }

    if (existing) {
      existing.cantidad += modalQty;
    } else {
      cart.push({
        nombre:   currentItem.nombre,
        precio:   currentItem.precio,
        cantidad: modalQty,
        extras:   extras,
        sin:      sin,
        nota:     nota,
      });
    }

    closeModal();
    updateCart();
    /* Feedback visual breve en el cart bar */
    var bar = $$('cart-bar');
    bar.style.background = 'var(--terracotta)';
    setTimeout(function () { bar.style.background = ''; }, 600);
  }

  /* ── CARRITO ─────────────────────────────────────────────────── */
  function cartTotal() {
    return cart.reduce(function (s, i) { return s + i.precio * i.cantidad; }, 0);
  }

  function cartCount() {
    return cart.reduce(function (s, i) { return s + i.cantidad; }, 0);
  }

  function updateCart() {
    var count = cartCount();
    var total = cartTotal();
    var bar   = $$('cart-bar');
    var panel = $$('cart-panel');

    if (count > 0) {
      bar.classList.add('visible');
    } else {
      bar.classList.remove('visible');
      panel.classList.remove('open');
      cartOpen = false;
    }

    $$('cart-count').textContent     = count;
    $$('cart-total').textContent     = '$' + total;
    $$('cart-items-text').textContent = count + (count === 1 ? ' producto' : ' productos');

    renderCartPanel(count, total);
  }

  function buildModsText(item) {
    var parts = [];
    if (item.extras.length) parts.push('+ ' + item.extras.join(', '));
    if (item.sin.length)    parts.push('sin ' + item.sin.join(', '));
    if (item.nota)          parts.push(item.nota);
    return parts.join(' · ');
  }

  function renderCartPanel(count, total) {
    var list = $$('cart-list');
    if (!list) return;

    if (!cart.length) {
      list.innerHTML = '<div style="padding:28px 24px;font-family:var(--sans);font-size:13px;color:var(--gold-soft);text-align:center">Tu pedido está vacío</div>';
    } else {
      list.innerHTML = cart.map(function (item, idx) {
        var mods = buildModsText(item);
        return (
          '<div class="ped-panel-item">' +
            '<div class="ped-panel-qty">' + item.cantidad + 'x</div>' +
            '<div class="ped-panel-info">' +
              '<div class="ped-panel-name">' + escHtml(item.nombre) + '</div>' +
              (mods ? '<div class="ped-panel-mods">' + escHtml(mods) + '</div>' : '') +
            '</div>' +
            '<div class="ped-panel-price">$' + (item.precio * item.cantidad) + '</div>' +
            '<button class="ped-panel-remove" data-idx="' + idx + '" aria-label="Eliminar">✕</button>' +
          '</div>'
        );
      }).join('');

      /* Botones de eliminar */
      list.querySelectorAll('.ped-panel-remove').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.stopPropagation();
          cart.splice(parseInt(btn.getAttribute('data-idx'), 10), 1);
          updateCart();
        });
      });
    }

    $$('panel-total').textContent = '$' + total;
  }

  /* ── WhatsApp ────────────────────────────────────────────────── */
  function buildWAUrl() {
    var payload = JSON.stringify({
      items: cart.map(function (i) {
        return {
          nombre:   i.nombre,
          precio:   i.precio,
          cantidad: i.cantidad,
          extras:   i.extras,
          sin:      i.sin,
          nota:     i.nota || '',
        };
      }),
    });
    return 'https://wa.me/' + PHONE + '?text=' + encodeURIComponent('PEDIDO_WEB:' + payload);
  }

  /* ── Inicialización ──────────────────────────────────────────── */
  function init() {
    /* Cargar menú */
    fetch(GVIZ_URL)
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.text();
      })
      .then(function (text) {
        var data = parseGviz(text);
        if (!data || !data.table) throw new Error('parse');
        renderMenu(buildGroups(data.table.rows || []));
      })
      .catch(function (err) {
        console.error('[pedido] Error cargando menú:', err);
        var wrap = $$('ped-menu');
        if (wrap) wrap.innerHTML = '<div class="ped-loading">Error al cargar el menú. Por favor recarga la página.</div>';
      });

    /* Modal: overlay click cierra */
    $$('ped-overlay').addEventListener('click', function (e) {
      if (e.target === this) closeModal();
    });

    /* Modal: botones cantidad */
    $$('modal-qty-minus').addEventListener('click', function () {
      if (modalQty > 1) $$('modal-qty-num').textContent = --modalQty;
    });
    $$('modal-qty-plus').addEventListener('click', function () {
      $$('modal-qty-num').textContent = ++modalQty;
    });

    /* Modal: confirmar / cancelar */
    $$('modal-confirm').addEventListener('click', confirmModal);
    $$('modal-cancel').addEventListener('click', closeModal);

    /* Teclado: Escape cierra modal */
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeModal();
    });

    /* Cart bar: toggle panel */
    $$('cart-bar').addEventListener('click', function () {
      cartOpen = !cartOpen;
      $$('cart-panel').classList.toggle('open', cartOpen);
    });

    /* Botón WhatsApp */
    $$('whatsapp-btn').addEventListener('click', function (e) {
      e.stopPropagation();
      if (!cart.length) return;
      window.open(buildWAUrl(), '_blank');
    });
  }

  document.addEventListener('DOMContentLoaded', init);
})();
