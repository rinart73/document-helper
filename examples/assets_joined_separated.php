<?php

declare(strict_types=1);

// Example: Joined and separated style-script assets

function registerAssets(): void
{
    service('document')
        ->registerStyle('icons', '/assets/css/icons.css')
        ->registerStyle('myapp', '/assets/css/myapp.css', ['icons'], '1.1.4')
        ->registerScript('myapp', '/assets/js/myapp.js', [], '1.0.1');
}

function addJoinedAssets(): void
{
    /**
     * By default script will automatically try to add a style with the same handle.
     * So script `myapp` will add style `myapp`, which in turn depends on style `icons`.
     */
    service('document')->addScripts('myapp');
}

function addSeparatedAssets(): void
{
    /**
     * This behaviour can be changed anytime before the `head` is built and rendered.
     * Now only the `myapp` script will be added.
     */
    $document = service('document');
    $document
        ->setStyleAddedByScript(false)
        ->addScripts('myapp');

    // and if you want to add the `myapp` and `icons` styles.
    $document->addStyles('myapp');

    // Or you can use the following method that will try to add styles and scripts with specified handles.
    $document->addLibraries('myapp');
}
