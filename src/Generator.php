<?php

namespace Genese;

final class Generator
{
    /**
     * @param string $generator
     * @param string $action
     * @param array $params
     * @throws Exception
     */
    public function execute(string $generator, string $action, array $params = []): void
    {
        foreach (glob('_templates/' . $generator . '/' . $action . '/*.t') as $filename)
        {
            (new Template($filename, $params))->execute();
        }
    }
}
