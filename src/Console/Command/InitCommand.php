<?php

namespace Genese\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('init')
            ->setDescription('Install some default templates as an example');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach (glob(__DIR__ . '/../../_templates/generator/*/*.t') as $tmp) {
            $path = substr($tmp, strlen(__DIR__ . '/../../'));
            file_exists(dirname($path)) || mkdir(dirname($path), 0777, true);
            file_exists($path) || copy($tmp, $path);
        }

        return Command::SUCCESS;
    }
}
