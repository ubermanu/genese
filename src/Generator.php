<?php

namespace Genese;

final class Generator
{
    /**
     * @var \Twig\Environment
     */
    protected \Twig\Environment $twig;

    /**
     * @constructor
     */
    public function __construct()
    {
        $this->twig = new \Twig\Environment(
            new \Twig\Loader\FilesystemLoader(getcwd())
        );
    }

    /**
     * @param string $filename
     * @param array $params
     * @throws Exception
     */
    public function execute(string $filename, array $params = []): void
    {
        try {
            $string = $this->twig->render($filename, $params);
        } catch (\Twig\Error\LoaderError | \Twig\Error\RuntimeError | \Twig\Error\SyntaxError $e) {
            throw new Exception($e->getMessage());
        }

        $fm = (new \Webuni\FrontMatter\FrontMatter)->parse($string);
        $template = new Template($fm->getContent(), $fm->getData());

        $to = $template->getOption('to');
        $body = $template->render();

        if ($to && $body) {
            file_put_contents($to, $body);
        }
    }
}
