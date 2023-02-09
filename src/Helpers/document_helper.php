<?php

declare(strict_types=1);

use Rinart73\DocumentHelper\Document;
use Rinart73\DocumentHelper\DocumentImages;

if (! function_exists('document_html')) {
    /**
     * Renders and returns html tag attributes (like lang, class and others).
     */
    function document_html(): string
    {
        /** @var Document $document */
        $document = service('document');

        return $document->renderHtml();
    }
}

if (! function_exists('document_head')) {
    /**
     * Renders and returns head tags (title, description, robots, styles, scripts and others).
     */
    function document_head(): string
    {
        /** @var Document $document */
        $document = service('document');

        return $document->renderHead();
    }
}

if (! function_exists('document_body')) {
    /**
     * Renders and returns body tag attributes (like class and others).
     */
    function document_body(): string
    {
        /** @var Document $document */
        $document = service('document');

        return $document->renderBody();
    }
}

if (! function_exists('document_footer')) {
    /**
     * Renders and returns footer tags (scripts and others).
     */
    function document_footer(): string
    {
        /** @var Document $document */
        $document = service('document');

        return $document->renderFooter();
    }
}

if (! function_exists('document_add_html_classes')) {
    /**
     * Adds new html tag classes to the existing ones.
     */
    function document_add_html_classes(string ...$classes): void
    {
        /** @var Document $document */
        $document = service('document');

        $document->addHtmlClasses(...$classes);
    }
}

if (! function_exists('document_add_body_classes')) {
    /**
     * Adds new body tag classes to the existing ones.
     */
    function document_add_body_classes(string ...$classes): void
    {
        /** @var Document $document */
        $document = service('document');

        $document->addBodyClasses(...$classes);
    }
}

if (! function_exists('document_add_styles')) {
    /**
     * Adds one or several previously registered styles
     */
    function document_add_styles(string ...$handles): void
    {
        /** @var Document $document */
        $document = service('document');

        $document->addStyles(...$handles);
    }
}

if (! function_exists('document_add_scripts')) {
    /**
     * Adds one or several previously registered scripts
     */
    function document_add_scripts(string ...$handles): void
    {
        /** @var Document $document */
        $document = service('document');

        $document->addScripts(...$handles);
    }
}

if (! function_exists('document_add_libraries')) {
    /**
     * Adds one or several previously registered styles and scripts
     */
    function document_add_libraries(string ...$handles): void
    {
        /** @var Document $document */
        $document = service('document');

        $document->addLibraries(...$handles);
    }
}

if (! function_exists('document_image')) {
    /**
     * Renders and returns img/picture tag with srcset
     *
     * @param array<int|string>|false $variantsOptions List of srcset widths, proportion ('16:9'), position ('top', 'bottom-right'). Use `false` to disable
     * @param array<string, string>   $imgAttrs
     * @param array<string, string>   $pictureAttrs
     */
    function document_image(string $path, $variantsOptions = [], array $imgAttrs = [], array $pictureAttrs = []): string
    {
        /** @var DocumentImages $images */
        $images = service('documentImages');

        return $images->renderTag($path, $variantsOptions, $imgAttrs, $pictureAttrs);
    }
}
