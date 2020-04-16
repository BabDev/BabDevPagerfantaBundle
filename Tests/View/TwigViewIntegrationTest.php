<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\View;

use BabDev\PagerfantaBundle\Twig\PagerfantaExtension;
use BabDev\PagerfantaBundle\Twig\PagerfantaRuntime;
use BabDev\PagerfantaBundle\View\TwigView;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\ViewFactory;
use Pagerfanta\View\ViewFactoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

/**
 * Integration tests which simulates a real Twig environment to validate templates are correctly generated.
 */
final class TwigViewIntegrationTest extends TestCase
{
    /**
     * @var ViewFactoryInterface
     */
    public $viewFactory;

    /**
     * @var UrlGeneratorInterface
     */
    public $router;

    /**
     * @var RequestStack
     */
    public $requestStack;

    /**
     * @var Environment
     */
    public $twig;

    protected function setUp(): void
    {
        $filesystemLoader = new FilesystemLoader();
        $filesystemLoader->addPath(__DIR__.'/../../Resources/views', 'BabDevPagerfantaBundle');

        $this->twig = new Environment(new ChainLoader([new ArrayLoader(['integration.html.twig' => '{{ pagerfanta(pager, options) }}']), $filesystemLoader]));
        $this->twig->addExtension(new PagerfantaExtension());
        $this->twig->addExtension(new TranslationExtension($this->createTranslator()));
        $this->twig->addRuntimeLoader($this->createRuntimeLoader());

        $this->router = $this->createRouter();
        $this->requestStack = new RequestStack();
    }

    protected function tearDown(): void
    {
        do {
            $request = $this->requestStack->pop();
        } while (null !== $request);
    }

    private function createPagerfanta(): Pagerfanta
    {
        return new Pagerfanta(new FixedAdapter(100, range(1, 100)));
    }

    public function dataPagerfantaRenderer(): \Generator
    {
        yield 'default template at page 1' => [
            1,
            ['omitFirstPage' => false, 'template' => '@BabDevPagerfantaBundle/default.html.twig'],
            '<nav>
    <span class="disabled">Previous</span>
    <span class="current" aria-current="page">1</span>
    <a href="/pagerfanta-view?page=2">2</a>
    <a href="/pagerfanta-view?page=3">3</a>
    <a href="/pagerfanta-view?page=4">4</a>
    <a href="/pagerfanta-view?page=5">5</a>
    <span class="dots">...</span>
    <a href="/pagerfanta-view?page=10">10</a>
    <a href="/pagerfanta-view?page=2" rel="next">Next</a>
</nav>'
        ];

        yield 'default template at page 1 with translated labels' => [
            1,
            ['omitFirstPage' => false, 'template' => '@BabDevPagerfantaBundle/default.html.twig', 'prev_message' => 'Previous Page', 'next_message' => 'Next Page'],
            '<nav>
    <span class="disabled">Previous Page</span>
    <span class="current" aria-current="page">1</span>
    <a href="/pagerfanta-view?page=2">2</a>
    <a href="/pagerfanta-view?page=3">3</a>
    <a href="/pagerfanta-view?page=4">4</a>
    <a href="/pagerfanta-view?page=5">5</a>
    <span class="dots">...</span>
    <a href="/pagerfanta-view?page=10">10</a>
    <a href="/pagerfanta-view?page=2" rel="next">Next Page</a>
</nav>'
        ];

        yield 'default template at page 5 with first page omitted' => [
            5,
            ['omitFirstPage' => true, 'template' => '@BabDevPagerfantaBundle/default.html.twig'],
            '<nav>
    <a href="/pagerfanta-view?page=4" rel="prev">Previous</a>
    <a href="/pagerfanta-view">1</a>
    <a href="/pagerfanta-view?page=2">2</a>
    <a href="/pagerfanta-view?page=3">3</a>
    <a href="/pagerfanta-view?page=4">4</a>
    <span class="current" aria-current="page">5</span>
    <a href="/pagerfanta-view?page=6">6</a>
    <a href="/pagerfanta-view?page=7">7</a>
    <span class="dots">...</span>
    <a href="/pagerfanta-view?page=10">10</a>
    <a href="/pagerfanta-view?page=6" rel="next">Next</a>
</nav>'
        ];

        yield 'Semantic UI template at page 1' => [
            1,
            ['omitFirstPage' => false, 'template' => '@BabDevPagerfantaBundle/semantic_ui.html.twig'],
            '<div class="ui pagination menu">
    <div class="disabled item">Previous</div>
    <div class="active item" aria-current="page">1</div>
    <a class="item" href="/pagerfanta-view?page=2">2</a>
    <a class="item" href="/pagerfanta-view?page=3">3</a>
    <a class="item" href="/pagerfanta-view?page=4">4</a>
    <a class="item" href="/pagerfanta-view?page=5">5</a>
    <div class="disabled item">&hellip;</div>
    <a class="item" href="/pagerfanta-view?page=10">10</a>
    <a class="item" href="/pagerfanta-view?page=2" rel="next">Next</a>
</div>'
        ];

        yield 'Semantic UI template at page 5 with first page omitted' => [
            5,
            ['omitFirstPage' => true, 'template' => '@BabDevPagerfantaBundle/semantic_ui.html.twig'],
            '<div class="ui pagination menu">
    <a class="item" href="/pagerfanta-view?page=4" rel="prev">Previous</a>
    <a class="item" href="/pagerfanta-view">1</a>
    <a class="item" href="/pagerfanta-view?page=2">2</a>
    <a class="item" href="/pagerfanta-view?page=3">3</a>
    <a class="item" href="/pagerfanta-view?page=4">4</a>
    <div class="active item" aria-current="page">5</div>
    <a class="item" href="/pagerfanta-view?page=6">6</a>
    <a class="item" href="/pagerfanta-view?page=7">7</a>
    <div class="disabled item">&hellip;</div>
    <a class="item" href="/pagerfanta-view?page=10">10</a>
    <a class="item" href="/pagerfanta-view?page=6" rel="next">Next</a>
</div>'
        ];

        yield 'Twitter Bootstrap template at page 1' => [
            1,
            ['omitFirstPage' => false, 'template' => '@BabDevPagerfantaBundle/twitter_bootstrap.html.twig'],
            '<div class="pagination">
    <ul>
        <li class="disabled"><span>Previous</span></li>
        <li class="active" aria-current="page"><span>1</span></li>
        <li><a href="/pagerfanta-view?page=2">2</a></li>
        <li><a href="/pagerfanta-view?page=3">3</a></li>
        <li><a href="/pagerfanta-view?page=4">4</a></li>
        <li><a href="/pagerfanta-view?page=5">5</a></li>
        <li class="disabled"><span>&hellip;</span></li>
        <li><a href="/pagerfanta-view?page=10">10</a></li>
        <li><a href="/pagerfanta-view?page=2" rel="next">Next</a></li>
    </ul>
</div>'
        ];

        yield 'Twitter Bootstrap template at page 5 with first page omitted' => [
            5,
            ['omitFirstPage' => true, 'template' => '@BabDevPagerfantaBundle/twitter_bootstrap.html.twig'],
            '<div class="pagination">
    <ul>
        <li><a href="/pagerfanta-view?page=4" rel="prev">Previous</a></li>
        <li><a href="/pagerfanta-view">1</a></li>
        <li><a href="/pagerfanta-view?page=2">2</a></li>
        <li><a href="/pagerfanta-view?page=3">3</a></li>
        <li><a href="/pagerfanta-view?page=4">4</a></li>
        <li class="active" aria-current="page"><span>5</span></li>
        <li><a href="/pagerfanta-view?page=6">6</a></li>
        <li><a href="/pagerfanta-view?page=7">7</a></li>
        <li class="disabled"><span>&hellip;</span></li>
        <li><a href="/pagerfanta-view?page=10">10</a></li>
        <li><a href="/pagerfanta-view?page=6" rel="next">Next</a></li>
    </ul>
</div>'
        ];

        yield 'Twitter Bootstrap 3 template at page 1' => [
            1,
            ['omitFirstPage' => false, 'template' => '@BabDevPagerfantaBundle/twitter_bootstrap3.html.twig'],
            '<ul class="pagination">
    <li class="disabled"><span>Previous</span></li>
    <li class="active" aria-current="page"><span>1</span></li>
    <li><a href="/pagerfanta-view?page=2">2</a></li>
    <li><a href="/pagerfanta-view?page=3">3</a></li>
    <li><a href="/pagerfanta-view?page=4">4</a></li>
    <li><a href="/pagerfanta-view?page=5">5</a></li>
    <li class="disabled"><span>&hellip;</span></li>
    <li><a href="/pagerfanta-view?page=10">10</a></li>
    <li><a href="/pagerfanta-view?page=2" rel="next">Next</a></li>
</ul>'
        ];

        yield 'Twitter Bootstrap 3 template at page 5 with first page omitted' => [
            5,
            ['omitFirstPage' => true, 'template' => '@BabDevPagerfantaBundle/twitter_bootstrap3.html.twig'],
            '<ul class="pagination">
    <li><a href="/pagerfanta-view?page=4" rel="prev">Previous</a></li>
    <li><a href="/pagerfanta-view">1</a></li>
    <li><a href="/pagerfanta-view?page=2">2</a></li>
    <li><a href="/pagerfanta-view?page=3">3</a></li>
    <li><a href="/pagerfanta-view?page=4">4</a></li>
    <li class="active" aria-current="page"><span>5</span></li>
    <li><a href="/pagerfanta-view?page=6">6</a></li>
    <li><a href="/pagerfanta-view?page=7">7</a></li>
    <li class="disabled"><span>&hellip;</span></li>
    <li><a href="/pagerfanta-view?page=10">10</a></li>
    <li><a href="/pagerfanta-view?page=6" rel="next">Next</a></li>
</ul>'
        ];

        yield 'Twitter Bootstrap 4 template at page 1' => [
            1,
            ['omitFirstPage' => false, 'template' => '@BabDevPagerfantaBundle/twitter_bootstrap4.html.twig'],
            '<ul class="pagination">
    <li class="page-item disabled"><span class="page-link">Previous</span></li>
    <li class="page-item active" aria-current="page"><span class="page-link">1</span></li>
    <li class="page-item"><a class="page-link" href="/pagerfanta-view?page=2">2</a></li>
    <li class="page-item"><a class="page-link" href="/pagerfanta-view?page=3">3</a></li>
    <li class="page-item"><a class="page-link" href="/pagerfanta-view?page=4">4</a></li>
    <li class="page-item"><a class="page-link" href="/pagerfanta-view?page=5">5</a></li>
    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
    <li class="page-item"><a class="page-link" href="/pagerfanta-view?page=10">10</a></li>
    <li class="page-item"><a class="page-link" href="/pagerfanta-view?page=2" rel="next">Next</a></li>
</ul>'
        ];

        yield 'Twitter Bootstrap 4 template at page 5 with first page omitted' => [
            5,
            ['omitFirstPage' => true, 'template' => '@BabDevPagerfantaBundle/twitter_bootstrap4.html.twig'],
            '<ul class="pagination">
    <li class="page-item"><a class="page-link" href="/pagerfanta-view?page=4" rel="prev">Previous</a></li>
    <li class="page-item"><a class="page-link" href="/pagerfanta-view">1</a></li>
    <li class="page-item"><a class="page-link" href="/pagerfanta-view?page=2">2</a></li>
    <li class="page-item"><a class="page-link" href="/pagerfanta-view?page=3">3</a></li>
    <li class="page-item"><a class="page-link" href="/pagerfanta-view?page=4">4</a></li>
    <li class="page-item active" aria-current="page"><span class="page-link">5</span></li>
    <li class="page-item"><a class="page-link" href="/pagerfanta-view?page=6">6</a></li>
    <li class="page-item"><a class="page-link" href="/pagerfanta-view?page=7">7</a></li>
    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
    <li class="page-item"><a class="page-link" href="/pagerfanta-view?page=10">10</a></li>
    <li class="page-item"><a class="page-link" href="/pagerfanta-view?page=6" rel="next">Next</a></li>
</ul>'
        ];
    }

    /**
     * @dataProvider dataPagerfantaRenderer
     */
    public function testPagerfantaRendering(int $page, array $options, string $testOutput): void
    {
        $request = Request::create('/');
        $request->attributes->set('_route', 'pagerfanta_view');
        $request->attributes->set('_route_params', []);

        $this->requestStack->push($request);

        $pagerfanta = $this->createPagerfanta();
        $pagerfanta->setCurrentPage($page);

        $this->assertViewOutputMatches(
            $this->twig->render('integration.html.twig', ['pager' => $pagerfanta, 'options' => $options]),
            $testOutput
        );
    }

    private function createRouter(): UrlGeneratorInterface
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('pagerfanta_view', new Route('/pagerfanta-view'));

        return new UrlGenerator($routeCollection, new RequestContext());
    }

    private function createRuntimeLoader(): RuntimeLoaderInterface
    {
        return new class($this) implements RuntimeLoaderInterface {
            private $testCase;

            public function __construct(TwigViewIntegrationTest $testCase)
            {
                $this->testCase = $testCase;
            }

            public function load($class)
            {
                switch ($class) {
                    case PagerfantaRuntime::class:
                        $viewFactory = new ViewFactory();
                        $viewFactory->set('twig', new TwigView($this->testCase->twig));

                        return new PagerfantaRuntime(
                            'twig',
                            $viewFactory,
                            $this->testCase->router,
                            $this->testCase->requestStack
                        );

                    default:
                        return;
                }
            }
        };
    }

    private function createTranslator(): Translator
    {
        $translator = new Translator('en');
        $translator->addLoader('xliff', new XliffFileLoader());
        $translator->addResource('xliff', __DIR__.'/../../Resources/translations/pagerfanta.en.xliff', 'en', 'pagerfanta');

        return $translator;
    }

    private function assertViewOutputMatches(string $view, string $expected): void
    {
        $this->assertSame($this->removeWhitespacesBetweenTags($expected), $view);
    }

    private function removeWhitespacesBetweenTags($string)
    {
        return preg_replace('/>\s+</', '><', $string);
    }
}
