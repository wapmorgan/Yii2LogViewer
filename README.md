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
~/.composer/vendor/bin/yii2log-view --no-vars runtime/logs/app.log | less -R
```

Available options:
```sh
Usage: bin/yii2log-view [options] log_file
Options:
   --no-trace  Suppreses traces in log
   --no-vars   Suppreses variable values in log
```

**Example**:
![Image](https://raw.githubusercontent.com/wapmorgan/Yii2LogViewer/master/doc/yii2log_viewer.png)
