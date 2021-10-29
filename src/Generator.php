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
     * @param string $name
     * @param string $action
     */
    public function __construct(string $name, string $action)
    {
        $this->name = $name;
        $this->action = $action;
    }

    /**
     * @param array $params
     * @throws Exception
     */
    public function execute(array $params = []): void
    {
        foreach (glob('_templates/' . $this->name . '/' . $this->action . '/*.t') as $filename) {
            (new Template($filename, $params))->execute();
        }
    }

    /**
     * Get the prompt configuration.
     * @return array|null
     */
    public function getConfig(): ?array
    {
        try {
            $filename = '_templates/' . $this->name . '/' . $this->action . '/prompt.json';
            return \json_decode(file_get_contents($filename), true);
        } catch (\Exception $e) {
            return null;
        }
    }
}
