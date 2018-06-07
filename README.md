# Yii 2 Log Viewer

[![Composer package](http://composer.network/badge/wapmorgan/yii2-log-viewer)](https://packagist.org/packages/wapmorgan/yii2-log-viewer)

**What is it?**
It's console script that colorizes and filters Yii2 log files (`runtime/logs/app.log`).

**What it does?**
It colorizes all significant parts of log and can filter dumped variables (like `$_GET`, `$_POST`, etc.) and stack traces.

How to install it:
```sh
composer global require wapmorgan/yii2-log-viewer dev-master
```

How to use it:
```sh
~/.composer/vendor/bin/yii2log-viewer --no-vars runtime/logs/app.log | less -R
// or file tail
tail runtime/logs/app.log | ~/.composer/vendor/bin/yii2log-viewer
```

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
