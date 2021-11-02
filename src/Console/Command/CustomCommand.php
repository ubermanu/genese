<?php

namespace Genese\Console\Command;

use Genese\Console\Input\CustomInputDefinition;
use Symfony\Component\Console\Command\Command;

/**
 * This command will let pass any given options.
 * So if you specify an undefined option, it will work normally.
 * @internal
 */
class CustomCommand extends Command
{
    /**
     * Custom full definition.
     * Declared as protected, so it can be extended (unlike the fullDefinition prop).
     *
     * @var CustomInputDefinition
     */
    protected CustomInputDefinition $customDefinition;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDefinition(new CustomInputDefinition());
    }

    /**
     * {@inheritdoc}
     */
    public function mergeApplicationDefinition(bool $mergeArgs = true)
    {
        if (null === $this->getApplication()) {
            return;
        }

        $definition = parent::getDefinition();

        $this->customDefinition = new CustomInputDefinition();
        $this->customDefinition->setOptions($definition->getOptions());
        $this->customDefinition->addOptions($this->getApplication()->getDefinition()->getOptions());

        if ($mergeArgs) {
            $this->customDefinition->setArguments($this->getApplication()->getDefinition()->getArguments());
            $this->customDefinition->addArguments($definition->getArguments());
        } else {
            $this->customDefinition->setArguments($definition->getArguments());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return $this->customDefinition ?? parent::getDefinition();
    }
}
