/**
 * Review photo upload/camera widget — shared by the full review form (customer/mybooking.php)
 * and the compact footer widget (includes/footer.php), which each call
 * initReviewPhotoWidget() with their own element IDs.
 */
function initReviewPhotoWidget(ids) {
  var form = document.getElementById(ids.form);
  if (!form) return;

  var uploadBtn = document.getElementById(ids.uploadBtn);
  var cameraBtn = document.getElementById(ids.cameraBtn);
  var removeBtn = document.getElementById(ids.removeBtn);
  var fileInput = document.getElementById(ids.fileInput);
  var preview = document.getElementById(ids.preview);
  var statusEl = document.getElementById(ids.status);
  var statusClass = ids.statusClass;

  var cameraModal = document.getElementById(ids.cameraModal);
  var cameraVideo = document.getElementById(ids.cameraVideo);
  var cameraCanvas = document.getElementById(ids.cameraCanvas);
  var captureBtn = document.getElementById(ids.captureBtn);
  var cancelBtn = document.getElementById(ids.cancelBtn);

  // lightbox untuk besarkan preview (dari upload ATAU camera capture — dua-dua guna preview yang sama)
  var lightbox = document.getElementById(ids.lightbox);
  var lightboxImg = document.getElementById(ids.lightboxImg);
  var lightboxClose = document.getElementById(ids.lightboxClose);
  var lightboxBack = document.getElementById(ids.lightboxBack);
  var homeUrl = ids.homeUrl;

  var CONFIDENCE_THRESHOLD = 0.5;
  var cocoModel = null;
  var modelLoading = null;
  var cameraStream = null;

  // ---- scan-frame corners (over the live camera feed) + a "Verified" badge that pops onto
  // the accepted preview thumbnail — purely visual polish, doesn't change what's validated ----
  function addScanFrame() {
    if (!cameraVideo || cameraVideo.parentNode.classList.contains('rp-scan-frame')) return;
    var wrap = document.createElement('div');
    wrap.className = 'rp-scan-frame';
    cameraVideo.parentNode.insertBefore(wrap, cameraVideo);
    wrap.appendChild(cameraVideo);
    ['tl', 'tr', 'bl', 'br'].forEach(function (pos) {
      var corner = document.createElement('span');
      corner.className = 'rp-scan-corner rp-scan-corner-' + pos;
      wrap.appendChild(corner);
    });
  }
  addScanFrame();

  function ensurePreviewBadge() {
    var wrap = preview.parentNode;
    if (!wrap.classList.contains('rp-preview-wrap')) {
      wrap = document.createElement('div');
      wrap.className = 'rp-preview-wrap';
      preview.parentNode.insertBefore(wrap, preview);
      wrap.appendChild(preview);

      var badge = document.createElement('div');
      badge.className = 'rp-verify-badge';
      badge.innerHTML =
        '<svg class="rp-verify-icon" viewBox="0 0 24 24" aria-hidden="true">' +
        '<circle cx="12" cy="12" r="11" fill="#1e9e57"/>' +
        '<path d="M7 12.5 10.5 16 17 8.5" stroke="#fff" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>' +
        '</svg>';
      wrap.appendChild(badge);
    }
    return wrap.querySelector('.rp-verify-badge');
  }

  function showVerifiedBadge() {
    var badge = ensurePreviewBadge();
    badge.classList.remove('is-visible');
    void badge.offsetWidth; // force reflow so the pop-in animation restarts every time
    badge.classList.add('is-visible');
  }

  function hideVerifiedBadge() {
    var wrap = preview.parentNode;
    if (wrap.classList.contains('rp-preview-wrap')) {
      var badge = wrap.querySelector('.rp-verify-badge');
      if (badge) badge.classList.remove('is-visible');
    }
  }

  var VEHICLE_CLASSES = ['car', 'motorcycle', 'bus', 'truck', 'bicycle', 'train', 'airplane', 'boat'];
  var FOOD_CLASSES = ['banana', 'apple', 'sandwich', 'orange', 'broccoli', 'carrot', 'hot dog', 'pizza', 'donut', 'cake', 'bowl'];

  function setStatus(text, kind) {
    statusEl.hidden = false;
    statusEl.textContent = text;
    statusEl.className = statusClass + ' ' + (kind || '');
  }

  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      var s = document.createElement('script');
      s.src = src;
      s.onload = resolve;
      s.onerror = reject;
      document.head.appendChild(s);
    });
  }

  function loadModel() {
    if (cocoModel) return Promise.resolve(cocoModel);
    if (modelLoading) return modelLoading;

    setStatus('Loading…', 'processing');
    modelLoading = loadScript('https://jsdelivr.net')
      .then(function () { return loadScript('https://jsdelivr.net'); })
      .then(function () { return window.cocoSsd.load(); })
      .then(function (model) { cocoModel = model; return model; });
    return modelLoading;
  }

  function classify(imgEl) {
    return loadModel().then(function (model) {
      setStatus('AI is checking your image…', 'processing');
      return model.detect(imgEl);
    }).then(function (predictions) {
      var best = null;
      predictions.forEach(function (p) {
        if (p.score >= CONFIDENCE_THRESHOLD && (!best || p.score > best.score)) best = p;
      });

      if (!best) {
        return { is_valid: false, category: 'uncertain', confidence: predictions[0] ? predictions[0].score : 0 };
      }
      if (best.class === 'bed') {
        return { is_valid: true, category: 'bedroom', confidence: best.score };
      }
      if (best.class === 'person') {
        return { is_valid: false, category: 'selfie', confidence: best.score };
      }
      if (VEHICLE_CLASSES.indexOf(best.class) !== -1) {
        return { is_valid: false, category: 'vehicle', confidence: best.score };
      }
      if (FOOD_CLASSES.indexOf(best.class) !== -1) {
        return { is_valid: false, category: 'food', confidence: best.score };
      }
      return { is_valid: false, category: 'uncertain', confidence: best.score };
    });
  }

  function assignFile(file) {
    var dt = new DataTransfer();
    dt.items.add(file);
    fileInput.files = dt.files;
  }

  function clearFile() {
    fileInput.value = '';
    preview.hidden = true; // Sembunyikan jika fail dibersihkan
    preview.src = '';
    if (removeBtn) removeBtn.hidden = true;
    hideVerifiedBadge();
  }

  function handleFile(file) {
    clearFile();
    var url = URL.createObjectURL(file);
    var img = new Image();
    img.onload = function () {
      classify(img).then(function (result) {
        if (result.is_valid) {
          setStatus('✓ Image verified. Your room photo is suitable for this review.', 'success');
          
          // PENTING: Hanya set src dan papar gambar jika disahkan tulen oleh AI
          preview.src = url;
          preview.hidden = false;
          if (removeBtn) removeBtn.hidden = false;
          showVerifiedBadge();
          assignFile(file);
        } else if (result.category === 'uncertain') {
          setStatus('Please upload a clearer photo showing the villa room.', 'uncertain');
        } else {
          setStatus('✕ Image rejected. Please upload a clear photo of the villa room only.', 'reject');
        }
      }).catch(function () {
        setStatus('Please try again, or submit your review without a photo.', 'reject');
      });
    };
    img.src = url;
  }

  uploadBtn.addEventListener('click', function () { fileInput.click(); });

  fileInput.addEventListener('change', function () {
    if (fileInput.files && fileInput.files[0]) handleFile(fileInput.files[0]);
  });

  if (removeBtn) {
    removeBtn.addEventListener('click', function () {
      clearFile();
      statusEl.hidden = true;
    });
  }

  if (lightbox && lightboxImg) {
    preview.addEventListener('click', function () {
      lightboxImg.src = preview.src;
      lightbox.classList.add('is-open');
    });
    if (lightboxClose) {
      lightboxClose.addEventListener('click', function () {
        lightbox.classList.remove('is-open');
      });
    }
    if (lightboxBack && homeUrl) {
      lightboxBack.addEventListener('click', function () {
        window.location.href = homeUrl; // customer tinggalkan page ni terus ke homepage
      });
    }
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) lightbox.classList.remove('is-open'); // klik background just tutup je
    });
  }

  function stopCamera() {
    if (cameraStream) {
      cameraStream.getTracks().forEach(function (t) { t.stop(); });
      cameraStream = null;
    }
    cameraModal.classList.remove('is-open');
  }

  cameraBtn.addEventListener('click', function () {
    setStatus('Requesting camera access…', 'processing');
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(function (stream) {
      cameraStream = stream;
      cameraVideo.srcObject = stream;
      cameraModal.classList.add('is-open');
      statusEl.hidden = true;
    }).catch(function () {
      setStatus('Camera access was denied or unavailable. Please use Upload Image instead.', 'reject');
    });
  });

  cancelBtn.addEventListener('click', stopCamera);

  captureBtn.addEventListener('click', function () {
    cameraCanvas.width = cameraVideo.videoWidth;
    cameraCanvas.height = cameraVideo.videoHeight;
    cameraCanvas.getContext('2d').drawImage(cameraVideo, 0, 0);
    cameraCanvas.toBlob(function (blob) {
      var file = new File([blob], 'camera-capture.jpg', { type: 'image/jpeg' });
      stopCamera();
      handleFile(file);
    }, 'image/jpeg', 0.9);
  });
}