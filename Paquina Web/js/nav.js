/* =============================================
   MOBILE MENU
============================================= */
var mob = document.getElementById('mobile-overlay');
var ham = document.getElementById('hamburger');

function toggleMob() {
  var open = mob.classList.toggle('open');
  ham.classList.toggle('open', open);
  document.body.style.overflow = open ? 'hidden' : '';
}

function closeMob() {
  mob.classList.remove('open');
  ham.classList.remove('open');
  document.body.style.overflow = '';
}

window.toggleMob = toggleMob;
window.closeMob  = closeMob;
