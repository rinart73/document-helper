<?php

declare(strict_types=1);

namespace Rinart73\DocumentHelper;

use Rinart73\DocumentHelper\Config\Services;

class Script extends Asset
{
    protected bool $inFooter           = true;
    protected string $localizationName = '';

    /**
     * @var array<mixed, mixed>
     */
    protected array $localizationData = [];

    /**
     * Check if the script should be rendered in footer
     */
    public function isInFooter(): bool
    {
        return $this->inFooter;
    }

    /**
     * Set if the script should be rendered in footer
     *
     * @return $this
     */
    public function setInFooter(bool $inFooter)
    {
        $this->inFooter = $inFooter;

        return $this;
    }

    /**
     * Get the name of the variable that will hold localization data
     */
    public function getLocalizationName(): string
    {
        return $this->localizationName;
    }

    /**
     * Set the name of the variable that will hold localization data
     *
     * @return $this
     */
    public function setLocalizationName(string $localizationName)
    {
        $this->localizationName = trim($localizationName);

        return $this;
    }

    /**
     * Get localization data
     *
     * @return array<mixed, mixed>
     */
    public function getLocalizationData(): array
    {
        return $this->localizationData;
    }

    /**
     * Set localization data
     *
     * @param array<mixed, mixed> $localizationData
     *
     * @return $this
     */
    public function setLocalizationData(array $localizationData)
    {
        $this->localizationData = $localizationData;

        return $this;
    }

    /**
     * Builds and renders a `script` tag
     */
    public function render(): string
    {
        $attributes = $this->attributes;

        if (! empty($this->inline)) {
            $attributes = array_merge([
                'id' => "{$this->handle}-inline-js",
            ], $attributes);

            return sprintf('<script %s>%s</script>', $this->renderAttributes($attributes), $this->inline);
        }

        $result = [];

        if (! empty($this->localizationData)) {
            $result[] = sprintf(
                '<script id="%s-js-extra">var %s = %s;</script>',
                esc($this->handle),
                esc($this->localizationName, 'js'),
                json_encode($this->localizationData, JSON_UNESCAPED_UNICODE)
            );
        }

        $document = Services::document();
        $src      = $document->transformPath($this->src);
        $version  = $this->version;

        if (parse_url($src, PHP_URL_HOST) === null) {
            if ($version === true) {
                $version = filemtime($document->getPublicDirectory() . $src);
            }

            // turn relative path ino URL
            $src = base_url($src);
        } elseif ($version === true) {
            $version = '';
        }

        if (! empty($version)) {
            $src .= '?ver=' . esc($version, 'url');
        }

        $attributes = array_merge([
            'id'  => "{$this->handle}-js",
            'src' => $src,
        ], $attributes);

        $result[] = sprintf('<script %s></script>', $this->renderAttributes($attributes));

        return implode("\n", $result);
    }
}
