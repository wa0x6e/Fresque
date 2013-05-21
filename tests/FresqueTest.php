<?php
namespace Fresque\Test;

// Used to mock the filesystem
use org\bovigo\vfs\vfsStream;

class FresqueTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $_SERVER['argv'] = array();

        $this->output = $this->getMock('\ezcConsoleOutput');
        $this->input = $this->getMock('\ezcConsoleInput');

        $this->shell = $this->getMock('\Fresque\Fresque', array('callCommand', 'outputTitle', 'kill', 'getUserChoice'));
        $this->shell->output = $this->output;
        $this->shell->input = $this->input;

        $this->shell->ResqueStatus = $this->ResqueStatus = $this->getMock('\ResqueStatus\ResqueStatus', array(), array(new \stdClass()));

        $this->startArgs = array(

            'Default' => array(
                'queue' => 'default',
                'workers' => 1,
                'interval' => 5,
                'verbose' => true,
                'user' => ''
            ),
            'Fresque' => array(
                'lib' => '',
                'include' => ''
            ),
            'Redis' => array(
                'host' => '',
                'database' => 0,
                'port' => 0,
                'namespace' => ''
            ),
            'Log' => array(
                'handler' => '',
                'target' => '',
                'filename' => ''
            )
        );

        $this->sendSignalOptions = array(
            'title' => 'Testing workers',
            'noWorkersMessage' => 'There is no workers to test',
            'allOption' => 'Test all workers',
            'selectMessage' => 'Worker to test',
            'actionMessage' => 'testing',
            'listTitle' => 'list of workers to test',
            'workers' => array(),
            'signal' => 'TEST',
            'successCallback' => function ($pid) {

            }
        );
    }

    /**
     * Should not print debug information when debug is enabled
     *
     * @covers \Fresque\Fresque::debug
     * @return  void
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
     * @return  void
     */
    public function testDebugWhenDisabled()
    {
        $this->output->expects($this->never())->method('outputLine');
        $this->shell->debug('test string');
    }

    /**
     * Check if a resque bin file is in the bin folder
     *
     * @covers \Fresque\Fresque::getResqueBinFile
     * @return  void
     */
    public function testGetResqueBin()
    {
        $method = new \ReflectionMethod('\Fresque\Fresque', 'getResqueBinFile');
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
     *
     * @covers \Fresque\Fresque::getResqueBinFile
     * @return  void
     */
    public function testGetResqueBinWithExtension()
    {
        $method = new \ReflectionMethod('\Fresque\Fresque', 'getResqueBinFile');
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
     *
     * @covers \Fresque\Fresque::getResqueBinFile
     * @return  void
     */
    public function testGetResqueBinFallbtestStopWhenNoWorkersackInRoot()
    {
        $method = new \ReflectionMethod('\Fresque\Fresque', 'getResqueBinFile');
        $method->setAccessible(true);

        $root = vfsStream::setup('resque');
        $this->assertEquals('./resque.php', $method->invoke($this->shell, vfsStream::url('resque')));
    }

    /**
     * Print a title
     *
     * @covers \Fresque\Fresque::outputTitle
     * @return  void
     */
    public function testOutputMainTitle()
    {
        $title = 'my first title';
        $this->output->expects($this->exactly(3))->method('outputLine');
        $this->output->expects($this->at(0))->method('outputLine')->with($this->equalTo(str_repeat('-', strlen($title))));
        $this->output->expects($this->at(1))->method('outputLine')->with($this->equalTo($title), $this->equalTo('title'));
        $this->output->expects($this->at(2))->method('outputLine')->with($this->equalTo(str_repeat('-', strlen($title))));

        $this->shell = $this->getMock('\Fresque\Fresque', array('callCommand'));
        $this->shell->output = $this->output;
        $this->shell->outputTitle($title);
    }

    /**
     * Print a subtitle
     *
     * @covers \Fresque\Fresque::outputTitle
     * @return  void
     */
    public function testOutputSubTitle()
    {
        $title = 'my first title';
        $this->output->expects($this->exactly(1))->method('outputLine')->with($this->equalTo($title), $this->equalTo('subtitle'));

        $this->shell = $this->getMock('\Fresque\Fresque', array('callCommand'));
        $this->shell->output = $this->output;
        $this->shell->outputTitle($title, false);
    }

    /**
     * Start a worker
     *
     * @covers \Fresque\Fresque::start
     * @return void
     */
    public function testStart()
    {

        $this->shell = $this->getMock('\Fresque\Fresque', array('callCommand', 'outputTitle', 'exec', 'checkStartedWorker'));
        $this->shell->output = $this->output;

        $this->shell->expects($this->never())->method('outputTitle');

        $this->shell->expects($this->once())->method('exec')->will($this->returnValue(true));
        $this->shell->expects($this->once())->method('checkStartedWorker')->will($this->returnValue(true));

        $this->output->expects($this->at(0))->method('outputText')->with($this->stringContains('starting worker'));
        $this->output->expects($this->at(1))->method('outputText')->with($this->stringContains('.'));
        $this->output->expects($this->at(2))->method('outputText')->with($this->stringContains('.'));
        $this->output->expects($this->at(3))->method('outputText')->with($this->stringContains('.'));
        $this->output->expects($this->at(4))->method('outputLine')->with($this->stringContains('done'));
        $this->output->expects($this->exactly(1))->method('outputLine');
        $this->output->expects($this->exactly(4))->method('outputText');

        $this->ResqueStatus = $this->getMock(
            'ResqueStatus\ResqueStatus',
            array('isRunningSchedulerWorker', 'addWorker'),
            array(new \stdClass())
        );

        $this->ResqueStatus->expects($this->once())->method('addWorker');
        $this->shell->ResqueStatus = $this->ResqueStatus;

        $this->shell->start($this->startArgs);
    }

    public function testRestartWhenNoStartedWorkers()
    {
        $this->shell = $this->getMock('\Fresque\Fresque', array('callCommand', 'start', 'stop', 'outputTitle'));
        $this->shell->output = $this->output;
        $this->shell->ResqueStatus = $this->ResqueStatus;

        $this->ResqueStatus->expects($this->once())->method('getWorkers')->will($this->returnValue(array()));
        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('restarting workers'));
        $this->output->expects($this->at(0))->method('outputLine')->with($this->stringContains('no workers to restart'));

        $this->output->expects($this->exactly(2))->method('outputLine');
        $this->shell->expects($this->never())->method('start');
        $this->shell->expects($this->never())->method('stop');
        $this->shell->ResqueStatus = $this->ResqueStatus;
        $this->shell->restart();
    }

    public function testRestart()
    {
        $this->shell = $this->getMock('\Fresque\Fresque', array('callCommand', 'start', 'stop', 'outputTitle'));
        $this->shell->output = $this->output;
        $this->shell->ResqueStatus = $this->ResqueStatus;
        $workers = array(0, 1);

        $this->ResqueStatus->expects($this->once())->method('getWorkers')->will($this->returnValue($workers));
        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('restarting workers'));
        $this->output->expects($this->once())->method('outputLine');

        $this->shell->expects($this->exactly(2))->method('start');
        $this->shell->expects($this->at(2))->method('start')->with($this->equalTo($workers[0]));
        $this->shell->expects($this->at(3))->method('start')->with($this->equalTo($workers[1]));
        $this->shell->expects($this->once())->method('stop');
        $this->shell->ResqueStatus = $this->ResqueStatus;
        $this->shell->restart();
    }

    public function testLoadWhenNothingToLoad()
    {
        $this->shell = $this->getMock('\Fresque\Fresque', array('callCommand', 'start', 'stop', 'outputTitle'));
        $this->shell->output = $this->output;
        $this->shell->ResqueStatus = $this->ResqueStatus;
        $workers = array(0, 1);

        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('Loading predefined workers'));
        $this->output->expects($this->at(0))->method('outputLine')->with($this->stringContains('You have no configured workers to load'));
        $this->output->expects($this->exactly(2))->method('outputLine');

        $this->shell->ResqueStatus = $this->ResqueStatus;
        $this->shell->setttings['Queues'] = array();
        $this->shell->load();
    }

    public function testLoad()
    {
        $this->shell = $this->getMock('\Fresque\Fresque', array('callCommand', 'start', 'stop', 'outputTitle', 'loadSettings'));
        $this->shell->output = $this->output;
        $this->shell->ResqueStatus = $this->ResqueStatus;
        $workers = array(0, 1);

        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('Loading predefined workers'));
        $this->output->expects($this->at(0))->method('outputLine')->with($this->stringContains('Loading 2 workers'));
        $this->shell->expects($this->exactly(2))->method('start');
        $this->output->expects($this->exactly(2))->method('outputLine');

        $this->shell->ResqueStatus = $this->ResqueStatus;
        $this->shell->config = '';
        $queue = array(
            'name' => 'default',
            'config' => '',
            'debug' => false
        );
        $this->shell->settings['Queues'] = array($queue, $queue);
        $this->shell->load();
    }

    /**
     * Queuing a job without arguments, will fail
     *
     * @covers \Fresque\Fresque::enqueue
     * @return  void
     */
    public function testEnqueueJobWithoutArguments()
    {
        $Resque = $this->getMock('\Fresque');
        $Resque::staticExpects($this->never())->method('enqueue');

        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('Queuing a job'));
        $this->output->expects($this->at(0))->method('outputLine')->with($this->stringContains('Enqueue takes at least 2 arguments'));
        $this->output->expects($this->at(1))->method('outputLine')->with($this->stringContains('usage'));
        $this->output->expects($this->at(5))->method('outputLine');
        $this->shell->enqueue();
    }

    /**
     * Queuing a job with wrong number of arguments, will fail
     *
     * @covers \Fresque\Fresque::enqueue
     * @return  void
     */
    public function testEnqueueJobWithWrongNumberOfArguments()
    {
        $Resque = $this->getMock('\Fresque');
        $Resque::staticExpects($this->never())->method('enqueue');

        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('Queuing a job'));
        $this->output->expects($this->at(0))->method('outputLine')->with($this->stringContains('Enqueue takes at least 2 arguments'));
        $this->output->expects($this->at(1))->method('outputLine')->with($this->stringContains('usage'));
        $this->output->expects($this->at(5))->method('outputLine');
        $this->shell->enqueue();
    }

    /**
     * Queuing a job with wrong number of arguments, will fail
     *
     * @covers \Fresque\Fresque::enqueue
     * @return  void
     */
    public function testEnqueueJob()
    {
        $id = md5(time());
        $job = array('queue', 'class');

        $shell = $this->shell;
        $shell::$Resque = $Resque = $this->getMockClass('\Resque');
        $Resque::staticExpects($this->once())->method('enqueue')->with($this->equalTo($job[0]), $this->equalTo($job[1]), $this->equalTo(array()))->will($this->returnValue($id));
        $this->input->expects($this->once())->method('getArguments')->will($this->returnValue($job));

        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('Queuing a job'));
        $this->output->expects($this->at(0))->method('outputLine')->with($this->stringContains('The job was enqueued successfully'));
        $this->output->expects($this->at(1))->method('outputLine')->with($this->stringContains('job id : #' . $id));
        $this->output->expects($this->exactly(2))->method('outputLine');

        $this->shell->enqueue();
    }

    /**
     * Printing help message
     *
     * @covers \Fresque\Fresque::help
     * @return  void
     */
    public function testHelp()
    {
        $this->shell->expects($this->once())->method('outputTitle')->with($this->equalTo('Welcome to Fresque'));
        $this->shell->commandTree = array(
            'start' => array(
                    'help' => 'Start a new worker',
                    'options' => array('u' => 'username', 'q' => 'queue name',
                            'i' => 'num', 'n' => 'num', 'l' => 'path', 'v', 'g')),
            'stop' => array(
                    'help' => 'Shutdown all workers',
                    'options' => array('f', 'w', 'g'))
        );

        $this->output->expects($this->at(2))->method('outputLine')->with($this->stringContains('Available commands'));

        $this->output->expects($this->at(3))->method('outputText')->with($this->stringContains('start'));
        $this->output->expects($this->at(4))->method('outputText')->with($this->stringContains($this->shell->commandTree['start']['help']));

        $this->output->expects($this->at(5))->method('outputText')->with($this->stringContains('stop'));
        $this->output->expects($this->at(6))->method('outputText')->with($this->stringContains($this->shell->commandTree['stop']['help']));

        $this->output->expects($this->exactly(4))->method('outputText');

        $this->shell->help();
    }


    /**
     * Printing help message when calling a unrecognized command
     *
     * @covers \Fresque\Fresque::help
     * @return  void
     */
    public function testPrintHelpWhenCallingUnhrecognizedCommand()
    {
        $this->shell->expects($this->once())->method('outputTitle')->with($this->equalTo('Welcome to Fresque'));
        $this->output->expects($this->at(1))->method('outputLine')->with($this->stringContains('Unrecognized command : hello'));

        $this->shell->commandTree = array();
        $this->shell->help('hello');
    }

    /**
     * @covers \Fresque\Fresque::sendSignal
     * @return  void
     */
    public function testSendSignalWhenNoWorkers()
    {
        $option = new \stdClass();
        $option->value = false;
        $this->input->expects($this->exactly(2))->method('getOption')->will($this->returnValue($option));

        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains($this->sendSignalOptions['title']));
        $this->output->expects($this->at(0))->method('outputLine')->with($this->stringContains($this->sendSignalOptions['noWorkersMessage']));
        $this->output->expects($this->exactly(2))->method('outputLine');

        $this->shell->sendSignal($this->sendSignalOptions);
    }

    /**
     * @covers \Fresque\Fresque::sendSignal
     * @return  void
     */
    public function testSendSignalWhenOnlyOneWorker()
    {
        $option = new \stdClass();
        $option->value = false;
        $this->input->expects($this->exactly(2))->method('getOption')->will($this->returnValue($option));

        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('testing workers'));
        $this->output->expects($this->at(0))->method('outputText')->with($this->stringContains('testing 100 ...'));
        $this->output->expects($this->at(2))->method('outputLine')->will($this->returnValue('done'));
        $this->output->expects($this->exactly(2))->method('outputLine');

        $this->shell->expects($this->once())->method('kill')->with($this->equalTo($this->sendSignalOptions['signal']), $this->equalTo('100'))->will($this->returnValue(array('code' => 0, 'message' => '')));

        $this->sendSignalOptions['workers'] = array('host:100:queue');
        $this->shell->sendSignal($this->sendSignalOptions);
    }

    /**
     * @covers \Fresque\Fresque::sendSignal
     * @return  void
     */
    public function testSendSignalDisplayErrorMessageOnFail()
    {
        $option = new \stdClass();
        $option->value = false;
        $this->input->expects($this->exactly(2))->method('getOption')->will($this->returnValue($option));

        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('testing workers'));
        $this->output->expects($this->at(0))->method('outputText')->with($this->stringContains('testing 100 ...'));
        $this->output->expects($this->at(2))->method('outputLine')->will($this->returnValue('error message'));
        $this->output->expects($this->exactly(2))->method('outputLine');

        $this->shell->expects($this->once())->method('kill')
            ->with($this->equalTo($this->sendSignalOptions['signal']), $this->equalTo('100'))
            ->will($this->returnValue(array('code' => 1, 'message' => 'Error message')));

        $this->sendSignalOptions['workers'] = array('host:100:queue');
        $this->shell->sendSignal($this->sendSignalOptions);
    }

    /**
     * @covers \Fresque\Fresque::sendSignal
     * @return  void
     */
    public function testSendSignalToAllWorkersWithAllOption()
    {
        $option = new \stdClass();
        $option->value = true;

        $this->input->expects($this->at(0))->method('getOption')->with($this->equalTo('force'))->will($this->returnValue($option));
        $this->input->expects($this->at(1))->method('getOption')->with($this->equalTo('all'))->will($this->returnValue($option));
        $this->input->expects($this->exactly(2))->method('getOption');

        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('testing workers'));
        $this->output->expects($this->at(0))->method('outputText')->with($this->stringContains('testing 100 ...'));
        $this->output->expects($this->at(1))->method('outputLine')->will($this->returnValue('done'));
        $this->output->expects($this->at(2))->method('outputText')->with($this->stringContains('testing 101 ...'));
        $this->output->expects($this->at(3))->method('outputLine')->will($this->returnValue('done'));
        $this->output->expects($this->at(4))->method('outputText')->with($this->stringContains('testing 102 ...'));
        $this->output->expects($this->at(5))->method('outputLine')->will($this->returnValue('done'));
        $this->output->expects($this->exactly(4))->method('outputLine');

        $this->shell->expects($this->exactly(3))->method('kill')->will($this->returnValue(array('code' => 0, 'message' => '')));

        $this->sendSignalOptions['workers'] = array(
            'host:100:queue',
            'host:101:queue',
            'host:102:queue'
        );
        $this->shell->sendSignal($this->sendSignalOptions);
    }

    /**
     * @covers \Fresque\Fresque::sendSignal
     * @return  void
     */
    public function testSendSignalToAllWorkersWithAllInput()
    {
        $option = new \stdClass();
        $option->value = false;

        $this->shell->expects($this->once())->method('getUserChoice')->will($this->returnValue('all'));

        $this->input->expects($this->at(0))->method('getOption')->with($this->equalTo('force'))->will($this->returnValue($option));
        $this->input->expects($this->at(1))->method('getOption')->with($this->equalTo('all'))->will($this->returnValue($option));
        $this->input->expects($this->exactly(2))->method('getOption');

        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('testing workers'));
        $this->output->expects($this->at(0))->method('outputText')->with($this->stringContains('testing 100 ...'));
        $this->output->expects($this->at(1))->method('outputLine')->will($this->returnValue('done'));
        $this->output->expects($this->at(2))->method('outputText')->with($this->stringContains('testing 101 ...'));
        $this->output->expects($this->at(3))->method('outputLine')->will($this->returnValue('done'));
        $this->output->expects($this->at(4))->method('outputText')->with($this->stringContains('testing 102 ...'));
        $this->output->expects($this->at(5))->method('outputLine')->will($this->returnValue('done'));
        $this->output->expects($this->exactly(4))->method('outputLine');

        $this->shell->expects($this->exactly(3))->method('kill')->will($this->returnValue(array('code' => 0, 'message' => '')));

        $this->sendSignalOptions['workers'] = array(
            'host:100:queue',
            'host:101:queue',
            'host:102:queue'
        );
        $this->shell->sendSignal($this->sendSignalOptions);
    }

    /**
     * @covers \Fresque\Fresque::sendSignal
     * @return  void
     */
    public function testSendSignalToOneWorkerWhenMultipleWorker()
    {
        $option = new \stdClass();
        $option->value = false;

        $this->shell->expects($this->once())->method('getUserChoice')->will($this->returnValue('2'));

        $this->input->expects($this->at(0))->method('getOption')->with($this->equalTo('force'))->will($this->returnValue($option));
        $this->input->expects($this->at(1))->method('getOption')->with($this->equalTo('all'))->will($this->returnValue($option));
        $this->input->expects($this->exactly(2))->method('getOption');

        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('testing workers'));
        $this->output->expects($this->at(0))->method('outputText')->with($this->stringContains('testing 101 ...'));
        $this->output->expects($this->at(1))->method('outputLine')->will($this->returnValue('done'));
        $this->output->expects($this->exactly(2))->method('outputLine');

        $this->shell->expects($this->exactly(1))->method('kill')->will($this->returnValue(array('code' => 0, 'message' => '')));

        $this->sendSignalOptions['workers'] = array(
            'host:100:queue',
            'host:101:queue',
            'host:102:queue'
        );
        $this->shell->sendSignal($this->sendSignalOptions);
    }

    /**
     * @covers \Fresque\Fresque::stats
     */
    public function testStats()
    {
        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('workers statistics'));

        $this->shell->stats();
        $this->markTestIncomplete();
    }

    /**
     * @covers \Fresque\Fresque::test
     */
    public function testTest()
    {
        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('testing configuration'));

        // $this->shell->test();
        $this->markTestIncomplete();
    }

    /**
     * @covers \Fresque\Fresque::testConfig
     */
    public function testTestConfig()
    {
        //$this->shell->testConfig();
        $this->markTestIncomplete();
    }

    /**
     * @covers \Fresque\Fresque::callCommand
     */
    public function testCallCommandWithValidCommand()
    {
        $shell = $this->shell;
        $shell::$Resque = $Resque = $this->getMockClass('\Resque', array('setBackend'));

        $Resque::staticExpects($this->once())->method('setBackend');

        $this->shell = $this->getMock('\Fresque\Fresque', array('start', 'help', 'loadSettings'));
        $this->shell->output = $this->output;
        $this->shell->input = $this->input;

        $helpOptions = new \stdClass();
        $helpOptions->value = false;

        $this->input->expects($this->once())->method('getOption')->with($this->equalTo('help'))->will($this->returnValue($helpOptions));

        $this->input->expects($this->once())->method('getOptionValues')->will($this->returnValue(array()));
        $this->shell->expects($this->once())->method('start');

        $this->shell->callCommand('start');
    }

    /**
     * @covers \Fresque\Fresque::callCommand
     */
    public function testCallCommandWithValidCommandButInvalidOptions()
    {
        $shell = $this->shell;
        $shell::$Resque = $Resque = $this->getMockClass('\Resque', array('setBackend'));

        $Resque::staticExpects($this->once())->method('setBackend');

        $this->shell = $this->getMock('\Fresque\Fresque', array('start', 'help', 'loadSettings'));
        $this->shell->output = $this->output;
        $this->shell->input = $this->input;

        $helpOptions = new \stdClass();
        $helpOptions->value = false;

        $this->input->expects($this->once())->method('getOption')->with($this->equalTo('help'))->will($this->returnValue($helpOptions));

        $options = array('tr' => '', 'br' => '', 'vr' => '', 'i' => '');

        $this->input->expects($this->once())->method('getOptionValues')->will($this->returnValue($options));
        $this->shell->expects($this->once())->method('start');

        $invalidOptions = $options;
        unset($invalidOptions['i']);
        $this->output->expects($this->at(0))->method('outputLine')->with($this->equalTo('Invalid options -' . implode(', -', array_keys($invalidOptions)) . ' will be ignored'));

        $this->shell->callCommand('start');
    }

    /**
     * @covers \Fresque\Fresque::callCommand
     */
    public function testCallCommandWithInvalidCommand()
    {
        $this->shell = $this->getMock('\Fresque\Fresque', array('help', 'loadSettings'));
        $this->shell->output = $this->output;
        $this->shell->input = $this->input;

        $this->shell->expects($this->never())->method('command');
        $this->shell->expects($this->once())->method('help')->with('command');
        $this->shell->callCommand('command');
    }
}
