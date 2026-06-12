<?php

declare(strict_types=1);

namespace Bm1\FileNoindex\Tests\Unit\Middleware;

use Bm1\FileNoindex\Middleware\RobotsTxtMiddleware;
use Bm1\FileNoindex\Service\DisallowListBuilder;
use Bm1\FileNoindex\Service\RobotsTxtAmender;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RobotsTxtMiddlewareTest extends UnitTestCase
{
    #[Test]
    public function robotsTxtIsServedWithBaseRulesWhenDisallowListCannotBeBuilt(): void
    {
        $failingBuilder = $this->createStub(DisallowListBuilder::class);
        $failingBuilder->method('build')->willThrowException(
            new \RuntimeException('e.g. missing database column before schema update')
        );
        $middleware = new RobotsTxtMiddleware(
            $failingBuilder,
            new RobotsTxtAmender(),
            new ResponseFactory(),
            new NullLogger(),
        );
        $site = new Site('test', 1, [
            'base' => 'https://example.com/',
            'routes' => [
                ['route' => 'robots.txt', 'type' => 'staticText', 'content' => "User-agent: *\nDisallow: /typo3/\n"],
            ],
        ]);
        $request = (new ServerRequest('https://example.com/robots.txt', 'GET'))
            ->withAttribute('site', $site);

        $response = $middleware->process($request, $this->createNeverCalledHandler());

        // A 5xx robots.txt would make crawlers treat the whole site as
        // disallowed - the base rules must be served instead
        self::assertSame(200, $response->getStatusCode());
        self::assertSame("User-agent: *\nDisallow: /typo3/\n", (string) $response->getBody());
    }

    private function createNeverCalledHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \LogicException('Handler must not be called for /robots.txt');
            }
        };
    }
}
