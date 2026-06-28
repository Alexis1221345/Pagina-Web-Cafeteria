/* =============================================
   LIGHTBOX
============================================= */
var lb    = document.getElementById('lightbox');
var lbImg = document.getElementById('lb-img');
var lbCap = document.getElementById('lb-caption');

function openLB(src, caption) {
  lbImg.src = src;
  lbCap.textContent = caption;
  lb.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeLB(e) {
  if (!e || e.target === lb || e.target === document.getElementById('lb-close')) {
    lb.classList.remove('open');
    document.body.style.overflow = '';
  }
}

document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') {
    closeLB();
    if (typeof closeMob === 'function') closeMob();
  }
});

window.openLB  = openLB;
window.closeLB = closeLB;
