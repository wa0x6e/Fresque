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

        $this->shell = $this->getMock('\Fresque\Fresque', array('callCommand', 'outputTitle', 'kill', 'getUserChoice', 'testConfig'));
        $this->shell->output = $this->output;
        $this->shell->input = $this->input;

        $this->shell->ResqueStatus = $this->ResqueStatus = $this->getMock('\ResqueStatus\ResqueStatus', array(), array(new \stdClass()));
        $this->shell->ResqueStats = $this->ResqueStats = $this->getMock('\Fresque\ResqueStats', array(), array(new \stdClass()));

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
            ),
            'Scheduler' => array(
                'lib' => './vendor/kamisama/phhp-resque-ex-scheduler',
                'log' => ''
            )
        );

        $this->sendSignalOptions = new \Fresque\SendSignalCommandOptions();

        $this->sendSignalOptions->title = 'Testing workers';
        $this->sendSignalOptions->noWorkersMessage = 'There is no workers to test';
        $this->sendSignalOptions->allOption = 'Test all workers';
        $this->sendSignalOptions->selectMessage = 'Worker to test';
        $this->sendSignalOptions->actionMessage = 'testing';
        $this->sendSignalOptions->listTitle = 'list of workers to test';
        $this->sendSignalOptions->workers = array();
        $this->sendSignalOptions->signal = 'TEST';
        $this->sendSignalOptions->successCallback = function ($pid) {
        };
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

        $this->shell = $this->getMock('\Fresque\Fresque', array('callCommand', 'outputTitle', 'exec', 'checkStartedWorker', 'getProcessOwner'));
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

    /**
     * Start a scheduler worker
     *
     * @covers \Fresque\Fresque::startScheduler
     * @return void
     */
    public function testStartScheduler() {
        $this->shell = $this->getMock('\Fresque\Fresque', array('callCommand', 'outputTitle', 'exec', 'checkStartedWorker', 'getProcessOwner'));
        $this->shell->output = $this->output;

        $pid = rand(0, 100);

        $this->shell->expects($this->never())->method('outputTitle');

        $this->shell->expects($this->once())->method('exec')->will($this->returnValue(true));
        $this->shell->expects($this->once())->method('checkStartedWorker')->will($this->returnValue(true));

        $this->output->expects($this->at(0))->method('outputText')->with($this->stringContains('Starting scheduler worker'));
        $this->output->expects($this->at(1))->method('outputText')->with($this->stringContains('.'));
        $this->output->expects($this->at(2))->method('outputText')->with($this->stringContains('.'));
        $this->output->expects($this->at(3))->method('outputText')->with($this->stringContains('.'));
        $this->output->expects($this->at(4))->method('outputLine')->with($this->stringContains('done'));
        $this->output->expects($this->exactly(1))->method('outputLine');
        $this->output->expects($this->exactly(4))->method('outputText');

        $this->ResqueStatus = $this->getMock(
            'ResqueStatus\ResqueStatus',
            array('isRunningSchedulerWorker', 'registerSchedulerWorker', 'addWorker'),
            array(new \stdClass())
        );

        $this->ResqueStatus->expects($this->once())->method('isRunningSchedulerWorker')->will($this->returnValue(false));
        $this->ResqueStatus->expects($this->once())->method('registerSchedulerWorker')->with($this->equalTo($pid));
        $this->ResqueStatus->expects($this->once())->method('addWorker');
        $this->shell->ResqueStatus = $this->ResqueStatus;

        $this->startArgs['Scheduler']['enabled'] = true;
        $this->shell->start($this->startArgs, true);
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

    /**
     * Load should returns an error message when there is nothing to load
     */
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
        $this->shell->runtime['Queues'] = array();
        $this->shell->runtime['Scheduler']['enabled'] = false;

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
        $this->shell->runtime['Queues'] = array($queue, $queue);
        $this->shell->runtime['Scheduler']['enabled'] = false;
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
                    'help' => 'Stop workers',
                    'options' => array('f', 'w', 'g', 'q', 'o'))
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

        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains($this->sendSignalOptions->title));
        $this->output->expects($this->at(0))->method('outputLine')->with($this->stringContains($this->sendSignalOptions->noWorkersMessage));
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

        $this->shell->expects($this->once())->method('kill')->with($this->equalTo($this->sendSignalOptions->signal), $this->equalTo('100'))->will($this->returnValue(array('code' => 0, 'message' => '')));

        $this->sendSignalOptions->workers = array('host:100:queue');
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
            ->with($this->equalTo($this->sendSignalOptions->signal), $this->equalTo('100'))
            ->will($this->returnValue(array('code' => 1, 'message' => 'Error message')));

        $this->sendSignalOptions->workers = array('host:100:queue');
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
        $this->input->expects($this->at(2))->method('getOption')->with($this->equalTo('queue'))->will($this->returnValue($option));
        $this->input->expects($this->at(3))->method('getOption')->with($this->equalTo('count'))->will($this->returnValue($option));
        $this->input->expects($this->exactly(4))->method('getOption');

        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('testing workers'));
        $this->output->expects($this->at(0))->method('outputText')->with($this->stringContains('testing 100 ...'));
        $this->output->expects($this->at(1))->method('outputLine')->will($this->returnValue('done'));
        $this->output->expects($this->at(2))->method('outputText')->with($this->stringContains('testing 101 ...'));
        $this->output->expects($this->at(3))->method('outputLine')->will($this->returnValue('done'));
        $this->output->expects($this->at(4))->method('outputText')->with($this->stringContains('testing 102 ...'));
        $this->output->expects($this->at(5))->method('outputLine')->will($this->returnValue('done'));
        $this->output->expects($this->exactly(4))->method('outputLine');

        $this->shell->expects($this->exactly(3))->method('kill')->will($this->returnValue(array('code' => 0, 'message' => '')));

        $this->sendSignalOptions->workers = array(
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
        $this->input->expects($this->at(2))->method('getOption')->with($this->equalTo('queue'))->will($this->returnValue($option));
        $this->input->expects($this->at(3))->method('getOption')->with($this->equalTo('count'))->will($this->returnValue($option));
        $this->input->expects($this->exactly(4))->method('getOption');

        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('testing workers'));
        $this->output->expects($this->at(0))->method('outputText')->with($this->stringContains('testing 100 ...'));
        $this->output->expects($this->at(1))->method('outputLine')->will($this->returnValue('done'));
        $this->output->expects($this->at(2))->method('outputText')->with($this->stringContains('testing 101 ...'));
        $this->output->expects($this->at(3))->method('outputLine')->will($this->returnValue('done'));
        $this->output->expects($this->at(4))->method('outputText')->with($this->stringContains('testing 102 ...'));
        $this->output->expects($this->at(5))->method('outputLine')->will($this->returnValue('done'));
        $this->output->expects($this->exactly(4))->method('outputLine');

        $this->shell->expects($this->exactly(3))->method('kill')->will($this->returnValue(array('code' => 0, 'message' => '')));

        $this->sendSignalOptions->workers = array(
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
        $this->input->expects($this->at(2))->method('getOption')->with($this->equalTo('queue'))->will($this->returnValue($option));
        $this->input->expects($this->at(3))->method('getOption')->with($this->equalTo('count'))->will($this->returnValue($option));
        $this->input->expects($this->exactly(4))->method('getOption');

        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('testing workers'));
        $this->output->expects($this->at(0))->method('outputText')->with($this->stringContains('testing 101 ...'));
        $this->output->expects($this->at(1))->method('outputLine')->will($this->returnValue('done'));
        $this->output->expects($this->exactly(2))->method('outputLine');

        $this->shell->expects($this->exactly(1))->method('kill')->will($this->returnValue(array('code' => 0, 'message' => '')));

        $this->sendSignalOptions->workers = array(
            'host:100:queue',
            'host:101:queue',
            'host:102:queue'
        );
        $this->shell->sendSignal($this->sendSignalOptions);
    }


    /**
     * Stop will send the QUIT signal and the active workers list to sendSignal()
     *
     * @covers \Fresque\Fresque::stop
     */
    public function testStop()
    {
        $shell = $this->getMock('\Fresque\Fresque', array('callCommand', 'outputTitle', 'kill', 'getUserChoice', 'sendSignal'));
        $shell->ResqueStatus = $this->ResqueStatus = $this->getMock('\ResqueStatus\ResqueStatus', array(), array(new \stdClass()));

        $workers = array('test', 'testOne');

        $shell->expects($this->once())->method('sendSignal')->with(
            $this->logicalAnd(
                $this->attributeEqualTo('signal', 'QUIT'),
                $this->attributeEqualTo('workers', $workers)
            )
        );

        $shell::$Resque_Worker = $Resque_Worker = $this->getMockClass('\Resque_Worker', array('all'));
        $Resque_Worker::staticExpects($this->once())->method('all')->will($this->returnValue($workers));

        $shell->stop();
    }

    /**
     * Stop will send the TERM signal if 'force' option is selected
     *
     * @covers \Fresque\Fresque::stop
     */
    public function testForceStop()
    {
        $option = new \stdClass();
        $option->value = true;

        $shell = $this->getMock('\Fresque\Fresque', array('callCommand', 'outputTitle', 'kill', 'getUserChoice', 'sendSignal'));
        $shell->input = $this->input;
        $shell->input->expects($this->at(0))->method('getOption')->with($this->equalTo('force'))->will($this->returnValue($option));
        $shell->ResqueStatus = $this->ResqueStatus = $this->getMock('\ResqueStatus\ResqueStatus', array(), array(new \stdClass()));

        $shell->expects($this->once())->method('sendSignal')->with(
            $this->attributeEqualTo('signal', 'TERM')
        );

        $shell->stop();
    }
    
    /**
     * Pause will send the USR2 signal and the active workers list to sendSignal()
     *
     * @covers \Fresque\Fresque::pause
     */
    public function testPause()
    {
        $shell = $this->getMock('\Fresque\Fresque', array('callCommand', 'outputTitle', 'kill', 'getUserChoice', 'sendSignal'));
        $shell->ResqueStatus = $this->getMock('\ResqueStatus\ResqueStatus', array('getPausedWorker'), array(new \stdClass()));
        $shell->ResqueStatus->expects($this->once())->method('getPausedWorker')->will($this->returnValue(array()));

        $workers = array('test', 'testOne');

        $shell->expects($this->once())->method('sendSignal')->with(
            $this->logicalAnd(
                $this->attributeEqualTo('signal', 'USR2'),
                $this->attributeEqualTo('workers', $workers)
            )
        );

        $shell::$Resque_Worker = $Resque_Worker = $this->getMockClass('\Resque_Worker', array('all'));
        $Resque_Worker::staticExpects($this->once())->method('all')->will($this->returnValue($workers));

        $shell->pause();
    }

    /**
     * Resume will send the CONT signal and the paused workers list to sendSignal()
     *
     * @covers \Fresque\Fresque::resume
     */
    public function testResume()
    {
        $workers = array('test', 'testOne');

        $shell = $this->getMock('\Fresque\Fresque', array('callCommand', 'outputTitle', 'kill', 'getUserChoice', 'sendSignal'));
        $shell->ResqueStatus = $this->getMock('\ResqueStatus\ResqueStatus', array('getPausedWorker'), array(new \stdClass()));
        $shell->ResqueStatus->expects($this->once())->method('getPausedWorker')->will($this->returnValue($workers));

        $shell->expects($this->once())->method('sendSignal')->with(
            $this->logicalAnd(
                $this->attributeEqualTo('signal', 'CONT'),
                $this->attributeEqualTo('workers', $workers)
            )
        );

        $shell->resume();
    }


    /**
     * @covers \Fresque\Fresque::stats
     */
    public function testStats()
    {
        $datas = array(
            array(
                'host' => 'w1',
                'pid' => 0,
                'queue' => 'queue1',
                'processed' => 15,
                'failed' => 0
            ),
            array(
                'host' => 'w2',
                'pid' => 0,
                'queue' => 'queue2',
                'processed' => 9,
                'failed' => 5
            )
        );

        $workersList = array(
            new DummyWorker($datas[0]['host'] . ':' . $datas[0]['pid'] . ':' . $datas[0]['queue'], $datas[0]['processed'], $datas[0]['failed']),
            new DummyWorker($datas[1]['host'] . ':' . $datas[1]['pid'] . ':' . $datas[1]['queue'], $datas[1]['processed'], $datas[1]['failed']),
        );

        $this->shell->expects($this->once())->method('outputTitle')->with($this->stringContains('resque statistics'));
        $this->shell->ResqueStats->expects($this->once())->method('getQueues')->will($this->returnValue(array('queue1', 'queue2', 'queue3', 'queue4')));
        $this->shell->ResqueStats->expects($this->once())->method('getWorkers')->will($this->returnValue($workersList));
        $this->ResqueStatus->expects($this->once())->method('getPausedWorker')->will($this->returnValue(array('w1:0:queue1')));

        $this->shell->ResqueStats->expects($this->at(2))->method('getQueueLength')->with($this->stringContains('queue4'))->will($this->returnValue(0));
        $this->shell->ResqueStats->expects($this->at(3))->method('getQueueLength')->with($this->stringContains('queue3'))->will($this->returnValue(9));
        $this->shell->ResqueStats->expects($this->at(4))->method('getQueueLength')->with($this->stringContains($datas[1]['queue']))->will($this->returnValue(10));
        $this->shell->ResqueStats->expects($this->at(5))->method('getQueueLength')->with($this->stringContains($datas[0]['queue']))->will($this->returnValue(3));

        $this->output->expects($this->at(5))->method('outputLine')->with($this->stringContains('queues stats'));
        $this->output->expects($this->at(6))->method('outputLine')->with($this->stringContains('queues count : 3'));
        $this->output->expects($this->at(7))->method('outputText')->with($this->stringContains($datas[0]['queue']));
        $this->output->expects($this->at(7))->method('outputText')->with($this->stringContains('3 pending jobs'));
        $this->output->expects($this->at(9))->method('outputText')->with($this->stringContains($datas[1]['queue']));
        $this->output->expects($this->at(9))->method('outputText')->with($this->stringContains('10 pending jobs'));
        $this->output->expects($this->at(11))->method('outputText')->with($this->stringContains('queue3'));
        $this->output->expects($this->at(11))->method('outputText')->with($this->stringContains('9 pending jobs'));
        $this->output->expects($this->at(12))->method('outputText')->with($this->stringContains('(unmonitored queue)'));

        $this->output->expects($this->at(15))->method('outputLine')->with($this->stringContains('workers stats'));
        $this->output->expects($this->at(16))->method('outputLine')->with($this->stringContains('active workers : ' . count($workersList)));

        $this->output->expects($this->at(17))->method('outputText')->with($this->stringContains('worker : ' . (string)$workersList[0]));
        $this->output->expects($this->at(18))->method('outputText')->with($this->stringContains('(paused)'));
        $this->output->expects($this->at(22))->method('outputLine')->with($this->stringContains('processed jobs : ' . $datas[0]['processed']));
        $this->output->expects($this->at(23))->method('outputLine')->with($this->stringContains('failed jobs    : ' . $datas[0]['failed']));

        $this->output->expects($this->at(24))->method('outputText')->with($this->stringContains('worker : ' . (string)$workersList[1]));
        $this->output->expects($this->at(28))->method('outputLine')->with($this->stringContains('processed jobs : ' . $datas[1]['processed']));
        $this->output->expects($this->at(29))->method('outputLine')->with($this->stringContains('failed jobs    : ' . $datas[1]['failed']));

        $this->output->expects($this->at(30))->method('outputLine');


        $this->shell->stats();
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

    /**
     * @covers \Fresque\Fresque::reset
     * @return void
     */
    public function testReset()
    {
        $this->ResqueStatus->expects($this->once())->method('clearWorkers');
        $this->ResqueStatus->expects($this->once())->method('unregisterSchedulerWorker');

        $this->shell->reset();
    }

    /**
     * loadSettings is using the default fresque.ini
     */
    public function testLoadSettingsUsingDefaultConfigFile()
    {
        $option = new \stdClass();
        $option->value = false;
        $this->input->expects($this->once())->method('getOption')->will($this->returnValue($option));

        $this->shell->loadSettings('');

        $this->assertEquals('./fresque.ini', $this->shell->config);
    }

    /**
     * loadSettings should die if .ini file does not exists
     * Setting from $args argument
     */
    public function testLoadSettingsUsingInexistingConfigFileFromArgs()
    {
        $iniFile = 'inexisting_file.ini';
        $option = new \stdClass();
        $option->value = false;
        $this->input->expects($this->never())->method('getOptionValues');
        $this->input->expects($this->never())->method('getOption');
        $this->output->expects($this->once())->method('outputLine')->with($this->stringContains('The config file \'' . $iniFile . '\' was not found'));

        $return = $this->shell->loadSettings('', array('config' => $iniFile));

        $this->assertEquals($iniFile, $this->shell->config);
        $this->assertEquals(false, $return);
    }

    /**
     * loadSettings should die if .ini file does not exists
     * Setting from cli option
     */
    public function testLoadSettingsUsingInexistingConfigFileFromOption()
    {
        $iniFile = 'inexisting_file.ini';
        $option = new \stdClass();
        $option->value = false;
        $this->input->expects($this->once())->method('getOptionValues')->will(
            $this->returnValue(array('config' => $iniFile))
        );
        $this->input->expects($this->never())->method('getOption');
        $this->output->expects($this->once())->method('outputLine')->with($this->stringContains('The config file \'' . $iniFile . '\' was not found'));

        $return = $this->shell->loadSettings('');

        $this->assertEquals($iniFile, $this->shell->config);
        $this->assertEquals(false, $return);
    }

    /**
     * loadSettings is using debug false by default
     */
    public function testLoadSettingsWithDebugToFalse()
    {
        $option = new \stdClass();
        $option->value = false;
        $this->input->expects($this->once())->method('getOptionValues');
        $this->input->expects($this->once())->method('getOption')->with($this->equalTo('verbose'))->will($this->returnValue($option));

        $return = $this->shell->loadSettings('');

        $this->assertEquals(false, $this->shell->debug);
        $this->assertEquals(true, $return);
    }

    /**
     * loadSettings is using debug setting from arguments
     */
    public function testLoadSettingsWithDebugFromArgs()
    {
        $option = new \stdClass();
        $option->value = false;
        $this->input->expects($this->never())->method('getOptionValues');
        $this->input->expects($this->once())->method('getOption')->with($this->equalTo('verbose'))->will($this->returnValue($option));

        $return = $this->shell->loadSettings('', array('debug' => true));

        $this->assertEquals(true, $this->shell->debug);
        $this->assertEquals(true, $return);
    }

    /**
     * loadSettings is using debug setting from arguments
     */
    public function testLoadSettingsWithDebugFromOption()
    {
        $option = new \stdClass();
        $option->value = false;
        $this->input->expects($this->once())->method('getOptionValues')->will($this->returnValue(array(
            'debug' => true
        )));
        $this->input->expects($this->once())->method('getOption')->with($this->equalTo('verbose'))->will($this->returnValue($option));

        $return = $this->shell->loadSettings('');

        $this->assertEquals(true, $this->shell->debug);
        $this->assertEquals(true, $return);
    }

    /**
     * loadSettings is using default verbose from .ini file
     */
    public function testLoadSettingsWithDefaultVerbose()
    {
        $option = new \stdClass();
        $option->value = false;
        $this->input->expects($this->once())->method('getOptionValues');
        $this->input->expects($this->once())->method('getOption')->with($this->equalTo('verbose'))->will($this->returnValue($option));

        $return = $this->shell->loadSettings('');

        $this->assertEquals(false, $this->shell->runtime['Default']['verbose']);
        $this->assertEquals(true, $return);
    }

    /**
     * loadSettings is using verbose from cli option
     */
    public function testLoadSettingsWithVerboseFromOption()
    {
        $option = new \stdClass();
        $option->value = true;
        $this->input->expects($this->never())->method('getOptionValues');
        $this->input->expects($this->any())->method('getOption')->with($this->equalTo('verbose'))->will($this->returnValue($option));

        $return = $this->shell->loadSettings('', array('verbose' => false));

        $this->assertEquals(true, $this->shell->runtime['Default']['verbose']);
        $this->assertEquals(true, $return);
    }

    /**
     * loadSettings call testConfig when not a test command
     */
    public function testLoadSettingCallForTestConfig()
    {
        $testResults = array(
            'name1' => true,
            'name2' => true
        );

        $option = new \stdClass();
        $option->value = true;
        $this->input->expects($this->once())->method('getOptionValues');
        $this->input->expects($this->any())->method('getOption')->with($this->equalTo('verbose'))->will($this->returnValue($option));
        $this->shell->expects($this->once())->method('testConfig')->will($this->returnValue($testResults));

        $return = $this->shell->loadSettings('');

        $this->assertEquals(true, $return);
    }

    /**
     * loadSettings call testConfig when not a test command
     */
    public function testLoadSettingDoNotCallForTestConfigOnTestCommand()
    {
        $option = new \stdClass();
        $option->value = true;
        $this->input->expects($this->once())->method('getOptionValues');
        $this->input->expects($this->any())->method('getOption')->with($this->equalTo('verbose'))->will($this->returnValue($option));
        $this->shell->expects($this->never())->method('testConfig');

        $return = $this->shell->loadSettings('test');

        $this->assertEquals(true, $return);
    }

    /**
     * loadSettings die when settings contains errors
     */
    public function testLoadSettingsDieWhenConfigContainsError()
    {
        $errors = array(
            'name1' => 'message1',
            'name2' => 'message2'
        );

        $option = new \stdClass();
        $option->value = true;
        $this->input->expects($this->once())->method('getOptionValues');
        $this->input->expects($this->any())->method('getOption')->with($this->equalTo('verbose'))->will($this->returnValue($option));
        $this->shell->expects($this->once())->method('testConfig')->will($this->returnValue($errors));
        $this->output->expects($this->at(0))->method('outputLine')->with($this->equalTo($errors['name1']));
        $this->output->expects($this->at(1))->method('outputLine')->with($this->equalTo($errors['name2']));
        $this->output->expects($this->at(2))->method('outputLine')->with();

        $return = $this->shell->loadSettings('');

        $this->assertEquals(false, $return);
    }

    /**
     * loadSettings will override .ini file settings with cli option
     */
    public function testLoadSettingsOverrideDefaultSettingsWithCLIOption()
    {
        $cliOption = array('host' => 'testhost', 'include' => 'custom.php');
        $option = new \stdClass();
        $option->value = false;
        $this->input->expects($this->once())->method('getOptionValues')->will($this->returnValue($cliOption));
        $this->input->expects($this->once())->method('getOption')->with($this->equalTo('verbose'))->will($this->returnValue($option));

        $return = $this->shell->loadSettings('');

        // New settings from CLI
        $this->assertEquals($cliOption['host'], $this->shell->runtime['Redis']['host']);
        $this->assertEquals($cliOption['include'], $this->shell->runtime['Fresque']['include']);

        // Other settings did not change
        $this->assertEquals(6379, $this->shell->runtime['Redis']['port']);

        $this->assertEquals(true, $return);
    }

    /**
     * loadSettings setup Queues for load command
     */
    public function testLoadSettingsSetupQueuesForLoadCommand()
    {
        $option = new \stdClass();
        $option->value = false;
        $this->input->expects($this->once())->method('getOptionValues')->will($this->returnValue(array('config' => __DIR__ . DS . 'test_fresque.ini')));
        $this->input->expects($this->once())->method('getOption')->with($this->equalTo('verbose'))->will($this->returnValue($option));

        $return = $this->shell->loadSettings('');

        $config = parse_ini_file(__DIR__ . DS . 'test_fresque.ini', true);

        $config['Queues']['activity']['queue'] = 'activity';

        $this->assertEquals($config['Queues'], $this->shell->runtime['Queues']);
        $this->assertEquals(true, $return);
    }


}


class DummyWorker
{
    public function __construct($name, $processedStat = 0, $failedStat = 0)
    {
        $this->name = $name;
        $this->processedStat = $processedStat;
        $this->failedStat = $failedStat;
    }

    public function getStat($cat)
    {
        switch($cat) {
            case 'processed' : return $this->processedStat;
            case 'failed' : return $this->failedStat;
        }
    }

    public function __toString()
    {
        return $this->name;
    }
}
