<?php

declare(strict_types=1);

// Example: Google Fonts with preconnect

use Rinart73\DocumentHelper\Document;
use Rinart73\DocumentHelper\Style;

function registerGoogleFonts(): void
{
    service('document')
        ->registerStyle(
            'google-fonts',
            'https://fonts.googleapis.com/css2?family=Roboto+Condensed:ital,wght@0,400;0,700;1,400;1,700&family=Roboto:ital,wght@0,400;0,500;0,700;1,400;1,500;1,700&display=swap'
        )
        ->prepareStyle('google-fonts', static function (Document $document, Style $style): void {
            $document->addLink('preconnect', 'https://fonts.googleapis.com')
                ->addLink('preconnect', 'https://fonts.gstatic.com', ['crossorigin' => '']);
        });
}

function addGoogleFonts(): void
{
    service('document')->addStyles('google-fonts');
}
