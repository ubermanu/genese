<?php

namespace Genese;

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
     * @param string $content
     * @param array $options
     */
    public function __construct(string $content, array $options = [])
    {
        $this->content = $content;
        $this->options = $options;
    }

    /**
     * TODO: Add processor for each setting (make it extensible)
     *
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
            $originalContent = file_get_contents($filename);
            if (preg_match($skipRegex, $originalContent) !== false) {
                return null;
            }
        }

        // Raise an error if the file does not exist and needs injection
        if ($inject && !file_exists($filename)) {
            throw new Exception('The file cannot be injected (not exists).');
        }

        // Insert the content at the given position (after or before)
        if ($inject) {
            $originalContent = file_get_contents($filename);
            $regex = $this->getOption('before') ?: $this->getOption('after');

            if (!$regex) {
                throw new Exception('You must define either "before" or "after" for the desired injection.');
            }

            preg_match($regex, $originalContent, $matches, PREG_OFFSET_CAPTURE);

            if (empty($matches)) {
                throw new Exception('No matches found for this injection.');
            }

            $offset = $matches[0][1];
            if (!$this->hasOption('before')) {
                $offset += strlen($matches[0][0]);
            }

            return substr_replace($originalContent, $this->content, $offset, 0);
        }

        return $this->content;
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
