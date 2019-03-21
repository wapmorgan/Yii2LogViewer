<?php
namespace wapmorgan\Yii2LogViewer;

use yii\console\Controller;
use yii\helpers\Console;

/**
 * Yii2 Log Viewer with filter
 *
 * @package wapmorgan\Yii2LogViewer
 */
class LogViewerController extends Controller
{
    public $defaultAction = 'file';

    /**
     * @var bool Whether traces should be displayed.
     */
    public $traces = true;

    /**
     * @var bool Whether log vars (_SERVER, _ENV, ...) should be displayed.
     */
    public $logVars = false;

    /**
     * @var string Signature of yii2 log message.
     * {LEVELS_LIST} will be replaced with joined via "|" list of yii2 log levels.
     */
    public $messageSignature = '~^(?<datetime>\d{4}\-\d{1,2}\-\d{1,2} \d{2}\:\d{2}\:\d{2}(\.\d*)?) (?<prefix>(\[.+\]\[.+\]\[.+\])|(.+))(?:\[(?<level>{LEVELS_LIST})\])(?:\[(?<category>.+)\]) (?<text>.+)$~U';

    public $levelsColors = [
        'error' => Console::BG_RED,
        'warning' => Console::FG_RED,
        'info' => Console::FG_CYAN,
        'trace' => Console::FG_GREY,
        'profile begin' => Console::FG_GREY,
        'profile end' => Console::FG_GREY,
        'profile' => Console::FG_GREY,
    ];

    public $signatureColors = [
        'number' => Console::FG_GREY,
        'datetime' => Console::FG_PURPLE,
        'prefix' => Console::FG_GREY,
        'category' => Console::FG_BLUE,
    ];

    public $logVarsList = [
        '_GET',
        '_POST',
        '_FILES',
        '_COOKIE',
        '_SESSION',
        '_SERVER',
    ];

    /**
     * @var resource
     */
    protected $input;

    protected $messageNumber = 1;
    protected $insideLogVar = false;
    protected $stackTraceLevel = 0;
    protected $lastMessageCategory;

    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['traces', 'logVars']
        );
    }

    /**
     * Viewing a log file
     * @param null|string $filename
     */
    public function actionFile($filename = null)
    {
        if (empty($filename)) {
            $filename = $this->askForFilename();
        }

        $this->input = fopen($filename, 'r');
        $this->processLog();
    }

    /**
     * Viewing a log as a pipe (following the output)
     * @param string $filename
     */
    public function actionPipe($filename)
    {

    }

    public function askForFilename()
    {
        $files = [];
        foreach (glob(\Yii::getAlias('@runtime/logs').'/*') as $log_file) {
            $files[$log_file] = [
                'size' => filesize($log_file),
                'updated_at' => filemtime($log_file),
            ];
        }

        uasort($files, function (array $a, array $b) {
            if ($a['updated_at'] > $b['updated_at']) return 1;
            else if ($a['updated_at'] == $b['updated_at']) return 0;
            return -1;
        });

        $files_list = [];
        foreach ($files as $log_file => $data) {
            $files_list[basename($log_file)] = $log_file.': updated at '
                .date('r', $data['updated_at'])
                .' ('.\Yii::$app->formatter->asSize($data['size']).')';
        }

        $selected = Console::select('Which file you would like to see?', $files_list);
        var_dump($files_list[$selected]);
        return strstr($files_list[$selected], ':', true);
    }

    protected function processLog()
    {
        $message_signature = str_replace('{LEVELS_LIST}',
            implode('|', array_keys($this->levelsColors)),
            $this->messageSignature);

        while (($line = fgets($this->input)) !== false) {
            // new log message
            try {
                if (preg_match($message_signature, $line, $message)) {
                        Console::output($this->colorizeMessageSignature($message)
                            .' '.$this->colorizeMessageLine($message['text'], $message['category']));
                } else {
                    $line = rtrim($line);
                    if (empty($line)) {
                        if ($this->stackTraceLevel > 0)
                            $this->stackTraceLevel = 0;
                        continue;
                    }

                    Console::output($this->colorizeMessageLine($line, $this->lastMessageCategory));
                }
            } catch (SkipLineException $e) {}
        }
    }

    protected function colorizeMessageSignature(array $message)
    {
        $this->lastMessageCategory = $message['category'];

        return Console::ansiFormat('#'.($this->messageNumber++), [$this->signatureColors['number']])
            .' '.Console::ansiFormat($message['datetime'], [$this->signatureColors['datetime']])
            .' '.Console::ansiFormat($message['prefix'], [$this->signatureColors['prefix']])
            .Console::ansiFormat('['.$message['level'].']', [$this->levelsColors[$message['level']]])
            .Console::ansiFormat('['.$message['category'].']', [$this->signatureColors['category']]);
    }

    protected function colorizeMessageLine($messageLine, $messageCategory = null)
    {
        // Check for log variable (_SERVER, ...)
        if (preg_match('~^\$('.implode('|', $this->logVarsList).') = (\[\]?)$~U', $messageLine, $logVarStart)) { // log vars
            // empty var
            if ($logVarStart[2] === '[]') {
                if (!$this->logVars)
                    throw new SkipLineException();
                return Console::ansiFormat('$'.$logVarStart[1], [Console::FG_PURPLE])
                    .' = '.Console::ansiFormat($logVarStart[2], [Console::FG_GREY]);
            } else {
                $this->insideLogVar = true;
                if (!$this->logVars)
                    throw new SkipLineException();
                return Console::ansiFormat('$'.$logVarStart[1], [onsole::FG_PURPLE])
                    .' = '.Console::ansiFormat($logVarStart[2], [Console::RESET]);
            }
        }

        if (preg_match('~^(?<next>Next )?(?<class>[^ ]+Exception)\: (?<message>.+) in (?<file>.+):(?<line>\d+)$~U', $messageLine, $exceptionMessage)) { // exception message
            return $this->colorizeException($exceptionMessage);
        }

        if ($messageLine === 'Stack trace:') { // stack trace beginning
            $this->stackTraceLevel = 1;
            throw new SkipLineException();
        }

        if ($this->insideLogVar) { // inside logged var
            if ($messageLine === ']') {
                $this->insideLogVar = false;

                if (!$this->logVars)
                    throw new SkipLineException();

                return Console::ansiFormat($messageLine, [Console::RESET]);
            }
            if ($this->logVars)
                throw new SkipLineException();

            return Console::ansiFormat($messageLine, [Console::FG_GREY]);
        }

        if ($this->stackTraceLevel > 0 && preg_match('~^#(?<number>\d+) (?<traceLine>(?<file>\[internal function\]|.+\((?<line>\d+)\))\: (?<call>.+)|\{main\})$~U', $messageLine, $stackTraceCall)) { // inside stack trace
            // supressed traces or too big depth
            if ($this->traces || $this->stackTraceLevel >= $this->maximumStackTraceDepth)
                throw new SkipLineException();

            // last trace entry - main script
            if ($stackTraceCall['traceLine'] === '{main}')
                throw new SkipLineException();

            return '|--'.str_repeat('---', ($this->stackTraceLevel++) - 1)
                .' '.Console::ansiFormat($stackTraceCall['call'], [Console::RESET])
                .' in '.Console::ansiFormat($stackTraceCall['file'], [Console::FG_GREY]);
        }

        // normal text
        return Console::ansiFormat($messageLine,
            [fnmatch('yii\\db\\*', $messageCategory, FNM_NOESCAPE)
                ? Console::FG_CYAN
                : Console::RESET]);
    }

    protected function colorizeException(array $exceptionMessage)
    {
        // var_dump($exceptionMessage);
        return (!empty($exceptionMessage['next']) ? '(=>) ' : null)
            .Console::ansiFormat($exceptionMessage['class'], [Console::FG_RED])
            .': '
            .Console::ansiFormat($exceptionMessage['message'], [Console::FG_RED])
            .PHP_EOL
            .(!empty($exceptionMessage['next']) ? '(=>) ' : null).' in file '
            .Console::ansiFormat($exceptionMessage['file'], [Console::RESET])
            .' on line '
            .Console::ansiFormat($exceptionMessage['line'], [Console::RESET]);
    }
}