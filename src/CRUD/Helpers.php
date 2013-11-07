<?php

namespace CRUD;


class Helpers
{
    public static function arrayMergeByKey()
    {
        return array_reduce(
            func_get_args(),
            function($sum, $item) {
                array_walk(
                    $item,
                    function($v, $k) use (&$sum) {
                        $sum[$k] = array_merge(
                            isset($sum[$k]) ? $sum[$k] : array(),
                            $v
                        );
                    },
                    array()
                );

                return $sum;
            }
        );
    }


    public static function getMethodBodyAndDocComment($cls, $mtd)
    {
        $func = new \ReflectionMethod($cls, $mtd);

        $start_line = $func->getStartLine() + 1;
        $length = $func->getEndLine() - $start_line - 1;
        $lines = array_slice(file($func->getFileName()), $start_line, $length);

        return array(implode('', $lines), $func->getDocComment());
    }
}
