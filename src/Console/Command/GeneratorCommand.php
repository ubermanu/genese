<?php

namespace Genese\Console\Command;

use Genese\Exception;
use Genese\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class GeneratorCommand extends CustomCommand
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
        'force',
        'dry-run',
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
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        foreach ($this->generator->getConfig() as $item) {
            if (isset($item['name'])) {
                $this->addOption(
                    $item['name'],
                    $item['shortcut'] ?? null,
                    InputOption::VALUE_OPTIONAL,
                    $item['message'] ?? '',
                    $item['initial'] ?? null
                );
            }
        }

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite all the files without asking');
        $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Check the output before the files are written');
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('Loading templates from %s', $this->generator->getPath()));
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $helper = $this->getHelper('question');

        foreach ($this->generator->getConfig() as $item) {
            if (!isset($item['name']) || $input->getOption($item['name']) !== null) {
                continue;
            }

            // Create a message from the name if not defined in the prompt.json
            $message = sprintf('<fg=cyan>?</> %s ', ($item['message'] ?? ucfirst($item['name']) . '?'));

            switch ($item['type'] ?? null) {
                case 'confirmation':
                {
                    $question = new ConfirmationQuestion($message, boolval($item['initial'] ?? true));
                    break;
                }
                case 'choice':
                {
                    $question = new ChoiceQuestion($message, $item['choices'] ?? [], $item['initial'] ?? '0');
                    $question->setMultiselect($item['multiple'] ?? false);
                    break;
                }
                case 'input':
                default:
                {
                    $question = new Question($message, $item['initial'] ?? null);
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $params = array_diff_key($input->getOptions(), $this->excludedOptions);

        try {
            foreach ($this->generator->getTemplates($params) as $template) {

                // If the file that the template outputs exists,
                // Ask confirmation from the user if not specified into the template settings
                if (file_exists($template->getOption('to'))
                    && !$template->getOption('force')
                    && !$template->getOption('inject')
                    && !$template->getOption('unless_exists')
                    && !$input->getOption('force')
                    && !$input->getOption('dry-run')
                ) {
                    $question = new ConfirmationQuestion(sprintf("\t<fg=red>exists: %s, Overwrite? (y/N)</> ", $template->getOption('to')), false);
                    if (!$this->getHelper('question')->ask($input, $output, $question)) {
                        continue;
                    }
                }

                if ($template->getOption('unless_exists') || $template->getOption('skip_if')) {
                    if (is_null($template->render())) {
                        $output->writeln(sprintf("\t<fg=yellow>skipped: %s</>", $template->getOption('to')));
                        continue;
                    }
                }

                if (!$input->getOption('dry-run')) {
                    $template->execute();
                }

                // Output the message
                $message = $template->getOption('inject') ? "\t<fg=magenta>inject: %s</>" : "\t<fg=green>added: %s</>";
                $output->writeln(sprintf($message, $template->getOption('to')));
            }

        } catch (Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * {@inheritdoc}
     */
    public function mergeApplicationDefinition(bool $mergeArgs = true)
    {
        parent::mergeApplicationDefinition($mergeArgs);

        // Reset all the default values to force user interaction
        foreach ($this->customDefinition->getOptions() as $option) {
            $option->setDefault(null);
        }
    }
}
