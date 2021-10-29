<?php

namespace Genese;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratorCommand extends Command
{
    /**
     * @var Generator
     */
    protected Generator $generator;

    /**
     * @var string[]
     */
    protected array $excludedOptions = [
        'help',
        'quiet',
        'verbose',
        'version',
        'ansi',
        'no-interaction',
    ];

    /**
     * @param string $generator
     * @param string $action
     * @param string $rootDir
     */
    public function __construct(string $generator, string $action, string $rootDir)
    {
        $this->generator = new Generator($generator, $action, $rootDir);
        parent::__construct(strtolower($generator) . ':' . strtolower($action));
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        foreach ($this->generator->getConfig() as $input) {
            if (isset($input['name'])) {
                $this->addOption(
                    $input['name'],
                    $input['shortcut'] ?? null,
                    InputOption::VALUE_OPTIONAL,
                    $input['message'] ?? null
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->generator->execute(array_diff_key($input->getOptions(), $this->excludedOptions));
            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->write(sprintf('<error>%s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
