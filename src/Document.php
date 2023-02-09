<?php

declare(strict_types=1);

namespace Rinart73\DocumentHelper;

use Closure;
use Rinart73\DocumentHelper\Traits\HTMLOperations;

/**
 * Provides the means to ease the construction of HTML document `html`, `head` and `body` tags.
 * Introduces style/script dependency system.
 */
class Document
{
    use HTMLOperations;

    protected string $publicDirectory;

    /**
     * @var array<string, string|null>
     */
    protected array $htmlAttributes = [];

    /**
     * @var array<string, string|null>
     */
    protected array $bodyAttributes = [];

    protected string $title       = '';
    protected string $titleSuffix = '';

    /**
     * @var array<string, array<string, string>>
     */
    protected array $metaByName = [];

    protected string $customHeadContent   = '';
    protected string $customFooterContent = '';

    /**
     * @var array<string, array<array<string, string>>>
     */
    protected array $linksByRel = [];

    /**
     * @var array<string, Style>
     */
    protected array $stylesByHandle = [];

    /**
     * @var array<string, bool>
     */
    protected array $addedStylesHandles = [];

    /**
     * @var array<string, bool>
     */
    protected array $renderedStylesHandles = [];

    /**
     * @var array<string, Script>
     */
    protected array $scriptsByHandle = [];

    /**
     * @var array<string, bool>
     */
    protected array $addedScriptsHandles = [];

    /**
     * Script handles that were already turned into tags and rendered
     *
     * @var array<string, bool>
     */
    protected array $renderedScriptsHandles = [];

    /**
     * Adding script will automatically add a style with the same handle
     */
    protected bool $styleAddedByScript = true;

    /**
     * When `true`, adding and removing styles and scripts won't work
     */
    protected bool $assetShiftsIgnored = false;

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
     * Retrieves one or all `html` tag attributes
     *
     * @return array<string, string|null>|string|null
     */
    public function getHtmlAttributes(?string $name = null)
    {
        if ($name === null) {
            return $this->htmlAttributes;
        }

        return $this->htmlAttributes[$name] ?? null;
    }

    /**
     * Overrides existing `html` tag attributes with new ones
     *
     * @param array<string, string|null> $attributes
     *
     * @return $this
     */
    public function setHtmlAttributes(array $attributes)
    {
        $this->htmlAttributes = $attributes;

        return $this;
    }

    /**
     * Merges new `html` tag attributes with existing ones.
     * Use value `null` to remove attribute. Use `''` to add attribute without value.
     *
     * @param array<string, string|null> $attributes
     *
     * @return $this
     */
    public function addHtmlAttributes(array $attributes)
    {
        $this->htmlAttributes = array_merge($this->htmlAttributes, $attributes);

        return $this;
    }

    /**
     * Adds new `html` tag classes to the existing ones
     *
     * @return $this
     */
    public function addHtmlClasses(string ...$classes)
    {
        if (empty($this->htmlAttributes['class'])) {
            $this->htmlAttributes['class'] = implode(' ', $classes);
        } else {
            $this->htmlAttributes['class'] .= ' ' . implode(' ', $classes);
        }

        return $this;
    }

    /**
     * Retrieves one or all `body` tag attributes
     *
     * @return array<string, string|null>|string|null
     */
    public function getBodyAttributes(?string $name = null)
    {
        if ($name === null) {
            return $this->bodyAttributes;
        }

        return $this->bodyAttributes[$name] ?? null;
    }

    /**
     * Completely overrides existing `body` tag attributes with new ones
     *
     * @param array<string, string|null> $attributes
     *
     * @return $this
     */
    public function setBodyAttributes(array $attributes)
    {
        $this->bodyAttributes = $attributes;

        return $this;
    }

    /**
     * Merges new `body` tag attributes with existing ones.
     * Use value `null` to remove attribute. Use value `''` to add attribute without value.
     *
     * @param array<string, string|null> $attributes
     *
     * @return $this
     */
    public function addBodyAttributes(array $attributes)
    {
        $this->bodyAttributes = array_merge($this->bodyAttributes, $attributes);

        return $this;
    }

    /**
     * Adds new `body` tag classes to the existing ones
     *
     * @return $this
     */
    public function addBodyClasses(string ...$classes)
    {
        if (empty($this->bodyAttributes['class'])) {
            $this->bodyAttributes['class'] = implode(' ', $classes);
        } else {
            $this->bodyAttributes['class'] .= ' ' . implode(' ', $classes);
        }

        return $this;
    }

    /**
     * Get the `title` tag content
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the `title` tag content
     *
     * @return $this
     */
    public function setTitle(string $title)
    {
        $this->title = trim($title);

        return $this;
    }

    /**
     * Get the value of `title` tag suffix
     */
    public function getTitleSuffix(): string
    {
        return $this->titleSuffix;
    }

    /**
     * Set the value of `title` tag suffix. It will be added after the main `title` content, before the closing `title` tag.
     *
     * @return $this
     */
    public function setTitleSuffix(string $titleSuffix)
    {
        $this->titleSuffix = trim($titleSuffix);

        return $this;
    }

    /**
     * Retrieve data of one or all `meta` tags
     *
     * @return array<string, array<string, string>>|array<string, string>|null
     */
    public function getMeta(?string $name = null)
    {
        if ($name === null) {
            return $this->metaByName;
        }

        return $this->metaByName[strtolower(trim($name))] ?? null;
    }

    /**
     * Adds/overrides `meta` tag content. Automatically detects whether `name`, `property` or `http-equiv` should be used
     *
     * @param array<string, string> $attributes
     *
     * @return $this
     */
    public function setMeta(string $name, string $content, array $attributes = [])
    {
        $name = strtolower(trim($name));

        switch($name) {
            case 'charset':
                $data = ['charset' => $content];
                break;

            case 'content-security-policy':
            case 'content-type':
            case 'default-style':
            case 'x-ua-compatible':
            case 'refresh':
                $data = ['http-equiv' => $name, 'content' => $content];
                break;

            default:
                $useProperty = false;
                if (strpos($name, ':') !== false) {
                    $parts = explode(':', $name);
                    if ($parts[0] !== 'twitter') {
                        // Open Graph property
                        $useProperty = true;
                    }
                }

                $data = $useProperty ? ['property' => $name, 'content' => $content] : ['name' => $name, 'content' => $content];
        }

        $this->metaByName[$name] = array_merge($data, $attributes);

        return $this;
    }

    /**
     * Removes one or all `meta` tags
     *
     * @return $this
     */
    public function removeMeta(?string $name = null)
    {
        if ($name === null) {
            $this->metaByName = [];
        } else {
            unset($this->metaByName[strtolower(trim($name))]);
        }

        return $this;
    }

    /**
     * Retrieves custom `head` tag content
     */
    public function getCustomHeadContent(): string
    {
        return $this->customHeadContent;
    }

    /**
     * Sets custom content that will be placed inside the `head` tag after links but before styles and head scripts
     *
     * @return $this
     */
    public function setCustomHeadContent(string $customHeadContent)
    {
        $this->customHeadContent = $customHeadContent;

        return $this;
    }

    /**
     * Retrieves custom footer content
     */
    public function getCustomFooterContent(): string
    {
        return $this->customFooterContent;
    }

    /**
     * Sets custom content that will be placed before footer scripts
     *
     * @return $this
     */
    public function setCustomFooterContent(string $customFooterContent)
    {
        $this->customFooterContent = $customFooterContent;

        return $this;
    }

    /**
     * Retrieves all `link` tag data or items with the same 'rel'
     *
     * @return array<array<string, string>>|array<string, array<array<string, string>>>|null
     */
    public function getLinks(?string $rel = null)
    {
        if ($rel === null) {
            return $this->linksByRel;
        }

        return $this->linksByRel[strtolower(trim($rel))] ?? null;
    }

    /**
     * Adds `link` tag or updates existing one with the same `rel` and `href`
     *
     * @param array<string, string> $attributes
     *
     * @return $this
     */
    public function addLink(string $rel, string $href, array $attributes = [])
    {
        $rel  = strtolower(trim($rel));
        $href = trim($href);

        $this->linksByRel[$rel] ??= [];

        foreach ($this->linksByRel[$rel] as $index => $link) {
            if ($link['href'] === $href) {
                // update attributes
                $this->linksByRel[$rel][$index] = array_merge($this->linksByRel[$rel][$index], $attributes);

                return $this;
            }
        }

        // add new tag
        $this->linksByRel[$rel][] = array_merge(['href' => $href], $attributes);

        return $this;
    }

    /**
     * Removes `link` tag data with matching `rel` and `href` (if specified)
     *
     * @return $this
     */
    public function removeLinks(?string $rel = null, ?string $href = null)
    {
        if ($rel === null) {
            $this->linksByRel = [];
        } elseif ($href === null) {
            unset($this->linksByRel[strtolower(trim($rel))]);
        } else {
            $href = trim($href);

            foreach ($this->linksByRel[strtolower(trim($rel))] as $index => $link) {
                if ($link['href'] === $href) {
                    unset($this->linksByRel[$rel][$index]);

                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Retrieves one or all registered styles
     *
     * @return Style|Style[]|null
     */
    public function getStyles(?string $handle = null)
    {
        if ($handle === null) {
            return $this->stylesByHandle;
        }

        return $this->stylesByHandle[trim($handle)] ?? null;
    }

    /**
     * Returns style handles that were explicitly added to the document
     *
     * @return array<string, bool>
     */
    // TODO: Deep search that includes dependencies
    public function getAddedStyles()
    {
        return $this->addedStylesHandles;
    }

    /**
     * Checks if a style was added in the document
     */
    // TODO: Deep search that includes dependencies
    public function hasAddedStyle(string $handle): bool
    {
        return isset($this->addedStylesHandles[trim($handle)]);
    }

    /**
     * Retrieves handles of styles that were already rendered in the document
     *
     * @return array<string, bool>
     */
    public function getRenderedStyles(): array
    {
        return $this->renderedStylesHandles;
    }

    /**
     * Registers a style. **Can overwrite already registered style.**
     *
     * @param string                $handle       Unique style identifier
     * @param string                $src          Relative or absolute URL of a CSS file
     * @param string[]              $dependencies Handles that correspond with styles that should be automatically added before this one
     * @param string|true           $version      Version number, will be added as `?ver=$version` query. If `true` will use file modification time instead
     * @param array<string, string> $attributes   Custom attributes (such as integrity or crossorigin)
     *
     * @return $this
     */
    public function registerStyle(string $handle, string $src, array $dependencies = [], $version = '', array $attributes = [])
    {
        if ($this->assetShiftsIgnored) {
            return $this;
        }

        $handle = trim($handle);

        $style = new Style($handle, $dependencies);
        $style->setSrc($src)
            ->setVersion($version)
            ->setAttributes($attributes);

        $this->stylesByHandle[$handle] = $style;

        return $this;
    }

    /**
     * Registers a style with inline CSS. **Can overwrite already registered style.**
     *
     * @param string                $handle       Unique style identifier
     * @param string                $inline       Valid CSS
     * @param string[]              $dependencies Handles that correspond with styles that should be automatically added before this one
     * @param array<string, string> $attributes   Custom attributes
     *
     * @return $this
     */
    public function registerInlineStyle(string $handle, string $inline, array $dependencies = [], array $attributes = [])
    {
        if ($this->assetShiftsIgnored) {
            return $this;
        }

        $handle = trim($handle);

        $style = new Style($handle, $dependencies);
        $style->setInline($inline)
            ->setAttributes($attributes);

        $this->stylesByHandle[$handle] = $style;

        return $this;
    }

    /**
     * Sets a function that will be executed before the style is rendered
     * to the document. **Styles and scripts cannot be registered, added or removed inside the function**.
     *
     * @param Closure $preparation (Document $document, Style $style)
     *
     * @return $this
     */
    public function prepareStyle(string $handle, Closure $preparation)
    {
        $style = $this->stylesByHandle[trim($handle)] ?? null;
        if ($style) {
            $style->setPreparation($preparation);
        }

        return $this;
    }

    /**
     * Adds previously registered styles to the document
     *
     * @return $this
     */
    public function addStyles(string ...$handles)
    {
        if ($this->assetShiftsIgnored) {
            return $this;
        }

        foreach ($handles as $handle) {
            $this->addedStylesHandles[trim($handle)] = true;
        }

        return $this;
    }

    /**
     * Removes styles from the document (**but doesn't unregister them**). If another style depends
     * on the removed style, it will be added and rendered anyway
     *
     * @return $this
     */
    public function removeStyles(string ...$handles)
    {
        if ($this->assetShiftsIgnored) {
            return $this;
        }

        foreach ($handles as $handle) {
            unset($this->addedStylesHandles[trim($handle)]);
        }

        return $this;
    }

    /**
     * Retrieves registered scripts
     *
     * @return Script|Script[]|null
     */
    public function getScripts(?string $handle = null)
    {
        if ($handle === null) {
            return $this->scriptsByHandle;
        }

        return $this->scriptsByHandle[trim($handle)] ?? null;
    }

    /**
     * Returns script handles that were explicitly added to the document
     *
     * @return array<string, bool>
     */
    // TODO: Deep search that includes dependencies
    public function getAddedScripts()
    {
        return $this->addedScriptsHandles;
    }

    /**
     * Checks if a script was added in the document
     */
    // TODO: Deep search that includes dependencies
    public function hasAddedScript(string $handle): bool
    {
        return isset($this->addedScriptsHandles[trim($handle)]);
    }

    /**
     * Retrieves handles of scripts that were already rendered in the document
     *
     * @return array<string, bool>
     */
    public function getRenderedScripts(): array
    {
        return $this->renderedScriptsHandles;
    }

    /**
     * Registers a script. **Can overwrite already registered script.**
     *
     * @param string                $handle       Unique script identifier
     * @param string                $src          Relative or absolute URL of a JS file
     * @param string[]              $dependencies Handles that correspond with scripts that should be automatically added before this one
     * @param string|true           $version      Version number, will be added as `?ver=$version` query. If `true` will use file modification time instead
     * @param array<string, string> $attributes   Custom attributes (such as integrity or crossorigin)
     * @param bool                  $inFooter     If the script should be in footer (`true`) or in head (`false`).
     *
     * @return $this
     */
    public function registerScript(string $handle, string $src, array $dependencies = [], $version = '', array $attributes = [], bool $inFooter = true)
    {
        if ($this->assetShiftsIgnored) {
            return $this;
        }

        $handle = trim($handle);

        $script = new Script($handle, $dependencies);
        $script->setSrc($src)
            ->setVersion($version)
            ->setAttributes($attributes)
            ->setInFooter($inFooter);

        $this->scriptsByHandle[$handle] = $script;

        return $this;
    }

    /**
     * Registers a script with inline JS. **Can overwrite already registered script.**
     *
     * @param string                $handle       Unique script identifier.
     * @param string                $inline       Valid JS.
     * @param string[]              $dependencies Handles that correspond with scripts that should be automatically added before this one
     * @param array<string, string> $attributes   Custom attributes
     * @param bool                  $inFooter     If the script should be in footer (`true`) or in head (`false`).
     *
     * @return $this
     */
    public function registerInlineScript(string $handle, string $inline, array $dependencies = [], array $attributes = [], $inFooter = true)
    {
        if ($this->assetShiftsIgnored) {
            return $this;
        }

        $handle = trim($handle);

        $script = new Script($handle, $dependencies);
        $script->setInline($inline)
            ->setAttributes($attributes)
            ->setInFooter($inFooter);

        $this->scriptsByHandle[$handle] = $script;

        return $this;
    }

    /**
     * Sets a function that will be executed before the script is rendered
     * to the document. **Styles and scripts cannot be registered, added or removed inside the function**.
     *
     * @param Closure $preparation (Document $document, Script $script)
     *
     * @return $this
     */
    public function prepareScript(string $handle, Closure $preparation)
    {
        $script = $this->scriptsByHandle[trim($handle)] ?? null;
        if ($script) {
            $script->setPreparation($preparation);
        }

        return $this;
    }

    /**
     * Adds a script with a JSON serialized data `var $objectName = {'key': 'value'..}` before
     * the script with specified handle. **Doesn't automatically add the main script**.
     *
     * @param string              $handle     Prepend data before this handle.
     * @param string              $objectName Name for a global variable that will contain data.
     * @param array<mixed, mixed> $data
     *
     * @return $this
     */
    public function localizeScript(string $handle, string $objectName, array $data)
    {
        $script = $this->scriptsByHandle[trim($handle)] ?? null;
        if ($script) {
            $script->setLocalizationName($objectName)
                ->setLocalizationData($data);
        }

        return $this;
    }

    /**
     * Adds previously registered scripts to the document
     *
     * @return $this
     */
    public function addScripts(string ...$handles)
    {
        if ($this->assetShiftsIgnored) {
            return $this;
        }

        foreach ($handles as $handle) {
            $this->addedScriptsHandles[trim($handle)] = true;
        }

        return $this;
    }

    /**
     * Removes scripts from this document (**but doesn't unregister them**).
     * If another script depends on the removed script, it will be added and rendered anyway.
     *
     * @return $this
     */
    public function removeScripts(string ...$handles)
    {
        if ($this->assetShiftsIgnored) {
            return $this;
        }

        foreach ($handles as $handle) {
            unset($this->addedScriptsHandles[trim($handle)]);
        }

        return $this;
    }

    /**
     * Adds previously registered styles and scripts
     *
     * @return $this
     */
    public function addLibraries(string ...$handles)
    {
        if ($this->assetShiftsIgnored) {
            return $this;
        }

        $this->addStyles(...$handles);
        $this->addScripts(...$handles);

        return $this;
    }

    /**
     * Check if scripts try to add styles with the same handle
     */
    public function isStyleAddedByScript(): bool
    {
        return $this->styleAddedByScript;
    }

    /**
     * If true, added scripts will try to add styles with the same handle
     *
     * @return $this
     */
    public function setStyleAddedByScript(bool $styleAddedByScript)
    {
        $this->styleAddedByScript = $styleAddedByScript;

        return $this;
    }

    /**
     * Builds an array of `html` tag attributes
     *
     * @return array<string, string|null>
     */
    public function buildHtml(): array
    {
        if (! empty($this->htmlAttributes['class'])) {
            // remove duplicate classes and extra spaces
            $classes                       = preg_replace('/\s+/', ' ', trim($this->htmlAttributes['class']));
            $classes                       = array_unique(explode(' ', $classes));
            $this->htmlAttributes['class'] = implode(' ', $classes);
        }

        return $this->htmlAttributes;
    }

    /**
     * Renders `html` tag attributes
     */
    public function renderHtml(): string
    {
        return $this->renderAttributes($this->buildHtml());
    }

    /**
     * Builds an array of tags that go inside of the `head` tag
     *
     * @return string[]
     */
    public function buildHead(): array
    {
        $result = [];

        $this->assetShiftsIgnored = true;

        // process scripts and styles before everything else, because they may have prepare functions

        if ($this->styleAddedByScript) {
            // simulate full run with head + footer scripts to get all script handles used in the document
            foreach (array_keys($this->resolveDependencies($this->scriptsByHandle, $this->addedScriptsHandles, [], true)) as $handle) {
                $this->addedStylesHandles[$handle] = true;
            }
        }

        $styles = [];

        foreach ($this->resolveDependencies($this->stylesByHandle, $this->addedStylesHandles) as $handle => $style) {
            $prepare = $style->getPreparation();
            if ($prepare) {
                $prepare($this, $style);
            }

            $styles[]                             = $style->render();
            $this->renderedStylesHandles[$handle] = true;
        }

        // head scripts
        $scripts = [];

        foreach ($this->resolveDependencies($this->scriptsByHandle, $this->addedScriptsHandles) as $handle => $script) {
            $prepare = $script->getPreparation();
            if ($prepare) {
                $prepare($this, $script);
            }

            $scripts[]                             = $script->render();
            $this->renderedScriptsHandles[$handle] = true;
        }

        // meta tags that specify encoding should go first
        foreach (['charset', 'content-type'] as $name) {
            if (! empty($this->metaByName[$name])) {
                $result[] = sprintf('<meta %s />', $this->renderAttributes($this->metaByName[$name]));
            }
        }

        if (! empty($this->title)) {
            $title = trim($this->title);
            if (! empty($this->titleSuffix)) {
                $title .= ' ' . trim($this->titleSuffix);
            }
            $result[] = '<title>' . esc($title) . '</title>';
        }

        foreach ($this->metaByName as $name => $meta) {
            if ($name !== 'charset' && $name !== 'content-type') {
                $result[] = sprintf('<meta %s />', $this->renderAttributes($meta));
            }
        }

        foreach ($this->linksByRel as $rel => $relGroup) {
            foreach ($relGroup as $link) {
                $linkAttributes = array_merge(['rel' => $rel], $link);
                $result[]       = sprintf('<link %s />', $this->renderAttributes($linkAttributes));
            }
        }

        if (! empty($this->customHeadContent)) {
            $result[] = $this->customHeadContent;
        }

        $this->assetShiftsIgnored = false;

        return [...$result, ...$styles, ...$scripts];
    }

    /**
     * Renders tags that go inside of the `head` tag
     */
    public function renderHead(): string
    {
        return implode("\n", $this->buildHead());
    }

    /**
     * Builds an array of `body` tag attributes
     *
     * @return array<string, string|null>
     */
    public function buildBody(): array
    {
        if (! empty($this->bodyAttributes['class'])) {
            // remove duplicate classes and extra spaces
            $classes                       = preg_replace('/\s+/', ' ', trim($this->bodyAttributes['class']));
            $classes                       = array_unique(explode(' ', $classes));
            $this->bodyAttributes['class'] = implode(' ', $classes);
        }

        return $this->bodyAttributes;
    }

    /**
     * Renders `body` tag attributes
     */
    public function renderBody(): string
    {
        return $this->renderAttributes($this->buildBody());
    }

    /**
     * Builds an array of tags that go in footer before the closing `body` tag
     *
     * @return string[]
     */
    public function buildFooter(): array
    {
        $result = [];

        $this->assetShiftsIgnored = true;

        // process scripts before everything else, because they may have prepare functions

        // footer scripts and late head scripts
        $scripts = [];

        foreach ($this->resolveDependencies($this->scriptsByHandle, $this->addedScriptsHandles, $this->renderedScriptsHandles, true) as $handle => $script) {
            $prepare = $script->getPreparation();
            if ($prepare) {
                $prepare($this, $script);
            }

            $scripts[]                             = $script->render();
            $this->renderedScriptsHandles[$handle] = true;

            if ($this->styleAddedByScript) {
                $this->addedStylesHandles[$handle] = true;
            }
        }

        if (! empty($this->customFooterContent)) {
            $result[] = $this->customFooterContent;
        }

        // styles 'prepare' function would be useless here, so it's not called
        foreach ($this->resolveDependencies($this->stylesByHandle, $this->addedStylesHandles, $this->renderedStylesHandles, true) as $handle => $style) {
            $result[]                             = $style->render();
            $this->renderedStylesHandles[$handle] = true;
        }

        $this->assetShiftsIgnored = false;

        return [...$result, ...$scripts];
    }

    /**
     * Renders tags that go in footer before the closing `body` tag
     */
    public function renderFooter(): string
    {
        return implode("\n", $this->buildFooter());
    }

    /**
     * Resolves script/style dependencies and returns a map of items in a proper order
     *
     * @param array<string, AssetInterface> $registeredItems
     * @param array<string, bool>           $addedItems
     * @param array<string, bool>           $renderedItems
     *
     * @return array<string, AssetInterface>
     */
    protected function resolveDependencies(array $registeredItems, array $addedItems, array $renderedItems = [], bool $isFooter = false): array
    {
        $result = [];

        $stack                   = array_reverse(array_keys($addedItems));
        $isDependenciesProcessed = [];

        while ($stack !== []) {
            $handle = array_pop($stack);

            // already added by other item or already rendered
            if (isset($result[$handle]) || isset($renderedItems[$handle])) {
                continue;
            }

            $item = $registeredItems[$handle] ?? null;
            if (! $item) {
                continue;
            }

            /**
             * Head scripts force all of their dependencies to go in head before them.
             * Styles are not affected because they should always go in head.
             */
            $itemInFooter = false;
            if ($item instanceof Script) {
                $itemInFooter = $item->isInFooter();
            }

            $forceDependenciesInHead = false;
            if (! $isFooter && ! $itemInFooter) {
                $forceDependenciesInHead = true;
            }

            if (empty($item->getDependencies()) || isset($isDependenciesProcessed[$handle])) {
                // don't put footer scripts in head
                if (! $isFooter && $itemInFooter) {
                    continue;
                }

                $result[$handle] = $item;

                continue;
            }

            // this item will be processed again after its dependencies
            $stack[]                          = $handle;
            $isDependenciesProcessed[$handle] = true;

            foreach ($item->getDependencies() as $dependencyHandle) {
                $stack[] = $dependencyHandle;

                if ($forceDependenciesInHead) {
                    $dependencyItem = $registeredItems[$dependencyHandle] ?? null;
                    if ($dependencyItem && $dependencyItem instanceof Script) {
                        $dependencyItem->setInFooter(false);
                    }
                }
            }
        }

        return $result;
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
