<?= $this->extend('layouts/blank') ?>

<?php
document_add_html_classes('h-100');
document_add_body_classes('bg-light', 'h-100');
document_add_styles('one');
document_add_scripts('two');
document_add_libraries('three', 'four');
?>

<?= $this->section('content') ?>
Test
<?= $this->endSection() ?>