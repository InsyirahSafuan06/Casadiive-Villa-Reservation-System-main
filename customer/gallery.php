<?php
$base = '../';
$active = 'gallery';
$pageTitle = 'Gallery — Casadive Villa';
$pageCss = 'style/gallery.css';
include __DIR__ . '/../includes/header.php';
?>

  <!-- HERO -->
  <section class="hero" id="top">
    <h1>Gallery</h1>
    <p>Discover the beauty of our rooms, facilities, and stunning beachside views.</p>
    <a href="#gallery-grid" class="scroll-down" aria-label="Scroll down"><span></span></a>
  </section>

  <!-- GALLERY GRID -->
  <section class="gallery" id="gallery-grid">
    <div class="container gallery-grid">
      <figure class="gallery-item ratio-tall"><span role="img" aria-label="Villa exterior view"></span></figure>
      <figure class="gallery-item ratio-tall"><span role="img" aria-label="Villa bedroom"></span></figure>
      <figure class="gallery-item ratio-tall"><span role="img" aria-label="Villa living area"></span></figure>
      <figure class="gallery-item ratio-tall"><span role="img" aria-label="Campsite by the beach"></span></figure>
      <figure class="gallery-item ratio-short"><span role="img" aria-label="Swimming pool"></span></figure>
      <figure class="gallery-item ratio-short"><span role="img" aria-label="Poolside lounge"></span></figure>
      <figure class="gallery-item ratio-tall"><span role="img" aria-label="Beachfront sunset"></span></figure>
      <figure class="gallery-item ratio-tall"><span role="img" aria-label="Campsite tents"></span></figure>
      <figure class="gallery-item ratio-portrait"><span role="img" aria-label="Villa balcony view"></span></figure>
      <figure class="gallery-item ratio-portrait"><span role="img" aria-label="Beachside walkway"></span></figure>
    </div>
  </section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
