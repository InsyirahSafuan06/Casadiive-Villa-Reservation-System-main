<?php
/**
 * @var string $title    tajuk (dah di-htmlspecialchars)
 * @var string $bodyHtml isi emel
 */
?>
<!doctype html>
<html>
<body style="margin:0;padding:24px;background:#F9F5EF;font-family:Arial,Helvetica,sans-serif;color:#000;">
  <div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:10px;padding:32px;border:1px solid rgba(106,58,25,.15);">
    <p style="font-size:22px;color:#FE810A;margin:0 0 20px;font-weight:bold;">Casadive Villa</p>
    <h1 style="font-size:20px;color:#6A3A19;margin:0 0 16px;"><?= $title ?></h1>
    <div style="font-size:15px;line-height:1.6;"><?= $bodyHtml ?></div>
    <hr style="border:none;border-top:1px solid #eee;margin:24px 0;">
    <p style="font-size:12px;color:#999;margin:0;">Casadive Villa &middot; This is an automated message, please do not reply directly to this email.</p>
  </div>
</body>
</html>
