<?php

declare(strict_types=1);

use org\bovigo\vfs\vfsStream;
use Rinart73\DocumentHelper\Config\Services;
use Rinart73\DocumentHelper\Document;
use Rinart73\DocumentHelper\Style;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class StylesTest extends TestCase
{
    private Document $document;

    protected function setUp(): void
    {
        $this->resetServices();

        vfsStream::setup();

        $this->document = Services::document(vfsStream::url('root/'));

        parent::setUp();
    }

    public function testSimple(): void
    {
        $this->document->registerStyle('one', 'assets/one.css');

        $this->assertFalse($this->document->hasAddedStyle('one'));

        $this->assertNotEmpty($this->document->getStyles());

        $style = $this->document->getStyles('one');

        $this->assertInstanceOf(Style::class, $style);
        $this->assertSame('one', $style->getHandle());
        $this->assertSame('assets/one.css', $style->getSrc());

        $this->document->addStyles('one');

        $this->assertTrue($this->document->hasAddedStyle('one'));

        $this->assertSame(['one' => true], $this->document->getAddedStyles());

        $this->assertSame(
            '<link id="one-css" rel="stylesheet" href="https://example.com/assets/one.css" />',
            $this->document->renderHead()
        );
        $this->assertEmpty($this->document->renderFooter());

        $this->assertSame(
            [
                'one' => true,
            ],
            $this->document->getRenderedStyles()
        );
    }

    public function testAttributes(): void
    {
        $this->document->registerStyle(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css',
            [],
            '5.2.3',
            [
                'integrity'   => 'sha256-wLz3iY/cO4e6vKZ4zRmo4+9XDpMcgKOvv/zEU3OMlRo=',
                'crossorigin' => 'anonymous',
            ]
        )
            ->addStyles('bootstrap');

        $style = $this->document->getStyles('bootstrap');

        $this->assertInstanceOf(Style::class, $style);
        $this->assertSame('bootstrap', $style->getHandle());
        $this->assertSame('https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css', $style->getSrc());
        $this->assertSame('5.2.3', $style->getVersion());
        $this->assertSame(
            [
                'integrity'   => 'sha256-wLz3iY/cO4e6vKZ4zRmo4+9XDpMcgKOvv/zEU3OMlRo=',
                'crossorigin' => 'anonymous',
            ],
            $style->getAttributes()
        );

        $this->assertSame(
            '<link id="bootstrap-css" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css?ver=5.2.3" integrity="sha256-wLz3iY/cO4e6vKZ4zRmo4+9XDpMcgKOvv/zEU3OMlRo=" crossorigin="anonymous" />',
            $this->document->renderHead()
        );
    }

    public function testOverride(): void
    {
        $this->document->registerStyle('one', 'one.css', [], '1.0.0')
            ->addStyles('one');

        $style = $this->document->getStyles('one');

        $this->assertInstanceOf(Style::class, $style);

        $style->setSrc('override/one.css')
            ->setVersion('1.1')
            ->setAttributes(['data-test' => '1234']);

        $this->assertSame('override/one.css', $style->getSrc());
        $this->assertSame('1.1', $style->getVersion());
        $this->assertSame(['data-test' => '1234'], $style->getAttributes());

        $this->assertSame(
            '<link id="one-css" rel="stylesheet" href="https://example.com/override/one.css?ver=1.1" data-test="1234" />',
            $this->document->renderHead()
        );
    }

    public function testInline(): void
    {
        $style = <<<'EOL'
            #myForm > p.test {
                margin: 10px 5px 2px;
                background: url("https://example.com/assets/test.jpg")
            }
            EOL;

        $this->document->registerInlineStyle('one', $style)
            ->addStyles('one');

        $expected = <<<'EOL'
            <style id="one-inline-css">#myForm > p.test {
                margin: 10px 5px 2px;
                background: url("https://example.com/assets/test.jpg")
            }</style>
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderHead());
    }

    public function testOverrideInline(): void
    {
        $this->document->registerInlineStyle('one', 'body { background: #ccc; }')
            ->addStyles('one');

        $style = $this->document->getStyles('one');

        $this->assertInstanceOf(Style::class, $style);

        $style->setInline('body.article-page { background: #ddd }');

        $this->assertSame('body.article-page { background: #ddd }', $style->getInline());

        $this->assertSame(
            '<style id="one-inline-css">body.article-page { background: #ddd }</style>',
            $this->document->renderHead()
        );
    }

    public function testDependency(): void
    {
        $this->document->registerStyle('one', 'one.css')
            ->registerStyle('two', 'two.css', ['one'])
            ->addStyles('two');

        $expected = <<<'EOL'
            <link id="one-css" rel="stylesheet" href="https://example.com/one.css" />
            <link id="two-css" rel="stylesheet" href="https://example.com/two.css" />
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderHead());
    }

    /**
     * Style depends on handle that isn't registered
     * -> add style anyway.
     */
    public function testMissingDependency(): void
    {
        $this->document->registerStyle('two', 'assets/two.css', ['one'])
            ->addStyles('two');

        $this->assertSame(
            '<link id="two-css" rel="stylesheet" href="https://example.com/assets/two.css" />',
            $this->document->renderHead()
        );
    }

    /**
     * Styles depend on each other
     * -> add anyway. Order will vary.
     */
    public function testCircularDependency(): void
    {
        $this->document->registerStyle('one', 'one.css', ['two'])
            ->registerStyle('two', 'two.css', ['one'])
            ->addStyles('one');

        $expected = <<<'EOL'
            <link id="one-css" rel="stylesheet" href="https://example.com/one.css" />
            <link id="two-css" rel="stylesheet" href="https://example.com/two.css" />
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderHead());
    }

    /**
     * A variation of circular dependency, style depends on itself
     * -> add it anyway.
     */
    public function testDependsOnSelf(): void
    {
        $this->document->registerStyle('one', 'one.css', ['one'])
            ->addStyles('one');

        $this->assertSame(
            '<link id="one-css" rel="stylesheet" href="https://example.com/one.css" />',
            $this->document->renderHead()
        );
    }

    public function testDepsComplex(): void
    {
        /**
         * A1
         * * B1
         * * * C1
         * * B2
         * * * C2
         * * * C3
         * A2
         * * B3
         * * * C2
         * * B4
         * * * B1
         *
         * Manually add: a1, c2, a2
         */
        $this->document->registerStyle('a1', 'a1.css', ['b1', 'b2'])
            ->registerStyle('b1', 'b1.css', ['c1'])
            ->registerStyle('b2', 'b2.css', ['c2', 'c3'])
            ->registerStyle('c1', 'c1.css')
            ->registerStyle('c2', 'c2.css')
            ->registerStyle('c3', 'c3.css')
            ->registerStyle('a2', 'a2.css', ['b3', 'b4'])
            ->registerStyle('b3', 'b3.css', ['c2'])
            ->registerStyle('b4', 'b4.css', ['b1'])
            ->addStyles('a1', 'c2', 'a2');

        $expected = <<<'EOL'
            <link id="c3-css" rel="stylesheet" href="https://example.com/c3.css" />
            <link id="c2-css" rel="stylesheet" href="https://example.com/c2.css" />
            <link id="b2-css" rel="stylesheet" href="https://example.com/b2.css" />
            <link id="c1-css" rel="stylesheet" href="https://example.com/c1.css" />
            <link id="b1-css" rel="stylesheet" href="https://example.com/b1.css" />
            <link id="a1-css" rel="stylesheet" href="https://example.com/a1.css" />
            <link id="b4-css" rel="stylesheet" href="https://example.com/b4.css" />
            <link id="b3-css" rel="stylesheet" href="https://example.com/b3.css" />
            <link id="a2-css" rel="stylesheet" href="https://example.com/a2.css" />
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderFooter());
    }

    /**
     * Try to remove style that is needed by another style
     * -> style will still be rendered.
     */
    public function testRemove(): void
    {
        $this->document->registerStyle('removable', 'removable.css')
            ->registerStyle('non-removable', 'non-removable.css')
            ->registerStyle('dependant', 'dependant.css', ['non-removable'])
            ->addStyles('removable', 'non-removable', 'dependant');

        $this->assertTrue($this->document->hasAddedStyle('non-removable'));
        $this->assertTrue($this->document->hasAddedStyle('removable'));
        $this->assertSame(
            [
                'removable'     => true,
                'non-removable' => true,
                'dependant'     => true,
            ],
            $this->document->getAddedStyles()
        );

        $this->document->removeStyles('removable', 'non-removable');

        $this->assertFalse($this->document->hasAddedStyle('non-removable'));
        $this->assertFalse($this->document->hasAddedStyle('removable'));

        $expected = <<<'EOL'
            <link id="non-removable-css" rel="stylesheet" href="https://example.com/non-removable.css" />
            <link id="dependant-css" rel="stylesheet" href="https://example.com/dependant.css" />
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderHead());
    }

    /**
     * Style added late (after head was rendered)
     * -> put it in footer.
     */
    public function testLate(): void
    {
        $this->document->registerStyle('one', 'one.css')
            ->registerStyle('two', 'two.css', ['one'])
            ->addStyles('two');

        $expected = <<<'EOL'
            <link id="one-css" rel="stylesheet" href="https://example.com/one.css" />
            <link id="two-css" rel="stylesheet" href="https://example.com/two.css" />
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderFooter());
    }

    public function testPrepare(): void
    {
        $this->document->registerStyle(
            'google-fonts',
            'https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;1,400;1,500;1,700&display=swap'
        )
            ->prepareStyle('google-fonts', static function (Document $document, Style $style): void {
                $style->setAttributes(['test' => 'value']);

                $document->addLink('preconnect', 'https://fonts.googleapis.com')
                    ->addLink('preconnect', 'https://fonts.gstatic.com', ['crossorigin' => '']);
            });

        $this->document->addLink('robots', 'none')
            ->addStyles('google-fonts');

        $expected = <<<'EOL'
            <link rel="robots" href="none" />
            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
            <link id="google-fonts-css" rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;1,400;1,500;1,700&amp;display=swap" test="value" />
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderHead());
    }

    /**
     * Prepare will do nothing because head was already rendered
     */
    public function testPrepareLate(): void
    {
        $this->document->registerStyle('one', 'one.css')
            ->prepareStyle('one', static function (Document $document, Style $style): void {
                $document->addLink('preconnect', 'https://somecdn.com');
            })
            ->addStyles('one');

        $this->assertSame(
            '<link id="one-css" rel="stylesheet" href="https://example.com/one.css" />',
            $this->document->renderFooter()
        );
    }

    /**
     * Prepare callbacks shouldn't be able to register, add or remove styles
     */
    public function testPrepareIncorrect(): void
    {
        $this->document->registerStyle('one', 'one.css')
            ->registerStyle('two', 'two.css')
            ->prepareStyle('two', static function (Document $document, Style $style): void {
                $document->registerStyle('three', 'three.css')
                    ->registerInlineStyle('four', 'body {background: gray}')
                    ->addStyles('three', 'four')
                    ->removeStyles('one');
            })
            ->addStyles('two', 'one');

        $expected = <<<'EOL'
            <link id="two-css" rel="stylesheet" href="https://example.com/two.css" />
            <link id="one-css" rel="stylesheet" href="https://example.com/one.css" />
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderHead());
    }

    public function testVersionModificationTime(): void
    {
        file_put_contents(vfsStream::url('root/one.css'), 'body { color: gray }');

        $modificationTime = filemtime(vfsStream::url('root/one.css'));

        $this->assertIsInt($modificationTime);

        $this->document->registerStyle('one', vfsStream::url('root/one.css'), [], true)
            ->addStyles('one');

        $this->assertSame(
            '<link id="one-css" rel="stylesheet" href="https://example.com/one.css?ver=' . $modificationTime . '" />',
            $this->document->renderHead()
        );
    }

    /**
     * fimetime shouldn't work for external styles
     */
    public function testVersionModificationTimeWrong(): void
    {
        $this->document->registerStyle(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css',
            [],
            true
        )
            ->addStyles('bootstrap');

        $this->document->renderHead();

        $this->assertSame(
            '<link id="bootstrap-css" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" />',
            $this->document->renderHead()
        );
    }
}
