<?php
/**
 * Fresque Class File
 *
 * PHP 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        Wan Qi Chen <kami@kamisama.me>
 * @copyright     Copyright 2012, Wan Qi Chen <kami@kamisama.me>
 * @link          https://github.com/kamisama/Fresque
 * @package       Fresque
 * @subpackage    Fresque.lib
 * @since         0.1.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Fresque;

define('DS', DIRECTORY_SEPARATOR);

/**
 * Fresque Class
 *
 * @package Fresque.lib
 * @since   0.1.0
 */
class Fresque
{
    protected $input;
    protected $output;

    protected $settings;
    protected $runtime;

    const VERSION = '1.1.3';

    public function __construct()
    {
        $this->command = array_splice($_SERVER['argv'], 1, 1);
        $this->command = empty($this->command) ? null : $this->command[0];

        $this->input = new \ezcConsoleInput();
        $this->output = new \ezcConsoleOutput();

        $helpOption = $this->input->registerOption(new \ezcConsoleOption('h', 'help'));
        $helpOption->isHelpOption = true;

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

        $settings = $this->loadSettings();

        $args = $this->input->getArguments();

        $globalOptions = array('s' => 'host', 'p' => 'port', 'b' => 'path',
            'c' => 'path', 'a' => 'path', 'd' => 'handler', 'r' => 'args,'
        );

        $commandTree = array(
                'start' => array(
                        'help' => 'Start a new worker',
                        'options' => array('u' => 'username', 'q' => 'queue name',
                                'i' => 'num', 'n' => 'num', 'l' => 'path', 'v')),
                'stop' => array(
                        'help' => 'Shutdown all workers',
                        'options' => array('f', 'w')),
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
                                'i' => 'num', 'n' => 'num', 'l' => 'path'))
                );

        if ($this->command === null || !method_exists($this, $this->command)) {
            $this->outputTitle('Welcome to Fresque');
            $this->output->outputLine('Fresque '. Fresque::VERSION.' by Wan Chen (Kamisama) (2013)');

            if (!method_exists($this, $this->command) && $this->command !== null && $this->command !== '--help') {
                $this->output->outputLine("\nUnrecognized command : " . $this->command, 'failure');
            }

            $this->output->outputLine();
            $this->output->outputLine("Available commands\n", 'subtitle');

            foreach ($commandTree as $name => $opt) {
                $this->output->outputText($name . str_repeat(' ', 15 - strlen($name)), 'bold');
                $this->output->outputText($opt['help'] . "\n");
            }

            $this->output->outputLine("\nUse <command> --help to get more infos about a command\n");

        } else {
            if ($helpOption->value === true) {
                $this->output->outputLine();
                $this->output->outputLine($commandTree[$this->command]['help']);

                if (!empty($commandTree[$this->command]['options'])) {
                    $this->output->outputLine("\nAvailable options\n", 'subtitle');

                    foreach ($commandTree[$this->command]['options'] as $name => $arg) {
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
                $allowed = array_merge($commandTree[$this->command]['options'], $globalOptions);
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
                \Resque::setBackend(
                    $this->runtime['Redis']['host'] . ':' . $this->runtime['Redis']['port'],
                    $this->runtime['Redis']['database'],
                    $this->runtime['Redis']['namespace']
                );
                $this->{$this->command}();
            }
        }
    }


    /**
     * Start workers
     */
    protected function start($args = null, $new = true)
    {
        if ($args === null) {
            $this->outputTitle('Creating workers');
        } else {
            $this->runtime = $args;
        }

        $cmd = 'nohup sudo -u '. escapeshellarg($this->runtime['Default']['user']) . ' bash -c "cd ' .
        escapeshellarg($this->runtime['Fresque']['lib']) . '; ' .
        (($this->runtime['Default']['verbose']) ? 'VVERBOSE' : 'VERBOSE') . '=true '.
        ' QUEUE=' . escapeshellarg($this->runtime['Default']['queue']) .
        ' APP_INCLUDE=' . escapeshellarg($this->runtime['Fresque']['include']) .
        ' INTERVAL=' . escapeshellarg($this->runtime['Default']['interval']) .
        ' REDIS_BACKEND=' . escapeshellarg($this->runtime['Redis']['host'] . ':' . $this->runtime['Redis']['port']) .
        ' REDIS_DATABASE=' . escapeshellarg($this->runtime['Redis']['database']) .
        ' REDIS_NAMESPACE=' . escapeshellarg($this->runtime['Redis']['namespace']) .
        ' COUNT=' . $this->runtime['Default']['workers'] .
        ' LOGHANDLER=' . escapeshellarg($this->runtime['Log']['handler']) .
        ' LOGHANDLERTARGET=' . escapeshellarg($this->runtime['Log']['target']) .
        ' php ./resque.php';
        $cmd .= ' >> '. escapeshellarg($this->runtime['Log']['filename']).' 2>&1" >/dev/null 2>&1 &';

        $workersCountBefore = \Resque::Redis()->scard('workers');
        $workersCountAfter = 0;
        passthru($cmd);

        $this->output->outputText('Starting worker ');


        $success = false;
        $attempt = 7;
        while ($attempt-- > 0) {
            for ($i = 0; $i < 3; $i++) {
                $this->output->outputText(".", 0);
                usleep(150000);
            }

            if (($workersCountBefore + $this->runtime['Default']['workers']) == ($workersCountAfter = \Resque::Redis()->scard('workers'))) {
                if ($args === null || $new === true) {
                    $this->addWorker($this->runtime);
                }
                $this->output->outputLine(
                    ' Done' . (($this->runtime['Default']['workers'] == 1)
                        ? ''
                        : ' x' . $this->runtime['Default']['workers']
                    ),
                    'success'
                );
                $success = true;
                break;
            }
        }

        if (!$success) {
            if ($workersCountBefore === $workersCountAfter) {
                $this->output->outputLine(' Fail', 'failure');
            } else {
                $this->output->outputLine(sprintf(' Error, could not start %s workers', (($workersCountBefore + $this->runtime['Default']['workers']) - $workersCountAfter)), 'warning');
            }

        }

        if ($args === null) {
            $this->output->outputLine();
        }
    }


    /**
     * Stop workers
     */
    protected function stop($shutdown = true, $restart = false)
    {
        $force = $this->input->getOption('force')->value;
        $all = $this->input->getOption('all')->value;

        $this->outputTitle('Stopping Workers', $shutdown);
        $workers = \Resque_Worker::all();
        sort($workers);
        if (empty($workers)) {
            $this->output->outputLine('There is no active workers to kill ...', 'failure');
        } else {

            $workersToKill = array();

            if (!$all && !$restart) {
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
                    $menuItems['all'] = 'Kill all workers';

                    $menuOptions = new \ezcConsoleMenuDialogOptions(
                        array(
                            'text' => 'Active workers list',
                            'selectText' => 'Worker to kill :',
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
                $this->output->outputText('Killing ' . $pid . ' ... ');
                isset($options['force']) ? $worker->shutDownNow() : $worker->shutDown();
                $worker->unregisterWorker();

                $output = array();
                $message = exec('kill -9 ' . $pid . ' 2>&1', $output, $code);

                if ($code == 0) {
                    $this->output->outputLine('Done', 'success');
                } else {
                    $this->output->outputLine($message, 'failure');
                }
            }
        }

        if ($shutdown) {
            $this->clearWorker();
        }

        $this->output->outputLine();
    }


    /**
     * Load workers from configuration
     */
    protected function load()
    {
        $this->outputTitle('Loading workers');

        if (!isset($this->settings['Queues']) || empty($this->settings['Queues'])) {
            $this->output->outputLine("You have no configured workers to load.\n", 'failure');
        } else {
            $this->output->outputLine(sprintf('Loading %s workers', count($this->settings['Queues'])));
            foreach ($this->settings['Queues'] as $queue) {
                $this->loadSettings($queue);
                $this->start($this->runtime);
            }
        }

        $this->output->outputLine();
    }


    /**
     * Restart all workers
     */
    protected function restart()
    {
        if (false !== $workers = $this->getWorkers()) {
            $this->stop(false, true);
            $this->outputTitle('Restarting workers', false);
            foreach ($workers as $worker) {
                $this->start($worker, false);
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
     */
    protected function tail()
    {
        $logs = array();
        $i = 1;
        $workers = (array)$this->getWorkers();

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
        passthru('tail -f ' . escapeshellarg($logs[$index - 1]));
    }


    /**
     * Add a job to a queue
     */
    protected function enqueue()
    {
        $args = $this->input->getArguments();

        if (count($args) >= 2) {
            $queue = array_shift($args);
            $class = array_shift($args);

            $result = \Resque::enqueue($queue, $class, $args);
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
     */
    protected function stats()
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

        if (!is_dir($this->runtime['Fresque']['lib']) || !is_dir($this->runtime['Fresque']['lib'])) {
            $results['PHPResque library'] =
                'Unable to found PHP Resque library. Check that the path is valid, and directory is readable';
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


        if (substr($this->runtime['Log']['filename'], 0, 2) == './') {
            $this->runtime['Log']['filename'] = dirname(__DIR__) . DS .
                substr($this->runtime['Log']['filename'], 2);
        } elseif (substr($this->runtime['Log']['filename'], 0, 1) != '/') {
            $this->runtime['Log']['filename'] = dirname(__DIR__) . DS . $this->runtime['Log']['filename'];
        }

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

        if (substr($this->runtime['Fresque']['lib'], 0, 2) == './') {
            $this->runtime['Fresque']['lib'] = dirname(__DIR__) . DS .
            substr($this->runtime['Fresque']['lib'], 2);
        }

        $found = true;
        foreach ($resqueFiles as $file) {
            if (!file_exists($this->runtime['Fresque']['lib'] . DS . $file)) {
                $found = false;
                break;
            }
        }

        if (!empty($this->runtime['Fresque']['lib']) && $found) {
            foreach ($resqueFiles as $file) {
                require_once $this->runtime['Fresque']['lib'] . DS . $file;
            }
        } else {
            $results['PHPResque library'] = 'Unable to find PHPResque library';
        }


        if (substr($this->runtime['Fresque']['include'], 0, 2) == './') {
            $this->runtime['Fresque']['include'] = dirname(__DIR__) . DS .
            substr($this->runtime['Fresque']['include'], 2);
        }
        if (!file_exists($this->runtime['Fresque']['include'])) {
            $results['Application autoloader'] = 'Your application autoloader file was not found';
        }

        return $results;
    }

    private function addWorker($args)
    {
        \Resque::Redis()->rpush('ResqueWorker', serialize($args));
    }

    private function getWorkers()
    {
        $listLength = \Resque::Redis()->llen('ResqueWorker');
        $workers = \Resque::Redis()->lrange('ResqueWorker', 0, $listLength-1);
        if (empty($workers)) {
            return false;
        } else {
            $temp = array();
            foreach ($workers as $worker) {
                $temp[] = unserialize($worker);
            }
            return $temp;
        }
    }

    private function clearWorker()
    {
        \Resque::Redis()->del('ResqueWorker');
    }

    /**
     * Convert options from various source to formatted options
     * understandable by Fresque
     */
    private function loadSettings($args = null)
    {
        $options = ($args === null) ? $this->input->getOptionValues(true) : $args;

        $config = isset($options['config']) ? $options['config'] : '.'.DS.'fresque.ini';
        if (!file_exists($config)) {
            $this->output->outputLine("The config file '$config' was not found", 'failure');
            die();
        }

        $this->settings = $this->runtime = parse_ini_file($config, true);

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

        if ($this->command != 'test') {
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
     * Print a pretty title
     *
     * @param string    $title      The title to print
     * @param bool      $primary    True to print a big title, else print a small title
     * @since 1.0.0
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
}


/**
 * DialogMenuValidator Class
 *
 * ezComponent class for validating dialog menu input
 *
 * @since 1.0.0
 */
class DialogMenuValidator implements \ezcConsoleMenuDialogValidator
{
    protected $elements = array();

    public function __construct($elements)
    {
        $this->elements = $elements;
    }

    public function fixup($result)
    {
        return (string)$result;
    }

    public function getElements()
    {
        return $this->elements;
    }

    public function getResultString()
    {

    }

    public function validate($result)
    {
        return in_array($result, array_keys($this->elements));
    }
}
