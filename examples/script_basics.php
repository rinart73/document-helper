<?php

declare(strict_types=1);

// Example: Script basics

function registerScripts(): void
{
    service('document')
        // `false` as `inFooter` puts jQuery in the head
        ->registerScript(
            'jquery',
            'https://cdn.jsdelivr.net/npm/jquery@3.6.3/dist/jquery.min.js',
            [],
            '3.6.3',
            [],
            false
        )
        // `true` as `version` will use `filemtime` to generate version query
        ->registerScript('app-common', '/assets/js/app-common.js', ['jquery'], true)
        ->registerScript('app-article', '/assets/js/app-article.js', ['app-common'], true);
}

function addDefaultScripts(): void
{
    // Result: jquery, app-common
    service('document')->addScripts('app-common');
}

function useInController(): void
{
    // Result: jquery, app-common, custom1234
    $inline = <<<'EOL'
        console.log("Hello 42");
        EOL;

    service('document')
        ->registerInlineScript('custom1234', $inline, ['app-common'])
        ->addScripts('custom1234');
}

function useInView(): void
{
    // Result: jquery, app-common, custom1234, app-article
    ?>

<?php
    document_add_scripts('app-article');
    ?>

<?php
}
?>