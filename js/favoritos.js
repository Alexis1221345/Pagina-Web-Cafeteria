/* =============================================
   FAVORITOS — 3 ITEMS ALEATORIOS DEL MENÚ
   Espera a sectionsLoaded (para tener el DOM)
   y a que scroll.js exponga window.revealObs.
============================================= */
document.addEventListener('sectionsLoaded', function () {

  var pool = [
    { img:'Imagenes/Expresso.jpeg',                name:'Espresso',               desc:'Doble shot, cuerpo intenso y aromático',           price:'$42'  },
    { img:'Imagenes/Cortado.jpeg',                 name:'Cortado',                desc:'Espresso con un toque de leche tibia',             price:'$48'  },
    { img:'Imagenes/Latte Caliente.jpeg',          name:'Latte Caliente',         desc:'Leche sedosa, arte latte de la casa',              price:'$62'  },
    { img:'Imagenes/Chocolate.jpeg',               name:'Chocolate de la Casa',   desc:'Cacao de Tabasco, leche espumada',                 price:'$58'  },
    { img:'Imagenes/Cold Brew.jpeg',               name:'Cold Brew',              desc:'Extracción en frío de 18 horas',                   price:'$58'  },
    { img:'Imagenes/Ice Latte.jpeg',               name:'Iced Latte',             desc:'Espresso sobre hielo y leche fría',                price:'$60'  },
    { img:'Imagenes/Affogato.jpeg',                name:'Affogato',               desc:'Helado de vainilla, espresso caliente',            price:'$72'  },
    { img:'Imagenes/Limonada de Temporada.jpeg',   name:'Limonada de Temporada',  desc:'Cítricos frescos, hierbas de la huerta',           price:'$54'  },
    { img:'Imagenes/Croissant de Mantequilla.jpg', name:'Croissant de Mantequilla',desc:'Hojaldre artesanal de tres días',                 price:'$48'  },
    { img:'Imagenes/Pan de Platano.jpeg',          name:'Pan de Plátano',         desc:'Con nuez caramelizada y canela',                   price:'$52'  },
    { img:'Imagenes/Tarta del Dia.jpeg',           name:'Tarta del Día',          desc:'Pregunta por la selección de hoy',                 price:'$66'  },
    { img:'Imagenes/Galleta de Avena.jpeg',        name:'Galleta de Avena',       desc:'Avena, chocolate amargo, sal de mar',              price:'$38'  },
    { img:'Imagenes/Rol de Canela.jpg',            name:'Rol de Canela',          desc:'Canela, glasé de vainilla, horneado al momento',   price:'$54'  },
    { img:'Imagenes/Crepa Dulce.jpg',              name:'Crepa Dulce',            desc:'Nuez, cajeta, crema batida artesanal',             price:'$58'  },
    { img:'Imagenes/Tostada de aguacate.jpg',      name:'Tostada de Aguacate',    desc:'Masa madre, aguacate, semillas, limón',            price:'$98'  },
    { img:'Imagenes/Omelette al Gusto.jpeg',       name:'Omelette al Gusto',      desc:'Dos huevos, pan de la casa, guarnición',           price:'$92'  },
    { img:'Imagenes/Bowl de Yogurt.jpeg',          name:'Bowl de Yogurt',         desc:'Yogurt natural, granola, fruta de temporada',      price:'$76'  },
    { img:'Imagenes/Chilaquiles de la Casa.jpeg',  name:'Chilaquiles de la Casa', desc:'Salsa verde o roja, crema, queso fresco',          price:'$104' }
  ];

  function shuffle(arr) {
    var a = arr.slice();
    for (var i = a.length - 1; i > 0; i--) {
      var j = Math.floor(Math.random() * (i + 1));
      var tmp = a[i]; a[i] = a[j]; a[j] = tmp;
    }
    return a;
  }

  function buildCard(item, index) {
    var delay  = index === 0 ? '' : ' reveal-d' + (index + 1);
    var card   = document.createElement('div');
    card.className = 'fav-card reveal' + delay;

    var imgWrap = document.createElement('div');
    imgWrap.className = 'fav-img-wrap';
    imgWrap.setAttribute('data-price', item.price);
    var img = document.createElement('img');
    img.src     = item.img;
    img.alt     = item.name;
    img.loading = 'lazy';
    imgWrap.appendChild(img);

    var body = document.createElement('div');
    body.className = 'fav-body';

    var num   = document.createElement('div');
    num.className   = 'fav-num';
    num.textContent = 'N.º 0' + (index + 1);

    var name  = document.createElement('div');
    name.className   = 'fav-name';
    name.textContent = item.name;

    var divider = document.createElement('div');
    divider.className = 'fav-divider';

    var desc  = document.createElement('p');
    desc.className   = 'fav-desc';
    desc.textContent = item.desc;

    var price = document.createElement('div');
    price.className   = 'fav-price';
    price.textContent = item.price;

    body.append(num, name, divider, desc, price);
    card.append(imgWrap, body);
    return card;
  }

  function renderFavoritos() {
    var grid  = document.getElementById('fav-grid');
    if (!grid) return;

    var items = shuffle(pool).slice(0, 3);
    grid.textContent = '';

    items.forEach(function (item, i) {
      var card = buildCard(item, i);
      grid.appendChild(card);
      if (window.revealObs) window.revealObs.observe(card);
    });
  }

  renderFavoritos();
});
