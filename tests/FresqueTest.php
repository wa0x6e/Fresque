<?php

// Used to mock the filesystem
use org\bovigo\vfs\vfsStream;

class FresqueTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $_SERVER['argv'] = array();

        $this->output = $this->getMock('\ezcConsoleOutput');
        $this->input = $this->getMock('\ezcConsoleInput');

        $this->shell = $this->getMock('\Fresque\Fresque', array('callCommand'));
        $this->shell->output = $this->output;
        $this->shell->input = $this->input;
    }

    /**
     * Should not print debug information when debug is enabled
     *
     * @covers \Fresque\Fresque::debug
     */
    public function testDebug()
    {
        $this->output->expects($this->at(0))->method('outputLine')->with($this->stringContains('[DEBUG] test string'));
        $this->shell->debug = true;
        $this->shell->debug('test string');
    }

    /**
     * Should not print debug information when debug is disabled
     *
     * @covers \Fresque\Fresque::debug
     */
    public function testDebugWhenDisabled()
    {
        $this->output->expects($this->never())->method('outputLine');
        $this->shell->debug = false;
        $this->shell->debug('test string');
    }

    /**
     * Check if a resque bin file is in the bin folder
     * @covers \Fresque\Fresque::getResqueBinFile
     */
    public function testGetResqueBin() {
        $method = new ReflectionMethod('\Fresque\Fresque', 'getResqueBinFile');
        $method->setAccessible(true);

        $root = vfsStream::setup('resque');
        $root->addChild(vfsStream::newDirectory('bin'));
        $root->getChild('bin')->addChild(vfsStream::newFile('resque'));

        $this->assertTrue($root->hasChild('bin'));
        $this->assertTrue($root->getChild('bin')->hasChild('resque'));
        $this->assertEquals('./bin/resque', $method->invoke($this->shell, vfsStream::url('resque')));
    }

    /**
     * Check if a resque bin file is in the bin folder,
     * but with a .php extension
     * @covers \Fresque\Fresque::getResqueBinFile
     */
    public function testGetResqueBinWithExtension() {
        $method = new ReflectionMethod('\Fresque\Fresque', 'getResqueBinFile');
        $method->setAccessible(true);

        $root = vfsStream::setup('resque');
        $root->addChild(vfsStream::newDirectory('bin'));
        $root->getChild('bin')->addChild(vfsStream::newFile('resque.php'));

        $this->assertTrue($root->hasChild('bin'));
        $this->assertTrue($root->getChild('bin')->hasChild('resque.php'));
        $this->assertEquals('./bin/resque.php', $method->invoke($this->shell, vfsStream::url('resque')));
    }

    /**
     * For old version of php-resque, when the file is in the root
     * @covers \Fresque\Fresque::getResqueBinFile
     */
    public function testGetResqueBinFallbackInRoot() {
        $method = new ReflectionMethod('\Fresque\Fresque', 'getResqueBinFile');
        $method->setAccessible(true);

        $root = vfsStream::setup('resque');
        $this->assertEquals('./resque.php', $method->invoke($this->shell, vfsStream::url('resque')));
    }

    /**
     * @covers \Fresque\Fresque::outputTitle
     */
    public function testOutputMainTitle()
    {
        $title = 'my first title';
        $this->output->expects($this->exactly(3))->method('outputLine');
        $this->output->expects($this->at(0))->method('outputLine')->with($this->equalTo(str_repeat('-', strlen($title))));
        $this->output->expects($this->at(1))->method('outputLine')->with($this->equalTo($title), $this->equalTo('title'));
        $this->output->expects($this->at(2))->method('outputLine')->with($this->equalTo(str_repeat('-', strlen($title))));

        $this->shell->outputTitle($title);
    }

    /**
     * @covers \Fresque\Fresque::outputTitle
     */
    public function testOutputSubTitle()
    {
        $title = 'my first title';
        $this->output->expects($this->exactly(1))->method('outputLine')->with($this->equalTo($title), $this->equalTo('subtitle'));

        $this->shell->outputTitle($title, false);
    }
}
