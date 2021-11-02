<?php

namespace Genese\Console;

use Genese\Exception;
use Genese\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
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
        'force',
        'dry-run',
    ];

    /**
     * @var InputDefinition
     */
    protected InputDefinition $fullDefinition;

    /**
     * @var InputDefinition
     */
    protected InputDefinition $definition;

    /**
     * @param Generator $generator
     */
    public function __construct(Generator $generator)
    {
        $this->generator = $generator;
        parent::__construct();
        $this->definition = new CustomInputDefinition();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        foreach ($this->generator->getConfig() as $item) {
            if (isset($item['name'])) {
                $this->addOption(
                    $item['name'],
                    $item['shortcut'] ?? null,
                    InputOption::VALUE_OPTIONAL,
                    $item['message'] ?? null,
                    $item['initial'] ?? null
                );
            }
        }

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite all the files without asking');
        $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Check the output before the files are written');
    }

    /**
     * @inheritDoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('Loading templates from %s', $this->generator->getPath()));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
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
                    $question = new Question(($item['message'] ?? '') . ' ', $item['initial'] ?? null);
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
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $params = array_diff_key($input->getOptions(), $this->excludedOptions);

        try {
            foreach ($this->generator->getTemplates($params) as $template) {

                // Ask confirmation from the user if not specified into the template
                if ($template->getOption('force') != 'true'
                    && file_exists($template->getOption('to'))
                    && !$template->getOption('inject')
                    && !$template->getOption('unless_exists')
                    && !$input->getOption('force')
                    && !$input->getOption('dry-run')
                ) {
                    $question = new ConfirmationQuestion(sprintf("<fg=red>Overwrite %s? (y/N)</>", $template->getOption('to')), false);
                    if (!$this->getHelper('question')->ask($input, $output, $question)) {
                        continue;
                    }
                }

                if ($template->getOption('unless_exists') || $template->getOption('skip_if')) {
                    if (is_null($template->render())) {
                        $output->writeln(sprintf("<fg=yellow>Skip %s</>", $template->getOption('to')));
                        continue;
                    }
                }

                if (!$input->getOption('dry-run')) {
                    $template->execute();
                }

                // Output the message
                $message = $template->getOption('inject') ? "<fg=magenta>Inject %s</>" : "<fg=green>Write %s</>";
                $output->writeln(sprintf($message, $template->getOption('to')));
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }

    /**
     * @inheritDoc
     */
    public function mergeApplicationDefinition(bool $mergeArgs = true)
    {
        if (null === $this->getApplication()) {
            return;
        }

        $this->fullDefinition = new CustomInputDefinition();
        $this->fullDefinition->setOptions($this->definition->getOptions());
        $this->fullDefinition->addOptions($this->getApplication()->getDefinition()->getOptions());

        if ($mergeArgs) {
            $this->fullDefinition->setArguments($this->getApplication()->getDefinition()->getArguments());
            $this->fullDefinition->addArguments($this->definition->getArguments());
        } else {
            $this->fullDefinition->setArguments($this->definition->getArguments());
        }
    }

    /**
     * @inheritDoc
     */
    public function getDefinition()
    {
        return $this->fullDefinition ?? $this->definition;
    }
}
