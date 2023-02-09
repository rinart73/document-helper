<?php

declare(strict_types=1);

// Example: Localized script

function registerScript(): void
{
    service('document')->registerScript('myscript', '/assets/js/myscript.js', [], '1.0.1');
}

function addScript(): void
{
    service('document')
        ->localizeScript('myscript', 'myscriptData', [
            'baseUrl'     => baseUrl(),
            'tokenInput'  => csrf_token(),
            'tokenHeader' => csrf_header(),
            'lang'        => [
                'errorGeneric' => lang('YourLangFile.errorGeneric'),
            ],
        ])
        ->addScripts('myscript');
}
