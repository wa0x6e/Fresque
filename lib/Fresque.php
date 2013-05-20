<?php
/**
 * Fresque Class File
 *
 * PHP 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @link       https://github.com/kamisama/Fresque
 * @since      0.1.0
 * @package    Fresque
 * @subpackage Fresque.lib
 * @author     Wan Qi Chen <kami@kamisama.me>
 * @copyright  Copyright 2012, Wan Qi Chen <kami@kamisama.me>
 *
 * @license    MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Fresque;

define('DS', DIRECTORY_SEPARATOR);
include __DIR__ . DS . 'DialogMenuValidator.php';

/**
 * Fresque Class
 *
 * @package Fresque.lib
 * @since   0.1.0
 */
class Fresque
{
    public $input;
    public $output;

    public $settings;
    public $runtime;

    public $debug = false;

    /**
     * @var Resque Classname
     */
    public static $Resque = '\Resque';

    public static $Resque_Worker = '\Resque_Worker';

    public static $checkStartedWorkerBufferTime = 100000;

    const VERSION = '2.0.0';

    public function __construct()
    {
        $command = array_splice($_SERVER['argv'], 1, 1);
        $command = empty($command) ? null : $command[0];

        $this->input = new \ezcConsoleInput();
        $this->output = new \ezcConsoleOutput();

        $this->input->registerOption(
            new \ezcConsoleOption(
                'u',
                'user',
                \ezcConsoleInput::TYPE_STRING,
                null,
                false,
                'User running the workers',
                'User running the workers'
            )
        );

        $this->input->registerOption(
            new \ezcConsoleOption(
                'q',
                'queue',
                \ezcConsoleInput::TYPE_STRING,
                null,
                false,
                'Name of the queue. If multiple queues, separate with comma.',
                'Name of the queue. If multiple queues, separate with comma.'
            )
        );

        $this->input->registerOption(
            new \ezcConsoleOption(
                'i',
                'interval',
                \ezcConsoleInput::TYPE_INT,
                null,
                false,
                'Pause time in seconds between each worker round',
                'Pause time in seconds between each worker round'
            )
        );

        $this->input->registerOption(
            new \ezcConsoleOption(
                'n',
                'workers',
                \ezcConsoleInput::TYPE_INT,
                null,
                false,
                'Number of workers to create',
                'Number of workers to create'
            )
        );

        $this->input->registerOption(
            new \ezcConsoleOption(
                'f',
                'force',
                \ezcConsoleInput::TYPE_NONE,
                null,
                false,
                'Force workers shutdown, forcing all the current jobs to finish (and fail)',
                'Force workers shutdown, forcing all the current jobs to finish (and fail)'
            )
        );

        $this->input->registerOption(
            new \ezcConsoleOption(
                'v',
                'verbose',
                \ezcConsoleInput::TYPE_NONE,
                null,
                false,
                'Log more verbose informations',
                'Log more verbose informations'
            )
        );

        $this->input->registerOption(
            new \ezcConsoleOption(
                'g',
                'debug',
                \ezcConsoleInput::TYPE_NONE,
                null,
                false,
                'Print debug informations',
                'Print debug informations'
            )
        );

        $this->input->registerOption(
            new \ezcConsoleOption(
                's',
                'host',
                \ezcConsoleInput::TYPE_STRING,
                null,
                false,
                'Redis server hostname',
                'Redis server hostname (eg. localhost, 127.0.0.1, etc ...)'
            )
        );

        $this->input->registerOption(
            new \ezcConsoleOption(
                'p',
                'port',
                \ezcConsoleInput::TYPE_INT,
                null,
                false,
                'Redis server port',
                'Redis server port'
            )
        );

        $this->input->registerOption(
            new \ezcConsoleOption(
                'l',
                'log',
                \ezcConsoleInput::TYPE_STRING,
                null,
                false,
                'Log file path',
                'Absolute path to the log file'
            )
        );

        $this->input->registerOption(
            new \ezcConsoleOption(
                'b',
                'lib',
                \ezcConsoleInput::TYPE_STRING,
                null,
                false,
                'PHPresque library path',
                'Absolute path to your PHPResque library'
            )
        );

        $this->input->registerOption(
            new \ezcConsoleOption(
                'a',
                'autoloader',
                \ezcConsoleInput::TYPE_STRING,
                null,
                false,
                'Application autoloader path',
                'Absolute path to your application autoloader file'
            )
        );

        $this->input->registerOption(
            new \ezcConsoleOption(
                'c',
                'config',
                \ezcConsoleInput::TYPE_STRING,
                null,
                false,
                'Configuration file path',
                'Absolute path to your configuration file'
            )
        );

        $this->input->registerOption(
            new \ezcConsoleOption(
                'd',
                'loghandler',
                \ezcConsoleInput::TYPE_STRING,
                null,
                false,
                'Log Handler',
                'Handler used for logging'
            )
        );

        $this->input->registerOption(
            new \ezcConsoleOption(
                'r',
                'handlertarget',
                \ezcConsoleInput::TYPE_STRING,
                null,
                false,
                'Log Handler options',
                'Arguments used for initializing the handler'
            )
        );

        $this->input->registerOption(
            new \ezcConsoleOption(
                'w',
                'all',
                \ezcConsoleInput::TYPE_NONE,
                null,
                false,
                'Stop all workers',
                'Stop all workers'
            )
        );

        $this->output->formats->title->color = 'yellow';
        $this->output->formats->title->style = 'bold';

        $this->output->formats->subtitle->color = 'blue';
        $this->output->formats->subtitle->style = 'bold';

        $this->output->formats->warning->color = 'red';

        $this->output->formats->bold->style = 'bold';

        $this->output->formats->highlight->color = 'blue';

        $this->output->formats->success->color = 'green';
        $this->output->formats->success->style = 'normal';

        try {
            $this->input->process();
        } catch (\ezcConsoleException $e) {
            $this->output->outputLine($e->getMessage() . "\n", 'failure');
            die();

        }

        $this->callCommand($command);
    }

    protected function registerHelpOption()
    {
        $helpOption = $this->input->registerOption(new \ezcConsoleOption('h', 'help'));
        $helpOption->isHelpOption = true;
    }

    /**
     *
     * @since  2.0.0
     * @return  void
     */
    public function callCommand($command)
    {
        $helpOption = $this->registerHelpOption();

        $settings = $this->loadSettings();

        $args = $this->input->getArguments();

        $globalOptions = array('s' => 'host', 'p' => 'port', 'b' => 'path',
            'c' => 'path', 'a' => 'path', 'd' => 'handler', 'r' => 'args,'
        );

        $this->commandTree = array(
            'start' => array(
                    'help' => 'Start a new worker',
                    'options' => array('u' => 'username', 'q' => 'queue name',
                            'i' => 'num', 'n' => 'num', 'l' => 'path', 'v', 'g')),
            'stop' => array(
                    'help' => 'Shutdown all workers',
                    'options' => array('f', 'w', 'g')),
            'restart' => array(
                    'help' => 'Restart all workers',
                    'options' => array()),
            'load' => array(
                    'help' => 'Load workers defined in your configuration file',
                    'options' => array('l')),
            'tail' => array(
                    'help' => 'Monitor the log file',
                    'options' => array()),
            'enqueue' => array(
                    'help' => 'Enqueue a new job',
                    'options' => array()),
            'stats' => array(
                    'help' => 'Display resque statistics',
                    'options' => array()),
            'test' => array(
                    'help' => 'Test your fresque configuration file',
                    'options' => array('u' => 'username', 'q' => 'queue name',
                            'i' => 'num', 'n' => 'num', 'l' => 'path')),
            'help' => array(
                    'help' => 'Print help',
                    'options' => array()),
        );

        if ($command === null || !array_key_exists($command, $this->commandTree)) {
            $this->help($command);
        } else {
            if ($helpOption->value === true) {
                $this->output->outputLine();
                $this->output->outputLine($this->commandTree[$command]['help']);

                if (!empty($this->commandTree[$command]['options'])) {
                    $this->output->outputLine("\nAvailable options\n", 'subtitle');

                    foreach ($this->commandTree[$command]['options'] as $name => $arg) {
                        $opt = $this->input->getOption(is_numeric($name) ? $arg : $name);
                        $o = (!empty($opt->short)
                            ? '-' . $opt->short : '  ') . ' ' . (is_numeric($name) ? ''
                            : '<'.$arg. '>');

                        $this->output->outputLine(
                            $o . str_repeat(' ', 15 - strlen($o)) . " --"
                            . $opt->long . str_repeat(' ', 15 - strlen($opt->long)) . " {$opt->longhelp}"
                        );
                    }
                }

                $this->output->outputLine("\nGlobal options\n", 'subtitle');

                foreach ($globalOptions as $name => $arg) {
                    $opt = $this->input->getOption(is_numeric($name) ? $arg : $name);
                    $o = '-' . $opt->short . ' ' . (is_numeric($name) ? '' : '<'.$arg. '>');

                    $this->output->outputLine(
                        $o . str_repeat(' ', 15 - strlen($o)) . " --"
                        . $opt->long . str_repeat(' ', 15 - strlen($opt->long)) . " {$opt->longhelp}"
                    );
                }

                $this->output->outputLine();

            } else {
                $allowed = array_merge($this->commandTree[$command]['options'], $globalOptions);
                foreach ($allowed as $name => &$arg) {
                    if (!is_numeric($name)) {
                        $arg = $name;
                    }
                }

                $unrecognized = array_diff(array_keys($this->input->getOptionValues()), array_values($allowed));
                if (!empty($unrecognized)) {
                    $this->output->outputLine(
                        'Invalid options ' . implode(
                            ', ',
                            array_map(
                                function ($opt) {
                                    return '-' . $opt;
                                },
                                $unrecognized
                            )
                        ) . ' will be ignored',
                        'warning'
                    );
                }
                call_user_func_array(
                    self::$Resque . '::setBackend',
                    array(
                        $this->runtime['Redis']['host'] . ':' . $this->runtime['Redis']['port'],
                        $this->runtime['Redis']['database'],
                        $this->runtime['Redis']['namespace']
                    )
                );

                $this->ResqueStatus = new \ResqueStatus\ResqueStatus(\Resque::Redis());
                $this->{$command}();
            }
        }
    }


    /**
     * Start workers
     *
     * @return  void
     */
    public function start($args = null)
    {
        if ($args === null) {
            $this->outputTitle('Creating workers');
        } else {
            $this->runtime = $args;
        }

        $pidFile = dirname(__DIR__) . DS . 'tmp' . DS . str_replace('.', '', microtime(true));
        $count = $this->runtime['Default']['workers'];

        $this->debug('Will start ' . $count . ' workers');

        for ($i = 1; $i <= $count; $i++) {

            $cmd = 'nohup sudo -u '. escapeshellarg($this->runtime['Default']['user']) . " \\\n".
            'bash -c "cd ' .
            escapeshellarg($this->runtime['Fresque']['lib']) . '; ' . " \\\n".
            (($this->runtime['Default']['verbose']) ? 'VVERBOSE' : 'VERBOSE') . '=true ' . " \\\n".
            'QUEUE=' . escapeshellarg($this->runtime['Default']['queue']) . " \\\n".
            'PIDFILE=' . escapeshellarg($pidFile) . " \\\n".
            'APP_INCLUDE=' . escapeshellarg($this->runtime['Fresque']['include']) . " \\\n".
            'INTERVAL=' . escapeshellarg($this->runtime['Default']['interval']) . " \\\n".
            'REDIS_BACKEND=' . escapeshellarg($this->runtime['Redis']['host'] . ':' . $this->runtime['Redis']['port']) . " \\\n".
            'REDIS_DATABASE=' . escapeshellarg($this->runtime['Redis']['database']) . " \\\n".
            'REDIS_NAMESPACE=' . escapeshellarg($this->runtime['Redis']['namespace']) . " \\\n".
            'COUNT=' . 1 . " \\\n".
            'LOGHANDLER=' . escapeshellarg($this->runtime['Log']['handler']) . " \\\n".
            'LOGHANDLERTARGET=' . escapeshellarg($this->runtime['Log']['target']) . " \\\n".
            'php ' . $this->getResqueBinFile($this->runtime['Fresque']['lib']) . " \\\n";
            $cmd .= ' >> '. escapeshellarg($this->runtime['Log']['filename']).' 2>&1" >/dev/null 2>&1 &';

            $this->debug('Starting worker (' . $i . ')');
            $this->debug("Running command :\n\t" . str_replace("\n", "\n\t", $cmd));

            $this->exec($cmd);

            $this->output->outputText('Starting worker ');

            $success = false;
            $attempt = 7;
            while ($attempt-- > 0) {
                for ($i = 0; $i < 3; $i++) {
                    $this->output->outputText(".", 0);
                    usleep(self::$checkStartedWorkerBufferTime);
                }

                if (false !== $pid = $this->checkStartedWorker($pidFile)) {

                    $success = true;
                    $this->output->outputLine(' Done', 'success');

                    $this->debug('Registering worker #' . $pid . ' to list of active workers');

                    $workerSettings = $this->runtime;
                    $workerSettings['workers'] = 1;
                    $this->ResqueStatus->addWorker($pid, $workerSettings);

                    break;
                }
            }

            if (!$success) {
                $this->output->outputLine(' Fail', 'failure');
            }
        }

        if ($args === null) {
            $this->output->outputLine();
        }
    }


    /**
     * Stop workers
     *
     * @return  void
     */
    public function stop()
    {
        $this->outputTitle('Stopping Workers');

        $force = $this->input->getOption('force')->value;
        $all = $this->input->getOption('all')->value;

        if ($force) {
            $this->debug("'Force' option detected, will force shutdown workers");
        }

        if ($all) {
            $this->debug("'All' option detected, will shutdown all workers");
        }

        $this->debug("Searching for active workers");

        $workers = call_user_func(self::$Resque_Worker. '::all');
        sort($workers);
        if (empty($workers)) {
            $this->output->outputLine('There is no active workers to stop ...', 'failure');
        } else {
            $this->debug("Found " . count($workers) . " active workers");

            $workersToKill = array();

            if (!$all && count($workers) > 1) {
                $i = 1;
                $menuItems = array();
                foreach ($workers as $worker) {
                    $menuItems[$i++] = sprintf(
                        "%s, started %s ago",
                        $worker,
                        $this->formatDateDiff(\Resque::Redis()->get('worker:' . $worker . ':started'))
                    );
                }

                if (count($menuItems) > 1) {
                    $menuItems['all'] = 'Stop all workers';

                    $menuOptions = new \ezcConsoleMenuDialogOptions(
                        array(
                            'text' => 'Active workers list',
                            'selectText' => 'Worker to stop :',
                            'validator' => new DialogMenuValidator($menuItems)
                        )
                    );
                    $menuDialog = new \ezcConsoleMenuDialog($this->output, $menuOptions);
                    do {
                        $menuDialog->display();
                    } while ($menuDialog->hasValidResult() === false);

                    $menuDialog->getResult();

                    if ($menuDialog->getResult() == 'all') {
                        $workerIndex = range(1, count($workers));
                    } else {
                        $workerIndex[] = $menuDialog->getResult();
                    }
                } else {
                    $workerIndex[] = 1;
                }

            } else {
                $workerIndex = range(1, count($workers));
            }

            foreach ($workerIndex as $index) {

                $worker = $workers[$index- 1];

                list($hostname, $pid, $queue) = explode(':', (string)$worker);
                $this->output->outputText('Stopping ' . $pid . ' ... ');
                $signal = $force ? 'TERM' : 'QUIT';

                $killResponse = $this->kill($signal, $pid);
                $this->ResqueStatus->removeWorker($pid);

                if ($killResponse['code'] === 0) {
                    $this->output->outputLine('Done', 'success');
                } else {
                    $this->output->outputLine($message, 'failure');
                }
            }
        }

        $this->output->outputLine();
    }


    /**
     * Load workers from configuration
     *
     * @return  void
     */
    public function load()
    {
        $this->outputTitle('Loading predefined workers');

        if (!isset($this->settings['Queues']) || empty($this->settings['Queues'])) {
            $this->output->outputLine("You have no configured workers to load.\n", 'failure');
        } else {
            $this->output->outputLine(sprintf('Loading %s workers', count($this->settings['Queues'])));

            $config = $this->config;
            $debug = $this->debug;

            foreach ($this->settings['Queues'] as $queue) {
                $queue['config'] = $config;
                $queue['debug'] = $debug;
                $this->loadSettings($queue);
                $this->start($this->runtime);
            }
        }

        $this->output->outputLine();
    }


    /**
     * Restart all workers
     *
     * @return  void
     */
    public function restart()
    {
        $workers = $this->ResqueStatus->getWorkers();

        $this->outputTitle('Restarting workers');

        if (!empty($workers)) {
            $this->stop();

            foreach ($workers as $worker) {
                $this->start($worker);
            }
        } else {
            $this->output->outputLine('No workers to restart', 'failure');
        }

        $this->output->outputLine();
    }


    /**
     * Tail a log file
     *
     * If more than one log file exists, will display a menu dialog with a list
     * of log files to choose from.
     *
     * @return  void
     */
    public function tail()
    {
        $logs = array();
        $i = 1;
        $workers = $this->ResqueStatus->getWorkers();

        foreach ($workers as $worker) {
            if ($worker['Log']['filename'] != '') {
                $logs[] = $worker['Log']['filename'];
            }
            if ($worker['Log']['handler'] == 'RotatingFile') {
                $fileInfo = pathinfo($worker['Log']['target']);
                $pattern = $fileInfo['dirname'] . DS . $fileInfo['filename'] . '-*' .
                (!empty($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '');

                $logs = array_merge($logs, glob($pattern));
            }
        }

        $logs = array_values(array_unique($logs));

        $this->outputTitle('Tailing log file');
        if (empty($logs)) {
            $this->output->outputLine('No log file to tail', 'failure');
            return;
        } elseif (count($logs) == 1) {
            $index = 1;
        } else {
            $menuOptions = new \ezcConsoleMenuDialogOptions(
                array(
                    'text' => 'Log files list',
                    'selectText' => 'Log to tail :',
                    'validator' => new DialogMenuValidator(array_combine(range(1, count($logs)), $logs))
                )
            );
            $menuDialog = new \ezcConsoleMenuDialog($this->output, $menuOptions);
            do {
                $menuDialog->display();
            } while ($menuDialog->hasValidResult() === false);

            $index = $menuDialog->getResult();
        }

        $this->output->outputLine('Tailing ' . $logs[$index - 1], 'subtitle');
        $this->tailCommand($logs[$index - 1]);
    }


    /**
     * Add a job to a queue
     *
     * @return  void
     */
    public function enqueue()
    {
        $this->outputTitle('Queuing a job');

        $args = $this->input->getArguments();

        if (count($args) >= 2) {
            $queue = array_shift($args);
            $class = array_shift($args);

            $result = call_user_func_array(self::$Resque . '::enqueue', array($queue, $class, $args));
            $this->output->outputLine("The job was enqueued successfully", 'success');
            $this->output->outputLine('Job ID : #' . $result . "\n");
        } else {
            $this->output->outputLine('Enqueue takes at least 2 arguments', 'failure');
            $this->output->outputLine('Usage : enqueue <queue> <job> <args>');
            $this->output->outputLine('   queue <string>  Name of the queue');
            $this->output->outputLine('   job   <string>  Job class name');
            $this->output->outputLine('   args  <string>  Comma separated list of arguments');
            $this->output->outputLine();
        }
    }


    /**
     * Print some stats about the workers
     *
     * @return  void
     */
    public function stats()
    {
        $this->outputTitle('Workers statistics');

        $this->output->outputLine();
        $this->output->outputLine('Jobs Stats', 'subtitle');
        $this->output->outputLine("   Processed Jobs : " . \Resque_Stat::get('processed'));
        $this->output->outputLine("   Failed Jobs    : " . \Resque_Stat::get('failed'), 'failure');
        $this->output->outputLine();
        $this->output->outputLine('Workers Stats', 'subtitle');
        $workers = \Resque_Worker::all();
        $this->output->outputLine("   Active Workers : " . count($workers));

        if (!empty($workers)) {
            foreach ($workers as $worker) {
                $this->output->outputLine("    Worker : " . $worker, 'bold');
                $this->output->outputLine(
                    "     - Started on     : " . \Resque::Redis()->get('worker:' . $worker . ':started')
                );
                $this->output->outputLine(
                    "     - Uptime         : " .
                    $this->formatDateDiff(new \DateTime(\Resque::Redis()->get('worker:' . $worker . ':started')))
                );
                $this->output->outputLine("     - Processed Jobs : " . $worker->getStat('processed'));
                $worker->getStat('failed') == 0
                    ? $this->output->outputLine("     - Failed Jobs    : " . $worker->getStat('failed'))
                    : $this->output->outputLine("     - Failed Jobs    : " . $worker->getStat('failed'), 'failure');
            }
        }

        $this->output->outputLine("\n");
    }


    /**
     * Test and validate the configuration file
     *
     * @return  void
     */
    public function test()
    {
        $this->outputTitle('Testing configuration');

        $results = $this->testConfig(true);
        foreach ($results as $name => $r) {
            $this->output->outputText($name . ' ' . str_repeat('.', 24 - strlen($name)));
            if ($r === null) {
                $this->output->outputText("OK\n", 'success');
            } else {
                $this->output->outputText($r . "\n", 'failure');
            }
        }

        if (array_filter(array_values($results)) === array()) {
            $this->output->outputLine("\nYour settings seems ok", 'success');
        } else {
            $this->output->outputLine("\nError detected in your settings", 'failure');
        }

        $this->output->outputLine("\nYour configuration", 'subtitle');

        foreach ($this->runtime as $cat => $confs) {
            $this->output->outputLine('['.$cat.']', 'bold');
            foreach ($this->runtime[$cat] as $name => $conf) {
                if (!is_array($conf)) {
                    $this->output->outputText("   ".$name . str_repeat(' ', 10 - strlen($name)));
                    $this->output->outputLine($conf);
                } else {
                    $this->output->outputLine('   '.$name, 'highlight');
                    foreach ($conf as $q => $o) {
                        $this->output->outputText("      ".$q . str_repeat(' ', 10 - strlen($q)));
                        $this->output->outputLine($o);
                    }
                }
            }
        }
    }

    public function testConfig($test = false)
    {
        $results = array(
                'Redis configuration' => null,
                'Redis server' => null,
                'Log File' => null,
                'PHPResque library' => null,
                'Application autoloader' => null
                );

        if (!isset($this->runtime['Redis']['host']) || !isset($this->runtime['Redis']['port'])) {
            $results['Redis configuration'] = 'Unable to read redis server configuration';
        }

        $this->runtime['Fresque']['lib'] = $this->absolutePath($this->runtime['Fresque']['lib']);

        if (!is_dir($this->runtime['Fresque']['lib']) || !is_dir($this->runtime['Fresque']['lib'])) {
            $results['PHPResque library']
                = 'Unable to found PHP Resque library. Check that the path is valid, and directory is readable';
        }

        try {
            if (file_exists($this->runtime['Fresque']['lib'] . DS . 'lib' . DS . 'Resque' . DS.'Redis.php')) {
                require_once($this->runtime['Fresque']['lib'] . DS . 'lib' . DS . 'Resque' . DS.'Redis.php');
                $redis = @new \Resque_Redis($this->runtime['Redis']['host'], (int) $this->runtime['Redis']['port']);

            } elseif (class_exists('Redis')) {
                $redis = new \Redis();
                @$redis->connect($this->runtime['Redis']['host'], (int) $this->runtime['Redis']['port']);
            } elseif (class_exists('Redisent')) {
                $redis = @new \Redisent($this->runtime['Redis']['host'], (int) $this->runtime['Redis']['port']);
            } else {
                $results['Redis server'] = 'Unable to find Redis Api';
            }
        } catch (\RedisException $e) {
            $results['Redis server'] = 'Unable to connect to Redis server at '
                . $this->runtime['Redis']['host'] . ':' . $this->runtime['Redis']['port'];
        }


        $this->runtime['Log']['filename'] = $this->absolutePath($this->runtime['Log']['filename']);

        $logPath = pathinfo($this->runtime['Log']['filename'], PATHINFO_DIRNAME);
        if (!is_dir($logPath)) {
            $results['Log File'] = 'The directory for the log file does not exists';
        } elseif (!is_writable($logPath)) {
            $results['Log File'] = 'The directory for the log file is not writable';
        }

        $output = array();
        exec('id ' . $this->runtime['Default']['user'] . ' 2>&1', $output, $status);
        if ($status != 0) {
            $results['user'] = sprintf('User %s does not exists', $this->runtime['Default']['user']);
        }

        $resqueFiles = array(
                'lib'.DS.'Resque.php',
                'lib'.DS.'Resque'.DS.'Stat.php',
                'lib'.DS.'Resque'.DS.'Worker.php'
        );



        $found = true;
        foreach ($resqueFiles as $file) {
            if (!file_exists($this->runtime['Fresque']['lib'] . DS . $file)) {
                $found = false;
                break;
            }
        }

        if (!$found) {
            $results['PHPResque library'] = 'Unable to find PHPResque library';
        }

        $this->runtime['Fresque']['include'] = $this->absolutePath($this->runtime['Fresque']['include']);
        if (!file_exists($this->runtime['Fresque']['include'])) {
            $results['Application autoloader'] = 'Your application autoloader file was not found';
        }

        return $results;
    }

    /**
     * Convert options from various source to formatted options
     * understandable by Fresque
     *
     * @return  void
     */
    public function loadSettings($args = null)
    {
        $options = ($args === null) ? $this->input->getOptionValues(true) : $args;

        $this->config = isset($options['config']) ? $options['config'] : '.'.DS.'fresque.ini';
        if (!file_exists($this->config)) {
            $this->output->outputLine("The config file '$this->config' was not found", 'failure');
            die();
        }

        $this->debug = isset($options['debug']) ? true : false;

        $this->settings = $this->runtime = parse_ini_file($this->config, true);

        $this->runtime['Redis']['host'] = isset($options['host']) ? $options['host'] : $this->settings['Redis']['host'];
        $this->runtime['Redis']['port'] = isset($options['port']) ? $options['port'] : $this->settings['Redis']['port'];
        $this->runtime['Redis']['database'] = $this->settings['Redis']['database'];
        $this->runtime['Redis']['namespace'] = $this->settings['Redis']['namespace'];

        $this->runtime['Log']['filename'] = isset($options['log'])
            ? $options['log']
            : $this->settings['Log']['filename'];

        $this->runtime['Log']['handler'] = isset($options['loghandler'])
            ? $options['loghandler']
            : $this->settings['Log']['handler'];

        $this->runtime['Log']['target'] = isset($options['handlertarget'])
            ? $options['handlertarget']
            : $this->settings['Log']['target'];

        $this->runtime['Fresque']['lib'] = isset($options['lib']) ? $options['lib'] : $this->settings['Fresque']['lib'];
        $this->runtime['Fresque']['include'] = isset($options['autoloader'])
            ? $options['autoloader'] : $this->settings['Fresque']['include'];

        $this->runtime['Default']['user'] = isset($options['user'])
            ? $options['user'] : $this->settings['Default']['user'];

        $this->runtime['Default']['queue'] = isset($options['queue'])
            ? $options['queue'] : $this->settings['Default']['queue'];

        $this->runtime['Default']['workers'] = isset($options['workers'])
         ? $options['workers'] : $this->settings['Default']['workers'];

        $this->runtime['Default']['interval'] = isset($options['interval'])
            ? $options['interval'] : $this->settings['Default']['interval'];

        if (isset($this->settings['Queues']) && !empty($this->settings['Queues'])) {
            foreach ($this->settings['Queues'] as $name => $options) {
                $this->settings['Queues'][$name]['queue'] = $name;
            }
        }

        $this->runtime['Default']['verbose'] = ($this->input->getOption('verbose')->value)
            ? $this->input->getOption('verbose')->value : $this->settings['Default']['verbose'];

        if ($command != 'test') {
            $results = $this->testConfig();
            if (!empty($results)) {
                $fail = false;

                foreach ($results as $name => $mess) {
                    if ($mess !== null) {
                        $fail = true;
                        $this->output->outputLine($mess, 'failure');
                    }
                }

                if ($fail) {
                    $this->output->outputLine();
                    exit(1);
                }
            }
        }
    }

    /**
     * Print help/welcome message
     *
     * @since  2.0.0
     * @return void
     */
    public function help($command = null)
    {
        $this->outputTitle('Welcome to Fresque');
        $this->output->outputLine('Fresque '. Fresque::VERSION.' by Wan Chen (Kamisama) (2013)');

        if (!array_key_exists($command, $this->commandTree)
            && $command !== null
            && ($command !== '--help' && $command !== '-h')
        ) {
            $this->output->outputLine("\nUnrecognized command : " . $command, 'failure');
        }

        $this->output->outputLine();
        $this->output->outputLine("Available commands\n", 'subtitle');

        foreach ($this->commandTree as $name => $opt) {
            $this->output->outputText($name . str_repeat(' ', 15 - strlen($name)), 'bold');
            $this->output->outputText($opt['help'] . "\n");
        }

        $this->output->outputLine("\nUse <command> --help to get more infos about a command\n");
    }


    /**
     * Print a pretty title
     *
     * @param string $title   The title to print
     * @param bool   $primary True to print a big title, else print a small title
     *
     * @since 1.0.0
     * @return  void
     */
    public function outputTitle($title, $primary = true)
    {
        $l = strlen($title);
        if ($primary) {
            $this->output->outputLine(str_repeat('-', $l), 'title');
        }
        $this->output->outputLine($title, $primary ? 'title' : 'subtitle');
        if ($primary) {
            $this->output->outputLine(str_repeat('-', $l), 'title');
        }
    }

    /**
     * A sweet interval formatting, will use the two biggest interval parts.
     * On small intervals, you get minutes and seconds.
     * On big intervals, you get months and days.
     * Only the two biggest parts are used.
     *
     * @param \DateTime $start
     * @param \DateTime|null $end
     *
     * @link http://www.php.net/manual/en/dateinterval.format.php
     * @return string
     */
    private function formatDateDiff($start, $end = null)
    {
        if (!($start instanceof \DateTime)) {
            $start = new \DateTime($start);
        }

        if ($end === null) {
            $end = new \DateTime();
        }

        if (!($end instanceof \DateTime)) {
            $end = new \DateTime($start);
        }

        $interval = $end->diff($start);
        $doPlural = function (
            $nb,
            $str
        ) {
            return $nb>1?$str.'s':$str;
        };

        $format = array();
        if ($interval->y !== 0) {
            $format[] = "%y ".$doPlural($interval->y, "year");
        }
        if ($interval->m !== 0) {
            $format[] = "%m ".$doPlural($interval->m, "month");
        }
        if ($interval->d !== 0) {
            $format[] = "%d ".$doPlural($interval->d, "day");
        }
        if ($interval->h !== 0) {
            $format[] = "%h ".$doPlural($interval->h, "hour");
        }
        if ($interval->i !== 0) {
            $format[] = "%i ".$doPlural($interval->i, "minute");
        }
        if ($interval->s !== 0) {
            if (!count($format)) {
                return "less than a minute";
            } else {
                $format[] = "%s ".$doPlural($interval->s, "second");
            }
        }

        // We use the two biggest parts
        if (count($format) > 1) {
            $format = array_shift($format)." and ".array_shift($format);
        } else {
            $format = array_pop($format);
        }

        // Prepend 'since ' or whatever you like
        return $interval->format($format);
    }

    /**
     * Return the absolute path to a file
     *
     * @param string $path Path to convert
     *
     * @return string Absolute path to the file
     */
    private function absolutePath($path)
    {
        if (substr($path, 0, 2) == './') {
            $path = dirname(__DIR__) . DS . substr($path, 2);
        } elseif (substr($path, 0, 1) !== '/' || substr($path, 0, 3) == '../') {
            $path = dirname(__DIR__) . DS . $path;
        }
        return rtrim($path, DS);
    }

    /**
     * Print debugging information
     *
     * @param string $string Information to print
     *
     * @since  2.0.0
     * @return void
     */
    public function debug($string)
    {
        if ($this->debug) {
            $this->output->outputLine('[DEBUG] ' . $string, 'success');
        }
    }

    /**
     * Return the php-resque executable file
     *
     * Maintain backward compatibility, as newer version of
     * php-resque has that file in another location
     *
     * @param String $base Php-resque folder path
     *
     * @since  1.1.6
     * @return String Relative path to php-resque executable file
     */
    protected function getResqueBinFile($base)
    {
        $paths = array(
            'bin' . DS . 'resque',
            'bin' . DS . 'resque.php',
            'resque.php'
        );

        foreach ($paths as $path) {
            if (file_exists($base . DS . $path)) {
                return '.' . DS . $path;
            }
        }
        return '.' . DS . 'resque.php';
    }

    /**
     * Calling systeme tail command
     *
     * @param string $path Path to the file to tail
     *
     * @codeCoverageIgnore
     * @since  2.0.0
     * @return void
     */
    protected function tailCommand($path)
    {
        passthru('tail -f ' . escapeshellarg($path));
    }

    /**
     * Calling a shell command
     *
     * @param string $cmd Command to pass to system shell
     *
     * @codeCoverageIgnore
     * @since  2.0.0
     * @return void
     */
    protected function exec($cmd)
    {
        passthru($cmd);
    }

    /**
     * Send a signal to a process
     *
     * @param  String $signal Signal to send
     * @param  int    $pid    PID of the process
     *
     * @codeCoverageIgnore
     * @return array with the code and message returned by the command
     */
    protected function kill($signal, $pid)
    {
        $output = array();
        $message = exec(sprintf('/bin/kill -%s %s 2>&1', $signal, $pid), $output, $code);
        return array('code' => $code, 'message' => $message);
    }

    protected function checkStartedWorker($pidFile)
    {
        $pid = false;
        if (file_exists($pidFile) && false !== $pid = file_get_contents($pidFile)) {
            unlink($pidFile);
            return (int)$pid;
        }
        return false;
    }
}
