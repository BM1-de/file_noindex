<?php

declare(strict_types=1);

namespace Bm1\FileNoindex\Middleware;

use Bm1\FileNoindex\Service\DisallowListBuilder;
use Bm1\FileNoindex\Service\RobotsTxtAmender;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Answers GET/HEAD requests to /robots.txt with the static content from the
 * site configuration (staticText route, if present) plus Disallow entries for
 * all files marked as "do not index".
 *
 * Runs after the site resolver (needs the resolved site) and before the
 * static route resolver (must win over a configured robots.txt route).
 *
 * The response is generated live on every request - robots.txt is requested
 * rarely (crawlers), one indexed query per request is uncritical and checkbox
 * changes become effective immediately without any cache invalidation logic.
 */
final class RobotsTxtMiddleware implements MiddlewareInterface
{
    private const DEFAULT_CONTENT = "User-agent: *\n";

    public function __construct(
        private readonly DisallowListBuilder $disallowListBuilder,
        private readonly RobotsTxtAmender $robotsTxtAmender,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)
            || $request->getUri()->getPath() !== '/robots.txt'
        ) {
            return $handler->handle($request);
        }

        $content = $this->robotsTxtAmender->amend(
            $this->getBaseContent($request),
            $this->disallowListBuilder->build()
        );

        $body = new Stream('php://temp', 'rw');
        $body->write($content);

        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withHeader('Cache-Control', 'public, max-age=3600')
            ->withBody($body);
    }

    /**
     * The site configuration's staticText route for robots.txt stays the
     * single place to maintain the base rules. Without one, a minimal
     * default group is used.
     */
    private function getBaseContent(ServerRequestInterface $request): string
    {
        $site = $request->getAttribute('site');
        if ($site instanceof Site) {
            foreach ($site->getConfiguration()['routes'] ?? [] as $route) {
                if (($route['route'] ?? '') === 'robots.txt'
                    && ($route['type'] ?? '') === 'staticText'
                ) {
                    return (string)($route['content'] ?? '');
                }
            }
        }
        return self::DEFAULT_CONTENT;
    }
}
