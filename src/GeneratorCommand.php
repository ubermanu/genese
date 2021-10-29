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
     * @param Generator $generator
     * @param string|null $name
     */
    public function __construct(Generator $generator, ?string $name = null)
    {
        $this->generator = $generator;
        parent::__construct($name);
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
            $this->generator->execute($input->getArguments());
            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->write(sprintf('<error>%s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
