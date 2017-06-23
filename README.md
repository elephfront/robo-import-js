# Robo Import JS

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?branch=master)](LICENSE.txt)
[![Build Status](https://travis-ci.org/Elephfront/robo-import-js.svg?branch=master)](https://travis-ci.org/Elephfront/robo-import-js)
[![Codecov](https://img.shields.io/codecov/c/github/Elephfront/robo-import-js.svg)](https://github.com/Elephfront/robo-import-js)

This [Robo](https://github.com/consolidation/robo) task brings an import method for Javascript files. Think of it as an equivalent of the PHP `include` function or as the `@import` statement in SASS files for Javascript files. 

## Requirements

- PHP >= 5.6.0
- Robo

## Installation

You can install this Robo task using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require elephfront/robo-import-js
```

## Using the task

You can load the task in your RoboFile using the `LoadImportJavascriptTasksTrait` trait:

```php
use Elephfront\RoboImportJs\Task\Loader\LoadImportJavascriptTasksTrait;

class RoboFile extends Tasks
{

    use LoadImportJavascriptTasksTrait;
    
    public function concatJavascript()
    {
        $this
            ->taskImportJavascript([
                'assets/js/main.js' => 'assets/min/main.min.js',
                'assets/js/home.js' => 'assets/min/home.min.js',
            ])
            ->run();
    }
}
```

The only argument the `taskImportJavascript()` takes is an array which maps the source files to the destination files : it will load the **assets/js/main.js**, do its magic and put the final content in **assets/min/main.min.js** and do the same for all of the other files.

In the end, you will get one file per entry in your maps array. 

Import in your JS files are made with the fake `roboimport()` method:

```javascript
// in the home.js file
roboimport('libs/jquery');
roboimport('plugins/slider.min.js');

var slider = $('.slider').initSlider();
```

When reading the content of the **home.js** file, the task will replace the `roboimport()` statements with the content of the linked file.

This is particularly useful if you want to have very "page specific" production JS files. 

Note that the task can read nested `roboimport()` statements, meaning an imported file can itself import other files.

## Contributing

If you find a bug or would like to ask for a feature, please use the [GitHub issue tracker](https://github.com/Elephfront/robo-import-js/issues).
If you would like to submit a fix or a feature, please fork the repository and [submit a pull request](https://github.com/Elephfront/robo-import-js/pulls).

### Coding standards

This repository follows the PSR-2 standard. 

## License

Copyright (c) 2017, Yves Piquel and licensed under [The MIT License](http://opensource.org/licenses/mit-license.php).
Please refer to the LICENSE.txt file.
