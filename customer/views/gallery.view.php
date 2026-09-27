<?php include __DIR__ . '/../../includes/header.php'; ?>

  <!-- HERO -->
  <section class="hero" id="top" style="background-image:linear-gradient(rgba(28,28,28,.2), rgba(28,28,28,.2)), url('../assets/images/casa2-casa3-sunset.jpg');background-size:cover;background-position:center;">
    <h1>Gallery</h1>
    <p>Discover the beauty of our rooms, facilities, and stunning beachside views.</p>
    <a href="#gallery-grid" class="scroll-down" aria-label="Scroll down"><span></span></a>
  </section>

  <!-- GALLERY GRID -->
  <section class="gallery" id="gallery-grid">
    <div class="container gallery-grid">
      <?php if (!$images): ?>
        <p>No gallery photos yet — check back soon!</p>
      <?php else: ?>
        <?php
          // ulang 3 nisbah ni ikut turutan (tall, tall, short, portrait) supaya masonry grid nampak
          // variasi macam reka bentuk asal — gambar sendiri tak simpan nisbah, senang untuk staff upload
          $ratios = ['ratio-tall', 'ratio-tall', 'ratio-short', 'ratio-portrait'];
          foreach ($images as $i => $img):
            $ratio = $ratios[$i % count($ratios)];
        ?>
          <figure class="gallery-item <?= $ratio ?>"><img src="../<?= htmlspecialchars($img['image_path']) ?>" alt="<?= htmlspecialchars($img['caption'] ?: 'Casadive Villa gallery photo') ?>" loading="lazy"></figure>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
