<?php

namespace Genese;

class Generator
{
    /**
     * @var string
     */
    protected string $path;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = trim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * @param array $params
     * @throws Exception
     */
    public function execute(array $params = []): void
    {
        foreach ($this->getTemplates($params) as $template) {
            $template->execute();
        }
    }

    /**
     * @param array $params
     * @return Template[]
     * @throws Exception
     */
    public function getTemplates(array $params = []): array
    {
        $templates = [];
        foreach (glob($this->path . '/*.t') as $filename) {
            $templates[] = (new Template)->load($filename, $params);
        }
        return $templates;
    }

    /**
     * Get the prompt configuration.
     * TODO: Check if the configuration is alright.
     *
     * @return array
     */
    public function getConfig(): array
    {
        $filename = $this->path . '/prompt.json';
        if (file_exists($filename)) {
            return @\json_decode(file_get_contents($filename), true) ?? [];
        }
        return [];
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
