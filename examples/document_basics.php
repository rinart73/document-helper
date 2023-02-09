<?php

declare(strict_types=1);

// Example: Document basics

function defaultValues(): void
{
    service('document')
        ->setHtmlAttributes(['lang' => 'en_US'])
        ->setTitleSuffix('| My WebSite')
        ->setMeta('charset', 'utf-8')
        ->setMeta('viewport', 'width=device-width, initial-scale=1')
        ->setMeta('robots', 'index, follow');
}

function useInController()
{
    service('document')
        ->setTitle('My article title')
        ->setMeta('description', 'My article description')
        ->setMeta('robots', 'index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large')
        ->setMeta('og:title', 'My article OpenGraph title')
        ->setMeta('og:description', 'My article OpenGraph description')
        ->addLink('canonical', 'https://example.com/articles/my-article/')
        ->addLink('alternate', 'https://example.com/articles/my-article/', ['hreflang' => 'en'])
        ->addLink('alternate', 'https://example.ru/articles/translated-article/', ['hreflang' => 'ru'])
        ->addBodyClasses('page-article');

    return view('someview');
}

function useInView(): void
{
    ?>

<?php
    document_add_html_classes('class1');
    document_add_body_classes('page-article--layout1');
    ?>

<?php
}
?>