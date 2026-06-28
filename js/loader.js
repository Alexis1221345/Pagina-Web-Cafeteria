/* =============================================
   SECTION LOADER
   Fetches each secciones/*.html and injects it
   into its <div data-include="..."> slot.
   Fires 'sectionsLoaded' when all are done.
   Requires a local server (VS Code Live Server,
   python3 -m http.server, etc.)
============================================= */
(function () {
  var slots = Array.from(document.querySelectorAll('[data-include]'));
  if (slots.length === 0) {
    document.dispatchEvent(new CustomEvent('sectionsLoaded'));
    return;
  }

  var done = 0;

  slots.forEach(function (slot) {
    var url = slot.getAttribute('data-include');

    fetch(url)
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status + ' — ' + url);
        return res.text();
      })
      .then(function (html) {
        var tpl = document.createElement('template');
        tpl.innerHTML = html;
        slot.replaceWith(tpl.content);
      })
      .catch(function (err) {
        console.error('[loader] No se pudo cargar:', url, err);
        slot.remove();
      })
      .finally(function () {
        done++;
        if (done === slots.length) {
          document.dispatchEvent(new CustomEvent('sectionsLoaded'));
        }
      });
  });
})();
