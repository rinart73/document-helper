<?php

declare(strict_types=1);

namespace Rinart73\DocumentHelper;

use Config\Services;
use Rinart73\DocumentHelper\Traits\HTMLOperations;

/**
 * Allows to resize/convert images and generate `img`/`picture` tags with `source`-s and srcsets.
 */
class DocumentImages
{
    use HTMLOperations;

    /**
     * Absolute path to the public directory
     */
    protected string $publicDirectory;

    /**
     * Where to store resized/converted images (relative to public directory)
     */
    protected string $cacheDirectory = 'cache/images';

    /**
     * If `true`, images will be generated on demand. If `false`, only existing images will be included in srcset
     */
    protected bool $onDemandGeneration = true;

    /**
     * Use `getMissing()` to get a list of all files that need to be generated
     */
    protected bool $reportMissing = false;

    /**
     * List of images that weren't found
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $missingByPath = [];

    /**
     * A list of PHP imageType constants. Specified image types will be generated and added as an alternative sources
     *
     * @var int[]
     */
    protected array $alternateTypes = [];

    /**
     * Crop position. Used when desired proportions don't match original
     */
    protected string $defaultPosition = 'center';

    /**
     * Default image quality
     */
    protected int $defaultQuality = 90;

    /**
     * Default widths for generating/searching image variants
     *
     * @var int[]
     */
    protected array $defaultSrcsetWidths = [];

    /**
     * Default attributes (such as `'loading' => 'lazy'`) for `img` tag
     *
     * @var array<string, string>
     */
    protected array $defaultImgAttributes = [];

    /**
     * Default attributes for `picture` tag that is generated when an image has more than one image type
     *
     * @var array<string, string>
     */
    protected array $defaultPictureAttributes = [];

    /**
     * @param string|null $publicDirectory Absolute path to the public directory. FCPATH by default
     */
    public function __construct(?string $publicDirectory = null)
    {
        $this->setPublicDirectory($publicDirectory ?? FCPATH);
    }

    /**
     * Get the absolute path to the public directory
     */
    public function getPublicDirectory(): string
    {
        return $this->publicDirectory;
    }

    /**
     * Set the absolute path to the public directory
     *
     * @return $this
     */
    public function setPublicDirectory(string $publicDirectory)
    {
        $this->publicDirectory = rtrim($publicDirectory, '/') . '/';

        return $this;
    }

    /**
     * Get path to the image cache directory relative to the public directory
     */
    public function getCacheDirectory(): string
    {
        return $this->cacheDirectory;
    }

    /**
     * Set path to the image cache directory relative to the public directory
     *
     * @return $this
     */
    public function setCacheDirectory(string $cacheDirectory)
    {
        $this->cacheDirectory = rtrim($cacheDirectory, '/');

        return $this;
    }

    /**
     * Check if images are being generated on-demand
     */
    public function isOnDemandGeneration(): bool
    {
        return $this->onDemandGeneration;
    }

    /**
     * Toggle image on demand generation
     *
     * @return $this
     */
    public function setOnDemandGeneration(bool $onDemandGeneration)
    {
        $this->onDemandGeneration = $onDemandGeneration;

        return $this;
    }

    /**
     * Check if reporting missing images is enabled
     */
    public function isReportMissing(): bool
    {
        return $this->reportMissing;
    }

    /**
     * Set to `true` to enable missing image reports, then you can use `getMissing()`.
     * When on demand generation is disabled, missing images are files that are needed but don't exist.
     * When on demand generation is enabled, missing images are files that were just generated.
     *
     * @return $this
     */
    public function setReportMissing(bool $reportMissing)
    {
        $this->reportMissing = $reportMissing;

        return $this;
    }

    /**
     * List of images that weren't found
     *
     * @return array<string, array<string, mixed>>
     */
    public function getMissing(): array
    {
        return $this->missingByPath;
    }

    /**
     * Retrieves the list of alternate image types
     *
     * @return int[]
     */
    public function getAlternateTypes(): array
    {
        return $this->alternateTypes;
    }

    /**
     * PHP imageType constants that will be used in the rendered tags
     *
     * @see https://www.php.net/manual/en/function.image-type-to-mime-type.php
     *
     * @return $this
     */
    public function setAlternateTypes(int ...$alternateTypes)
    {
        $this->alternateTypes = $alternateTypes;

        return $this;
    }

    /**
     * Get default position
     */
    public function getDefaultPosition(): string
    {
        return $this->defaultPosition;
    }

    /**
     * Set default position (such as 'top', 'top-left', 'right', 'bottom-right' etc).
     *
     * @return $this
     */
    public function setDefaultPosition(string $defaultPosition)
    {
        $this->defaultPosition = $defaultPosition;

        return $this;
    }

    /**
     * Get default quality
     */
    public function getDefaultQuality(): int
    {
        return $this->defaultQuality;
    }

    /**
     * Set default quality
     *
     * @return $this
     */
    public function setDefaultQuality(int $quality)
    {
        $this->defaultQuality = $quality;

        return $this;
    }

    /**
     * Get the list of default srcset widths
     *
     * @return int[]
     */
    public function getDefaultSrcsetWidths(): array
    {
        return $this->defaultSrcsetWidths;
    }

    /**
     * Set default srcset widths
     *
     * @return $this
     */
    public function setDefaultSrcsetWidths(int ...$defaultSrcsetWidths)
    {
        $this->defaultSrcsetWidths = $defaultSrcsetWidths;

        return $this;
    }

    /**
     * Get default img attributes
     *
     * @return array<string, string>
     */
    public function getDefaultImgAttributes(): array
    {
        return $this->defaultImgAttributes;
    }

    /**
     * Set default img attributes
     *
     * @param array<string, string> $imgAttributes
     *
     * @return $this
     */
    public function setDefaultImgAttributes(array $imgAttributes)
    {
        $this->defaultImgAttributes = $imgAttributes;

        return $this;
    }

    /**
     * Get default picture attributes
     *
     * @return array<string, string>
     */
    public function getDefaultPictureAttributes(): array
    {
        return $this->defaultPictureAttributes;
    }

    /**
     * Set default picture attributes
     *
     * @param array<string, string> $pictureAttributes
     *
     * @return $this
     */
    public function setDefaultPictureAttributes(array $pictureAttributes)
    {
        $this->defaultPictureAttributes = $pictureAttributes;

        return $this;
    }

    /**
     * Renders img/picture tag with srcset
     *
     * @param string                     $path              Relative or absolute path to the source image. Accepts links to external images as well but it disables most of the features
     * @param array<int|string>|false    $variantsOptions   Proportion (`'16:9'`), crop position (`'top-right'`), list of srcset widths
     * @param array<string, string|null> $imgAttributes     Add `img` tag attributes or override default ones
     * @param array<string, string|null> $pictureAttributes Add `picture` tag attributes or override default ones
     */
    public function renderTag(string $path, $variantsOptions = [], array $imgAttributes = [], array $pictureAttributes = []): string
    {
        $path = $this->transformPath($path);

        $imgAttributes = array_merge($this->defaultImgAttributes, $imgAttributes);

        $imageInfo = null;
        $host      = parse_url($path, PHP_URL_HOST);

        // don't get image info for files from another websites
        if (empty($host)) {
            $imgAttributes['src'] = base_url($path);

            $imageInfo = @getimagesize($this->publicDirectory . $path);
            if ($imageInfo && (empty($imgAttributes['width']) || empty($imgAttributes['height']))) {
                // set width and height to prevent Cumulative Layout Shift
                $imgAttributes['width']  = $imageInfo[0];
                $imgAttributes['height'] = $imageInfo[1];
            }
        } else {
            $imgAttributes['src'] = $path;
        }

        // variants are disabled (no srcset = no picture) or failed to retrieve basic info
        if ($variantsOptions === false || ! $imageInfo || empty($imgAttributes['width']) || empty($imgAttributes['height'])) {
            return sprintf('<img %s />', $this->renderAttributes($imgAttributes));
        }

        if (empty($variantsOptions)) {
            $variantsOptions = $this->defaultSrcsetWidths;
        }

        $data = $this->buildVariants($path, $variantsOptions);
        if (! $data) {
            return sprintf('<img %s />', $this->renderAttributes($imgAttributes));
        }

        $imgAttributes['src']    = $data['src'];
        $imgAttributes['width']  = $data['width'];
        $imgAttributes['height'] = $data['height'];

        if (empty($data['srcset'])) {
            // only one image
            return sprintf('<img %s />', $this->renderAttributes($imgAttributes));
        }

        // add default 'sizes'
        if (empty($imgAttributes['sizes'])) {
            $imgAttributes['sizes'] = "(max-width: {$imgAttributes['width']}px) 100vw, {$imgAttributes['width']}px";
        }

        if (count($data['srcset']) === 1) {
            // no alternate types = no need to use picture tag
            $set                     = reset($data['srcset']);
            $imgAttributes['srcset'] = implode(', ', $set);

            return sprintf('<img %s />', $this->renderAttributes($imgAttributes));
        }

        $pictureAttributes = array_merge($this->defaultPictureAttributes, $pictureAttributes);

        $sources = [];

        foreach ($data['srcset'] as $type => $set) {
            $mimeType  = image_type_to_mime_type($type);
            $sources[] = sprintf('<source type="%s" srcset="%s" sizes="%s" />', $mimeType, implode(', ', $set), $imgAttributes['sizes']);
        }
        unset($imgAttributes['sizes']);

        return sprintf("<picture %s>\n%s\n<img %s />\n</picture>", $this->renderAttributes($pictureAttributes), implode("\n", $sources), $this->renderAttributes($imgAttributes));
    }

    /**
     * Calculates width, height, src and srcset for an image
     *
     * @param string            $path            Relative or absolute path to the source image. **Doesn't accept URLs to external images**
     * @param array<int|string> $variantsOptions Proportion (`'16:9'`), crop position (`'top-right'`), list of srcset widths
     *
     * @return array{width:int, height:int, src:string, srcset:array<int, string[]>}|false Variants data or `false` if a source
     *                                                                                     file is not a valid image or belongs to another domain.
     */
    public function buildVariants(string $path, array $variantsOptions)
    {
        $path = $this->transformPath($path);

        $host = parse_url($path, PHP_URL_HOST);
        if (! empty($host)) {
            return false;
        }

        $imageInfo = @getimagesize($this->publicDirectory . $path);
        if (! $imageInfo || empty($imageInfo[0]) || empty($imageInfo[1])) {
            return false;
        }

        /** @var int $width */
        $width = $imageInfo[0];
        /** @var int $height */
        $height = $imageInfo[1];
        /** @var int $imageType */
        $imageType = $imageInfo[2];

        $result = [
            'width'  => $width,
            'height' => $height,
            'src'    => $path,
        ];

        $widthToHeightRatio = null;
        $position           = $this->defaultPosition;
        $srcsetWidths       = [];
        $maxSrcsetWidth     = 0;

        // collect proportons, position and srcset widths
        foreach ($variantsOptions as $option) {
            if (is_string($option)) {
                if (strpos($option, ':') !== false) {
                    $proportions = array_map('floatval', explode(':', $option));
                    if (count($proportions) === 2 && $proportions[0] > 0 && $proportions[1] > 0) {
                        // custom width:height ratio
                        $widthToHeightRatio = $proportions[0] / $proportions[1];
                    }
                } else {
                    $position = $option;
                }
            } elseif ($option <= $width) {
                $srcsetWidths[$option] = true;
                /** @var int $maxSrcsetWidth */
                $maxSrcsetWidth = max($maxSrcsetWidth, $option);
            } else {
                // if scalingOptions request width > original width, include original instead
                $srcsetWidths[$width] = true;
                /** @var int $maxSrcsetWidth */
                $maxSrcsetWidth = max($maxSrcsetWidth, $width);
            }
        }

        if ($widthToHeightRatio && $maxSrcsetWidth > 0) {
            // change img width & height attributes to match custom proportions
            $result['width']  = $maxSrcsetWidth;
            $result['height'] = (int) round($maxSrcsetWidth / $widthToHeightRatio);
        } else {
            // default proportions
            $widthToHeightRatio = $width / $height;
            // include original image in srcset
            $srcsetWidths[$width] = true;
        }

        $srcset = [];

        foreach ($this->alternateTypes as $type) {
            if ($type !== $imageType) {
                $srcset[$type] = [];
            }
        }
        $srcset[$imageType] = [];

        /** @var int $newWidth */
        foreach (array_keys($srcsetWidths) as $newWidth) {
            $newHeight = (int) round($newWidth / $widthToHeightRatio);

            $variantData = $this->resize($path, $newWidth, $newHeight, null, $position);
            if (! $variantData || ! file_exists($this->publicDirectory . $variantData['path'])) {
                continue;
            }

            $variantData['path']  = base_url($variantData['path']);
            $srcset[$imageType][] = "{$variantData['path']} {$newWidth}w";
            // if we're using custom proportions, we need to update main img src
            if ($newWidth === $result['width']) {
                $result['src'] = $variantData['path'];
            }

            // alternative types (webp, avif etc)
            foreach (array_keys($srcset) as $type) {
                if ($type === $imageType) {
                    continue;
                }

                $variantData = $this->resize($path, $newWidth, $newHeight, $type, $position);
                if (! $variantData || ! file_exists($this->publicDirectory . $variantData['path'])) {
                    continue;
                }

                $variantData['path'] = base_url($variantData['path']);
                $srcset[$type][]     = "{$variantData['path']} {$newWidth}w";
            }
        }

        if (count($srcset) === 1 && count(reset($srcset)) === 1) {
            // the only image present in srcset is the source file -> no need for srcset
            $srcset = [];
        }

        $result['srcset'] = $srcset;

        return $result;
    }

    /**
     * Resizes an image and/or converts it into another format.
     *
     * @param string      $path      Relative or absolute path to the source image. **Doesn't accept URLs to external images**
     * @param int|null    $width     If null, will be based on height proportionally
     * @param int|null    $height    If null, will be based on width proportionally
     * @param int|null    $imageType A PHP imageType constant, allows to convert image into another format,
     *                               e.g. https://www.php.net/manual/en/function.image-type-to-mime-type.php
     * @param string|null $position  Crop position (e.g 'top', 'top-left', 'center', 'right', 'bottom' etc).
     *
     * @return array{path:string, width:int, height:int}|false Resized image data or `false` if a source file is not
     *                                                         a valid image or belongs to another domain
     */
    public function resize(string $path, ?int $width = null, ?int $height = null, ?int $imageType = null, ?string $position = null, ?int $quality = null)
    {
        $path = $this->transformPath($path);

        $host = parse_url($path, PHP_URL_HOST);
        if (! empty($host)) {
            return false;
        }

        $imageInfo = @getimagesize($this->publicDirectory . $path);
        if (! $imageInfo || empty($imageInfo[0]) || empty($imageInfo[1])) {
            return false;
        }

        $info = pathinfo($path);
        $info['dirname'] ??= '_';
        $info['extension'] ??= '';

        /** @var int $originalWidth */
        $originalWidth = $imageInfo[0];
        /** @var int $originalHeight */
        $originalHeight = $imageInfo[1];

        // uploads/foo.jpg, 48, 48, webp -> /cache/images/uploads/foo_jpg/48x48.webp
        $thumbnailDirectory = "{$this->cacheDirectory}/{$info['dirname']}/{$info['filename']}_{$info['extension']}";

        $originalExtension = image_type_to_extension($imageInfo[2], false);

        $newExtension = null;
        if ($imageType) {
            $newExtension = image_type_to_extension($imageType, false);
        }
        $newExtension ??= $originalExtension;

        if (($width ?? 0) < 0) {
            $width = 0;
        }
        if (($height ?? 0) < 0) {
            $height = 0;
        }
        if (empty($width) || empty($height)) {
            if (empty($width) && empty($height)) {
                $width  = $originalWidth;
                $height = $originalHeight;
            } elseif (empty($width)) {
                $width = (int) round($originalWidth * ($height / $originalHeight));
            } else {
                $height = (int) round($originalHeight * ($width / $originalWidth));
            }
        }

        if ($width === $originalWidth && $height === $originalHeight && $newExtension === $originalExtension) {
            // same size, same extension = no work needed
            return [
                'path'   => $path,
                'width'  => $width,
                'height' => $height,
            ];
        }

        $thumbnailPath = "{$thumbnailDirectory}/{$width}x{$height}.{$newExtension}";

        if (! file_exists($this->publicDirectory . $thumbnailPath)) {
            $position ??= $this->defaultPosition;
            $quality ??= $this->defaultQuality;

            if ($this->reportMissing) {
                $this->missingByPath[$path] ??= [];
                $this->missingByPath[$path][$thumbnailPath] = [
                    'width'     => $width,
                    'height'    => $height,
                    'imageType' => $imageType,
                    'position'  => $position,
                    'quality'   => $quality,
                ];
            }
            if ($this->onDemandGeneration) {
                @mkdir($this->publicDirectory . $thumbnailDirectory, 0777, true);

                $image = Services::image()->withFile($this->publicDirectory . $path)
                    ->fit($width, $height, $position);

                if ($imageType) {
                    $image->convert($imageType);
                }

                $image->save($this->publicDirectory . $thumbnailPath, $quality);
            }
        }

        return [
            'path'   => $thumbnailPath,
            'width'  => $width,
            'height' => $height,
        ];
    }

    /**
     * Deletes resized/converted variants of image(-s) from the cache folder
     */
    public function clearCache(?string $path = null): void
    {
        helper('filesystem');

        if (empty($path)) {
            if (is_dir($this->publicDirectory . $this->cacheDirectory)) {
                delete_files($this->publicDirectory . $this->cacheDirectory, true);
            }

            @rmdir($this->publicDirectory . $this->cacheDirectory);

            return;
        }

        $path = $this->transformPath($path);

        $info = pathinfo($path);
        $info['dirname'] ??= '_';
        $info['extension'] ??= '';

        $thumbDir = "{$this->cacheDirectory}/{$info['dirname']}/{$info['filename']}_{$info['extension']}";

        if (is_dir($this->publicDirectory . $thumbDir)) {
            delete_files($this->publicDirectory . $thumbDir, true);
        }

        @rmdir($this->publicDirectory . $thumbDir);
    }

    /**
     * Deletes image and its resized/converted variants in the cache folder
     */
    public function delete(string $path): void
    {
        $path = $this->transformPath($path);

        $this->clearCache($path);

        @unlink($this->publicDirectory . $path);
    }

    /**
     * Transforms absolute path into a relative one. Transforms external URL into a relative path
     * if the URL domain matches config domain.
     */
    public function transformPath(string $path): string
    {
        $path = trim($path);

        // absolute -> relative path
        if (strpos($path, $this->publicDirectory) === 0) {
            $path = substr($path, strlen($this->publicDirectory));
        }

        // remove host from the path if it belongs to this website
        $host = parse_url($path, PHP_URL_HOST);
        if (! empty($host)) {
            $baseUrl = base_url();
            if (strpos($path, $baseUrl) === 0) {
                $path = substr($path, strlen($baseUrl));
            }
        }

        return ltrim($path, '/');
    }
}
