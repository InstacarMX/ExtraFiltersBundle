<?php

namespace Instacar\ExtraFiltersBundle\Test\Util;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;

final class PackageVersion
{
    private static VersionParser $semverParser;

    private static function getVersionParser(): VersionParser
    {
        return self::$semverParser ?? self::$semverParser = new VersionParser();
    }

    public static function isLegacyApiPlatform(): bool
    {
        $semverParser = self::getVersionParser();

        return InstalledVersions::satisfies($semverParser, 'api-platform/core', '^2.7');
    }
}
