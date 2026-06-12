<?php

declare(strict_types=1);

namespace Bm1\FileNoindex\Tests\Unit\Service;

use Bm1\FileNoindex\Service\DisallowListBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DisallowListBuilderTest extends UnitTestCase
{
    private DisallowListBuilder $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new DisallowListBuilder(
            $this->createStub(ConnectionPool::class),
            $this->createStub(ResourceFactory::class),
            $this->createStub(ProcessedFileRepository::class),
            $this->createStub(StorageRepository::class),
        );
    }

    /**
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    public static function publicUrlToPathDataProvider(): array
    {
        return [
            'relative url gets leading slash' => ['fileadmin/foo/bar.jpg', '/fileadmin/foo/bar.jpg'],
            'absolute path stays unchanged' => ['/fileadmin/foo/bar.jpg', '/fileadmin/foo/bar.jpg'],
            'absolute url is reduced to its path' => ['https://cdn.example.org/assets/bar.jpg', '/assets/bar.jpg'],
            'protocol relative url is reduced to its path' => ['//cdn.example.org/assets/bar.jpg', '/assets/bar.jpg'],
            'encoded characters are kept' => ['fileadmin/T%C3%BCrme%20und%20B%C3%A4ume.jpg', '/fileadmin/T%C3%BCrme%20und%20B%C3%A4ume.jpg'],
            'null yields null' => [null, null],
            'empty string yields null' => ['', null],
            'absolute url without path yields null' => ['https://cdn.example.org', null],
        ];
    }

    #[Test]
    #[DataProvider('publicUrlToPathDataProvider')]
    public function normalizePublicUrlToPathWorks(?string $publicUrl, ?string $expected): void
    {
        self::assertSame($expected, $this->subject->normalizePublicUrlToPath($publicUrl));
    }

    #[Test]
    public function wildcardPatternsCoverCoreProcessingPrefixes(): void
    {
        self::assertSame(
            [
                '/fileadmin/_processed_/*/csm_photo_*',
                '/fileadmin/_processed_/*/preview_photo_*',
            ],
            $this->subject->buildWildcardPatterns('/fileadmin/_processed_/', 'photo')
        );
    }

    #[Test]
    public function wildcardPatternsEncodeSpecialCharactersInFileName(): void
    {
        self::assertSame(
            [
                '/fileadmin/_processed_/*/csm_T%C3%BCrme%20Foto_*',
                '/fileadmin/_processed_/*/preview_T%C3%BCrme%20Foto_*',
            ],
            $this->subject->buildWildcardPatterns('/fileadmin/_processed_/', 'Türme Foto')
        );
    }
}
