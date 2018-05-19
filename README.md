# Yii 2 Log Viewer

**What is it?**
It's console script that colorizes and filters Yii2 log files (`runtime/logs/app.log`).

**What it does?**
It colorizes all significant parts of log and can filter dumped variables (like `$_GET`, `$_POST`, etc.) and stack traces.

That's example of yii2 log file process by script:
```sh
vendor/bin/view --no-vars runtime/logs/app.log
```

![Image](https://raw.githubusercontent.com/wapmorgan/Yii2LogViewer/master/doc/yii2log_viewer.png)

**How to use it?**
Install with composer and run
```sh
vendor/bin/view --no-vars runtime/logs/app.log
```
