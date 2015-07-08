<?php
/**
 * DialogMenuValidator Class File
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
 * @since         1.3.3
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
