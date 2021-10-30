<?php

namespace Genese;

use Twig\Environment as TwigEnvironment;
use Twig\Loader\LoaderInterface;
use Webuni\FrontMatter\FrontMatter;

class Template
{
    /**
     * @var string
     */
    protected string $content;

    /**
     * @var array
     */
    protected array $options;

    /**
     * @var TwigEnvironment
     */
    protected TwigEnvironment $twig;

    /**
     * @var FrontMatter
     */
    protected FrontMatter $frontMatter;

    /**
     * @param LoaderInterface|null $loader
     */
    public function __construct(?LoaderInterface $loader = null)
    {
        $this->twig = new TwigEnvironment(
            $loader ?? new \Twig\Loader\FilesystemLoader(getcwd())
        );
        $this->frontMatter = new FrontMatter();
    }

    /**
     * @param string $filename
     * @param array $params
     * @return $this
     * @throws Exception
     */
    public function load(string $filename, array $params = []): self
    {
        try {
            $string = $this->twig->render($filename, $params);
        } catch (\Twig\Error\LoaderError | \Twig\Error\RuntimeError | \Twig\Error\SyntaxError $e) {
            throw new Exception($e->getMessage());
        }

        $fm = $this->frontMatter->parse($string);
        $this->options = $fm->getData();
        $this->content = $fm->getContent();

        return $this;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function render(): ?string
    {
        $filename = $this->getOption('to');
        $force = $this->getOption('force') ?? false;
        $unlessExists = $this->getOption('unless_exists');
        $skipRegex = $this->getOption('skip_if');
        $inject = $this->getOption('inject');

        // Skip if the file already exists
        if (!$force && $unlessExists == 'true' && file_exists($filename)) {
            return null;
        }

        // Skip if the regex matches in the destination file
        if (!$force && $skipRegex && file_exists($filename)) {
            if (preg_match($skipRegex, $this->getOriginalContent()) !== false) {
                return null;
            }
        }

        // Raise an error if the file does not exist and needs injection
        if ($inject && !file_exists($filename)) {
            throw new Exception('The file cannot be injected (not exists).');
        }

        // Insert the content at the given position (after or before)
        if ($inject) {
            $regex = $this->getOption('before') ?: $this->getOption('after') ?: $this->getOption('replace');

            if (!$regex) {
                throw new Exception('You must define either "before" or "after" for the desired injection.');
            }

            preg_match($regex, $this->getOriginalContent(), $matches, PREG_OFFSET_CAPTURE);

            if (empty($matches)) {
                throw new Exception('No matches found for this injection.');
            }

            if ($this->hasOption('replace')) {
                return substr_replace($this->getOriginalContent(), $this->content, $matches[0][1], strlen($matches[0][0]));
            } else {
                $offset = $matches[0][1];
                if (!$this->hasOption('before')) {
                    $offset += strlen($matches[0][0]);
                }
                return substr_replace($this->getOriginalContent(), $this->content, $offset, 0);
            }
        }

        return $this->content;
    }

    /**
     * @throws Exception
     */
    public function execute(): void
    {
        $to = $this->getOption('to');
        $body = $this->render();

        if ($to && $body !== null) {
            file_exists(dirname($to)) || mkdir(dirname($to), 0777, true);
            file_put_contents($to, $body);
        }
    }

    /**
     * @return string
     */
    public function getOriginalContent(): string
    {
        return file_get_contents($this->getOption('to'));
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getOption(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }
}
