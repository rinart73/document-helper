# Document Helper

[![PHPUnit](https://github.com/rinart73/document-helper/workflows/PHPUnit/badge.svg)](https://github.com/rinart73/document-helper/actions/workflows/phpunit.yml)
[![PHPStan](https://github.com/rinart73/document-helper/workflows/PHPStan/badge.svg)](https://github.com/rinart73/document-helper/actions/workflows/phpstan.yml)
[![Psalm](https://github.com/rinart73/document-helper/actions/workflows/psalm.yml/badge.svg)](https://github.com/rinart73/document-helper/actions/workflows/psalm.yml)
[![Coverage Status](https://coveralls.io/repos/github/rinart73/document-helper/badge.svg?branch=develop)](https://coveralls.io/github/rinart73/document-helper?branch=develop)

Document Helper is a CodeIgniter 4 library for easier HTML generation, particularly when it comes to meta-tags, styles, scripts and images.

It's heavilly inspired by OpenCart and Wordpress.

## Features

- Add document `title`, `meta`, `link` tags in Controllers; `html` and `body` classes in Controllers and Views.
- Register scripts and styles that you might need. When you request them, their dependencies are added and sorted **automatically**.
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
### Suggested setup

Add the `document` helper and the `Rinart73\DocumentHelper\Document` class into your `Controllers/BaseController.php`:
```php
namespace App\Controllers;

use Rinart73\DocumentHelper\Document;

abstract class BaseController extends Controller
{
    protected $helpers = ['document'];
    protected Document $document;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->document = service('document');
        $this->registerDocumentLibraries();
        $this->initDocumentDefaults();
    }

    // Register styles and scripts that you **might** need
    protected function registerDocumentLibraries()
    {
        $this->document->registerScript('jquery', 'https://cdn.jsdelivr.net/npm/jquery@3.6.3/dist/jquery.min.js', [], '3.6.3');
    }

    // Set default Document parameters for your pages
    protected function initDocumentDefaults()
    {
        $this->document
            ->setHtmlAttributes(['lang' => 'en-US'])
            ->setMeta('charset', 'utf-8')
            ->setMeta('viewport', 'width=device-width, initial-scale=1')
            ->setMeta('robots', 'index, follow')
            ->setTitleSuffix('| MyWebSite')
            ->addScripts('jquery');
    }
```
Then add helper functions into your layouts:
`Views/layouts/layout-default.php`
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
Then you will be able to use the features that the library provides in your Controllers and Views:
`Controllers/Articles.php`
```php
namespace App\Controllers;

class Articles extends BaseController
{
    public function index()
    {
        $this->document
            ->addBodyClasses('archive', 'archive--articles')
            ->setTitle('My articles')
            ->setMeta('description', 'Articles archive description');
    }
}
```

## Overview

Refer to [examples](examples) for more showcases.

### Document tags

```php
$document = service('document');

$document
    ->setHtmlAttributes(['lang' => 'en-US'])
    ->setBodyAttributes(['class' => 'page-article'])
    ->setTitle('My article')
    ->setTitleSuffix('| WebSite')
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

$document
    ->registerStyle('library', 'assets/css/library.css', [], '1.1')
    ->registerScript('library', 'assets/js/library.js', [], '1.1');

$document->registerScript('core', 'assets/js/core.js', [], '1.1.2');

$document->registerScript('app-common', 'assets/js/app-common.js', ['core', 'library'], '1.2');

/**
 * Will add `library` and `core` styles and scripts before `app-common`.
 * By default scripts automatically request styles with the same handle but the feature can be turned off.
 */
$this->document->addScripts('app-common');

// add script tag with serialized data before the `app-common` script
$document->localizeScript('app-common', 'appCommonData', [
  'baseUrl' => base_url(),
  'errorGeneric' => lang('Common.errorGeneric')
]);

// add inline script with custom attributes in the script tag
$document
    ->registerInlineScript('my-inline', 'console.log("Hello world");', [], ['data-test' => '42'])
    ->addScripts('my-inline');

```

### Images

```php
$images = service('documentImages');

// generate img tag with width and height
$images->renderTag('uploads/my.jpg');

// img tag without width and height because it's an external resource
$images->renderTag('https://via.placeholder.com/640x360');

// set image defaults and enable WebP
$images->setAlternateTypes(IMAGETYPE_WEBP)
  ->setSrcsetWidths(1536, 1024, 768)
  ->setImgAttributes(['class' => 'img-fluid', 'loading' => 'lazy']);

/**
 * Will generate:
 * 1. Proportionally scaled JPEG versons with 1536px, 1024px and 768px widths
 * 2. WebP versions of the full size image and resized images
 * Will render a picture tag with sources and img inside
 * By default images are generated on-demand but this behaviour can be altered.
 */
$images->renderTag('uploads/my.jpg');

/**
 * Will generate:
 * 1. 1024x1024 and 768x768 versions of the image. If image needs to be cut,
 * data in the top right corner will be prioritised.
 * 2. WebP versions of the resized images
 * Will render a picture tag with sources and img inside (won't include original file),
 * Default class attribute will be overridden but loading=lazy will be kept.
 */
$images->renderTag('uploads/my.jpg', ['1:1', 'top-right', 1024, 768], ['class' => 'img-square']);
```

### Helpers

Helper functions are typically meant to be used inside views, although they can be used anywhere else.

`Controllers/YourController.php`

```php
class YourController extends BaseController
{
    public function index()
    {
        helper('document');
    }
}
```

`Views/layout-default.php`
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
<?= $this->extend('layout-default') ?>

<?php
document_add_html_classes('h-100');
document_add_body_classes('bg-light', 'h-100', 'page-auth');
document_add_libraries('bootstrap', 'app-auth');
?>

<?= $this->section('content') ?>

<main class="container-xl h-100">
    <div class="row h-100 align-items-center justify-content-center">
        <div class="col">
            <?= document_image('uploads/my.jpg', ['1:1', 'top-right', 1024, 768], ['class' => 'img-square']) ?>
        </div>
    </div>
</main>

<?= $this->endSection() ?>
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
