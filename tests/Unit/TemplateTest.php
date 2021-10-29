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
    public function testCanRenderFile()
    {
        $expected = <<<END
<?php

class Test
{
}
END;

        $template = $this->create('01-php-class.t');
        $this->assertEquals($expected, trim($template->render()));
    }

    /**
     * @covers
     * @throws Exception
     */
    public function testCanRenderFileWithParams()
    {
        $expected = <<<END
<?php

class MyClass
{
}
END;

        $template = $this->create('02-php-class-with-name.t', ['name' => 'MyClass']);
        $this->assertEquals($expected, trim($template->render()));
    }

    /**
     * @covers
     * @throws Exception
     */
    public function testCanRenderEmptyFile()
    {
        $template = $this->create('03-empty-file.t');
        $this->assertEmpty(trim($template->render()));
    }

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
        $template->execute();
        $this->assertEquals($expected, trim($template->getOriginalContent()));
    }

    /**
     * @covers
     * @depends testCanCreateFile
     * @throws Exception
     */
    public function testCanInjectFileBefore()
    {
        $expected = <<<END
<?php

final class Test
{
}
END;

        $template = $this->create('04-inject-class-before.t');
        $template->execute();
        $this->assertEquals($expected, trim($template->getOriginalContent()));
    }

    /**
     * @covers
     * @depends testCanInjectFileBefore
     * @throws Exception
     */
    public function testCanInjectFileAfter()
    {
        $expected = <<<END
<?php

final class Test extends \Exception
{
}
END;

        $template = $this->create('05-inject-class-after.t');
        $template->execute();
        $this->assertEquals($expected, trim($template->getOriginalContent()));
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
