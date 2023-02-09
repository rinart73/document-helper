<?php

declare(strict_types=1);

// Example: Style basics

function registerStyles(): void
{
    service('document')
        ->registerStyle(
            'bootstrap-icons',
            'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.min.css',
            [],
            '1.10.3'
        )
        // `true` as `version` will use `filemtime` to generate version query
        ->registerStyle('app-common', '/assets/css/app-common.css', ['bootstrap-icons'], true)
        ->registerStyle('app-article', '/assets/css/app-article.css', ['app-common'], true);
}

function addDefaultStyles(): void
{
    // Result: bootstrap-icons, app-common
    service('document')->addStyles('app-common');
}

function useInController(): void
{
    // Result: bootstrap-icons, app-common, custom1234

    $inline = <<<'EOL'
        body {
            background: #ddd;
        }
        EOL;

    service('document')
        ->registerInlineStyle('custom1234', $inline, ['app-common'])
        ->addStyles('custom1234');
}

function useInView(): void
{
    // Result: bootstrap-icons, app-common, custom1234, app-article
    ?>

<?php
    document_add_styles('app-article');
    ?>

<?php
}
?>