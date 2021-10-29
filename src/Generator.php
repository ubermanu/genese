<?php

namespace Genese;

class Generator
{
    /**
     * @var string
     */
    protected string $name;

    /**
     * @var string
     */
    protected string $action;

    /**
     * @var string
     */
    protected string $rootDir;

    /**
     * @param string $name
     * @param string $action
     * @param string $rootDir
     */
    public function __construct(string $name, string $action, string $rootDir = '_templates')
    {
        $this->name = $name;
        $this->action = $action;
        $this->rootDir = trim($rootDir, DIRECTORY_SEPARATOR);
    }

    /**
     * @param array $params
     * @throws Exception
     */
    public function execute(array $params = []): void
    {
        foreach (glob($this->rootDir . '/' . $this->name . '/' . $this->action . '/*.t') as $filename) {
            (new Template)->load($filename, $params)->execute();
        }
    }

    /**
     * Get the prompt configuration.
     * @return array|null
     */
    public function getConfig(): ?array
    {
        try {
            $filename = $this->rootDir . '/' . $this->name . '/' . $this->action . '/prompt.json';
            return \json_decode(file_get_contents($filename), true);
        } catch (\Exception $e) {
            return null;
        }
    }
}
