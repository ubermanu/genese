<?php

namespace Genese;

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
        $helper = $this->getHelper('question');
        $options = array_diff_key($input->getOptions(), $this->excludedOptions);

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
                    $question->setMultiselect($item['multiselect'] ?? false);
                    break;
                }
                case 'input':
                default:
                {
                    $question = new Question($item['message'] ?? '', $item['initial'] ?? null);
                    break;
                }
            }
            $res = $helper->ask($input, $output, $question);
            $input->setOption($item['name'], $res);
        }

        try {
            $this->generator->execute($options);
            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->write(sprintf('<error>%s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
