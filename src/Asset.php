<?php

declare(strict_types=1);

namespace Rinart73\DocumentHelper;

use Closure;
use Rinart73\DocumentHelper\Traits\HTMLOperations;

abstract class Asset implements AssetInterface
{
    use HTMLOperations;

    protected string $handle;
    protected string $src    = '';
    protected string $inline = '';

    /**
     * @var string[]
     */
    protected array $dependencies;

    /**
     * @var string|true
     */
    protected $version = '';

    /**
     * @var array<string, string>
     */
    protected array $attributes = [];

    protected ?Closure $preparation = null;

    /**
     * @param string   $handle       Unique identifier
     * @param string[] $dependencies List of handles that should be added before this item
     */
    public function __construct(string $handle, array $dependencies = [])
    {
        $this->handle       = trim($handle);
        $this->dependencies = $dependencies;
    }

    /**
     * Get the value of handle
     */
    public function getHandle(): string
    {
        return $this->handle;
    }

    /**
     * Get the value of src
     */
    public function getSrc(): string
    {
        return $this->src;
    }

    /**
     * Set the value of src
     *
     * @return $this
     */
    public function setSrc(string $src)
    {
        $this->src    = trim($src);
        $this->inline = '';

        return $this;
    }

    /**
     * Get the inline content
     */
    public function getInline(): string
    {
        return $this->inline;
    }

    /**
     * Set inline content
     *
     * @return $this
     */
    public function setInline(string $inline)
    {
        $this->inline = $inline;
        $this->src    = '';

        return $this;
    }

    /**
     * Get list of handles that this item depends on
     *
     * @return string[]
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Get the value of version
     *
     * @return string|true
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set version number. Will be added as `?ver=$version` to the src. If `true` will use file modification time instead
     *
     * @param string|true $version
     *
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version === true ? true : trim($version);

        return $this;
    }

    /**
     * Get custom attributes
     *
     * @return array<string, string>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set custom attributes that will be included in the final tag
     *
     * @param array<string, string> $attributes
     *
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Get preparation function
     */
    public function getPreparation(): ?Closure
    {
        return $this->preparation;
    }

    /**
     * Set the function that is executed when the item is rendered
     * to the document. **Styles and scripts cannot be registered, added or removed inside the function**.
     *
     * @param Closure $preparation (Document $document, Asset $style)
     *
     * @return $this
     */
    public function setPreparation(?Closure $preparation)
    {
        $this->preparation = $preparation;

        return $this;
    }
}
