<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Rinart73\DocumentHelper\Document;
use Rinart73\DocumentHelper\DocumentImages;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = ['document'];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    protected Document $document;

    protected DocumentImages $documentImages;

    /**
     * Constructor.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        $this->document = service('document');
        $this->registerDocumentLibraries();
        $this->initDocumentDefaults();

        $this->documentImages = service('documentImages');
        $this->initDocumentImagesDefaults();
    }

    /**
     * Adds definitions for scripts and styles that **might** be used on this page.
     */
    protected function registerDocumentLibraries(): void
    {
        // bootstrap
        $this->document->registerStyle(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css',
            [],
            '5.2.3'
        )->registerScript(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js',
            [],
            '5.2.3'
        );

        // Common styles and scripts for all pages
        $this->document->registerStyle(
            'app-common',
            'assets/css/common.css',
            ['bootstrap'],
            '1.0.1',
        )->registerScript(
            'app-common',
            'assets/js/common.js',
            ['bootstrap'],
            '1.0.1',
        );

        // Home page
        $this->document->registerScript(
            'app-home',
            'assets/js/home.js',
            ['app-common'],
            '1.0.3',
        );
    }

    /**
     * Set document parameters for every page
     */
    protected function initDocumentDefaults(): void
    {
        $this->document->setHtmlAttributes(['lang' => 'en-US'])
            ->setMeta('charset', 'utf-8')
            ->setMeta('viewport', 'width=device-width, initial-scale=1')
            ->setMeta('robots', 'index, follow')
            ->addScripts('bootstrap', 'app-common');

        $this->document->localizeScript('app-common', 'appCommonData', [
            'baseUrl' => base_url(),
        ]);
    }

    /**
     * Set image parameters for every page
     */
    protected function initDocumentImagesDefaults(): void
    {
        $this->documentImages->setReportMissing(true)
            ->setAlternateTypes(IMAGETYPE_WEBP)
            ->setQuality(85)
            ->setSrcsetWidths(1536, 1024, 768)
            ->setImgAttributes(['class' => 'img-fluid', 'loading' => 'lazy']);
    }
}
