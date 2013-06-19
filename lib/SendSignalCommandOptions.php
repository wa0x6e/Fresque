<?php
/**
 * SendSignalCommandOptions Class File
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
 * @since         2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Fresque;

/**
 * DialogMenuValidator Class
 *
 * ezComponent class for validating dialog menu input
 *
 * @since 1.0.0
 */
class SendSignalCommandOptions
{
    public $title = '';
    public $noWorkersMessage = '';
    public $allOption = '';
    public $selectMessage = '';
    public $actionMessage = '';
    public $workers = array();
    public $signal = '';
    public $successCallback;

    public function onSuccess($pid, $workerName)
    {
        $callback = $this->successCallback;
        return $callback($pid, $workerName);
    }
}
