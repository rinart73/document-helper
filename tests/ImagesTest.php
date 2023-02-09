<?php

declare(strict_types=1);

use CodeIgniter\Images\Exceptions\ImageException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Rinart73\DocumentHelper\Config\Services;
use Rinart73\DocumentHelper\DocumentImages;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ImagesTest extends TestCase
{
    private vfsStreamDirectory $root;
    private DocumentImages $images;

    protected function setUp(): void
    {
        $this->resetServices();

        $this->root = vfsStream::setup();

        $this->images = Services::documentImages(vfsStream::url('root/'));

        $uploads = vfsStream::newDirectory('uploads');
        $this->root->addChild($uploads);

        vfsStream::copyFromFileSystem(
            TESTPATH . '_support/Images/',
            $uploads
        );

        parent::setUp();
    }

    public function testResize(): void
    {
        $this->assertSame([
            'path'   => 'cache/images/uploads/horizontal_jpg/400x400.jpeg',
            'width'  => 400,
            'height' => 400,
        ], $this->images->resize('uploads/horizontal.jpg', 400, 400, null, 'top-right', 87));

        $this->assertFileExists(vfsStream::url('root/cache/images/uploads/horizontal_jpg/400x400.jpeg'));

        $imageInfo = @getimagesize(vfsStream::url('root/cache/images/uploads/horizontal_jpg/400x400.jpeg'));

        $this->assertIsArray($imageInfo);
        $this->assertSame(400, $imageInfo[0] ?? 0);
        $this->assertSame(400, $imageInfo[1] ?? 0);
        $this->assertSame(IMAGETYPE_JPEG, $imageInfo[2] ?? -1);
    }

    public function testResizeAutoHeight(): void
    {
        $this->assertSame([
            'path'   => 'cache/images/uploads/horizontal_jpg/400x266.jpeg',
            'width'  => 400,
            'height' => 266,
        ], $this->images->resize('uploads/horizontal.jpg', 400));

        $this->assertFileExists(vfsStream::url('root/cache/images/uploads/horizontal_jpg/400x266.jpeg'));

        $imageInfo = @getimagesize(vfsStream::url('root/cache/images/uploads/horizontal_jpg/400x266.jpeg'));

        $this->assertIsArray($imageInfo);
        $this->assertSame(400, $imageInfo[0] ?? 0);
        $this->assertSame(266, $imageInfo[1] ?? 0);
        $this->assertSame(IMAGETYPE_JPEG, $imageInfo[2] ?? -1);
    }

    public function testResizeAutoWidth(): void
    {
        $this->assertSame([
            'path'   => 'cache/images/uploads/vertical_png/267x400.png',
            'width'  => 267,
            'height' => 400,
        ], $this->images->resize('uploads/vertical.png', null, 400));

        $this->assertFileExists(vfsStream::url('root/cache/images/uploads/vertical_png/267x400.png'));

        $imageInfo = @getimagesize(vfsStream::url('root/cache/images/uploads/vertical_png/267x400.png'));

        $this->assertIsArray($imageInfo);
        $this->assertSame(267, $imageInfo[0] ?? 0);
        $this->assertSame(400, $imageInfo[1] ?? 0);
        $this->assertSame(IMAGETYPE_PNG, $imageInfo[2] ?? -1);
    }

    public function testResizeFull(): void
    {
        $this->assertSame([
            'path'   => 'uploads/horizontal.jpg',
            'width'  => 768,
            'height' => 511,
        ], $this->images->resize('uploads/horizontal.jpg'));
    }

    public function testResizeNewType(): void
    {
        $this->assertSame([
            'path'   => 'cache/images/uploads/horizontal_jpg/768x511.webp',
            'width'  => 768,
            'height' => 511,
        ], $this->images->resize('/uploads/horizontal.jpg', null, null, IMAGETYPE_WEBP));

        $this->assertFileExists(vfsStream::url('root/cache/images/uploads/horizontal_jpg/768x511.webp'));

        $imageInfo = @getimagesize(vfsStream::url('root/cache/images/uploads/horizontal_jpg/768x511.webp'));

        $this->assertIsArray($imageInfo);
        $this->assertSame(768, $imageInfo[0] ?? 0);
        $this->assertSame(511, $imageInfo[1] ?? 0);
        $this->assertSame(IMAGETYPE_WEBP, $imageInfo[2] ?? -1);
    }

    public function testResizeNotAnImage(): void
    {
        $this->assertFalse($this->images->resize('uploads/not-an-image.txt'));
    }

    public function testResizeExternal(): void
    {
        $this->assertFalse($this->images->resize('https://via.placeholder.com/640x360'));
    }

    public function testResizeWrongDimensions(): void
    {
        $this->assertSame([
            'path'   => 'uploads/horizontal.jpg',
            'width'  => 768,
            'height' => 511,
        ], $this->images->resize('/uploads/horizontal.jpg', -1, -1));
    }

    public function testResizeWrongNewType(): void
    {
        $this->expectException(ImageException::class);

        $this->images->resize('/uploads/horizontal.jpg', null, null, 1_000_000);
    }

    public function testVariants(): void
    {
        $this->assertSame(
            [
                'width'  => 768,
                'height' => 511,
                'src'    => 'https://example.com/uploads/horizontal.jpg',
                'srcset' => [
                    2 => [
                        'https://example.com/cache/images/uploads/horizontal_jpg/400x266.jpeg 400w',
                        'https://example.com/uploads/horizontal.jpg 768w',
                    ],
                ],
            ],
            $this->images->buildVariants('/uploads/horizontal.jpg', [400])
        );
    }

    public function testVariantsAltType(): void
    {
        $this->images->setAlternateTypes(IMAGETYPE_WEBP);

        $this->assertSame([IMAGETYPE_WEBP], $this->images->getAlternateTypes());

        $this->assertSame(
            [
                'width'  => 768,
                'height' => 511,
                'src'    => 'https://example.com/uploads/horizontal.jpg',
                'srcset' => [
                    18 => [
                        'https://example.com/cache/images/uploads/horizontal_jpg/768x511.webp 768w',
                    ],
                    2 => [
                        'https://example.com/uploads/horizontal.jpg 768w',
                    ],
                ],
            ],
            $this->images->buildVariants('/uploads/horizontal.jpg', [])
        );
    }

    public function testVariantsAltTypeResize(): void
    {
        $this->images->setAlternateTypes(IMAGETYPE_WEBP);

        $this->assertSame(
            [
                'width'  => 768,
                'height' => 511,
                'src'    => 'https://example.com/uploads/horizontal.jpg',
                'srcset' => [
                    18 => [
                        'https://example.com/cache/images/uploads/horizontal_jpg/400x266.webp 400w',
                        'https://example.com/cache/images/uploads/horizontal_jpg/768x511.webp 768w',
                    ],
                    2 => [
                        'https://example.com/cache/images/uploads/horizontal_jpg/400x266.jpeg 400w',
                        'https://example.com/uploads/horizontal.jpg 768w',
                    ],
                ],
            ],
            $this->images->buildVariants('/uploads/horizontal.jpg', [400])
        );
    }

    public function testVariantsProportions(): void
    {
        $this->assertSame(
            [
                'width'  => 300,
                'height' => 300,
                'src'    => 'https://example.com/cache/images/uploads/horizontal_jpg/300x300.jpeg',
                'srcset' => [],
            ],
            $this->images->buildVariants('/uploads/horizontal.jpg', ['1:1', 300])
        );
    }

    public function testVariantsProportionsOriginalWidth(): void
    {
        $this->assertSame(
            [
                'width'  => 768,
                'height' => 384,
                'src'    => 'https://example.com/cache/images/uploads/horizontal_jpg/768x384.jpeg',
                'srcset' => [],
            ],
            $this->images->buildVariants('/uploads/horizontal.jpg', ['2:1', 768])
        );
    }

    /**
     * Requested width > original width
     * -> use original width
     */
    public function testVariantsProportionsLarger(): void
    {
        $this->assertSame(
            [
                'width'  => 768,
                'height' => 384,
                'src'    => 'https://example.com/cache/images/uploads/horizontal_jpg/768x384.jpeg',
                'srcset' => [],
            ],
            $this->images->buildVariants('/uploads/horizontal.jpg', ['2:1', 1024])
        );
    }

    /**
     * Proportions without at least one width
     * -> ignore proportions
     */
    public function testVariantsProportionsMissingSize(): void
    {
        $this->assertSame(
            [
                'width'  => 768,
                'height' => 511,
                'src'    => 'https://example.com/uploads/horizontal.jpg',
                'srcset' => [],
            ],
            $this->images->buildVariants('/uploads/horizontal.jpg', ['1:1'])
        );
    }

    public function testVariantsDoesntExist(): void
    {
        $this->assertFalse($this->images->buildVariants('/uploads/does-not-exist.jpg', [400]));
    }

    public function testVariantsWrongAltType(): void
    {
        $this->expectException(ImageException::class);

        $this->images->setAlternateTypes(1_000_000);

        $this->images->buildVariants('/uploads/horizontal.jpg', []);
    }

    public function testVariantsExternal(): void
    {
        $this->assertFalse($this->images->buildVariants('https://via.placeholder.com/640x360', []));
    }

    public function testTagSimple(): void
    {
        $this->assertSame(
            '<img src="https://example.com/uploads/horizontal.jpg" width="768" height="511" />',
            $this->images->renderTag('uploads/horizontal.jpg')
        );
    }

    public function testTagImgAttributes(): void
    {
        $this->assertSame(
            '<img class="img-fluid" loading="lazy" src="https://example.com/uploads/horizontal.jpg" width="768" height="511" />',
            $this->images->renderTag('uploads/horizontal.jpg', [], ['class' => 'img-fluid', 'loading' => 'lazy'])
        );
    }

    public function testTagResize(): void
    {
        $this->assertSame(
            '<img src="https://example.com/uploads/horizontal.jpg" width="768" height="511" sizes="(max-width: 768px) 100vw, 768px" srcset="https://example.com/cache/images/uploads/horizontal_jpg/512x341.jpeg 512w, https://example.com/cache/images/uploads/horizontal_jpg/300x200.jpeg 300w, https://example.com/uploads/horizontal.jpg 768w" />',
            $this->images->renderTag('uploads/horizontal.jpg', [512, 300])
        );
    }

    public function testTagResizeAbsolute(): void
    {
        $this->assertSame(
            '<img src="https://example.com/uploads/horizontal.jpg" width="768" height="511" sizes="(max-width: 768px) 100vw, 768px" srcset="https://example.com/cache/images/uploads/horizontal_jpg/300x200.jpeg 300w, https://example.com/uploads/horizontal.jpg 768w" />',
            $this->images->renderTag(vfsStream::url('root/uploads/horizontal.jpg'), [300])
        );
    }

    /**
     * Image from another website
     * -> don't try to get info, fallback to img tag
     */
    public function testTagResizeExternal(): void
    {
        $this->assertSame(
            '<img src="https://via.placeholder.com/640x360" />',
            $this->images->renderTag('https://via.placeholder.com/640x360', [300])
        );
    }

    public function testTagExternalThis(): void
    {
        $this->assertSame(
            '<img src="https://example.com/uploads/horizontal.jpg" width="768" height="511" sizes="(max-width: 768px) 100vw, 768px" srcset="https://example.com/cache/images/uploads/horizontal_jpg/300x200.jpeg 300w, https://example.com/uploads/horizontal.jpg 768w" />',
            $this->images->renderTag('https://example.com/uploads/horizontal.jpg', [300])
        );
    }

    /**
     * SVG image
     * -> fallback to img tag
     */
    public function testTagSVG(): void
    {
        $this->assertSame(
            '<img src="https://example.com/uploads/icon.svg" />',
            $this->images->renderTag('uploads/icon.svg', [16])
        );
    }

    /**
     * Source file doesn't exist
     * -> fallback to simple img tag
     */
    public function testTagDoesntExist(): void
    {
        $this->assertSame(
            '<img src="https://example.com/uploads/doesnt-exist.jpg" />',
            $this->images->renderTag('uploads/doesnt-exist.jpg', [512, 300])
        );
    }

    /**
     * Source file doesn't have an extension
     * -> cache folder will have no extension, but variants will get extension based on image type
     */
    public function testTagNoExtension(): void
    {
        $this->assertSame(
            '<img src="https://example.com/uploads/no-extension" width="768" height="512" sizes="(max-width: 768px) 100vw, 768px" srcset="https://example.com/cache/images/uploads/no-extension_/512x341.jpeg 512w, https://example.com/cache/images/uploads/no-extension_/300x200.jpeg 300w, https://example.com/uploads/no-extension 768w" />',
            $this->images->renderTag('uploads/no-extension', [512, 300])
        );
    }

    /**
     * Source file is not an image
     * -> fallback to simple img tag
     */
    public function testTagNotAnImage(): void
    {
        $this->assertSame(
            '<img src="https://example.com/uploads/not-an-image.txt" />',
            $this->images->renderTag('uploads/not-an-image.txt', [512, 300])
        );
    }

    /**
     * Custom width:height ratio
     */
    public function testTagProportions(): void
    {
        $this->assertSame(
            '<img src="https://example.com/cache/images/uploads/vertical_png/512x288.png" width="512" height="288" sizes="(max-width: 512px) 100vw, 512px" srcset="https://example.com/cache/images/uploads/vertical_png/512x288.png 512w, https://example.com/cache/images/uploads/vertical_png/300x169.png 300w" />',
            $this->images->renderTag('uploads/vertical.png', ['16:9', 'top', 512, 300])
        );
    }

    /**
     * WebP variant
     */
    public function testTagAlternateTypes(): void
    {
        $this->images->setAlternateTypes(IMAGETYPE_WEBP);

        $expected = <<<'EOL'
            <picture class="img-picture test">
            <source type="image/webp" srcset="https://example.com/cache/images/uploads/horizontal_jpg/512x341.webp 512w, https://example.com/cache/images/uploads/horizontal_jpg/300x200.webp 300w, https://example.com/cache/images/uploads/horizontal_jpg/768x511.webp 768w" sizes="(max-width: 768px) 100vw, 768px" />
            <source type="image/jpeg" srcset="https://example.com/cache/images/uploads/horizontal_jpg/512x341.jpeg 512w, https://example.com/cache/images/uploads/horizontal_jpg/300x200.jpeg 300w, https://example.com/uploads/horizontal.jpg 768w" sizes="(max-width: 768px) 100vw, 768px" />
            <img src="https://example.com/uploads/horizontal.jpg" width="768" height="511" />
            </picture>
            EOL;

        $this->assertEqualsIgnoringLE(
            $expected,
            $this->images->renderTag('uploads/horizontal.jpg', [512, 300], [], ['class' => 'img-picture test'])
        );
    }

    /**
     * Alternative types are specified but srcset widths are empty
     * -> generate WebP version of the full source
     */
    public function testTagAltTypesNoWidths(): void
    {
        $this->images->setAlternateTypes(IMAGETYPE_WEBP);

        $expected = <<<'EOL'
            <picture >
            <source type="image/webp" srcset="https://example.com/cache/images/uploads/horizontal_jpg/768x511.webp 768w" sizes="(max-width: 768px) 100vw, 768px" />
            <source type="image/jpeg" srcset="https://example.com/uploads/horizontal.jpg 768w" sizes="(max-width: 768px) 100vw, 768px" />
            <img src="https://example.com/uploads/horizontal.jpg" width="768" height="511" />
            </picture>
            EOL;

        $this->assertEqualsIgnoringLE(
            $expected,
            $this->images->renderTag('uploads/horizontal.jpg')
        );
    }

    /**
     * Source file is WebP already
     */
    public function testTagAlreadyWebp(): void
    {
        $this->images->setAlternateTypes(IMAGETYPE_WEBP);

        $this->assertSame(
            '<img src="https://example.com/uploads/already.webp" width="768" height="512" sizes="(max-width: 768px) 100vw, 768px" srcset="https://example.com/cache/images/uploads/already_webp/512x341.webp 512w, https://example.com/uploads/already.webp 768w" />',
            $this->images->renderTag('uploads/already.webp', [512])
        );
    }

    public function testTagGlobalOptions(): void
    {
        $this->images->setCacheDirectory('cache/')
            ->setDefaultPosition('top')
            ->setDefaultQuality(85)
            ->setDefaultSrcsetWidths(468, 200)
            ->setDefaultImgAttributes(['class' => 'img-fluid', 'loading' => 'lazy', 'decoding' => 'async'])
            ->setDefaultPictureAttributes(['class' => 'my-picture']);

        $this->assertSame(vfsStream::url('root/'), $this->images->getPublicDirectory());
        $this->assertSame('cache', $this->images->getCacheDirectory());
        $this->assertSame('top', $this->images->getDefaultPosition());
        $this->assertSame(85, $this->images->getDefaultQuality());
        $this->assertSame([468, 200], $this->images->getDefaultSrcsetWidths());
        $this->assertSame([
            'class'    => 'img-fluid',
            'loading'  => 'lazy',
            'decoding' => 'async',
        ], $this->images->getDefaultImgAttributes());
        $this->assertSame(['class' => 'my-picture'], $this->images->getDefaultPictureAttributes());

        $this->assertSame(
            '<img class="img-other" loading="lazy" decoding="async" src="https://example.com/uploads/horizontal.jpg" width="768" height="511" sizes="(max-width: 768px) 100vw, 768px" srcset="https://example.com/cache/uploads/horizontal_jpg/468x311.jpeg 468w, https://example.com/cache/uploads/horizontal_jpg/200x133.jpeg 200w, https://example.com/uploads/horizontal.jpg 768w" />',
            $this->images->renderTag('uploads/horizontal.jpg', [], ['class' => 'img-other'])
        );
    }

    /**
     * Only add variants that already exist
     */
    public function testTagGenerateOff(): void
    {
        $this->images->buildVariants('uploads/vertical.png', [300]);

        $this->images->setOnDemandGeneration(false)
            ->setReportMissing(true);

        $this->assertFalse($this->images->isOnDemandGeneration());
        $this->assertTrue($this->images->isReportMissing());

        $this->assertSame(
            '<img src="https://example.com/uploads/vertical.png" width="768" height="1152" sizes="(max-width: 768px) 100vw, 768px" srcset="https://example.com/cache/images/uploads/vertical_png/300x450.png 300w, https://example.com/uploads/vertical.png 768w" />',
            $this->images->renderTag('uploads/vertical.png', [512, 300])
        );

        $this->assertSame(
            [
                'uploads/vertical.png' => [
                    'cache/images/uploads/vertical_png/512x768.png' => [
                        'width'     => 512,
                        'height'    => 768,
                        'imageType' => null,
                        'position'  => 'center',
                        'quality'   => 90,
                    ],
                ],
            ],
            $this->images->getMissing()
        );
    }

    /**
     * Clear cache and delete source file
     */
    public function testClear(): void
    {
        $this->images->renderTag('uploads/horizontal.jpg', [512, 300]);

        $this->assertDirectoryExists(vfsStream::url('root/cache/images/uploads/horizontal_jpg'));
        $this->assertSame(
            [
                '.',
                '..',
                '300x200.jpeg',
                '512x341.jpeg',
            ],
            scandir(vfsStream::url('root/cache/images/uploads/horizontal_jpg'))
        );

        $this->images->clearCache('uploads/horizontal.jpg');

        $this->assertDirectoryDoesNotExist(vfsStream::url('root/cache/images/uploads/horizontal_jpg'));
        $this->assertFileExists(vfsStream::url('root/uploads/horizontal.jpg'));

        $this->images->delete('uploads/horizontal.jpg');

        $this->assertFileDoesNotExist(vfsStream::url('root/uploads/horizontal.jpg'));
    }

    public function testClearAll(): void
    {
        $this->images->renderTag('uploads/horizontal.jpg', [400]);
        $this->images->renderTag('uploads/vertical.png', [300]);

        $this->images->clearCache();

        $this->assertDirectoryDoesNotExist(vfsStream::url('root/cache/images'));
    }
}
