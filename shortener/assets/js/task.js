/* NS Link - task page control layer */
(function () {
  'use strict';

  var btn = document.getElementById('continueBtn');
  if (!btn) return;

  var taskCard = document.querySelector('.task-card');
  var wait = parseInt((taskCard && taskCard.getAttribute('data-wait')) || '8', 10);
  var countdown = document.querySelector('.countdown');
  var timerEl = document.querySelector('.task-timer');
  var fill = document.querySelector('.task-bar-fill');
  var remaining = wait > 0 ? wait : 8;
  var started = false;
  var interval = null;

  function tick() {
    if (!started) return;
    remaining -= 1;
    if (countdown) countdown.textContent = String(Math.max(0, remaining));
    if (timerEl) timerEl.classList.add('started');
    if (fill) fill.style.width = Math.min(100, Math.round(((wait - remaining) / wait) * 100)) + '%';
    if (remaining <= 0) {
      if (interval) window.clearInterval(interval);
      btn.disabled = false;
      btn.classList.add('active');
      if (timerEl) timerEl.classList.add('done');
    }
  }

  btn.addEventListener('click', function () {
    if (btn.disabled) return;
    var next = btn.getAttribute('data-next') || '';
    var dest = (btn.getAttribute('data-dest') || next) || '';
    if (dest !== '') {
      window.location.href = dest;
    }
  });

  function start() {
    if (started) return;
    started = true;
    interval = window.setInterval(tick, 1000);
  }

  // ?skip=1 for fast testing
  var skip = window.location.search.indexOf('skip') !== -1;
  if (skip) {
    remaining = 1;
    start();
  } else {
    window.addEventListener('scroll', function () {
      if (!started && window.scrollY > 40) start();
    }, { passive: true });
  }
})();