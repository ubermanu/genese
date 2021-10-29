<?php

namespace Genese\Tests\Unit;

use Genese\Exception;
use Genese\Template;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    /**
     * @covers
     * @throws Exception
     */
    public function testCanCreateFile()
    {
        $expected = <<<END
<?php

class Test
{
}
END;

        $template = $this->create('01-php-class.t');
        $this->assertEquals(trim($template->render()), $expected);
    }

    /**
     * @covers
     * @throws Exception
     */
    public function testCanCreateFileWithParams()
    {
        $expected = <<<END
<?php

class MyClass
{
}
END;

        $template = $this->create('02-php-class-with-name.t', ['name' => 'MyClass']);
        $this->assertEquals(trim($template->render()), $expected);
    }

    /**
     * @covers
     * @throws Exception
     */
    public function testCanCreateEmptyFile()
    {
        $template = $this->create('03-empty-file.t');
        $this->assertEmpty($template->render());
    }

    /**
     * @param string $filename
     * @param array $params
     * @return Template
     * @throws Exception
     */
    protected function create(string $filename, array $params = []): Template
    {
        return (new Template)->load('tests/_files/' . $filename, $params);
    }
}
