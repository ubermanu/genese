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
        $output->writeln(sprintf('Loading templates from %s', $this->generator->getPath()));

        $this->askMissingOptions($input, $output);
        $params = array_diff_key($input->getOptions(), $this->excludedOptions);

        try {
            foreach ($this->generator->getTemplates($params) as $template) {

                // Ask confirmation from the user if not specified into the template
                // TODO: Add a global force argument to skip this
                if ($template->getOption('force') != 'true'
                    && file_exists($template->getOption('to'))
                    && !$template->getOption('inject')
                    && !$template->getOption('unless_exists')
                ) {
                    $helper = $this->getHelper('question');
                    $question = new ConfirmationQuestion(sprintf('<error>Overwrite %s? (y/N)</error>', $template->getOption('to')), false);
                    if (!$helper->ask($input, $output, $question)) {
                        continue;
                    }
                }

                if ($template->getOption('unless_exists') || $template->getOption('skip_if')) {
                    if (is_null($template->render())) {
                        $output->writeln(sprintf('<comment>Skip %s</comment>', $template->getOption('to')));
                        continue;
                    }
                }

                $template->execute();

                // Output the message
                $message = $template->getOption('inject') ? '<comment>%s</comment>' : '<info>%s</info>';
                $output->writeln(sprintf($message, $template->getOption('to')));
            }

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
