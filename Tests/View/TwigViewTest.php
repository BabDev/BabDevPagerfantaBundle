<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\View;

use BabDev\PagerfantaBundle\View\TwigView;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Exception\InvalidArgumentException;
use Pagerfanta\Pagerfanta;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Template;
use Twig\TemplateWrapper

final class TwigViewTest extends TestCase
{
    /**
     * @var MockObject|Environment
     */
    private $twig;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
    }

    public function testRendersWithATemplateSpecifiedInTheOptions(): void
    {
        $options = ['template' => 'test.html.twig'];

        $template = $this->createMock(Template::class);
        $template->expects($this->once())
            ->method('displayBlock')
            ->willReturn('Twig template');

        $this->twig->expects($this->once())
            ->method('load')
            ->with($options['template'])
            ->willReturn(new TemplateWrapper($this->twig, $template));

        $this->twig->expects($this->once())
            ->method('mergeGlobals')
            ->willReturn([]);

        $this->twig->expects($this->once())
            ->method('isDebug')
            ->willReturn(false);

        $this->assertSame(
            'Twig template',
            (new TwigView($this->twig, 'constructor.html.twig'))->render($this->createPagerfanta(), $this->createRouteGenerator(), $options)
        );
    }

    public function testRendersWithATemplateSpecifiedInTheConstructorWhenNotSetInTheOptions(): void
    {
        $this->twig->expects($this->once())
            ->method('render')
            ->with('constructor.html.twig')
            ->willReturn('Twig template');

        $this->assertSame(
            'Twig template',
            (new TwigView($this->twig, 'constructor.html.twig'))->render($this->createPagerfanta(), $this->createRouteGenerator())
        );
    }

    public function testRendersWithTheDefaultTemplateWhenNotSetInConstructorOrOptions(): void
    {
        $this->twig->expects($this->once())
            ->method('render')
            ->with(TwigView::DEFAULT_TEMPLATE)
            ->willReturn('Twig template');

        $this->assertSame(
            'Twig template',
            (new TwigView($this->twig))->render($this->createPagerfanta(), $this->createRouteGenerator())
        );
    }

    public function testRejectsANonCallableRouteGenerator(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $pagerfanta = $this->createPagerfanta();
        $routeGenerator = new \stdClass;
        $options = [];

        $this->twig->expects($this->never())
            ->method('render');

        (new TwigView($this->twig))->render($pagerfanta, $routeGenerator, $options);
    }

    private function createPagerfanta(): Pagerfanta
    {
        return new Pagerfanta(new FixedAdapter(100, range(1, 100)));
    }

    private function createRouteGenerator(): callable
    {
        return static function (): void { };
    }
}
