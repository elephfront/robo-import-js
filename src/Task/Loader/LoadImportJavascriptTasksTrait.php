<?php
/**
 * Copyright (c) Yves Piquel (http://www.havokinspiration.fr)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Yves Piquel (http://www.havokinspiration.fr)
 * @link          http://github.com/elephfront/robo-import-js
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types=1);
namespace Elephfront\RoboImportJs\Task\Loader;

use Elephfront\RoboImportJs\Task\ImportJavascript;

/**
 * Class LoadTasksTrait
 *
 * Custom Robo `loadTask` trait. Allows to expose custom tasks to the RoboFile.
 */
trait LoadImportJavascriptTasksTrait
{

    /**
     * Exposes the ImportJavascript task.
     *
     * @param array $destinationMap Key / value pairs array where the key is the source and the value the destination.
     * @return \Elephfront\RoboImportJs\Task\ImportJavascript
     */
    protected function taskImportJavascript(array $destinationMap = [])
    {
        return $this->task(ImportJavascript::class, $destinationMap);
    }
}
