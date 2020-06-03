<?php
$disclaimerValue = $metaData[$identifier . '_disclaimer'][0];
$disclaimerChecked = (!empty($disclaimerValue) ? ' checked' : '');
$disclaimerDescription = $properties['disclaimer_description'];
?>
<fieldset>
    <input class="wc_input_decimal wc_input_text_with_disclaimer input-text regular-input" type="text" name="<?= $identifier; ?>" id="<?= $identifier; ?>" style="width: 50px !important;" value="<?= htmlentities($value); ?>" placeholder="<?= $placeholder; ?>"/>
    <p class="description"><?= $description; ?></p>
    <input type="checkbox" name="<?= $identifier; ?>_disclaimer" id="<?= $identifier; ?>_disclaimer"<?= $disclaimerChecked; ?> />
    <label for="<?= $identifier; ?>_disclaimer"><?= $disclaimerDescription; ?></label>
</fieldset>
