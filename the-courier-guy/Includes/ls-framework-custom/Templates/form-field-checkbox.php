<?php
$checked = (!empty($value) ? ' checked' : '');
?>
<input type="checkbox" id="<?= $identifier; ?>" name="<?= $identifier; ?>"<?= $checked; ?><?= $readonly; ?> />
