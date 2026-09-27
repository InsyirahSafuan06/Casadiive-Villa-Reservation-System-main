/**
 * Helper kongsi untuk modal overlay ringkas (cancel-booking reminder, thanks-for-purchase,
 * thanks-for-reviewing) — semua guna corak sama: tutup bila klik X/butang lain/luar kad.
 * Dulu logik ni disalin 3 kali (mybooking.view.php x2, sucess_payment.view.php) — sekarang satu je.
 */
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
