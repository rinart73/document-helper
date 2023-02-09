<?php

declare(strict_types=1);

use org\bovigo\vfs\vfsStream;
use Rinart73\DocumentHelper\Config\Services;
use Rinart73\DocumentHelper\Document;
use Rinart73\DocumentHelper\Script;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ScriptsTest extends TestCase
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
        $this->document->registerScript('one', 'assets/one.js');

        $this->assertFalse($this->document->hasAddedScript('one'));

        $this->assertNotEmpty($this->document->getScripts());

        $script = $this->document->getScripts('one');

        $this->assertInstanceOf(Script::class, $script);
        $this->assertSame('one', $script->getHandle());
        $this->assertSame('assets/one.js', $script->getSrc());

        $this->document->addScripts('one');

        $this->assertTrue($this->document->hasAddedScript('one'));
        $this->assertSame(['one' => true], $this->document->getAddedScripts());

        $this->assertEmpty($this->document->renderHead());
        $this->assertSame(
            '<script id="one-js" src="https://example.com/assets/one.js"></script>',
            $this->document->renderFooter()
        );

        $this->assertSame(
            [
                'one' => true,
            ],
            $this->document->getRenderedScripts()
        );
    }

    public function testAttributes(): void
    {
        $this->document->registerScript(
            'jquery',
            'https://cdn.jsdelivr.net/npm/jquery@3.6.3/dist/jquery.min.js',
            [],
            '3.6.3',
            [
                'integrity'   => 'sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=',
                'crossorigin' => 'anonymous',
                'async'       => '',
            ],
            false
        )
            ->addScripts('jquery');

        $script = $this->document->getScripts('jquery');

        $this->assertInstanceOf(Script::class, $script);
        $this->assertSame('jquery', $script->getHandle());
        $this->assertSame(
            'https://cdn.jsdelivr.net/npm/jquery@3.6.3/dist/jquery.min.js',
            $script->getSrc()
        );
        $this->assertSame('3.6.3', $script->getVersion());
        $this->assertSame(
            [
                'integrity'   => 'sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=',
                'crossorigin' => 'anonymous',
                'async'       => '',
            ],
            $script->getAttributes()
        );

        $this->assertSame(
            '<script id="jquery-js" src="https://cdn.jsdelivr.net/npm/jquery@3.6.3/dist/jquery.min.js?ver=3.6.3" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous" async></script>',
            $this->document->renderHead()
        );
        $this->assertEmpty($this->document->renderFooter());
    }

    public function testOverride(): void
    {
        $this->document->registerScript('one', 'one.js', [], '1.0.0')
            ->addScripts('one');

        $script = $this->document->getScripts('one');

        $this->assertInstanceOf(Script::class, $script);

        $script->setSrc('override/one.js')
            ->setVersion('1.1')
            ->setAttributes(['data-test' => '1234']);

        $this->assertSame('override/one.js', $script->getSrc());
        $this->assertSame('1.1', $script->getVersion());
        $this->assertSame(['data-test' => '1234'], $script->getAttributes());

        $this->assertSame(
            '<script id="one-js" src="https://example.com/override/one.js?ver=1.1" data-test="1234"></script>',
            $this->document->renderFooter()
        );
    }

    public function testInline(): void
    {
        $script = <<<'EOL'
            (function() {
                let x = 42;
                console.log('hello', x);
            })();
            EOL;

        $this->document->registerInlineScript('one', $script)
            ->addScripts('one');

        $expected = <<<'EOL'
            <script id="one-inline-js">(function() {
                let x = 42;
                console.log('hello', x);
            })();</script>
            EOL;

        $this->assertEmpty($this->document->renderHead());

        $this->assertEqualsIgnoringLE($expected, $this->document->renderFooter());
    }

    public function testOverrideInline(): void
    {
        $this->document->registerInlineScript('one', 'alert("Hello")')
            ->addScripts('one');

        $script = $this->document->getScripts('one');

        $this->assertInstanceOf(Script::class, $script);

        $script->setInline('alert("Hello world")');

        $this->assertSame('alert("Hello world")', $script->getInline());

        $this->assertSame(
            '<script id="one-inline-js">alert("Hello world")</script>',
            $this->document->renderFooter()
        );
    }

    public function testDependency(): void
    {
        $this->document->registerScript('one', 'one.js')
            ->registerScript('two', 'two.js', ['one'])
            ->addScripts('two');

        $expected = <<<'EOL'
            <script id="one-js" src="https://example.com/one.js"></script>
            <script id="two-js" src="https://example.com/two.js"></script>
            EOL;

        $this->assertEmpty($this->document->renderHead());
        $this->assertEqualsIgnoringLE($expected, $this->document->renderFooter());
    }

    /**
     * Script depends on handle that isn't registered
     * -> add script anyway.
     */
    public function testMissingDependency(): void
    {
        $this->document->registerScript('two', 'two.js', ['one'])
            ->addScripts('two');

        $this->assertNull($this->document->getScripts('one'));

        $this->assertSame('<script id="two-js" src="https://example.com/two.js"></script>', $this->document->renderFooter());
    }

    /**
     * Scripts depend on each other
     * -> add anyway. Order will vary.
     */
    public function testCircularDependency(): void
    {
        $this->document->registerScript('one', 'one.js', ['two'])
            ->registerScript('two', 'two.js', ['three'])
            ->registerScript('three', 'three.js', ['one'])
            ->addScripts('one');

        $expected = <<<'EOL'
            <script id="one-js" src="https://example.com/one.js"></script>
            <script id="three-js" src="https://example.com/three.js"></script>
            <script id="two-js" src="https://example.com/two.js"></script>
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderFooter());
    }

    /**
     * A variation of circular dependency, script depends on itself
     * -> add it anyway.
     */
    public function testDependsOnSelf(): void
    {
        $this->document->registerScript('one', 'one.js', ['one'])
            ->addScripts('one');

        $this->assertSame(
            '<script id="one-js" src="https://example.com/one.js"></script>',
            $this->document->renderFooter()
        );
    }

    /**
     * Script in the footer depends on script in the head.
     */
    public function testHeadFooter(): void
    {
        $this->document->registerScript('head', 'head.js', [], '', [], false)
            ->registerScript('footer', 'footer.js', ['head'])
            ->addScripts('footer');

        $this->assertSame('<script id="head-js" src="https://example.com/head.js"></script>', $this->document->renderHead());

        $this->assertSame('<script id="footer-js" src="https://example.com/footer.js"></script>', $this->document->renderFooter());
    }

    /**
     * Script in the head depends on script in the footer (incorrect configuration)
     * -> force footer script to go in head.
     */
    public function testFooterHead(): void
    {
        // head < footer < footer < head
        $this->document->registerScript('head-one', 'head-one.js', [], '', [], false)
            ->registerScript('footer-one', 'footer-one.js', ['head-one'])
            ->registerScript('footer-two', 'footer-two.js', ['footer-one'])
            ->registerScript('head-two', 'head-two.js', ['footer-two'], '', [], false)
            ->addScripts('head-two');

        $expected = <<<'EOL'
            <script id="head-one-js" src="https://example.com/head-one.js"></script>
            <script id="footer-one-js" src="https://example.com/footer-one.js"></script>
            <script id="footer-two-js" src="https://example.com/footer-two.js"></script>
            <script id="head-two-js" src="https://example.com/head-two.js"></script>
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderHead());

        $this->assertEmpty($this->document->renderFooter());
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
        $this->document->registerScript('a1', 'a1.js', ['b1', 'b2'])
            ->registerScript('b1', 'b1.js', ['c1'])
            ->registerScript('b2', 'b2.js', ['c2', 'c3'])
            ->registerScript('c1', 'c1.js')
            ->registerScript('c2', 'c2.js')
            ->registerScript('c3', 'c3.js')
            ->registerScript('a2', 'a2.js', ['b3', 'b4'])
            ->registerScript('b3', 'b3.js', ['c2'])
            ->registerScript('b4', 'b4.js', ['b1'])
            ->addScripts('a1', 'c2', 'a2');

        $expected = <<<'EOL'
            <script id="c3-js" src="https://example.com/c3.js"></script>
            <script id="c2-js" src="https://example.com/c2.js"></script>
            <script id="b2-js" src="https://example.com/b2.js"></script>
            <script id="c1-js" src="https://example.com/c1.js"></script>
            <script id="b1-js" src="https://example.com/b1.js"></script>
            <script id="a1-js" src="https://example.com/a1.js"></script>
            <script id="b4-js" src="https://example.com/b4.js"></script>
            <script id="b3-js" src="https://example.com/b3.js"></script>
            <script id="a2-js" src="https://example.com/a2.js"></script>
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderFooter());
    }

    /**
     * Try to remove script that is needed by another script
     * -> script will still be rendered.
     */
    public function testRemove(): void
    {
        $this->document->registerScript('removable', 'removable.js')
            ->registerScript('non-removable', 'non-removable.js')
            ->registerScript('dependant', 'dependant.js', ['non-removable'])
            ->addScripts('removable', 'non-removable', 'dependant');

        $this->assertTrue($this->document->hasAddedScript('non-removable'));
        $this->assertTrue($this->document->hasAddedScript('removable'));
        $this->assertSame(
            [
                'removable'     => true,
                'non-removable' => true,
                'dependant'     => true,
            ],
            $this->document->getAddedScripts()
        );

        $this->document->removeScripts('removable', 'non-removable');

        $this->assertFalse($this->document->hasAddedScript('non-removable'));
        $this->assertFalse($this->document->hasAddedScript('removable'));

        $expected = <<<'EOL'
            <script id="non-removable-js" src="https://example.com/non-removable.js"></script>
            <script id="dependant-js" src="https://example.com/dependant.js"></script>
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderFooter());
    }

    /**
     * Head script added late (after head was rendered)
     * -> put it in footer.
     */
    public function testLate(): void
    {
        $this->document->registerScript('one', 'one.js', [], '', [], false)
            ->registerScript('two', 'two.js', ['one'], '', [], false)
            ->addScripts('two');

        $expected = <<<'EOL'
            <script id="one-js" src="https://example.com/one.js"></script>
            <script id="two-js" src="https://example.com/two.js"></script>
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderFooter());
    }

    public function testLocalize(): void
    {
        $this->document->registerScript('app-common', 'common.js', [], '1.1.1')
            ->localizeScript('app-common', 'appCommonData', [
                'baseUrl' => 'https://example.com',
                'lang'    => [
                    'errorGeneric' => 'An error happened. Try again later.',
                ],
            ])
            ->addScripts('app-common');

        $expected = <<<'EOL'
            <script id="app-common-js-extra">var appCommonData = {"baseUrl":"https:\/\/example.com","lang":{"errorGeneric":"An error happened. Try again later."}};</script>
            <script id="app-common-js" src="https://example.com/common.js?ver=1.1.1"></script>
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderFooter());
    }

    public function testOverrideLocalize(): void
    {
        $this->document->registerScript('one', 'one.js')
            ->addScripts('one')
            ->localizeScript('one', 'oneData', [
                'greeting' => 'Hello',
            ]);

        $script = $this->document->getScripts('one');

        $this->assertInstanceOf(Script::class, $script);

        $script->setLocalizationName('ONE_DATA')
            ->setLocalizationData(['greeting' => 'Good morning']);

        $this->assertSame('ONE_DATA', $script->getLocalizationName());
        $this->assertSame(['greeting' => 'Good morning'], $script->getLocalizationData());

        $expected = <<<'EOL'
            <script id="one-js-extra">var ONE_DATA = {"greeting":"Good morning"};</script>
            <script id="one-js" src="https://example.com/one.js"></script>
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderFooter());
    }

    public function testPrepareHead(): void
    {
        $this->document->registerScript('head', 'head.js', [], '', [], false)
            ->prepareScript('head', static function (Document $document, Script $script): void {
                $script->setAttributes(['test' => '123']);

                $document->setMeta('some-meta', 'value');
            });

        $this->document->addScripts('head');

        $expected = <<<'EOL'
            <meta name="some-meta" content="value" />
            <script id="head-js" src="https://example.com/head.js" test="123"></script>
            EOL;
        $this->assertEqualsIgnoringLE($expected, $this->document->renderHead());
    }

    public function testPrepareFooter(): void
    {
        $this->document->registerScript('footer', 'footer.js')
            ->prepareScript('footer', static function (Document $document, Script $script): void {
                $script->setAttributes(['test' => '456']);

                $document->setCustomFooterContent($document->getCustomFooterContent() . '<!-- Prepare result -->');
            });
        $this->document->addScripts('footer');

        $expected = <<<'EOL'
            <!-- Prepare result -->
            <script id="footer-js" src="https://example.com/footer.js" test="456"></script>
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderFooter());
    }

    /**
     * Prepare callbacks shouldn't be able to register, add or remove scripts
     */
    public function testPrepareIncorrect(): void
    {
        $this->document->registerScript('one', 'one.js')
            ->registerScript('two', 'two.js')
            ->prepareScript('two', static function (Document $document, Script $script): void {
                $document->registerScript('three', 'three.js')
                    ->registerInlineScript('four', 'console.log("will not be added")')
                    ->addScripts('three', 'four')
                    ->addLibraries('three', 'four')
                    ->removeScripts('one');
            })
            ->addScripts('two', 'one');

        $expected = <<<'EOL'
            <script id="two-js" src="https://example.com/two.js"></script>
            <script id="one-js" src="https://example.com/one.js"></script>
            EOL;

        $this->assertEmpty($this->document->renderHead());
        $this->assertEqualsIgnoringLE($expected, $this->document->renderFooter());
    }

    /**
     * Adding scripts automatically adds corresponding styles
     */
    public function testStylesAddedByScripts(): void
    {
        $this->document->registerStyle('one', 'one.css')
            ->registerScript('one', 'one.js');

        $this->document->registerStyle('two', 'two.css');

        $this->document->registerStyle('three', 'three.css')
            ->registerScript('three', 'three.js');

        $this->document->registerStyle('four', 'four.css', ['one', 'two'])
            ->registerScript('four', 'four.js', ['one', 'three']);

        $this->document->addScripts('four');

        $expected = <<<'EOL'
            <link id="three-css" rel="stylesheet" href="https://example.com/three.css" />
            <link id="one-css" rel="stylesheet" href="https://example.com/one.css" />
            <link id="two-css" rel="stylesheet" href="https://example.com/two.css" />
            <link id="four-css" rel="stylesheet" href="https://example.com/four.css" />
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderHead());

        $expected = <<<'EOL'
            <script id="three-js" src="https://example.com/three.js"></script>
            <script id="one-js" src="https://example.com/one.js"></script>
            <script id="four-js" src="https://example.com/four.js"></script>
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderFooter());
    }

    public function testStylesAddedByScriptsLate(): void
    {
        $this->document->buildHead();

        $this->document->registerStyle('one', 'one.css')
            ->registerScript('one', 'one.js');

        $this->document->registerStyle('two', 'two.css');

        $this->document->registerStyle('three', 'three.css')
            ->registerScript('three', 'three.js');

        $this->document->registerStyle('four', 'four.css', ['one', 'two'])
            ->registerScript('four', 'four.js', ['one', 'three']);

        $this->document->addScripts('four');

        $expected = <<<'EOL'
            <link id="three-css" rel="stylesheet" href="https://example.com/three.css" />
            <link id="one-css" rel="stylesheet" href="https://example.com/one.css" />
            <link id="two-css" rel="stylesheet" href="https://example.com/two.css" />
            <link id="four-css" rel="stylesheet" href="https://example.com/four.css" />
            <script id="three-js" src="https://example.com/three.js"></script>
            <script id="one-js" src="https://example.com/one.js"></script>
            <script id="four-js" src="https://example.com/four.js"></script>
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderFooter());
    }

    /**
     * Old behaviour: scripts don't automatically request styles
     */
    public function testStylesAreNotAddedByScripts(): void
    {
        $this->document->setStyleAddedByScript(false);

        $this->assertFalse($this->document->isStyleAddedByScript());

        $this->document->registerStyle('one', 'one.css')
            ->registerScript('one', 'one.js');

        $this->document->registerStyle('two', 'two.css');

        $this->document->registerStyle('three', 'three.css')
            ->registerScript('three', 'three.js');

        $this->document->registerStyle('four', 'four.css', ['one', 'two'])
            ->registerScript('four', 'four.js', ['one', 'three']);

        $this->document->addScripts('four');

        $this->assertEmpty($this->document->renderHead());

        $expected = <<<'EOL'
            <script id="three-js" src="https://example.com/three.js"></script>
            <script id="one-js" src="https://example.com/one.js"></script>
            <script id="four-js" src="https://example.com/four.js"></script>
            EOL;

        $this->assertEqualsIgnoringLE($expected, $this->document->renderFooter());
    }

    public function testAddLibraries(): void
    {
        $this->document->registerStyle('one', 'one.css')
            ->registerScript('two', 'two.js')
            ->addLibraries('one', 'two');

        $this->assertSame(
            '<link id="one-css" rel="stylesheet" href="https://example.com/one.css" />',
            $this->document->renderHead()
        );
        $this->assertSame(
            '<script id="two-js" src="https://example.com/two.js"></script>',
            $this->document->renderFooter()
        );
    }

    public function testVersionModificationTime(): void
    {
        file_put_contents(vfsStream::url('root/one.js'), 'console.log("Hello");');

        $modificationTime = filemtime(vfsStream::url('root/one.js'));

        $this->assertIsInt($modificationTime);

        $this->document->registerScript('one', 'https://example.com/one.js', [], true)
            ->addScripts('one');

        $this->document->renderHead();

        $this->assertSame(
            '<script id="one-js" src="https://example.com/one.js?ver=' . $modificationTime . '"></script>',
            $this->document->renderFooter()
        );
    }

    /**
     * fimetime shouldn't work for external scripts
     */
    public function testVersionModificationTimeWrong(): void
    {
        $this->document->registerScript(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js',
            [],
            true
        )
            ->addScripts('bootstrap');

        $this->document->renderHead();

        $this->assertSame(
            '<script id="bootstrap-js" src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js"></script>',
            $this->document->renderFooter()
        );
    }
}
