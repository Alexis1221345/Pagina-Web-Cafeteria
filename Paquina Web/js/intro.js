/* =============================================
   VIDEO INTRO
============================================= */
var intro      = document.getElementById('intro');
var introVideo = document.getElementById('intro-video');

document.body.style.overflow = 'hidden';

function closeIntro() {
  intro.classList.add('closing');
  document.body.style.overflow = '';
  setTimeout(function () { intro.style.display = 'none'; }, 1500);
}

introVideo.addEventListener('ended', function () {
  setTimeout(closeIntro, 400);
});

/* Safety fallback: close after 15 s even if video hangs */
setTimeout(closeIntro, 15000);

window.closeIntro = closeIntro;
