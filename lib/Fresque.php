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
 * @subpackage	  Fresque.lib
 * @since         1.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Fresque;

define('DS', DIRECTORY_SEPARATOR);

/**
 * Fresque Class
 *
 * @package Fresque.lib
 * @since 	1.0.0
 */
class Fresque
{
    protected $input;
    protected $output;

    protected $settings;
    protected $runtime;

    const VERSION = '0.2.6';

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
                't',
                'tail',
                \ezcConsoleInput::TYPE_NONE,
                null,
                false,
                'Display the tail onscreen',
                'Display the tail onscreen',
                array(),
                array(),
                false
            )
        );

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

        $this->output->formats->title->color = 'yellow';
        $this->output->formats->title->style = 'bold';

        $this->output->formats->subtitle->color = 'blue';
        $this->output->formats->subtitle->style = 'bold';

        $this->output->formats->warning->color = 'red';

        $this->output->formats->bold->style = 'bold';

        $this->output->formats->highlight->color = 'blue';

        try {
            $this->input->process();
        } catch (\ezcConsoleException $e) {
            die($e->getMessage());
        }

        $settings = $this->loadSettings();

        $args = $this->input->getArguments();

        $globalOptions = array('s' => 'host', 'p' => 'port', 'b' => 'path', 'c' => 'path', 'a' => 'path', 'd' => 'handler', 'r' => 'args,');
        $commandTree = array(
                'start' => array(
                        'help' => 'Start a new \worker',
                        'options' => array('u' => 'username', 'q' => 'queue name',
                                'i' => 'num', 'n' => 'num', 't', 'l' => 'path')),
                'stop' => array(
                        'help' => 'Shutdown all workers',
                        'options' => array('f')),
                'restart' => array(
                        'help' => 'Restart all workers',
                        'options' => array()),
                'load' => array(
                        'help' => 'Load workers defined in your configuration file',
                        'options' => array('l')),
                'tail' => array(
                        'help' => 'Tail the log',
                        'options' => array()),
                'enqueue' => array(
                        'help' => 'Enqueue a new \job (for testing purpose only)',
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
            $this->output->outputLine('------------------', 'success');
            $this->output->outputLine('Welcome to Fresque', 'success');
            $this->output->outputLine('------------------', 'success');
            $this->output->outputLine('Fresque '. Fresque::VERSION.' by Wan Chen (Kamisama) (2012)');

            if (!method_exists($this, $this->command)) {
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
                        $o = '-' . $opt->short . ' ' . (is_numeric($name) ? '' : '<'.$arg. '>');

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
                $this->{$this->command}();
            }
        }
    }

    protected function start($params = null)
    {
        if ($params === null) {
            $params = $this->input->getOptionValues(true);
        }

        $queue        = isset($params['queue'])    ? $params['queue'] : $this->settings['Default']['queue'];
        $user         = isset($params['user'])     ? $params['user'] : get_current_user();
        $interval     = isset($params['interval']) ? (int) $params['interval'] : $this->settings['Default']['interval'];
        $count        = isset($params['workers'])  ? (int) $params['workers'] : $this->settings['Default']['workers'];

        if ($count == 1) {
            $this->output->outputText("Forking 1 new PHP Resque worker service (");
        } else {
            $this->output->outputText("Forking " . $count . " new PHP Resque worker services (");
        }
        $this->output->outputText('queue:', 'highlight');
        $this->output->outputText($queue);
        $this->output->outputText(' user:', 'highlight');
        $this->output->outputText($user . ")\n");


        $cmd = 'nohup sudo -u '.$user.' bash -c "cd ' .
        escapeshellarg($this->runtime['Fresque']['lib']) . '; VVERBOSE=true' .
        ' QUEUE=' . escapeshellarg($queue) .
        ' APP_INCLUDE=' . escapeshellarg($this->runtime['Fresque']['include']) .
        ' INTERVAL=' . escapeshellarg($interval) .
        ' REDIS_BACKEND=' . escapeshellarg($this->runtime['Redis']['host'] . ':' . $this->runtime['Redis']['port']) .
        ' COUNT=' . $count .
        ' LOGHANDLER=' . escapeshellarg($this->runtime['Log']['handler']) .
        ' LOGHANDLERTARGET=' . escapeshellarg($this->runtime['Log']['target']) .
        ' php ./resque.php';
        $cmd .= ' >> '. escapeshellarg($this->runtime['Log']['filename']).' 2>&1" >/dev/null 2>&1 &';

        passthru($cmd);

        if (isset($params['tail'])) {
            sleep(3); // give it time to output to the log for the first time
            $this->tail();
        }

        $this->addWorker($params);
    }

    protected function stop($shutdown = true)
    {
        $force = $this->input->getOption('force');

        $this->output->outputLine('Shutting down Resque Worker complete', 'failure');
        $workers = \Resque_Worker::all();
        if (empty($workers)) {
            $this->output->outputLine('   There were no active workers to kill ...');
        } else {
            $this->output->outputLine('Killing '.count($workers).' workers ...');
            foreach ($workers as $w) {
                $force->value ? $w->shutDownNow() : $w->shutDown();               // Send signal to stop processing jobs
                $w->unregisterWorker();                                           // Remove jobs from resque environment
                list($hostname, $pid, $queue) = explode(':', (string) $w);
                $this->output->outputLine('Killing ' . $pid);
                exec('kill -9 '.$pid);                                            // Kill all remaining system process
            }
        }

        if ($shutdown) {
            $this->clearWorker();
        }
    }

    protected function load()
    {
        if (!isset($this->settings['Queues'])) {
            $this->output->outputLine('   You have no configured queues to load.');
        } else {
            foreach ($this->settings['Queues'] as $queue) {
                $this->start($queue);
            }
        }
    }

    protected function restart()
    {
        $this->stop(false);

        if (false !== $workers = $this->getWorkers()) {
            foreach ($workers as $worker) {
                $this->start($worker);
            }
        } else {
            $this->start();
        }
    }

    protected function tail()
    {
        $log = $this->runtime['Fresque']['log'];

        if (file_exists($log)) {
            passthru('sudo tail -f ' . escapeshellarg($log));
        } else {
            $this->output->outputLine('Log file does not exist. Is the service running?');
        }
    }

    protected function enqueue()
    {
        $args = $this->input->getArguments();

        if (count($args) >= 2) {
            $queue = array_shift($args);
            $class = array_shift($args);

            \Resque::enqueue($queue, $class, $args);
            $this->output->outputLine('The job was successfully enqueued', 'success');
        } else {
            $this->output->outputLine('Enqueue takes at least 2 arguments', 'failure');
        }
    }

    protected function stats()
    {
        $this->output->outputLine('PHPResque Statistics', 'title');

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

    public function test()
    {
        $this->output->outputLine('Testing configuration', 'title');

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

            }
            elseif (class_exists('Redis')) {
                $redis = new \Redis();
                @$redis->connect($this->runtime['Redis']['host'], (int) $this->runtime['Redis']['port']);
            }
            elseif (class_exists('Redisent')) {
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
        }
        elseif (!is_writable($logPath)) {
            $results['Log File'] = 'The directory for the log file is not writable';
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
                require($this->runtime['Fresque']['lib'] . DS . $file);
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

    private function loadSettings()
    {
        $options = $this->input->getOptionValues(true);

        $config = isset($options['config']) ? $options['config'] : '.'.DS.'fresque.ini';
        if (!file_exists($config)) {
            $this->output->outputLine("The config file '$config' was not found", 'failure');
            die();
        }

        $this->settings = $this->runtime = parse_ini_file($config, true);

        $this->runtime['Redis']['host'] = isset($options['host']) ? $options['host'] : $this->settings['Redis']['host'];
        $this->runtime['Redis']['port'] = isset($options['port']) ? $options['port'] : $this->settings['Redis']['port'];

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

        if (isset($this->settings['Queues']) && !empty($this->settings['Queues'])) {
            foreach ($this->settings['Queues'] as $name => $options) {
                $this->settings['Queues'][$name]['queue'] = $name;
            }
        }

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
                    exit(1);
                }
            }
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

