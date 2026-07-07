/* =============================================
   GALERÍA SHOWCASE — Timed Cards (GSAP)
   Adaptado de "Timed Cards Opening" (dilums · CodePen)
   Estructura de dos recuadros:
     - Panel izquierdo: texto (fondo propio, siempre legible)
     - Panel derecho: imágenes (más grande)
   El temporizador del cambio dibuja el contorno dorado
   de ambos recuadros (stroke SVG con pathLength=100).
   Extras: arranca al ser visible, se pausa fuera de vista,
   flechas funcionales, CTA abre el lightbox, relayout en resize.
============================================= */
(function () {
  var data = [
    {
      place: 'Interior',
      title: 'La Barra',
      title2: 'Principal',
      desc: 'El corazón de Muna: madera clara, cerámica hecha a mano y el aroma del café recién molido recibiéndote desde la puerta.',
      caption: 'Interior · La barra principal',
      image: 'Imagenes/1.jpg'
    },
    {
      place: 'Ambiente',
      title: 'Junto a',
      title2: 'La Ventana',
      desc: 'Una mesa junto a la ventana, luz de mañana y tiempo de sobra. Los rincones de Muna invitan a quedarse.',
      caption: 'Ambiente · Mesa junto a la ventana',
      image: 'Imagenes/2.jpg'
    },
    {
      place: 'Oficio',
      title: 'El Barista',
      title2: 'En Acción',
      desc: 'Cada taza pasa por manos que conocen su oficio: molienda precisa, extracción paciente y atención al detalle.',
      caption: 'Oficio · El barista en acción',
      image: 'Imagenes/3.jpg'
    },
    {
      place: 'Luz',
      title: 'Mediodía',
      title2: 'En Muna',
      desc: 'Al mediodía el salón se llena de luz natural. Un espacio diseñado para la calma en el corazón de la ciudad.',
      caption: 'Luz · Mediodía en Muna',
      image: 'Imagenes/6.jpg'
    },
    {
      place: 'Ritual',
      title: 'Preparación',
      title2: 'Del Café',
      desc: 'Del grano de origen a la taza: métodos de filtrado que celebran el ritual del café de especialidad.',
      caption: 'Ritual · Preparación del café',
      image: 'Imagenes/7.jpg'
    },
    {
      place: 'Calma',
      title: 'El Espacio',
      title2: 'Respira',
      desc: 'Plantas, texturas cálidas y silencio suficiente para leer, trabajar o simplemente estar.',
      caption: 'Calma · El espacio respira',
      image: 'Imagenes/9.jpg'
    }
  ];

  var ease = 'sine.inOut';
  var container, media, cover, progressFg, pagination, outlines;
  var mw, mh, cardWidth, cardHeight, gap, offsetTop, offsetLeft;
  var numberSize = 50;
  var order = [0, 1, 2, 3, 4, 5];
  var detailsEven = true;
  var busy = false;
  var started = false;
  var visible = false;
  var visWaiters = [];
  var loopGen = 0;

  function getCard(i) { return '#gs-card' + i; }
  function getCardContent(i) { return '#gs-card-content-' + i; }
  function getSliderItem(i) { return '#gs-slide-item-' + i; }

  function sleep(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }

  function animate(target, duration, properties) {
    return new Promise(function (resolve) {
      gsap.to(target, Object.assign({}, properties, {
        duration: duration,
        onComplete: resolve,
        onInterrupt: resolve
      }));
    });
  }

  function whenVisible() {
    if (visible) return Promise.resolve();
    return new Promise(function (r) { visWaiters.push(r); });
  }

  /* ── Medidas del panel de imágenes ── */
  function computeDims() {
    mw = media.clientWidth;
    mh = media.clientHeight;
    var small = mw < 560;
    cardWidth  = small ? 100 : 150;
    cardHeight = small ? 140 : 210;
    gap        = small ? 12 : 20;
    offsetTop  = mh - cardHeight - (small ? 20 : 32);
    offsetLeft = mw - (cardWidth + gap) * (small ? 2.3 : 2.7);
  }

  /* ── Contornos: ajustar rects al tamaño de cada recuadro ── */
  function sizeOutlines() {
    outlines.forEach(function (svg) {
      var w = svg.clientWidth;
      var h = svg.clientHeight;
      var rect = svg.querySelector('.gs-outline-rect');
      rect.setAttribute('x', 1);
      rect.setAttribute('y', 1);
      rect.setAttribute('width', Math.max(0, w - 2));
      rect.setAttribute('height', Math.max(0, h - 2));
      rect.setAttribute('rx', 13);
    });
  }

  function resetOutlines() {
    gsap.killTweensOf('.gs-outline-rect');
    gsap.set('.gs-outline-rect', { strokeDashoffset: 100, opacity: 1 });
  }

  /* ── Construcción del DOM ── */
  function buildMarkup() {
    var stage = document.getElementById('gs-stage');
    var cards = data.map(function (item, i) {
      return '<div class="gs-card" id="gs-card' + i + '" style="background-image:url(\'' + item.image + '\')"></div>';
    }).join('');
    var contents = data.map(function (item, i) {
      return '<div class="gs-card-content" id="gs-card-content-' + i + '">' +
        '<div class="gs-content-start"></div>' +
        '<div class="gs-content-place">' + item.place + '</div>' +
        '<div class="gs-content-title-1">' + item.title + '</div>' +
        '<div class="gs-content-title-2">' + item.title2 + '</div>' +
      '</div>';
    }).join('');
    stage.innerHTML = cards + contents;

    document.getElementById('gs-slide-numbers').innerHTML = data.map(function (_, i) {
      return '<div class="gs-item" id="gs-slide-item-' + i + '">' + (i + 1) + '</div>';
    }).join('');
  }

  function fillDetails(sel, item) {
    document.querySelector(sel + ' .gs-text').textContent = item.place;
    document.querySelector(sel + ' .gs-title-1').textContent = item.title;
    document.querySelector(sel + ' .gs-title-2').textContent = item.title2;
    document.querySelector(sel + ' .gs-desc').textContent = item.desc;
  }

  /* ── Estado inicial + entrada ── */
  function init() {
    var active = order[0];
    var rest = order.slice(1);
    var detailsActive = detailsEven ? '#gs-details-even' : '#gs-details-odd';
    var detailsInactive = detailsEven ? '#gs-details-odd' : '#gs-details-even';
    var set = gsap.set;

    computeDims();
    sizeOutlines();
    resetOutlines();
    fillDetails(detailsActive, data[active]);

    set(pagination, { y: 60, opacity: 0 });

    set(getCard(active), { x: 0, y: 0, width: mw, height: mh, borderRadius: 0 });
    set(getCardContent(active), { x: 0, y: 0, opacity: 0 });
    set(detailsActive, { opacity: 0, zIndex: 22, x: -60 });
    set(detailsInactive, { opacity: 0, zIndex: 12 });
    set(detailsInactive + ' .gs-text', { y: 100 });
    set(detailsInactive + ' .gs-title-1', { y: 100 });
    set(detailsInactive + ' .gs-title-2', { y: 100 });
    set(detailsInactive + ' .gs-desc', { y: 50 });
    set(detailsInactive + ' .gs-cta', { y: 60 });

    set(progressFg, { width: (100 / order.length) * (active + 1) + '%' });

    rest.forEach(function (i, index) {
      set(getCard(i), {
        x: offsetLeft + 300 + index * (cardWidth + gap),
        y: offsetTop,
        width: cardWidth,
        height: cardHeight,
        zIndex: 30,
        borderRadius: 14
      });
      set(getCardContent(i), {
        x: offsetLeft + 300 + index * (cardWidth + gap),
        zIndex: 40,
        y: offsetTop + cardHeight - 92
      });
      set(getSliderItem(i), { x: (index + 1) * numberSize });
    });

    var startDelay = 0.6;

    gsap.to(cover, {
      x: mw + 200,
      delay: 0.5,
      ease: ease,
      onComplete: function () {
        setTimeout(function () { loop(); }, 500);
      }
    });

    rest.forEach(function (i, index) {
      gsap.to(getCard(i), {
        x: offsetLeft + index * (cardWidth + gap),
        zIndex: 30,
        ease: ease,
        delay: startDelay + 0.05 * index
      });
      gsap.to(getCardContent(i), {
        x: offsetLeft + index * (cardWidth + gap),
        zIndex: 40,
        ease: ease,
        delay: startDelay + 0.05 * index
      });
    });
    gsap.to(pagination, { y: 0, opacity: 1, ease: ease, delay: startDelay });
    gsap.to(detailsActive, { opacity: 1, x: 0, ease: ease, delay: startDelay });

    started = true;
  }

  /* ── Un paso del carrusel ── */
  function step() {
    return new Promise(function (resolve) {
      order.push(order.shift());
      detailsEven = !detailsEven;

      var detailsActive = detailsEven ? '#gs-details-even' : '#gs-details-odd';
      var detailsInactive = detailsEven ? '#gs-details-odd' : '#gs-details-even';

      fillDetails(detailsActive, data[order[0]]);

      gsap.set(detailsActive, { zIndex: 22 });
      gsap.to(detailsActive, { opacity: 1, x: 0, delay: 0.4, ease: ease });
      gsap.to(detailsActive + ' .gs-text', { y: 0, delay: 0.1, duration: 0.7, ease: ease });
      gsap.to(detailsActive + ' .gs-title-1', { y: 0, delay: 0.15, duration: 0.7, ease: ease });
      gsap.to(detailsActive + ' .gs-title-2', { y: 0, delay: 0.15, duration: 0.7, ease: ease });
      gsap.to(detailsActive + ' .gs-desc', { y: 0, delay: 0.3, duration: 0.4, ease: ease });
      gsap.to(detailsActive + ' .gs-cta', { y: 0, delay: 0.35, duration: 0.4, ease: ease, onComplete: resolve });
      gsap.set(detailsInactive, { zIndex: 12 });

      var active = order[0];
      var rest = order.slice(1);
      var prv = rest[rest.length - 1];

      gsap.set(getCard(prv), { zIndex: 10 });
      gsap.set(getCard(active), { zIndex: 20 });
      gsap.to(getCard(prv), { scale: 1.5, ease: ease });

      gsap.to(getCardContent(active), {
        y: offsetTop + cardHeight - 10,
        opacity: 0,
        duration: 0.3,
        ease: ease
      });
      gsap.to(getSliderItem(active), { x: 0, ease: ease });
      gsap.to(getSliderItem(prv), { x: -numberSize, ease: ease });
      gsap.to(progressFg, {
        width: (100 / order.length) * (active + 1) + '%',
        ease: ease
      });

      gsap.to(getCard(active), {
        x: 0,
        y: 0,
        ease: ease,
        width: mw,
        height: mh,
        borderRadius: 0,
        onComplete: function () {
          var xNew = offsetLeft + (rest.length - 1) * (cardWidth + gap);
          gsap.set(getCard(prv), {
            x: xNew,
            y: offsetTop,
            width: cardWidth,
            height: cardHeight,
            zIndex: 30,
            borderRadius: 14,
            scale: 1
          });
          gsap.set(getCardContent(prv), {
            x: xNew,
            y: offsetTop + cardHeight - 92,
            opacity: 1,
            zIndex: 40
          });
          gsap.set(getSliderItem(prv), { x: rest.length * numberSize });

          gsap.set(detailsInactive, { opacity: 0 });
          gsap.set(detailsInactive + ' .gs-text', { y: 100 });
          gsap.set(detailsInactive + ' .gs-title-1', { y: 100 });
          gsap.set(detailsInactive + ' .gs-title-2', { y: 100 });
          gsap.set(detailsInactive + ' .gs-desc', { y: 50 });
          gsap.set(detailsInactive + ' .gs-cta', { y: 60 });
        }
      });

      rest.forEach(function (i, index) {
        if (i !== prv) {
          var xNew = offsetLeft + index * (cardWidth + gap);
          gsap.set(getCard(i), { zIndex: 30 });
          gsap.to(getCard(i), {
            x: xNew,
            y: offsetTop,
            width: cardWidth,
            height: cardHeight,
            ease: ease,
            delay: 0.1 * (index + 1)
          });
          gsap.to(getCardContent(i), {
            x: xNew,
            y: offsetTop + cardHeight - 92,
            opacity: 1,
            zIndex: 40,
            ease: ease,
            delay: 0.1 * (index + 1)
          });
          gsap.to(getSliderItem(i), { x: (index + 1) * numberSize, ease: ease });
        }
      });
    });
  }

  function doStep() {
    if (busy) return Promise.resolve();
    busy = true;
    return step().then(function () { busy = false; });
  }

  /* ── Loop automático ──
     El contorno de ambos recuadros se dibuja durante la
     espera; al completarse el trazo, cambia la imagen. */
  function loop() {
    (async function run() {
      while (true) {
        var gen = loopGen;
        await whenVisible();
        resetOutlines();
        await animate('.gs-outline-rect', 3, { strokeDashoffset: 0, ease: 'none' });
        if (gen !== loopGen) { while (busy) { await sleep(150); } continue; }
        await animate('.gs-outline-rect', 0.5, { opacity: 0, ease: ease });
        if (gen !== loopGen) { while (busy) { await sleep(150); } continue; }
        await doStep();
      }
    })();
  }

  /* ── Relayout en resize (solo en reposo) ── */
  function relayout() {
    if (!started || busy) return;
    computeDims();
    sizeOutlines();
    var set = gsap.set;
    var active = order[0];
    var rest = order.slice(1);
    set(getCard(active), { x: 0, y: 0, width: mw, height: mh, borderRadius: 0 });
    rest.forEach(function (i, index) {
      set(getCard(i), {
        x: offsetLeft + index * (cardWidth + gap),
        y: offsetTop,
        width: cardWidth,
        height: cardHeight,
        zIndex: 30,
        borderRadius: 14
      });
      set(getCardContent(i), {
        x: offsetLeft + index * (cardWidth + gap),
        y: offsetTop + cardHeight - 92,
        zIndex: 40,
        opacity: 1
      });
      set(getSliderItem(i), { x: (index + 1) * numberSize });
    });
    set(getSliderItem(active), { x: 0 });
    set(progressFg, { width: (100 / order.length) * (active + 1) + '%' });
    set(cover, { x: mw + 200 });
  }

  /* ── Controles manuales ── */
  window.gsNext = function () {
    if (!started || busy) return;
    loopGen++;
    resetOutlines();
    doStep();
  };

  window.gsPrev = function () {
    if (!started || busy) return;
    loopGen++;
    resetOutlines();
    order.unshift(order.pop());
    order.unshift(order.pop());
    doStep();
  };

  window.gsOpenCurrent = function () {
    var item = data[order[0]];
    if (window.openLB) window.openLB(item.image, item.caption);
  };

  /* ── Precarga de imágenes ── */
  function loadImages() {
    return Promise.all(data.map(function (item) {
      return new Promise(function (resolve) {
        var img = new Image();
        img.onload = resolve;
        img.onerror = resolve; /* no bloquear el arranque por una imagen caída */
        img.src = item.image;
      });
    }));
  }

  /* ── Arranque: tras inyección de secciones + visibilidad ── */
  function setup() {
    container = document.getElementById('gal-showcase');
    if (!container || typeof gsap === 'undefined') return;

    media = document.getElementById('gs-panel-media');
    cover = container.querySelector('.gs-cover');
    progressFg = container.querySelector('.gs-progress-fg');
    pagination = container.querySelector('.gs-pagination');
    outlines = Array.prototype.slice.call(container.querySelectorAll('.gs-outline'));

    buildMarkup();

    var imagesReady = false;
    var seen = false;

    function tryStart() {
      if (imagesReady && seen && !started) init();
    }

    loadImages().then(function () {
      imagesReady = true;
      tryStart();
    });

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        visible = entry.isIntersecting;
        if (visible) {
          seen = true;
          tryStart();
          visWaiters.splice(0).forEach(function (r) { r(); });
        }
      });
    }, { threshold: 0.25 });
    io.observe(container);

    var rt;
    window.addEventListener('resize', function () {
      clearTimeout(rt);
      rt = setTimeout(relayout, 250);
    });
  }

  if (document.getElementById('gal-showcase')) setup();
  else document.addEventListener('sectionsLoaded', setup);
})();
