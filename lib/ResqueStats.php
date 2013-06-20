<?php
/**
 * ResqueStats Class File
 *
 * PHP 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        Wan Qi Chen <kami@kamisama.me>
 * @copyright     Copyright 2013, Wan Qi Chen <kami@kamisama.me>
 * @link          https://github.com/kamisama/Fresque
 * @package       Fresque
 * @subpackage    Fresque.lib
 * @since         1.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Fresque;

/**
 * ResqueStats Class
 *
 * @since 1.2.0
 */
class ResqueStats
{
    private $redis;

    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    /**
     * Return a list of queues
     *
     * @return array of queues
     */
    public function getQueues()
    {
        return $this->redis->smembers('queues');
    }

    /**
     * Return the number of jobs in a queue
     *
     * @param string $queue name of the queue
     *
     * @return int number of queued jobs
     */
    public function getQueueLength($queue)
    {
        return $this->redis->llen('queue:' . $queue);
    }

    /**
     * Return a list of workers
     *
     * @return array of workers
     */
    public function getWorkers()
    {
        return (array)\Resque_Worker::all();
    }

    /**
     * Return the start date of a worker
     *
     * @param string $worker Name of the worker
     * @return string ISO-8601 formatted date
     */
    public function getWorkerStartDate($worker)
    {
        return $this->redis->get('worker:' . $worker . ':started');
    }
}
