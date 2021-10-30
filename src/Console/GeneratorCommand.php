<?php

namespace Genese\Console;

use Genese\Exception;
use Genese\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

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
     * @param Generator $generator
     */
    public function __construct(Generator $generator)
    {
        $this->generator = $generator;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        foreach ($this->generator->getConfig() as $item) {
            if (isset($item['name'])) {
                $this->addOption($item['name'], $item['shortcut'] ?? null, InputOption::VALUE_OPTIONAL, $item['message'] ?? null);
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf('<info>Loading templates from %s</info>', $this->generator->getPath()));

        $this->askMissingOptions($input, $output);
        $options = array_diff_key($input->getOptions(), $this->excludedOptions);

        try {
            $this->generator->execute($options);
            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function askMissingOptions(InputInterface $input, OutputInterface $output): void
    {
        $helper = $this->getHelper('question');

        foreach ($this->generator->getConfig() as $item) {
            if (!isset($item['name']) || $input->getOption($item['name']) !== null) {
                continue;
            }

            switch ($item['type'] ?? null) {
                case 'confirmation':
                {
                    $question = new ConfirmationQuestion($item['message'] ?? '', boolval($item['initial'] ?? true));
                    break;
                }
                case 'choice':
                {
                    $question = new ChoiceQuestion($item['message'] ?? '', $item['choices'] ?? [], $item['initial'] ?? '0');
                    $question->setMultiselect($item['multiple'] ?? false);
                    break;
                }
                case 'input':
                default:
                {
                    $question = new Question($item['message'] ?? '', $item['initial'] ?? null);
                    break;
                }
            }

            $question->setTrimmable($item['trim'] ?? true);
            $question->setHidden($item['hidden'] ?? false);

            if (isset($item['errorMessage'])) {
                $question->setErrorMessage($item['errorMessage']);
            }

            $res = $helper->ask($input, $output, $question);
            $input->setOption($item['name'], $res);
        }
    }
}
