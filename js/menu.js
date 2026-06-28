/* =============================================
   DYNAMIC MENU LOADER
   Reads menu data from Google Sheets (public CSV via gviz)
   and renders it with the same CSS structure as the static version.
   Fires after sectionsLoaded so scroll.js already has revealObs ready.
============================================= */
(function () {
  var SHEETS_ID = '16t5lMZ3-KkQgXrfP-OyTfmWKq0hTwI3Ys67eVp0vn5w';
  var SHEET_NAME = 'Menu';
  var GVIZ_URL =
    'https://docs.google.com/spreadsheets/d/' + SHEETS_ID +
    '/gviz/tq?tqx=out:json&sheet=' + encodeURIComponent(SHEET_NAME);

  /* Column indices (0-based, matching the sheet header order):
     A=0 categoria_num | B=1 categoria | C=2 categoria_foto
     D=3 nombre | E=4 precio | F=5 descripcion
     G=6 imagen | H=7 disponible | I=8 extras | J=9 sin_opciones */

  function cellVal(cell) {
    if (!cell) return '';
    return cell.v != null ? String(cell.v) : '';
  }

  function parseGviz(text) {
    // Strip JSONP wrapper: /*O_o*/\ngoogle.visualization.Query.setResponse({...});
    var json = text.replace(/^[^{]*/, '').replace(/\);\s*$/, '');
    try {
      return JSON.parse(json);
    } catch (e) {
      console.error('[menu] Error parseando respuesta de Sheets:', e);
      return null;
    }
  }

  function groupByCategory(rows) {
    var groups = [];
    var index  = {};

    rows.forEach(function (row) {
      var c = row.c || [];
      var disponible = cellVal(c[7]);
      if (disponible.toUpperCase() === 'FALSE') return;

      var catNum  = cellVal(c[0]);
      var catName = cellVal(c[1]);
      var catFoto = cellVal(c[2]);
      var nombre  = cellVal(c[3]);
      if (!nombre) return;

      var key = catNum || catName;
      if (!index[key]) {
        var group = { catNum: catNum, catName: catName, catFoto: catFoto, items: [] };
        groups.push(group);
        index[key] = group;
      }
      index[key].items.push({
        nombre:     nombre,
        precio:     cellVal(c[4]),
        descripcion: cellVal(c[5]),
        imagen:     cellVal(c[6]),
        extras:     cellVal(c[8]),
        sin:        cellVal(c[9]),
      });
    });

    return groups;
  }

  /**
   * Convierte un valor de la columna imagen/categoria_foto a un src usable.
   * Acepta:
   *   - URL completa: https://... → se usa directamente
   *   - Link de Google Drive: https://drive.google.com/file/d/ID/view → convierte a URL directa
   *   - Nombre de archivo local: Expresso.jpeg → Imagenes/Expresso.jpeg
   */
  function toImgSrc(value) {
    if (!value) return '';
    var v = value.trim();

    // Google Drive share link  →  URL directa de imagen
    var driveMatch = v.match(/\/file\/d\/([a-zA-Z0-9_-]+)/);
    if (driveMatch) {
      return 'https://drive.google.com/thumbnail?id=' + driveMatch[1] + '&sz=w400';
    }

    // Cualquier URL completa → usar tal cual
    if (v.startsWith('http://') || v.startsWith('https://')) {
      return v;
    }

    // Nombre de archivo local → carpeta Imagenes/
    return 'Imagenes/' + v;
  }

  /* Build one .menu-cat block (mirrors the static HTML structure exactly) */
  function buildCategoryHTML(group) {
    var delayClasses = ['reveal-d1', 'reveal-d2', 'reveal-d3', 'reveal-d4'];

    /* Left panel — category header + photo */
    var fotoSrc = toImgSrc(group.catFoto);
    var photoHTML = fotoSrc
      ? '<div class="menu-cat-photo"><img src="' + escHtml(fotoSrc) +
        '" alt="' + escHtml(group.catName) + '" loading="lazy"></div>'
      : '';

    var catLeft =
      '<div class="menu-cat-left reveal">' +
        '<div class="menu-cat-num">'   + escHtml(group.catNum)  + '</div>' +
        '<div class="menu-cat-title">' + escHtml(group.catName) + '</div>' +
        photoHTML +
      '</div>';

    /* Right panel — individual items */
    var itemsHTML = group.items.map(function (item, i) {
      var delay = delayClasses[i % delayClasses.length];
      var imgSrc = toImgSrc(item.imagen);
      var thumbHTML = imgSrc
        ? '<div class="menu-thumb"><img src="' + escHtml(imgSrc) +
          '" alt="' + escHtml(item.nombre) + '" loading="lazy"></div>'
        : '';
      return (
        '<div class="menu-item reveal ' + delay + '">' +
          thumbHTML +
          '<div class="menu-item-body">' +
            '<div class="menu-item-row">' +
              '<span class="menu-item-name">' + escHtml(item.nombre)  + '</span>' +
              '<span class="menu-item-price">$' + escHtml(item.precio) + '</span>' +
            '</div>' +
            '<p class="menu-item-desc">' + escHtml(item.descripcion) + '</p>' +
          '</div>' +
        '</div>'
      );
    }).join('');

    return (
      '<div class="menu-cat">' +
        catLeft +
        '<div class="menu-items">' + itemsHTML + '</div>' +
      '</div>'
    );
  }

  /* All Sheets values pass through escHtml before entering innerHTML,
     so concatenated HTML strings below are XSS-safe. */
  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function observeNewRevealElements(container) {
    /* scroll.js stores the observer in window.revealObs after sectionsLoaded */
    if (!window.revealObs) return;
    container.querySelectorAll('.reveal').forEach(function (el) {
      window.revealObs.observe(el);
    });
  }

  function renderMenu(groups) {
    var container = document.getElementById('menu-categories');
    if (!container) return;

    if (groups.length === 0) {
      container.innerHTML = '<p class="menu-note" style="text-align:center">El menú no está disponible en este momento.</p>';
      return;
    }

    container.innerHTML = groups.map(buildCategoryHTML).join('');
    observeNewRevealElements(container);
  }

  function loadMenu() {
    fetch(GVIZ_URL)
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.text();
      })
      .then(function (text) {
        var data = parseGviz(text);
        if (!data || !data.table || !data.table.rows) throw new Error('Formato inesperado');
        var groups = groupByCategory(data.table.rows);
        renderMenu(groups);
      })
      .catch(function (err) {
        console.error('[menu] No se pudo cargar el menú dinámico:', err);
        /* On error, keep static fallback — show a friendly message */
        var container = document.getElementById('menu-categories');
        if (container) {
          container.innerHTML =
            '<p class="menu-note" style="text-align:center">' +
            'Menú temporalmente no disponible. Visítanos para conocer nuestra carta de hoy.' +
            '</p>';
        }
      });
  }

  document.addEventListener('sectionsLoaded', function () {
    loadMenu();
  });
})();
