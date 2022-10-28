<?php

/**
 * @brief    简易输出(simple echo)
 *
 * @param   object|array  $arg
 * @param   string        $key
 * @param   bool          $isReturn
 *
 * @return  mixed
 **/
function se($arg, $key, $isReturn = false)
{
    $value = '';
    if (is_array($arg)) {
        $value = isset($arg[$key]) ? $arg[$key] : '';
    } elseif (is_object($arg)) {
        $value = isset($arg->$key) ? $arg->$key : '';
    }
    if ($isReturn) {
        return $value;
    } else {
        echo $value;
    }
}

