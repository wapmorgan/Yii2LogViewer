<?php
namespace wapmorgan\Yii2LogViewer;

use InvalidArgumentException;

class Colorizer {
    /** @var resource */
    protected $input;

    /** @var resource */
    protected $output;

    /** @var boolean */
    protected $suppressTraces = false;

    /** @var boolean */
    protected $suppressLogVars = false;

    protected static $yii2LogLevels = [
        'error' => Terminal::RED_BACKGROUND,
        'warning' => Terminal::RED_TEXT,
        'info' => Terminal::CYAN_TEXT,
        'trace' => Terminal::GRAY_TEXT,
        'profile begin' => Terminal::GRAY_TEXT,
        'profile end' => Terminal::WHITE_TEXT,
        'profile' => Terminal::WHITE_TEXT,
    ];

    protected static $yii2LogVars = [
        '_GET',
        '_POST',
        '_FILES',
        '_COOKIE',
        '_SESSION',
        '_SERVER',
    ];

    /** @var boolean */
    protected $insideLogVar = false;

    /** @var integer */
    protected $stackTraceLevel = 0;

    /** @var integer */
    protected $maximumStackTraceDepth = 5;

    /** @var string|null */
    protected $lastMessageCategory;

    public function setInputFileResource($fp)
    {
        if (!is_resource($fp))
            throw new InvalidArgumentException('You should pass a resource');
        $this->input = $fp;
    }

    public function setOutputFileResource($fp)
    {
        if (!is_resource($fp))
            throw new InvalidArgumentException('You should pass a resource');
        $this->output = $fp;
    }

    public function suppressTraces($flag = true)
    {
        $this->suppressTraces = (boolean)$flag;
    }

    public function suppressLogVars($flag = true)
    {
        $this->suppressLogVars = (boolean)$flag;
    }

    public function colorize()
    {
        if (!is_resource($this->input) || !is_resource($this->output))
            throw new InvalidArgumentException('Need input and output');

        $this->colorizeInputToOutput();
    }

    protected function colorizeInputToOutput()
    {
        $yii2_log_signature = $this->getYii2LogMessageSignature();

        if ($this->suppressTraces || $this->suppressLogVars)
            fwrite($this->output, Terminal::colorize(ucfirst(implode(' and ',
                array_merge($this->suppressTraces ? ['traces'] : [], $this->suppressLogVars ? ['variables'] : [])
            )).' skipped'.PHP_EOL, Terminal::GRAY_TEXT));

        while (($line = fgets($this->input)) !== false) {
            // new log message
            if (preg_match($yii2_log_signature, $line, $message)) {
                try {
                    fwrite($this->output, $this->colorizeMessageSignature($message)
                        .' '.$this->colorizeMessageLine($message['text'], $message['category']).PHP_EOL);
                } catch (SkipLineException $e) {}
            } else {
                $line = rtrim($line);
                if (empty($line)) {
                    if ($this->stackTraceLevel > 0)
                        $this->stackTraceLevel = 0;
                    continue;
                }

                try {
                    fwrite($this->output, $this->colorizeMessageLine($line, $this->lastMessageCategory).PHP_EOL);
                } catch (SkipLineException $e) {}
            }
        }
    }

    protected function colorizeMessageSignature(array $message)
    {
        static $messageNumber = 1;

        $this->lastMessageCategory = $message['category'];

        return Terminal::colorize('#'.($messageNumber++), Terminal::GRAY_TEXT).' '.Terminal::colorize($message['datetime'], Terminal::PURPLE_TEXT)
            .' '.Terminal::colorize($message['prefix'], Terminal::GRAY_TEXT)
            .Terminal::colorize('['.$message['level'].']', self::$yii2LogLevels[$message['level']])
            .Terminal::colorize('['.$message['category'].']', Terminal::WHITE_TEXT);
    }

    protected function colorizeMessageLine($messageLine, $messageCategory = null)
    {
        // var_dump($messageLine);
        if (preg_match('~^\$('.implode('|', self::$yii2LogVars).') = (\[\]?)$~U', $messageLine, $logVarStart)) { // log vars
            // empty var
            if ($logVarStart[2] === '[]') {
                if ($this->suppressLogVars)
                    throw new SkipLineException();

                return Terminal::colorize('$'.$logVarStart[1], Terminal::PURPLE_BOLD_TEXT).' = '.Terminal::colorize($logVarStart[2], Terminal::GRAY_TEXT);
            } else {
                $this->insideLogVar = true;

                if ($this->suppressLogVars)
                    throw new SkipLineException();

                return Terminal::colorize('$'.$logVarStart[1], Terminal::PURPLE_BOLD_TEXT).' = '.Terminal::colorize($logVarStart[2], Terminal::WHITE_TEXT);
            }
        } else if (preg_match('~^(?<next>Next )?(?<class>[^ ]+Exception)\: (?<message>.+) in (?<file>.+):(?<line>\d+)$~U', $messageLine, $exceptionMessage)) { // exception message
            return $this->colorizeException($exceptionMessage);
        } else if ($messageLine === 'Stack trace:') { // stack trace beginning
            $this->stackTraceLevel = 1;
            throw new SkipLineException();
        } else if ($this->insideLogVar) { // inside logged var
                if ($messageLine === ']') {
                    $this->insideLogVar = false;

                    if ($this->suppressLogVars)
                        throw new SkipLineException();

                    return Terminal::colorize($messageLine, Terminal::WHITE_TEXT);
                } else {
                    if ($this->suppressLogVars)
                        throw new SkipLineException();

                    return Terminal::colorize($messageLine, Terminal::GRAY_TEXT);
                }
        } else if ($this->stackTraceLevel > 0 && preg_match('~^#(?<number>\d+) (?<traceLine>(?<file>\[internal function\]|.+\((?<line>\d+)\))\: (?<call>.+)|\{main\})$~U', $messageLine, $stackTraceCall)) { // inside stack trace
            // supressed traces or too big depth
            if ($this->suppressTraces || $this->stackTraceLevel >= $this->maximumStackTraceDepth)
                throw new SkipLineException();

            // last trace entry - main script
            if ($stackTraceCall['traceLine'] === '{main}')
                throw new SkipLineException();
            else
                return '|--'.str_repeat('---', ($this->stackTraceLevel++) - 1)
                    .' '.Terminal::colorize($stackTraceCall['call'], Terminal::WHITE_TEXT)
                    .' in '.Terminal::colorize($stackTraceCall['file'], Terminal::GRAY_TEXT);
        } else { // normal text
            return Terminal::colorize($messageLine, fnmatch('yii\\db\\*', $messageCategory, FNM_NOESCAPE) ? Terminal::CYAN_TEXT : Terminal::WHITE_TEXT);
        }
    }

    protected function colorizeException(array $exceptionMessage)
    {
        // var_dump($exceptionMessage);
        return (!empty($exceptionMessage['next']) ? '(=>) ' : null).Terminal::colorize($exceptionMessage['class'], Terminal::RED_TEXT)
            .': '.Terminal::colorize($exceptionMessage['message'], Terminal::RED_BOLD_TEXT)
            .PHP_EOL
            .(!empty($exceptionMessage['next']) ? '(=>) ' : null).' in file '.Terminal::colorize($exceptionMessage['file'], Terminal::WHITE_TEXT).' on line '.Terminal::colorize($exceptionMessage['line'], Terminal::WHITE_TEXT);
    }

    protected function getYii2LogMessageSignature()
    {
        return '~^(?<datetime>\d{4}\-\d{1,2}\-\d{1,2} \d{2}\:\d{2}\:\d{2}(\.\d*)?) '
            .'(?<prefix>(\[.+\]\[.+\]\[.+\])|(.+))'
            .'(?:\[(?<level>'.implode('|', array_keys(self::$yii2LogLevels)).')\])'
            .'(?:\[(?<category>.+)\])'
            .' (?<text>.+)$~U';
    }
}