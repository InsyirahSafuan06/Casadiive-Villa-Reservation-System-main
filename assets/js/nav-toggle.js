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

  // Deliberately NOT closing the menu when a nav link is tapped: every link here goes to
  // a different full page, so the whole document (menu included) is about to be replaced
  // anyway. Closing it here used to hide the just-tapped <a> (display:none via the CSS
  // class) synchronously inside the click handler, which some mobile browsers treat as
  // "the tapped element disappeared" and quietly cancel the navigation for — the menu would
  // visibly close but the page would never actually change. See git history if this is ever
  // reused for same-page anchors, where closing on click would matter again.

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeMenu();
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth > 760) closeMenu();
  });
})();
