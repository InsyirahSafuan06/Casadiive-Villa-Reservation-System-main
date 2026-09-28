initReviewPhotoWidget({
  form: 'footer-review-form',
  uploadBtn: 'footer-upload-btn',
  cameraBtn: 'footer-camera-btn',
  removeBtn: 'footer-remove-btn',
  fileInput: 'footer-file-input',
  preview: 'footer-image-preview',
  status: 'footer-verify-status',
  statusClass: 'footer-verify-status',
  cameraModal: 'footer-camera-modal',
  cameraVideo: 'footer-camera-video',
  cameraCanvas: 'footer-camera-canvas',
  captureBtn: 'footer-camera-capture-btn',
  cancelBtn: 'footer-camera-cancel-btn',
  lightbox: 'footer-lightbox',
  lightboxImg: 'footer-lightbox-img',
  lightboxClose: 'footer-lightbox-close',
  lightboxBack: 'footer-lightbox-back',
  homeUrl: FOOTER_REVIEW_HOME_URL
});

(function () {
  var anon = document.getElementById('footer-review-anonymous');
  var nameInput = document.getElementById('footer-review-name');
  if (!anon || !nameInput) return;
  anon.addEventListener('change', function () {
    nameInput.disabled = anon.checked;
    if (anon.checked) nameInput.value = '';
  });
})();
