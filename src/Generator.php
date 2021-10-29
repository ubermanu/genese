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
        $filename = sprintf('_templates/%s/%s', $generator, $action);
        (new Template($filename, $params))->execute();
    }
}
