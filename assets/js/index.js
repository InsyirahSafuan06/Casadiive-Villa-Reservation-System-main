// butang "Check Availability" kena bawa pelanggan ke page Villa ke Campsite,
// ikut apa yang dia pilih dekat dropdown Type — tukar action borang sebelum submit
var quickBookingForm = document.getElementById('quick-booking-form');
var quickBookingType = document.getElementById('qb-type');
function updateQuickBookingAction() {
  quickBookingForm.action = quickBookingType.value === 'Campsite' ? 'customer/campsite.php' : 'customer/villa.php';
}
quickBookingType.addEventListener('change', updateQuickBookingAction);
updateQuickBookingAction(); // set betul-betul dari awal, ikut pilihan default pun

// PHP dah papar 5 bintang penuh untuk setiap ulasan, JS ni je yang "padamkan" warna
// bintang lepas rating sebenar (contoh: rating 3 bintang, so bintang ke-4 & ke-5 jadi pudar)
document.querySelectorAll('.stars[data-rating]').forEach(function (starsEl) {
  var rating = parseInt(starsEl.dataset.rating, 10) || 0; // rating ulasan ni berapa bintang
  starsEl.querySelectorAll('.star').forEach(function (star, index) {
    if (index >= rating) {
      star.classList.add('star-empty'); // bintang lepas rating, buat pudar
    }
  });
});

// klik gambar testimonial untuk besarkan dalam lightbox terus atas homepage —
// tutup je lightbox untuk balik tengok homepage semula, tak payah buka tab baru
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
  if (e.target === photoLightbox) closePhotoLightbox(); // klik background pun tutup
});
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closePhotoLightbox();
});
