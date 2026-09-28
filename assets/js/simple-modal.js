function initSimpleModal(overlayId, opts) {
  opts = opts || {};
  var overlay = document.getElementById(overlayId);
  if (!overlay) return null;

  function close() { overlay.hidden = true; }
  function open() { overlay.hidden = false; }

  (opts.closeIds || []).forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('click', close);
  });

  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) close();
  });

  if (opts.confirmId && opts.onConfirm) {
    var confirmBtn = document.getElementById(opts.confirmId);
    if (confirmBtn) confirmBtn.addEventListener('click', opts.onConfirm);
  }

  return { close: close, open: open };
}
