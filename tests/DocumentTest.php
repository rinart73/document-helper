<?php

declare(strict_types=1);

use Rinart73\DocumentHelper\Config\Services;
use Rinart73\DocumentHelper\Document;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class DocumentTest extends TestCase
{
    private Document $document;

    protected function setUp(): void
    {
        $this->resetServices();

        $this->document = Services::document();

        parent::setUp();
    }

    public function testSetHtmlAttributes(): void
    {
        $this->document->setHtmlAttributes([
            'lang'    => 'en-US',
            'escaped' => ' "test" ',
            'novalue' => '',
            'removed' => null,
        ]);

        $this->assertNull($this->document->getHtmlAttributes('non-existant'));
        $this->assertSame('en-US', $this->document->getHtmlAttributes('lang'));

        $expected = [
            'lang'    => 'en-US',
            'escaped' => ' "test" ',
            'novalue' => '',
            'removed' => null,
        ];

        $this->assertSame($expected, $this->document->getHtmlAttributes());
        $this->assertSame($expected, $this->document->buildHtml());
        $this->assertSame('lang="en-US" escaped="&quot;test&quot;" novalue', $this->document->renderHtml());
    }

    public function testAddHtmlAttributes(): void
    {
        $this->document->setHtmlAttributes([
            'lang'      => 'en-US',
            'removable' => '123',
        ])
            ->addHtmlAttributes([
                'lang'      => 'en-GB',
                'removable' => null,
                'class'     => 'some',
            ]);

        $expected = [
            'lang'      => 'en-GB',
            'removable' => null,
            'class'     => 'some',
        ];

        $this->assertSame($expected, $this->document->getHtmlAttributes());
        $this->assertSame($expected, $this->document->buildHtml());
        $this->assertSame('lang="en-GB" class="some"', $this->document->renderHtml());
    }

    public function testAddHtmlClasses(): void
    {
        $this->document->addHtmlClasses('one', 'two')
            ->addHtmlClasses('one ');

        $this->assertSame([
            'class' => 'one two one ',
        ], $this->document->getHtmlAttributes());

        $this->assertSame([
            'class' => 'one two',
        ], $this->document->buildHtml());

        $this->assertSame('class="one two"', $this->document->renderHtml());
    }

    public function testSetBodyAttributes(): void
    {
        $this->document->setBodyAttributes([
            'class'   => 'page-article',
            'escaped' => ' "test" ',
            'novalue' => '',
            'removed' => null,
        ]);

        $this->assertNull($this->document->getBodyAttributes('non-existant'));
        $this->assertSame('page-article', $this->document->getBodyAttributes('class'));

        $expected = [
            'class'   => 'page-article',
            'escaped' => ' "test" ',
            'novalue' => '',
            'removed' => null,
        ];

        $this->assertSame($expected, $this->document->getBodyAttributes());
        $this->assertSame($expected, $this->document->buildBody());
        $this->assertSame('class="page-article" escaped="&quot;test&quot;" novalue', $this->document->renderBody());
    }

    public function testAddBodyAttributes(): void
    {
        $this->document->setBodyAttributes([
            'class'     => 'page-home',
            'removable' => '123',
        ])
            ->addBodyAttributes([
                'class'     => 'page-article',
                'removable' => null,
                'data-var'  => 'some',
            ]);

        $expected = [
            'class'     => 'page-article',
            'removable' => null,
            'data-var'  => 'some',
        ];

        $this->assertSame($expected, $this->document->getBodyAttributes());
        $this->assertSame($expected, $this->document->buildBody());
        $this->assertSame('class="page-article" data-var="some"', $this->document->renderBody());
    }

    public function testAddBodyClasses(): void
    {
        $this->document->addBodyClasses('one', 'two')
            ->addBodyClasses('one ');

        $this->assertSame([
            'class' => 'one two one ',
        ], $this->document->getBodyAttributes());

        $this->assertSame([
            'class' => 'one two',
        ], $this->document->buildBody());

        $this->assertSame('class="one two"', $this->document->renderBody());
    }

    public function testTitle(): void
    {
        $this->document->setTitle(' My article | WebSite ');

        $this->assertSame('My article | WebSite', $this->document->getTitle());
        $this->assertSame(['<title>My article | WebSite</title>'], $this->document->buildHead());
        $this->assertSame('<title>My article | WebSite</title>', $this->document->renderHead());
    }

    public function testTitleSuffix(): void
    {
        $this->document->setTitleSuffix('| WebSite  ')
            ->setTitle(' My article');

        $this->assertSame('| WebSite', $this->document->getTitleSuffix());
        $this->assertSame('My article', $this->document->getTitle());
        $this->assertSame(['<title>My article | WebSite</title>'], $this->document->buildHead());
        $this->assertSame('<title>My article | WebSite</title>', $this->document->renderHead());
    }

    public function testSetMeta(): void
    {
        $this->document->setMeta('viewport', ' width=device-width, initial-scale=1 ')
            ->setMeta('Content-Security-Policy', "default-src 'self'")
            ->setMeta('charset', 'utf-8')
            ->setMeta('og:type', 'article')
            ->setMeta('twitter:image', 'https://example.com/path/to/image.jpg', ['test' => 'value']);

        $this->assertNull($this->document->getMeta('non-existant'));

        $this->assertSame([
            'http-equiv' => 'content-security-policy',
            'content'    => "default-src 'self'",
        ], $this->document->getMeta('Content-Security-Policy'));

        $this->assertSame([
            'viewport' => [
                'name'    => 'viewport',
                'content' => ' width=device-width, initial-scale=1 ',
            ],
            'content-security-policy' => [
                'http-equiv' => 'content-security-policy',
                'content'    => "default-src 'self'",
            ],
            'charset' => [
                'charset' => 'utf-8',
            ],
            'og:type' => [
                'property' => 'og:type',
                'content'  => 'article',
            ],
            'twitter:image' => [
                'name'    => 'twitter:image',
                'content' => 'https://example.com/path/to/image.jpg',
                'test'    => 'value',
            ],
        ], $this->document->getMeta());

        $expected = <<<'EOL'
            <meta charset="utf-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1" />
            <meta http-equiv="content-security-policy" content="default-src &#039;self&#039;" />
            <meta property="og:type" content="article" />
            <meta name="twitter:image" content="https://example.com/path/to/image.jpg" test="value" />
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderHead());
    }

    public function testOverrideMeta(): void
    {
        $this->document->setMeta('charset', 'utf-8');

        $this->assertSame(['charset' => 'utf-8'], $this->document->getMeta('charset'));

        $this->document->setMeta('charset', 'windows-1251');

        $this->assertSame(['charset' => 'windows-1251'], $this->document->getMeta('charset'));
    }

    public function testRemoveMeta(): void
    {
        $this->document->setMeta('og:locale', 'ru_RU')
            ->setMeta('charset', 'utf-8');

        $this->assertSame(
            [
                'og:locale' => [
                    'property' => 'og:locale',
                    'content'  => 'ru_RU',
                ],
                'charset' => [
                    'charset' => 'utf-8',
                ],
            ],
            $this->document->getMeta()
        );

        $this->document->removeMeta('og:locale');

        $this->assertSame(
            [
                'charset' => [
                    'charset' => 'utf-8',
                ],
            ],
            $this->document->getMeta()
        );

        $this->document->removeMeta();

        /** @phpstan-ignore-next-line */
        $this->assertEmpty($this->document->getMeta());
    }

    public function testCustomHeadContent(): void
    {
        $this->document->setCustomHeadContent('<!-- head content -->');

        $this->assertSame('<!-- head content -->', $this->document->getCustomHeadContent());
        $this->assertSame('<!-- head content -->', $this->document->renderHead());
    }

    public function testCustomFooterContent(): void
    {
        $this->document->setCustomFooterContent('<div class="modal"></div>');

        $this->assertSame('<div class="modal"></div>', $this->document->getCustomFooterContent());
        $this->assertSame('<div class="modal"></div>', $this->document->renderFooter());
    }

    public function testAddLink(): void
    {
        $this->document->addLink('canonical', 'https://example.com/my-article/')
            ->addLink('alternate', 'https://example.com/my-article/', ['hreflang' => 'en'])
            ->addLink('alternate', 'https://example.ru/translated-article/', ['hreflang' => 'ru']);

        $this->assertNull($this->document->getLinks('non-existant'));

        $this->assertSame([
            [
                'href' => 'https://example.com/my-article/',
            ],
        ], $this->document->getLinks('canonical'));

        $this->assertSame([
            'canonical' => [
                [
                    'href' => 'https://example.com/my-article/',
                ],
            ],
            'alternate' => [
                [
                    'href'     => 'https://example.com/my-article/',
                    'hreflang' => 'en',
                ],
                [
                    'href'     => 'https://example.ru/translated-article/',
                    'hreflang' => 'ru',
                ],
            ],
        ], $this->document->getLinks());

        $expected = <<<'EOL'
            <link rel="canonical" href="https://example.com/my-article/" />
            <link rel="alternate" href="https://example.com/my-article/" hreflang="en" />
            <link rel="alternate" href="https://example.ru/translated-article/" hreflang="ru" />
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderHead());
    }

    public function testOverrideLink(): void
    {
        $this->document->addLink('preconnect', 'https://fonts.gstatic.com');

        $this->assertSame([
            [
                'href' => 'https://fonts.gstatic.com',
            ],
        ], $this->document->getLinks('preconnect'));

        $this->document->addLink('preconnect', 'https://fonts.gstatic.com', ['crossorigin' => '']);

        $this->assertSame([
            [
                'href'        => 'https://fonts.gstatic.com',
                'crossorigin' => '',
            ],
        ], $this->document->getLinks('preconnect'));

        $this->assertSame(
            '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />',
            $this->document->renderHead()
        );
    }

    public function testRemoveLink(): void
    {
        $this->document->addLink('canonical', 'https://example.com/my-article/')
            ->addLink('alternate', 'https://example.com/my-article/', ['hreflang' => 'en'])
            ->addLink('alternate', 'https://example.ru/translated-article/', ['hreflang' => 'ru']);

        $this->document->removeLinks('alternate', 'https://example.ru/translated-article/');

        $this->assertSame([
            [
                'href'     => 'https://example.com/my-article/',
                'hreflang' => 'en',
            ],
        ], $this->document->getLinks('alternate'));

        $this->document->removeLinks('alternate');

        $this->assertNull($this->document->getLinks('alternate'));

        $this->document->removeLinks();

        $this->assertEmpty($this->document->getLinks());
    }
}
