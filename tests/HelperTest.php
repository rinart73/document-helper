<?php

declare(strict_types=1);

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Rinart73\DocumentHelper\Config\Services;
use Rinart73\DocumentHelper\Document;
use Rinart73\DocumentHelper\DocumentImages;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class HelperTest extends TestCase
{
    private vfsStreamDirectory $root;
    private Document $document;
    private DocumentImages $images;

    protected function setUp(): void
    {
        $this->resetServices();

        $this->document = Services::document();

        $this->root = vfsStream::setup();

        $this->images = Services::documentImages(vfsStream::url('root/'));

        $uploads = vfsStream::newDirectory('uploads');
        $this->root->addChild($uploads);

        vfsStream::copyFromFileSystem(
            TESTPATH . '_support/Images/',
            $uploads
        );

        Services::renderer(TESTPATH . '_support/Views/');

        parent::setUp();
    }

    /**
     * Adding styles and scripts inside view
     * -> Because views are being processed before layouts, styles and head scripts should be
     * properly placed inside the head tag.
     */
    public function testViewBasic(): void
    {
        $this->document->registerStyle('one', 'one.css')
            ->registerStyle('three', 'three.css')
            ->registerScript('two', 'two.js', [], '', [], false)
            ->registerScript('three', 'three.js')
            ->registerScript('four', 'four.js', ['two']);

        $expected = <<<'EOL'
            <!doctype html>
            <html class="h-100">
            <head>
                <link id="one-css" rel="stylesheet" href="https://example.com/one.css" />
            <link id="three-css" rel="stylesheet" href="https://example.com/three.css" />
            <script id="two-js" src="https://example.com/two.js"></script></head>
            <body class="bg-light h-100">
                Test
                <script id="three-js" src="https://example.com/three.js"></script>
            <script id="four-js" src="https://example.com/four.js"></script></body>
            </html>
            EOL;

        $this->assertEqualsIgnoringLE($expected, view('view-basic'));
    }

    public function testViewImage(): void
    {
        $this->images->setReportMissing(true)
            ->setAlternateTypes(IMAGETYPE_WEBP)
            ->setDefaultSrcsetWidths(512, 300)
            ->setDefaultImgAttributes(['class' => 'img-fluid', 'loading' => 'lazy']);

        $expected = <<<'EOL'
            <!doctype html>
            <html >
            <head>
                </head>
            <body >
                <picture >
            <source type="image/webp" srcset="https://example.com/cache/images/uploads/horizontal_jpg/512x341.webp 512w, https://example.com/cache/images/uploads/horizontal_jpg/300x200.webp 300w, https://example.com/cache/images/uploads/horizontal_jpg/768x511.webp 768w" sizes="(max-width: 768px) 100vw, 768px" />
            <source type="image/jpeg" srcset="https://example.com/cache/images/uploads/horizontal_jpg/512x341.jpeg 512w, https://example.com/cache/images/uploads/horizontal_jpg/300x200.jpeg 300w, https://example.com/uploads/horizontal.jpg 768w" sizes="(max-width: 768px) 100vw, 768px" />
            <img class="img-fluid" loading="lazy" src="https://example.com/uploads/horizontal.jpg" width="768" height="511" />
            </picture>
            <img class="img-fluid" loading="lazy" src="https://example.com/uploads/horizontal.jpg" width="768" height="511" />
            <picture class="generated-picture">
            <source type="image/webp" srcset="https://example.com/cache/images/uploads/vertical_png/450x675.webp 450w, https://example.com/cache/images/uploads/vertical_png/768x1152.webp 768w" sizes="(max-width: 768px) 100vw, 768px" />
            <source type="image/png" srcset="https://example.com/cache/images/uploads/vertical_png/450x675.png 450w, https://example.com/uploads/vertical.png 768w" sizes="(max-width: 768px) 100vw, 768px" />
            <img class="img-other" loading="lazy" src="https://example.com/uploads/vertical.png" width="768" height="1152" />
            </picture>
            <picture >
            <source type="image/webp" srcset="https://example.com/cache/images/uploads/vertical_png/100x100.webp 100w" sizes="(max-width: 100px) 100vw, 100px" />
            <source type="image/png" srcset="https://example.com/cache/images/uploads/vertical_png/100x100.png 100w" sizes="(max-width: 100px) 100vw, 100px" />
            <img class="img-fluid" loading="lazy" src="https://example.com/cache/images/uploads/vertical_png/100x100.png" width="100" height="100" />
            </picture>
                </body>
            </html>
            EOL;

        $this->assertEqualsIgnoringLE($expected, view('view-images'));
    }
}
