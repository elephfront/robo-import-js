<?php
namespace Elephfront\RoboImportJs\Tests;

use Elephfront\RoboImportJs\Task\ImportJavascript;
use Elephfront\RoboImportJs\Tests\Utility\MemoryLogger;
use PHPUnit\Framework\TestCase;
use Robo\Result;
use Robo\Robo;
use Robo\State\Data;

/**
 * Class ImportJavascriptTest
 *
 * Test cases for the ImportJavascript Robo task.
 *
 * @package Mystiq\AlphaTheme\Tests
 */
class ImportJavascriptTest extends TestCase
{

    /**
     * Instance of the task that will be tested.
     *
     * @var \Elephfront\RoboImportJs\Task\ImportJavascript
     */
    protected $task;

    /**
     * setUp.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Robo::setContainer(Robo::createDefaultContainer());
        $this->task = new ImportJavascript();
        $this->task->setLogger(new MemoryLogger());
        if (file_exists(TESTS_ROOT . 'app' . DS . 'js' . DS . 'output.js')) {
            unlink(TESTS_ROOT . 'app' . DS . 'js' . DS . 'output.js');
        }
    }

    /**
     * tearDown.
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->task);
    }

    /**
     * Tests that giving the task no destinations map will throw an exception.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Impossible to run the ImportJavascript task without a destinations map.
     * @return void
     */
    public function testNoDestinationsMap()
    {
        $this->task->run();
    }

    /**
     * Tests that giving the task a destinations map with an invalid source file will throw an exception.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Impossible to find source file `bogus`
     * @return void
     */
    public function testInexistantSource()
    {
        $this->task->setDestinationsMap([
            'bogus' => 'bogus'
        ]);
        $this->task->run();
    }

    /**
     * Test a basic import with a single import but with an inexistant imported file.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp #Impossible to find imported file `(.*)js/imports/not-here.js`#
     *
     * @return void
     */
    public function testBasicImportWithInexistantImportedFile()
    {
        $this->task->setDestinationsMap([
            TESTS_ROOT . 'app' . DS . 'js' . DS . 'simple-wrong.js' => TESTS_ROOT . 'app' . DS . 'js' . DS . 'output.js'
        ]);
        $this->task->run();
    }

    /**
     * Test a basic import with a single import.
     *
     * @return void
     */
    public function testBasicImport()
    {
        $this->task->setDestinationsMap([
            TESTS_ROOT . 'app' . DS . 'js' . DS . 'simple.js' => TESTS_ROOT . 'app' . DS . 'js' . DS . 'output.js'
        ]);
        $result = $this->task->run();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(0, $result->getExitCode());

        $this->assertEquals(
            file_get_contents(TESTS_ROOT . 'comparisons' . DS . __FUNCTION__ . '.js'),
            file_get_contents(TESTS_ROOT . 'app' . DS . 'js' . DS . 'output.js')
        );

        $source = TESTS_ROOT . 'app' . DS . 'js' . DS . 'simple.js';
        $destination = TESTS_ROOT . 'app' . DS . 'js' . DS . 'output.js';
        $expectedLog = 'Replaced import statement from file <info>' . $source . '</info> to <info>
            ' . $destination . '</info>';
        $this->assertEquals(
            $expectedLog,
            $this->task->logger()->getLogs()[0]
        );
    }

    /**
     * Test an import with the writeFile feature disabled.
     *
     * @return void
     */
    public function testImportNoWrite()
    {
        $this->task->setDestinationsMap([
            TESTS_ROOT . 'app' . DS . 'js' . DS . 'simple.js' => TESTS_ROOT . 'app' . DS . 'js' . DS . 'output.js'
        ]);
        $this->task->disableWriteFile();
        $result = $this->task->run();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(0, $result->getExitCode());
        
        $this->assertFalse(file_exists(TESTS_ROOT . 'app' . DS . 'js' . DS . 'output.js'));

        $source = TESTS_ROOT . 'app' . DS . 'js' . DS . 'simple.js';
        $expectedLog = 'Replaced import statement from file <info>' . $source . '</info>';
        $this->assertEquals(
            $expectedLog,
            $this->task->logger()->getLogs()[0]
        );
    }

    /**
     * Test a basic import with a nested import.
     *
     * @return void
     */
    public function testNestedImport()
    {
        $this->task->setDestinationsMap([
            TESTS_ROOT . 'app' . DS . 'js' . DS . 'nested.js' => TESTS_ROOT . 'app' . DS . 'js' . DS . 'output.js'
        ]);
        $result = $this->task->run();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(0, $result->getExitCode());

        $this->assertEquals(
            file_get_contents(TESTS_ROOT . 'comparisons' . DS . __FUNCTION__ . '.js'),
            file_get_contents(TESTS_ROOT . 'app' . DS . 'js' . DS . 'output.js')
        );
    }

    /**
     * Test a basic import using the chained state.
     *
     * @return void
     */
    public function testImportWithChainedState()
    {
        $data = new Data();
        $data->mergeData([
            TESTS_ROOT . 'app' . DS . 'js' . DS . 'simple.js' => [
                'js' => 'roboimport(\'imports/bogus\');',
                'destination' => TESTS_ROOT . 'app' . DS . 'js' . DS . 'output.js'
            ]
        ]);
        $this->task->receiveState($data);
        $result = $this->task->run();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(0, $result->getExitCode());
        
        $resultData = $result->getData();
        $expected = [
            TESTS_ROOT . 'app' . DS . 'js' . DS . 'simple.js' => [
                'js' => '// Some bogus JS code goes here',
                'destination' => TESTS_ROOT . 'app' . DS . 'js' . DS . 'output.js'
            ]
        ];

        $this->assertTrue(is_array($resultData));
        $this->assertEquals($expected, $resultData);
    }

    /**
     * Test an import with a source map containing multiple files.
     *
     * @return void
     */
    public function testMultipleSourcesImport()
    {
        $basePath = TESTS_ROOT . 'app' . DS . 'js' . DS;
        $desinationsMap = [
            $basePath . 'simple.js' => $basePath . 'output.js',
            $basePath . 'nested.js' => $basePath . 'output-nested.js'
        ];

        $comparisonsMap = [
            $basePath . 'simple.js' => TESTS_ROOT . 'comparisons' . DS . 'testBasicImport.js',
            $basePath . 'nested.js' => TESTS_ROOT . 'comparisons' . DS . 'testNestedImport.js'
        ];

        $this->task->setDestinationsMap($desinationsMap);
        $result = $this->task->run();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(0, $result->getExitCode());

        foreach ($desinationsMap as $source => $destination) {
            $this->assertEquals(
                file_get_contents($comparisonsMap[$source]),
                file_get_contents($destination)
            );
        }

        $source = $basePath . 'simple.js';
        $destination = $basePath . 'output.js';
        $expectedLog = 'Replaced import statement from file <info>' . $source . '</info> to <info>
            ' . $destination . '</info>';
        $this->assertEquals(
            $expectedLog,
            $this->task->logger()->getLogs()[0]
        );

        $source = TESTS_ROOT . 'app' . DS . 'js' . DS . 'nested.js';
        $destination = TESTS_ROOT . 'app' . DS . 'js' . DS . 'output-nested.js';
        $expectedLog = 'Replaced import statement from file <info>' . $source . '</info> to <info>
            ' . $destination . '</info>';
        $this->assertEquals(
            $expectedLog,
            $this->task->logger()->getLogs()[1]
        );
    }
}
