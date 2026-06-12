<?php

declare(strict_types=1);

namespace Bm1\FileNoindex\Tests\Functional\Middleware;

use Bm1\FileNoindex\Middleware\RobotsTxtMiddleware;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RobotsTxtMiddlewareTest extends FunctionalTestCase
{
    public const PASSED_THROUGH_STATUS = 418;

    protected array $testExtensionsToLoad = ['bm1/file-noindex'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->get(StorageRepository::class)->createLocalStorage('fileadmin', 'fileadmin/', 'relative');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/files.csv');
        foreach ([
            '/fileadmin/test/photo.jpg',
            '/fileadmin/test/public.jpg',
            '/fileadmin/test/Türme über Bayreuth.jpg',
            '/fileadmin/_processed_/1/2/csm_photo_0123456789.jpg',
            // csm_photo_9876543210.jpg is deliberately NOT created: stale
            // database rows without a physical file must not be listed
        ] as $relativePath) {
            $absolutePath = $this->instancePath . $relativePath;
            GeneralUtility::mkdir_deep(dirname($absolutePath));
            file_put_contents($absolutePath, 'x');
        }
    }

    #[Test]
    public function disallowEntriesAreMergedIntoStaticTextRouteContent(): void
    {
        $body = (string) $this->dispatch($this->createRobotsTxtRequest(withStaticTextRoute: true))->getBody();

        self::assertStringStartsWith("User-agent: *\n", $body);
        // Base rules from the site configuration are kept
        self::assertStringContainsString('Disallow: /typo3/', $body);
        // Original file
        self::assertStringContainsString('Disallow: /fileadmin/test/photo.jpg', $body);
        // Existing processed variant
        self::assertStringContainsString('Disallow: /fileadmin/_processed_/1/2/csm_photo_0123456789.jpg', $body);
        // Stale database row without physical file is not listed
        self::assertStringNotContainsString('csm_photo_9876543210.jpg', $body);
        // Wildcard patterns for future processed variants
        self::assertStringContainsString('Disallow: /fileadmin/_processed_/*/csm_photo_*', $body);
        self::assertStringContainsString('Disallow: /fileadmin/_processed_/*/preview_photo_*', $body);
    }

    #[Test]
    public function responseHasTextPlainContentTypeAndCacheControl(): void
    {
        $response = $this->dispatch($this->createRobotsTxtRequest(withStaticTextRoute: true));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/plain; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertSame('public, max-age=3600', $response->getHeaderLine('Cache-Control'));
    }

    #[Test]
    public function defaultGroupIsUsedWithoutStaticTextRoute(): void
    {
        $body = (string) $this->dispatch($this->createRobotsTxtRequest(withStaticTextRoute: false))->getBody();

        self::assertStringStartsWith("User-agent: *\n", $body);
        self::assertStringNotContainsString('Disallow: /typo3/', $body);
        self::assertStringContainsString('Disallow: /fileadmin/test/photo.jpg', $body);
    }

    #[Test]
    public function unmarkedAndMissingFilesAreNotListed(): void
    {
        $body = (string) $this->dispatch($this->createRobotsTxtRequest(withStaticTextRoute: true))->getBody();

        self::assertStringNotContainsString('public.jpg', $body);
        self::assertStringNotContainsString('missing.jpg', $body);
    }

    #[Test]
    public function fileNamesWithSpecialCharactersAreListedEncoded(): void
    {
        $body = (string) $this->dispatch($this->createRobotsTxtRequest(withStaticTextRoute: true))->getBody();

        self::assertStringContainsString('Disallow: /fileadmin/test/T%C3%BCrme%20%C3%BCber%20Bayreuth.jpg', $body);
        self::assertStringContainsString('Disallow: /fileadmin/_processed_/*/csm_T%C3%BCrme%20%C3%BCber%20Bayreuth_*', $body);
        self::assertStringNotContainsString('Türme', $body);
    }

    #[Test]
    public function otherPathsArePassedThroughToNextHandler(): void
    {
        $request = (new ServerRequest('https://example.com/some/page', 'GET'))
            ->withAttribute('site', $this->createSite(false));

        self::assertSame(self::PASSED_THROUGH_STATUS, $this->dispatch($request)->getStatusCode());
    }

    #[Test]
    public function nonReadRequestMethodsArePassedThroughToNextHandler(): void
    {
        $request = (new ServerRequest('https://example.com/robots.txt', 'POST'))
            ->withAttribute('site', $this->createSite(true));

        self::assertSame(self::PASSED_THROUGH_STATUS, $this->dispatch($request)->getStatusCode());
    }

    private function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new Response())->withStatus(RobotsTxtMiddlewareTest::PASSED_THROUGH_STATUS);
            }
        };
        return $this->get(RobotsTxtMiddleware::class)->process($request, $handler);
    }

    private function createRobotsTxtRequest(bool $withStaticTextRoute): ServerRequestInterface
    {
        return (new ServerRequest('https://example.com/robots.txt', 'GET'))
            ->withAttribute('site', $this->createSite($withStaticTextRoute));
    }

    private function createSite(bool $withStaticTextRoute): Site
    {
        $configuration = ['base' => 'https://example.com/'];
        if ($withStaticTextRoute) {
            $configuration['routes'] = [
                [
                    'route' => 'robots.txt',
                    'type' => 'staticText',
                    'content' => "User-agent: *\r\nDisallow: /typo3/\r\n",
                ],
            ];
        }
        return new Site('test', 1, $configuration);
    }
}
