(function () {
  var toggle = document.getElementById('navToggle');
  var links = document.getElementById('navLinks');
  var closeBtn = document.getElementById('navClose');
  var backdrop = document.getElementById('navBackdrop');
  if (!toggle || !links) return;

  function openMenu() {
    links.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
    if (backdrop) backdrop.hidden = false;
  }

  function closeMenu() {
    links.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    if (backdrop) backdrop.hidden = true;
  }

  toggle.addEventListener('click', function () {
    if (links.classList.contains('is-open')) closeMenu();
    else openMenu();
  });

  if (closeBtn) closeBtn.addEventListener('click', closeMenu);
  if (backdrop) backdrop.addEventListener('click', closeMenu);

  links.addEventListener('click', function (e) {
    // links now wrap an icon + text, so the click target can be the <svg>/<path>
    // inside the <a> rather than the <a> itself — closest() catches both.
    if (e.target.closest('a')) closeMenu();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeMenu();
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth > 760) closeMenu();
  });
})();
