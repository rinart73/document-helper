<!doctype html>
<html <?= document_html() ?>>
<head>
    <?= document_head() ?>
</head>
<body <?= document_body() ?>>
    <?= $this->renderSection('content') ?>
    <?= document_footer() ?>
</body>
</html>