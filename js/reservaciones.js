(function () {
  var personas = 2;
  var MAX_P = 8;
  var MIN_P = 1;

  function init() {
    var form     = document.getElementById('res-form');
    var fechaIn  = document.getElementById('res-fecha');
    var submitBtn = document.getElementById('res-submit');
    var newBtn   = document.getElementById('res-new-btn');

    if (!form) return;

    // Fecha mínima: hoy
    var today = new Date();
    var yyyy  = today.getFullYear();
    var mm    = String(today.getMonth() + 1).padStart(2, '0');
    var dd    = String(today.getDate()).padStart(2, '0');
    fechaIn.min = yyyy + '-' + mm + '-' + dd;

    // Deshabilitar lunes en el picker (HTML no soporta nativamente, validamos al enviar)
    fechaIn.addEventListener('change', function () {
      var d = new Date(this.value + 'T12:00:00');
      if (d.getDay() === 1) {
        mostrarError('Estamos cerrados los lunes. Por favor elige otro día.');
        this.value = '';
      } else {
        ocultarError();
      }
    });

    // Contador de personas
    document.getElementById('res-personas-menos').addEventListener('click', function () {
      if (personas > MIN_P) {
        personas--;
        actualizarPersonas();
      }
    });

    document.getElementById('res-personas-mas').addEventListener('click', function () {
      if (personas < MAX_P) {
        personas++;
        actualizarPersonas();
      }
    });

    // Submit
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      enviarReservacion();
    });

    // Nueva reservación
    if (newBtn) {
      newBtn.addEventListener('click', function () {
        document.getElementById('res-success-state').style.display = 'none';
        document.getElementById('res-form-state').style.display    = '';
        form.reset();
        personas = 2;
        actualizarPersonas();
        ocultarError();
      });
    }
  }

  function actualizarPersonas() {
    document.getElementById('res-personas-num').textContent = personas;
    document.getElementById('res-personas').value = personas;
  }

  function mostrarError(msg) {
    var el = document.getElementById('res-error');
    el.textContent = msg;
    el.style.display = '';
  }

  function ocultarError() {
    document.getElementById('res-error').style.display = 'none';
  }

  function enviarReservacion() {
    var nombre    = document.getElementById('res-nombre').value.trim();
    var telefono  = document.getElementById('res-telefono').value.trim();
    var fecha     = document.getElementById('res-fecha').value;
    var hora      = document.getElementById('res-hora').value;
    var peticiones = document.getElementById('res-peticiones').value.trim();

    ocultarError();

    if (!nombre || !telefono || !fecha || !hora) {
      mostrarError('Por favor completa todos los campos requeridos.');
      return;
    }

    var d = new Date(fecha + 'T12:00:00');
    if (d.getDay() === 1) {
      mostrarError('Estamos cerrados los lunes. Por favor elige otro día.');
      return;
    }

    var btn  = document.getElementById('res-submit');
    var span = document.getElementById('res-submit-text');
    btn.disabled = true;
    span.textContent = 'Enviando…';

    fetch('php/reservar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        nombre:     nombre,
        telefono:   telefono,
        fecha:      fecha,
        hora:       hora,
        personas:   personas,
        peticiones: peticiones,
        fuente:     'web',
      }),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.ok) {
          mostrarExito(data);
        } else {
          mostrarError(data.error || 'Ocurrió un error. Por favor intenta de nuevo.');
        }
      })
      .catch(function () {
        mostrarError('Error de conexión. Verifica tu internet e intenta de nuevo.');
      })
      .finally(function () {
        btn.disabled = false;
        span.textContent = 'Confirmar reservación';
      });
  }

  function formatFecha(isoDate) {
    var parts = isoDate.split('-');
    var meses = ['enero','febrero','marzo','abril','mayo','junio',
                 'julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return parts[2] + ' de ' + meses[parseInt(parts[1]) - 1] + ' de ' + parts[0];
  }

  function formatHora(hora) {
    var h  = parseInt(hora.split(':')[0], 10);
    var m  = hora.split(':')[1];
    var ampm = h >= 12 ? 'pm' : 'am';
    var h12  = h > 12 ? h - 12 : (h === 0 ? 12 : h);
    return h12 + ':' + m + ' ' + ampm;
  }

  function makeDetailRow(label, value) {
    var p = document.createElement('p');
    var strong = document.createElement('strong');
    strong.textContent = label + ': ';
    p.appendChild(strong);
    p.appendChild(document.createTextNode(value));
    return p;
  }

  function mostrarExito(data) {
    document.getElementById('res-form-state').style.display    = 'none';
    document.getElementById('res-success-state').style.display = '';

    document.getElementById('res-success-msg').textContent =
      'Tu reservación ha sido registrada en nuestro calendario. Te esperamos pronto.';

    var details = document.getElementById('res-success-details');
    details.textContent = '';
    details.appendChild(makeDetailRow('Código de reserva', String(data.reservation_id || '')));
    details.appendChild(makeDetailRow('Fecha',    formatFecha(String(data.fecha    || ''))));
    details.appendChild(makeDetailRow('Hora',     formatHora(String(data.hora      || ''))));
    details.appendChild(makeDetailRow('Personas', String(data.personas || '')));
  }

  document.addEventListener('sectionsLoaded', init);
})();
