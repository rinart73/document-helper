<?= $this->extend('layouts/blank') ?>

<?= $this->section('content') ?>
<?= document_image('uploads/horizontal.jpg') ?>

<?= document_image('uploads/horizontal.jpg', false) ?>

<?= document_image('uploads/vertical.png', [450], ['class' => 'img-other'], ['class' => 'generated-picture']) ?>

<?= document_image('uploads/vertical.png', ['1:1', 'top', 100]) ?>

<?= $this->endSection() ?>