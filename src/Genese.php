<?php

namespace Genese;

class Genese
{
    /**
     * @return string
     */
    public static function getVersion(): string
    {
        $version = '@git_version@';

        if (str_starts_with($version, '@')) {
            return \Composer\InstalledVersions::getRootPackage()['pretty_version'];
        }

        return $version;
    }
}
