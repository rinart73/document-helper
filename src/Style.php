<?php

declare(strict_types=1);

namespace Rinart73\DocumentHelper;

use Rinart73\DocumentHelper\Config\Services;

class Style extends Asset
{
    /**
     * Builds and renders a `style`/`link` tag depending on if the style is inline or not
     */
    public function render(): string
    {
        $attributes = $this->attributes;

        if (! empty($this->inline)) {
            $attributes = array_merge([
                'id' => "{$this->handle}-inline-css",
            ], $attributes);

            return sprintf('<style %s>%s</style>', $this->renderAttributes($attributes), $this->inline);
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
            'id'   => "{$this->handle}-css",
            'rel'  => 'stylesheet',
            'href' => $src,
        ], $attributes);

        return sprintf('<link %s />', $this->renderAttributes($attributes));
    }
}
