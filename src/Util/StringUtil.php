<?php

namespace Instacar\ExtraFiltersBundle\Util;

final class StringUtil
{
    public static function removeSuffix(string $string, string $suffix): string
    {
        if (str_ends_with($string, $suffix)) {
            return substr($string, 0, strlen($string) - strlen($suffix));
        }

        return $string;
    }
}
