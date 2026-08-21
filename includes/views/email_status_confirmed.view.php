<?php
/**
 * @var string $name
 * @var string $checkin
 * @var string $checkout
 * @var string $deposit
 * @var string $total
 */
?>
<p>Hi <?= $name ?>,</p>
<p>Your booking has been <strong>confirmed</strong>! Here are your stay details:</p>
<table style="width:100%;border-collapse:collapse;margin:16px 0;">
  <tr><td style="padding:6px 0;color:#844525;">Check-in</td><td style="padding:6px 0;text-align:right;"><strong><?= $checkin ?></strong></td></tr>
  <tr><td style="padding:6px 0;color:#844525;">Check-out</td><td style="padding:6px 0;text-align:right;"><strong><?= $checkout ?></strong></td></tr>
  <tr><td style="padding:6px 0;color:#844525;">Deposit Paid</td><td style="padding:6px 0;text-align:right;">RM <?= $deposit ?></td></tr>
  <tr><td style="padding:6px 0;color:#844525;">Total Amount</td><td style="padding:6px 0;text-align:right;">RM <?= $total ?></td></tr>
</table>
<p>Thank you for choosing Casadive Villa. We look forward to welcoming you!</p>
