<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use InvalidArgumentException;
use Psr\Log\NullLogger;
use Yiisoft\Files\FileHelper;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\Theme;
use Yiisoft\View\View;

/**
 * ViewTest.
 */
final class ViewTest extends TestCase
{
    private string $testViewPath = '';

    public function setUp(): void
    {
        parent::setUp();

        $this->testViewPath = sys_get_temp_dir() . '/' . str_replace('\\', '_', self::class) . uniqid('', false);

        FileHelper::ensureDirectory($this->testViewPath);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        FileHelper::removeDirectory($this->testViewPath);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/13058
     */
    public function testExceptionOnRenderFile(): void
    {
        $view = $this->createView($this->testViewPath);

        $exceptionViewFile = $this->testViewPath . DIRECTORY_SEPARATOR . 'exception.php';
        file_put_contents(
            $exceptionViewFile,
            <<<'PHP'
<h1>Exception</h1>
<?php throw new Exception('Test Exception'); ?>
PHP
        );
        $normalViewFile = $this->testViewPath . DIRECTORY_SEPARATOR . 'no-exception.php';
        file_put_contents(
            $normalViewFile,
            <<<'PHP'
<h1>No Exception</h1>
PHP
        );

        $obInitialLevel = ob_get_level();

        try {
            $view->renderFile($exceptionViewFile);
        } catch (\Exception $e) {
            // shutdown exception
        }
        $view->renderFile($normalViewFile);

        $this->assertEquals($obInitialLevel, ob_get_level());
    }

    public function testRelativePathInView(): void
    {
        $themePath = $this->testViewPath . '/theme1';
        FileHelper::ensureDirectory($themePath);

        $baseView = "{$this->testViewPath}/theme1/base.php";
        file_put_contents(
            $baseView,
            <<<'PHP'
<?= $this->render("sub") ?>
PHP
        );

        $subView = "{$this->testViewPath}/sub.php";
        $subViewContent = 'subviewcontent';
        file_put_contents($subView, $subViewContent);

        $view = $this->createView(
            $this->testViewPath,
            new Theme([
                $this->testViewPath => $themePath,
            ])
        );

        $this->assertSame($subViewContent, $view->render('//base'));
    }

    public function testLocalizedDirectory(): void
    {
        $view = $this->createView($this->testViewPath);
        $this->createFileStructure([
            'views' => [
                'faq.php' => 'English FAQ',
                'de-DE' => [
                    'faq.php' => 'German FAQ',
                ],
            ],
        ], $this->testViewPath);
        $viewFile = $this->testViewPath . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'faq.php';
        $sourceLanguage = 'en-US';

        // Source language and target language are same. The view path should be unchanged.
        $currentLanguage = $sourceLanguage;
        $this->assertSame($viewFile, $view->localize($viewFile, $currentLanguage, $sourceLanguage));

        // Source language and target language are different. The view path should be changed.
        $currentLanguage = 'de-DE';
        $this->assertSame(
            $this->testViewPath . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $currentLanguage . DIRECTORY_SEPARATOR . 'faq.php',
            $view->localize($viewFile, $currentLanguage, $sourceLanguage)
        );
    }

    /**
     * Creates test files structure.
     *
     * @param string $baseDirectory base directory path.
     * @param array $items file system objects to be created in format: objectName => objectContent
     * Arrays specifies directories, other values - files.
     */
    private function createFileStructure(array $items, string $baseDirectory = null): void
    {
        foreach ($items as $name => $content) {
            $itemName = $baseDirectory . '/' . $name;
            if (\is_array($content)) {
                if (isset($content[0], $content[1]) && $content[0] === 'symlink') {
                    symlink($baseDirectory . DIRECTORY_SEPARATOR . $content[1], $itemName);
                } else {
                    if (!mkdir($itemName, 0777, true) && !is_dir($itemName)) {
                        throw new \RuntimeException(sprintf('Directory "%s" was not created', $itemName));
                    }
                    $this->createFileStructure($content, $itemName);
                }
            } else {
                file_put_contents($itemName, $content);
            }
        }
    }

    public function testDefaultParameterIsPassedToView(): void
    {
        $this->webView->setDefaultParameters(['parameter' => 'default_parameter']);
        $output = $this->webView->render('//parameters');
        $this->assertStringContainsString('default_parameter', $output);
    }

    public function testDefaultParameterIsOverwrittenByLocalParameter(): void
    {
        $this->webView->setDefaultParameters(['parameter' => 'default_parameter']);
        $output = $this->webView->render('//parameters', [
            'parameter' => 'local_parameter',
        ]);
        $this->assertStringContainsString('local_parameter', $output);
    }

    public function testPlaceholderSalt(): void
    {
        $this->webView->setPlaceholderSalt('apple');
        $this->assertSame(dechex(crc32('apple')), $this->webView->getPlaceholderSignature());
    }

    public function testGetBasePath(): void
    {
        $basePath = '@resources/views';
        $view = new View($basePath, new Theme(), new SimpleEventDispatcher(), new NullLogger());

        self::assertSame($basePath, $view->getBasePath());
    }

    public function testDefaultExtension(): void
    {
        $view = $this->createSimpleView();

        // Default is "php"
        self::assertSame('php', $view->getDefaultExtension());

        $extension = 'html';
        $view->setDefaultExtension($extension);

        self::assertSame($extension, $view->getDefaultExtension());
    }

    public function testDefaultParameters(): void
    {
        $view = $this->createSimpleView();

        // Default is empty
        self::assertSame([], $view->getDefaultParameters());

        $parameters = ['name' => 'Zayac'];
        $view->setDefaultParameters($parameters);

        self::assertSame($parameters, $view->getDefaultParameters());
    }

    public function testSetBlock(): void
    {
        $view = $this->createSimpleView();

        $id = 'abc';
        $value = 'hello';
        $view->setBlock($id, $value);

        self::assertSame($value, $view->getBlock($id));
    }

    public function testGetBlockWithoutBlocks(): void
    {
        $view = $this->createViewWithBlocks([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Block: "test" not found.');
        $view->getBlock('test');
    }

    public function testGetNotExistsBlock(): void
    {
        $view = $this->createViewWithBlocks(['abc' => 'hello']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Block: "test" not found.');
        $view->getBlock('test');
    }

    public function testGetExistsBlock(): void
    {
        $id = 'abc';
        $value = 'hello';
        $view = $this->createViewWithBlocks([
            'A' => 'Letter A',
            $id => $value,
            'Z' => 'Letter Z',
        ]);

        self::assertSame($value, $view->getBlock($id));
    }

    public function testRemoveBlock(): void
    {
        $id = 'abc';
        $view = $this->createViewWithBlocks([
            'A' => 'Letter A',
            $id => 'hello',
            'Z' => 'Letter Z',
        ]);
        $view->removeBlock($id);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Block: "' . $id . '" not found.');
        $view->getBlock($id);
    }

    public function testHasBlock(): void
    {
        $id = 'abc';
        $view = $this->createViewWithBlocks([
            'A' => 'Letter A',
            $id => 'hello',
            'Z' => 'Letter Z',
        ]);

        self::assertTrue($view->hasBlock($id));
        self::assertFalse($view->hasBlock('non-exists'));
    }

    private function createViewWithBlocks(array $blocks): View
    {
        $view = $this->createSimpleView();

        foreach ($blocks as $id => $value) {
            $view->setBlock($id, $value);
        }

        return $view;
    }

    private function createSimpleView(): View
    {
        return new View('', new Theme(), new SimpleEventDispatcher(), new NullLogger());
    }


}
