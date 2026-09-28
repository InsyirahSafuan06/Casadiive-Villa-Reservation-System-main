var quickBookingForm = document.getElementById('quick-booking-form');
var quickBookingType = document.getElementById('qb-type');
function updateQuickBookingAction() {
  quickBookingForm.action = quickBookingType.value === 'Campsite' ? 'customer/campsite.php' : 'customer/villa.php';
}
quickBookingType.addEventListener('change', updateQuickBookingAction);
updateQuickBookingAction();

document.querySelectorAll('.stars[data-rating]').forEach(function (starsEl) {
  var rating = parseInt(starsEl.dataset.rating, 10) || 0;
  starsEl.querySelectorAll('.star').forEach(function (star, index) {
    if (index >= rating) {
      star.classList.add('star-empty');
    }
  });
});

var photoLightbox = document.getElementById('photo-lightbox');
var photoLightboxImg = document.getElementById('photo-lightbox-img');
var photoLightboxClose = document.getElementById('photo-lightbox-close');
var photoLightboxBack = document.getElementById('photo-lightbox-back');

function openPhotoLightbox(src, alt) {
  photoLightboxImg.src = src;
  photoLightboxImg.alt = alt;
  photoLightbox.classList.add('is-open');
}

function closePhotoLightbox() {
  photoLightbox.classList.remove('is-open');
  photoLightboxImg.src = '';
}

document.querySelectorAll('.testimonial-photo').forEach(function (img) {
  img.addEventListener('click', function () {
    openPhotoLightbox(img.src, img.alt);
  });
});

photoLightboxClose.addEventListener('click', closePhotoLightbox);
photoLightboxBack.addEventListener('click', closePhotoLightbox);
photoLightbox.addEventListener('click', function (e) {
  if (e.target === photoLightbox) closePhotoLightbox();
});
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closePhotoLightbox();
});
