<?php
/**
 * @var string $name
 * @var string $checkin
 * @var string $doorCodeHtml
 */
?>
<p>Hi <?= $name ?>,</p>
<p>This is a friendly reminder that your check-in date is coming up:</p>
<table style="width:100%;border-collapse:collapse;margin:16px 0;">
  <tr><td style="padding:6px 0;color:#844525;">Check-in Date</td><td style="padding:6px 0;text-align:right;"><strong><?= $checkin ?></strong></td></tr>
  <tr><td style="padding:6px 0;color:#844525;">Check-in Time</td><td style="padding:6px 0;text-align:right;">After 3.00 PM</td></tr>
  <tr><td style="padding:6px 0;color:#844525;">Check-out Time</td><td style="padding:6px 0;text-align:right;">Before 12.00 PM</td></tr>
</table>
<p><?= $doorCodeHtml ?><br>Please use the code above to unlock the main door upon your arrival.</p>
<p>If you have any questions or need assistance, feel free to contact us. We hope you have a safe journey and enjoy your stay at Casadive Villa. See you soon!</p>
