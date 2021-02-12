<?php
declare(strict_types=1);

enforce_login();
View::show_header('Donation Canceled'); ?>

<div>
  <div class="header">
    <h3 id="forums">Donation Canceled</h3>
  </div>

  <div class="box">
    <p>
      It's the thought that counts.
      Please reconsider donating in the future.
    </p>
  </div>
</div>
<?php View::show_footer();
