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

  var lightbox = document.getElementById(ids.lightbox);
  var lightboxImg = document.getElementById(ids.lightboxImg);
  var lightboxClose = document.getElementById(ids.lightboxClose);
  var lightboxBack = document.getElementById(ids.lightboxBack);
  var homeUrl = ids.homeUrl;

  var cameraStream = null;
  var autoCaptureTimer = null;
  var AUTO_CAPTURE_MS = 5000;

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

  function setStatus(text, kind) {
    statusEl.hidden = false;
    statusEl.textContent = text;
    statusEl.className = statusClass + ' ' + (kind || '');
  }

  function assignFile(file) {
    var dt = new DataTransfer();
    dt.items.add(file);
    fileInput.files = dt.files;
  }

  function clearFile() {
    fileInput.value = '';
    preview.hidden = true;
    preview.src = '';
    if (removeBtn) removeBtn.hidden = true;
  }

  function handleFile(file) {
    clearFile();
    preview.src = URL.createObjectURL(file);
    preview.hidden = false;
    if (removeBtn) removeBtn.hidden = false;
    assignFile(file);
    setStatus('Photo added.', 'success');
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
        window.location.href = homeUrl;
      });
    }
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) lightbox.classList.remove('is-open');
    });
  }

  function stopCamera() {
    if (autoCaptureTimer) {
      clearTimeout(autoCaptureTimer);
      autoCaptureTimer = null;
    }
    if (cameraStream) {
      cameraStream.getTracks().forEach(function (t) { t.stop(); });
      cameraStream = null;
    }
    cameraModal.classList.remove('is-open');
  }

  function capturePhoto() {
    if (autoCaptureTimer) {
      clearTimeout(autoCaptureTimer);
      autoCaptureTimer = null;
    }
    if (!cameraVideo.videoWidth || !cameraVideo.videoHeight) {
      autoCaptureTimer = setTimeout(capturePhoto, 200);
      return;
    }
    cameraCanvas.width = cameraVideo.videoWidth;
    cameraCanvas.height = cameraVideo.videoHeight;
    cameraCanvas.getContext('2d').drawImage(cameraVideo, 0, 0);
    cameraCanvas.toBlob(function (blob) {
      var file = new File([blob], 'camera-capture.jpg', { type: 'image/jpeg' });
      stopCamera();
      handleFile(file);
    }, 'image/jpeg', 0.9);
  }

  cameraBtn.addEventListener('click', function () {
    setStatus('Requesting camera access…', 'processing');
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(function (stream) {
      cameraStream = stream;
      cameraVideo.srcObject = stream;
      cameraModal.classList.add('is-open');
      statusEl.hidden = true;
      autoCaptureTimer = setTimeout(capturePhoto, AUTO_CAPTURE_MS);
    }).catch(function () {
      setStatus('Camera access was denied or unavailable. Please use Upload Image instead.', 'reject');
    });
  });

  cancelBtn.addEventListener('click', stopCamera);

  captureBtn.addEventListener('click', capturePhoto);
}