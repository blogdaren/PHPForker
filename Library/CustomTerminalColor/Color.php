<?php
/**
 * @script   Color.php
 * @brief    Color wrapper for ShellColor
 * @author   blogdaren<blogdaren@163.com>
 * @version  1.0.0
 * @modify   2018-09-28
 */
namespace PHPForker\Library\CustomTerminalColor;                                                                                                    

use PHPForker\Library\CustomTerminalColor\ShellColor;

class Color
{
    /**
     * @brief    show info in level 
     *
     * @param    string  $text
     *
     * @return   string
     */
    static public function showInfo($text)
    {
        echo ShellColor::getColorfulText($text, 'black', 'green');
    }

    /**
     * @brief    show warn in level 
     *
     * @param    string  $text
     *
     * @return   string
     */
    static public function showWarning($text)
    {
        echo ShellColor::getColorfulText($text, 'black', 'yellow');
    }

    /**
     * @brief    show error in level
     *
     * @param    string  $text
     *
     * @return   string
     */
    static public function showError($text)
    {
        echo ShellColor::getColorfulText($text, 'white', 'red');
    }

    /**
     * @brief    get colorful text
     *
     * @param    string  $text
     * @param    string  $fg
     * @param    string  $bg
     *
     * @return   string
     */
    static public function getColorfulText($text, $fg = 'yellow', $bg = 'black', $decoration = '')
    {
        return ShellColor::getColorfulText($text, $fg, $bg, $decoration);
    }

    /**
     * @brief    display colorful custom text -> will be removed in future !!!
     *
     * @param    string  $text
     * @param    string  $fg
     * @param    string  $bg
     *
     * @return   none
     */
    static public function display($text, $fg = 'yellow', $bg = 'black', $decoration = '')
    {
        echo self::show($text, $fg, $bg, $decoration);
    }

    /**
     * @brief    show colorful custom text  -> recommed to use
     *
     * @param    string  $text
     * @param    string  $fg
     * @param    string  $bg
     *
     * @return   none
     */
    static public function show($text, $fg = 'yellow', $bg = 'black', $decoration = '')
    {
        echo self::getColorfulText($text, $fg, $bg, $decoration);
    }
}
