<?php

namespace Genese\Console;

use Symfony\Component\Console\Input\InputOption;

/**
 * Custom input definition that allows undefined options.
 * If the option does not exist, returns an optional one.
 */
class CustomInputDefinition extends \Symfony\Component\Console\Input\InputDefinition
{
    /**
     * @inheritDoc
     */
    public function hasOption(string $name)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function hasShortcut(string $name)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getOption(string $name)
    {
        return parent::hasOption($name)
            ? parent::getOption($name)
            : new InputOption($name, null, InputOption::VALUE_OPTIONAL);
    }
}
