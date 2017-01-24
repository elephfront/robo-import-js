<?php
namespace HavokInspiration\RoboImportJs\Task\Loader;

use HavokInspiration\RoboImportJs\Task\Assets\ImportJavascript;

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
     * @return \HavokInspiration\RoboImportJs\Task\Assets\ImportJavascript
     */
    protected function taskImportJavascript($destinationMap = [])
    {
        return $this->task(ImportJavascript::class, $destinationMap);
    }
}
