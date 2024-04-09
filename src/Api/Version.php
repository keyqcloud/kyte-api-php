<?php
namespace Kyte\Api;

class Version
{
    const MAJOR=1;
    const MINOR=0;
    const PATCH=0;

    public static function get()
    {
        return sprintf('v%s.%s.%s', self::MAJOR, self::MINOR, self::PATCH);
    }
}
