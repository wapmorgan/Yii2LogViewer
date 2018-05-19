<?php
namespace wapmorgan\Yii2LogViewer;

class Terminal {
    const RESET_COLOR = "\e[0m";
    const ORANGE_TEXT = "\e[0;33m";
    const YELLOW_TEXT = "\e[0;93m";
    const GRAY_TEXT = "\e[0;37m";
    const RED_TEXT = "\e[0;31m";
    const GREEN_TEXT = "\e[0;32m";
    const BLUE_TEXT = "\e[0;34m";
    const PURPLE_TEXT = "\e[0;35m";
    const WHITE_TEXT = "\e[1;97m";
    const CYAN_TEXT = "\e[0;36m";

    const RED_UNDERLINED_TEXT = "\e[4;31m";
    const GREEN_UNDERLINED_TEXT = "\e[4;32m";

    const RED_BACKGROUND = "\e[41m";
    const GREEN_BACKGROUND = "\e[42m";

    const RED_BOLD_TEXT = "\e[1;31m";
    const PURPLE_BOLD_TEXT = "\e[1;35m";

    /**
     * Added prefix for color and postfix for color reset
     * @param $text
     * @param $color
     * @return string
     */
    static public function colorize($text, $color) {
        return $color.$text.self::RESET_COLOR;
    }
}
