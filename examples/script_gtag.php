<?php

declare(strict_types=1);

// Example: Google Tag Manager / Google Analytics

function registerGtag(): void
{
    $gtagID = 'G-XXXXXXXXXX';

    $inline = <<<EOL
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{$gtagID}');
        EOL;

    service('document')
        ->registerScript(
            'gtag-library',
            "https://www.googletagmanager.com/gtag/js?id={$gtagID}",
            [],
            '',
            ['async' => ''],
            false
        )
        ->registerInlineScript('gtag', $inline, ['gtag-library'], [], false);
}

function addGtag(): void
{
    service('document')->addScripts('gtag');
}
