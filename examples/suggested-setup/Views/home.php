<?= $this->extend('layouts/default') ?>

<?php
document_add_body_classes('page-home');
document_add_libraries('app-home');
?>

<?= $this->section('content') ?>

<main class="container-xl">
    <div class="row">
        <div class="col">
            <p>Some content here</p>
            <?= document_image('uploads/my.jpg', ['16:9', 'top-right', 1536, 1024, 768]) ?>
        </div>
    </div>
</main>

<?= $this->endSection() ?>