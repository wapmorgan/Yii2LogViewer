# Yii 2 Log Viewer

[![Latest Stable Version](https://poser.pugx.org/wapmorgan/yii2-log-viewer/v/stable)](https://packagist.org/packages/wapmorgan/yii2-log-viewer)

**What is it?**
It's console script that colorizes and filters Yii2 log files (`runtime/logs/app.log`).

**What it does?**
It colorizes all significant parts of log and can filter dumped variables (like `$_GET`, `$_POST`, etc.) and stack traces.

How to install it:
```sh
composer require wapmorgan/yii2-log-viewer
```

How to use it
---

### First way (console command)
1. Include yii2 console command into your project (in `config/console.php`):
  ```php
  'controllerMap' => [
    'log-viewer' => wapmorgan\Yii2LogViewer\LogViewerController::class,
  ]
  ```
2. **Run** it:
  ```sh
  ./yii log-viewer runtime/logs/app.log
  ```

### Second way (own binary)
1. Use own binary:
  ```sh
  ./vendor/bin/yii2log-viewer --no-vars runtime/logs/app.log | less -R
  // or file tail
  tail runtime/logs/app.log | ~/.composer/vendor/bin/yii2log-viewer
  ```

## Options
Available options:
```sh
Yii2 Log Colorizer & Filter.

Usage:
  yii2log-viewer [options] [-f] LOG_FILE
  yii2log-viewer [options] [-f] [-p]

Options:
  -p         Enables reading from pipe, not from file.
  -f         Enables following text file. To quit Ctrl+C.
  --no-trace Enables suppressing all back traces.
  --no-vars  Enables suppressing all exported variables (GET, POST, ...).
```

**Example**:
![Image](https://raw.githubusercontent.com/wapmorgan/Yii2LogViewer/master/doc/yii2log_viewer.png)
