<?php
/**
 * @var Concrete\Core\Form\Service\Form $form
 * @var string $ips
 */
?>
<div class="form-group">
    <?= $form->label('ips', t('Manual list of IP addresses')) ?>
    <?= $form->textarea('ips', $ips, ['rows' => 7]) ?>
</div>
