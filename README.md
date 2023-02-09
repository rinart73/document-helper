# Document Helper

[![](https://github.com/rinart73/document-helper/workflows/PHPUnit/badge.svg)](https://github.com/rinart73/document-helper/actions/workflows/phpunit.yml)
[![](https://github.com/rinart73/document-helper/workflows/PHPStan/badge.svg)](https://github.com/rinart73/document-helper/actions/workflows/phpstan.yml)
[![](https://github.com/rinart73/document-helper/workflows/Deptrac/badge.svg)](https://github.com/rinart73/document-helper/actions/workflows/deptrac.yml)
[![Coverage Status](https://coveralls.io/repos/github/rinart73/document-helper/badge.svg?branch=develop)](https://coveralls.io/github/rinart73/document-helper?branch=develop)

Document Helper is a CodeIgniter 4 library for easier HTML generation, particularly when it comes to meta-tags, scripts, styles and images.

It's heavilly inspired by OpenCart and Wordpress.

## Features

- Add document `title`, `meta`, `link` tags in Controllers; `html` and `body` attributes in Controllers and Views - no need for View Layout spam.
- Register scripts and styles that you might need once and when you request them, their dependencies are added and sorted **automatically**.
- Generate image variants (resized versions for `srcset`, alternative image types such as WebP) and render `img`/`picture` at the same time.

## Getting Started

### Prerequisites

- A [CodeIgniter 4.2.7+](https://github.com/codeigniter4/CodeIgniter4/) based project
- [Composer](https://getcomposer.org/) for package management
- PHP 7.4+

### Installation

Installation is done through Composer.
```console
composer require rinart73/document-helper
```

## Overview

Refer to [examples](examples) for more showcases.

### Document tags

```php
$document = service('document');

$document->setHtmlAttributes(['lang' => 'en-US'])
    ->setBodyAttributes(['class' => 'page-article'])
    ->setTitle('My article | WebSite')
    ->setMeta('charset', 'utf-8')
    ->setMeta('viewport', 'width=device-width, initial-scale=1')
    ->setMeta('description', 'My article description')
    ->setMeta('robots', 'index, follow')
    ->addLink('canonical', 'https://example.com/articles/my-article/')
    ->addLink('alternate', 'https://example.com/articles/my-article/', ['hreflang' => 'en'])
    ->addLink('alternate', 'https://example.ru/articles/translated-article/', ['hreflang' => 'ru']);
```

### Scripts and styles

```php
$document = service('document');

$document->registerStyle('library', 'assets/css/library.css', [], '1.1')
    ->registerScript('library', 'assets/js/library.js', [], '1.1');

$document->registerScript('core', 'assets/js/core.js', [], '1.1.2');

$document->registerScript('app-common', 'assets/js/app-common.js', ['core', 'library'], '1.2');

/**
 * Will add `library` and `core` styles and scripts before `app-common`.
 * By default scripts automatically request corresponding styles but this can be turned off.
 */
$this->document->addScript('app-common');

// add script tag with serialized data before the `app-common` script
$document->localizeScript('app-common', 'appCommonData', [
  'baseUrl' => base_url(),
  'errorGeneric' => lang('Common.errorGeneric')
]);

// add inline script with custom attributes in the script tag
$document->addInlineScript('my-inline', 'console.log("Hello world");', [], ['data-test' => '42']);

```

### Images

```php
$images = service('documentImages');

// generate img tag with width and height
$images->renderTag('uploads/my.jpg');

// generate img tag without width and height because it's an external resource
$images->renderTag('https://via.placeholder.com/640x360');

// set future image defaults and enable WebP
$images->setAlternateTypes(IMAGETYPE_WEBP)
  ->setSrcsetWidths(1536, 1024, 768)
  ->setImgAttributes(['class' => 'img-fluid', 'loading' => 'lazy']);

/**
 * Will generate:
 * 1. Proportionally scaled JPEG versons with 1536px, 1024px and 768px widths
 * 2. WebP versions of the full size image and resized images
 * Will render a picture tag with sources and img inside
 */
$images->renderTag('uploads/my.jpg');

/**
 * Will generate:
 * 1. 1024x1024 and 768x768 versions of the image. If image needs to be cut,
 * data in the top right corner will be prioritised.
 * 2. WebP versions of the resized images
 * Will render a picture tag with sources and img inside (won't include original file),
 * default class attribute will be overridden but loading=lazy will be kept.
 */
$images->renderTag('uploads/my.jpg', ['1:1', 'top-right', 1024, 768], ['class' => 'img-square']);
```

### Helpers

Helper functions are typically meant to be used inside views, although they can be used anywhere else.

*Controllers/YourController.php*

```php
class YourController extends BaseController
{
    public function index()
    {
        helper('document');
    }
}
```

*Views/layout.php*:
```php
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
```

*Views/view-auth.php*
```php
<?= $this->extend('layout') ?>

<?php
document_add_html_class('h-100');
document_add_body_class('bg-light', 'h-100', 'page-auth');
document_add_library('bootstrap', 'app-auth');
?>

<?= $this->section('content') ?>

<main class="container-xl h-100">
    <div class="row h-100 align-items-center justify-content-center">
        <div class="col">
            Example content
        </div>
    </div>
</main>

<?= $this->endSection() ?>
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.