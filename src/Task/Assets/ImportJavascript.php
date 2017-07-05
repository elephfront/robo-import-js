<?php
namespace Elephfront\RoboImportJs\Task\Assets;

use InvalidArgumentException;
use Robo\Contract\TaskInterface;
use Robo\Result;
use Robo\State\Data;
use Robo\Task\BaseTask;

/**
 * Class ImportJavascript
 *
 * Allows to have an import statement in Javascript files as in SASS files.
 * Import statements will be replaced by the value of the file linked. Import statement can be nested (meaning an
 * imported file can import other files.
 */
class ImportJavascript extends BaseTask implements TaskInterface
{

    /**
     * Import statement pattern. All found will be replaced by their contents if found.
     *
     * @var string
     */
    const IMPORT_PATTERN = '/roboimport\(\'(.*)\'\);/';

    /**
     * List of the destinations files mapped by the sources name. One source equals one destination.
     *
     * @var array
     */
    protected $destinationsMap = [];

    /**
     * Data that will be passed to the next task with the Robo response.
     *
     * @var array
     */
    protected $returnData = [];

    /**
     * Data that was received from the previous task.
     * This array can stay empty if this task if the first to be run.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Whether or not the destination file should be written after the replace have been done.
     *
     * @var bool
     */
    protected $writeFile = true;

    /**
     * Enables the `writeFile` property
     *
     * @return void
     */
    public function enableWriteFile()
    {
        $this->writeFile = true;
    }

    /**
     * Disables the `writeFile` property
     *
     * @return void
     */
    public function disableWriteFile()
    {
        $this->writeFile = false;
    }

    /**
     * Constructor. Will bind the destinations map.
     *
     * @param array $destinationsMap Key / value pairs array where the key is the source and the value the destination.
     */
    public function __construct(array $destinationsMap = [])
    {
        $this->setDestinationsMap($destinationsMap);
    }

    /**
     * Sets the destinations map.
     *
     * @param array $destinationsMap Key / value pairs array where the key is the source and the value the destination.
     * @return self
     */
    public function setDestinationsMap(array $destinationsMap = [])
    {
        $this->destinationsMap = $destinationsMap;

        return $this;
    }

    /**
     * Runs the tasks : will replace all import statements from the source files from the `self::$destinationsMap` and
     * write them to the destinations file from the `self::$destinationsMap`.
     *
     * @return \Robo\Result Result object from Robo
     * @throws \InvalidArgumentException If no destinations map has been found.
     */
    public function run()
    {
        $error = false;
        if ($this->data) {
            foreach ($this->data as $source => $content) {
                $js = $content['js'];
                $destination = $content['destination'];
                $sourceDir = dirname($source) . DIRECTORY_SEPARATOR;

                try {
                    $js = $this->replaceImports($js, $sourceDir);

                    if ($this->writeFile && !$this->writeFile($destination, $js)) {
                        $error = $source;
                        break;
                    }

                    $this->outputSuccessMessage($source, $destination);
                    $this->returnData[$source] = ['js' => $js, 'destination' => $destination];
                } catch (InvalidArgumentException $e) {
                    $error = $e->getMessage();
                    break;
                }
            }
        } else {
            if (empty($this->destinationsMap)) {
                throw new InvalidArgumentException(
                    'Impossible to run the ImportJavascript task without a destinations map.'
                );
            }

            foreach ($this->destinationsMap as $source => $destination) {
                $js = $this->getContent($source);

                $this->returnData[$source] = ['js' => $js, 'destination' => $destination];

                if ($this->writeFile && !$this->writeFile($destination, $js)) {
                    if (!file_put_contents($destination, $js)) {
                        $error = $source;
                        break;
                    }
                }

                $this->outputSuccessMessage($source, $destination);
            }
        }

        if ($error) {
            $messageTemplate = 'An error occurred while writing the destination file for source file `%s`';
            $outputMessage = sprintf($messageTemplate, $source);
            if ($error !== $source) {
                $messageTemplate .= '. Error : %s';
                $outputMessage = sprintf($messageTemplate, $source, $error);
            }

            return Result::error(
                $this,
                $outputMessage
            );
        } else {
            return Result::success($this, 'All import statements in JS files replaced.', $this->returnData);
        }
    }

    /**
     * Output a success message to the Console.
     *
     * @param string $source Path of the source file
     * @param string $destination Path of the destination file
     *
     * @return void
     */
    public function outputSuccessMessage($source, $destination)
    {
        $outputMessage = 'Replaced import statement from file <info>%s</info>';

        if ($this->writeFile) {
            $outputMessage .= ' to <info>%s</info>';
        }

        $this->printTaskSuccess(
            sprintf(
                $outputMessage,
                $source,
                $destination
            )
        );
    }

    /**
     * Write the `$destination` file with the javascript content passed in `$js`
     *
     * @param string $destination Path of the destination file to write in
     * @param string $js Javascript content to write into the file
     * @return int Number of bytes written or false on failure.
     */
    public function writeFile($destination, $js)
    {
        $destinationDirectory = dirname($destination);

        if (!is_dir($destinationDirectory)) {
            mkdir($destinationDirectory, 0755, true);
        }

        return file_put_contents($destination, $js);
    }

    /**
     * Will parse the source code passed as parameter and find an replace all imports statement matching
     * self::IMPORT_PATTERN.
     *
     * @param string $source Relative source code path.
     * @return string Source content with import statements replaced or source code unmodified.
     * @throws \InvalidArgumentException If a source file can not be found.
     */
    protected function getContent($source)
    {
        $sourcePath = realpath($source);

        if (!is_file($sourcePath)) {
            throw new InvalidArgumentException(sprintf('Impossible to find source file `%s`', $source));
        }

        $sourceDir = dirname($source) . DIRECTORY_SEPARATOR;
        $sourceContent = file_get_contents($sourcePath);

        return $this->replaceImports($sourceContent, $sourceDir);
    }

    /**
     * Find all import statement in the source content and returns the `$matches` array of the `preg_match_all()`
     * method.
     *
     * @param string $sourceContent Source code of a JS file.
     * @param string $sourceDir Base (relative) source code file directory.
     * @return string The source code with its import statements replaced (or unmodified if no import statements have
     * been found.
     * @throws \InvalidArgumentException If an imported file can not be found.
     */
    protected function replaceImports($sourceContent, $sourceDir)
    {
        $imports = $this->findImports($sourceContent);
        if ($imports) {
            foreach ($imports[1] as $key => $import) {
                $importPath = $sourceDir . $import . '.js';

                if (!is_file($importPath)) {
                    throw new InvalidArgumentException(sprintf('Impossible to find imported file `%s`', $importPath));
                }

                $importContent = $this->getContent($importPath);
                $sourceContent = str_replace($imports[0][$key], $importContent, $sourceContent);
            }
        }

        return $sourceContent;
    }

    /**
     * Find all import statement in the source content and returns the `$matches` array of the `preg_match_all()`
     * method.
     *
     * @param string $sourceContent Source code of a JS file.
     * @return array `$matches` array of the `preg_match_all()` or empty array if no import statement where found in the
     * source code.
     */
    protected function findImports($sourceContent)
    {
        preg_match_all(self::IMPORT_PATTERN, $sourceContent, $matches);

        if (empty($matches)) {
            return [];
        }

        return $matches;
    }

    /**
     * Gets the state from the previous task. Stores it in the `data` attribute of the object.
     * This method is called before the task is run.
     *
     * @param \Robo\State\Data $state State passed from the previous task.
     * @return void
     */
    public function receiveState(Data $state)
    {
        $this->data = $state->getData();
    }
}