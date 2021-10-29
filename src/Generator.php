<?php

namespace Genese;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Generator
{
    protected \Twig\Environment $twig;

    public function __construct()
    {
        $this->twig = new \Twig\Environment(
            new \Twig\Loader\FilesystemLoader(getcwd() . '/_templates')
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
        } catch (LoaderError | RuntimeError | SyntaxError $e) {
            throw new Exception($e->getMessage());
        }

        $fm = (new \Webuni\FrontMatter\FrontMatter)->parse($string);

        $settings = $fm->getData();

        // TODO: Add processor for each setting (make it extensible)

        // TO
        if (!isset($settings['to'])) {
            throw new Exception('You must define a filename for the generated one.');
        }

        // FORCE
        $force = false;
        if (isset($settings['force'])) {
            if ($settings['force'] == 'true') {
                $force = true;
            }
        }

        // UNLESS EXISTS
        if (!$force && isset($settings['unless_exists'])) {
            if ($settings['unless_exists'] == 'true') {
                return;
            }
        }

        // SKIP
        if (!$force && isset($settings['skip_if'])) {
            $originalContent = file_get_contents($settings['to']);
            preg_match($settings['skip_if'], $originalContent, $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches)) {
                return;
            }
        }

        $content = $fm->getContent();

        // INJECT
        if (isset($settings['inject'])) {
            if (!file_exists($settings['to'])) {
                throw new Exception('The file cannot be injected (not exists)');
            }

            $originalContent = file_get_contents($settings['to']);
            $regex = $settings['before'] ?? $settings['after'];
            preg_match($regex, $originalContent, $matches, PREG_OFFSET_CAPTURE);

            if (empty($matches)) {
                throw new Exception('No matches for injection');
            }

            $offset = $matches[0][1];
            if (!isset($settings['before'])) {
                $offset += strlen($matches[0][0]);
            }

            $content = substr_replace($originalContent, $fm->getContent(), $offset, 0);
        }

        file_put_contents($settings['to'], $content);
    }
}
