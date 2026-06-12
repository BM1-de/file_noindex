<?php

declare(strict_types=1);

namespace Bm1\FileNoindex\Service;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Collects the URL paths of all files marked as "do not index" including
 * their processed variants, ready to be used as robots.txt Disallow entries.
 *
 * Only files from local, public storages are considered - files that are not
 * publicly reachable do not need a robots.txt entry.
 */
class DisallowListBuilder
{
    /**
     * File name prefixes used by the core image processing tasks
     * (Image/CropScaleMask -> "csm_", Image/Preview -> "preview_").
     */
    private const PROCESSED_NAME_PREFIXES = ['csm_', 'preview_'];

    /**
     * @var array<int, string|null>
     */
    private array $processingFolderPathCache = [];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly ResourceFactory $resourceFactory,
        private readonly ProcessedFileRepository $processedFileRepository,
        private readonly StorageRepository $storageRepository,
    ) {}

    /**
     * @return list<string> Encoded URL paths and wildcard patterns, deduplicated
     */
    public function build(): array
    {
        $paths = [];
        foreach ($this->fetchMarkedFileUids() as $fileUid) {
            try {
                $file = $this->resourceFactory->getFileObject($fileUid);
            } catch (FileDoesNotExistException) {
                continue;
            }
            $storage = $file->getStorage();
            if (!$storage->isPublic() || $storage->getDriverType() !== 'Local') {
                continue;
            }
            $originalPath = $this->normalizePublicUrlToPath($file->getPublicUrl());
            if ($originalPath === null) {
                continue;
            }
            $paths[] = $originalPath;
            foreach ($this->getProcessedVariantPaths($file) as $variantPath) {
                $paths[] = $variantPath;
            }
            $processingFolderPath = $this->getProcessingFolderPublicPath($storage);
            if ($processingFolderPath !== null) {
                foreach ($this->buildWildcardPatterns($processingFolderPath, $file->getNameWithoutExtension()) as $pattern) {
                    $paths[] = $pattern;
                }
            }
        }
        return array_values(array_unique($paths));
    }

    /**
     * Wildcard patterns covering processed variants that do not exist yet
     * (the core shards processed files into hashed subfolders, robots.txt "*"
     * also matches slashes). Deliberately over-blocks similar file names,
     * e.g. "csm_foto_*" also matches variants of "foto_2.jpg".
     *
     * @return list<string>
     */
    public function buildWildcardPatterns(string $processingFolderPath, string $fileNameWithoutExtension): array
    {
        $stem = rawurlencode($fileNameWithoutExtension);
        $patterns = [];
        foreach (self::PROCESSED_NAME_PREFIXES as $prefix) {
            $patterns[] = $processingFolderPath . '*/' . $prefix . $stem . '_*';
        }
        return $patterns;
    }

    /**
     * Reduces a public URL as returned by the FAL drivers (relative like
     * "fileadmin/foo.jpg" or absolute like "https://cdn.example.org/foo.jpg")
     * to an absolute URL path suitable for a Disallow line.
     */
    public function normalizePublicUrlToPath(?string $publicUrl): ?string
    {
        if ($publicUrl === null || $publicUrl === '') {
            return null;
        }
        if (preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $publicUrl)) {
            $path = parse_url($publicUrl, PHP_URL_PATH);
            return is_string($path) && $path !== '' ? $path : null;
        }
        return '/' . ltrim($publicUrl, '/');
    }

    /**
     * @return list<int>
     */
    private function fetchMarkedFileUids(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file');
        $queryBuilder->getRestrictions()
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, 0));
        $result = $queryBuilder
            ->select('f.uid')
            ->from('sys_file', 'f')
            ->join(
                'f',
                'sys_file_metadata',
                'm',
                $queryBuilder->expr()->eq('m.file', $queryBuilder->quoteIdentifier('f.uid'))
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'm.tx_filenoindex_noindex',
                    $queryBuilder->createNamedParameter(1, ParameterType::INTEGER)
                ),
                // Only the default language metadata record counts (l10n_mode=exclude)
                $queryBuilder->expr()->eq(
                    'm.sys_language_uid',
                    $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)
                ),
                $queryBuilder->expr()->eq(
                    'f.missing',
                    $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)
                )
            )
            ->orderBy('f.uid')
            ->executeQuery();

        return array_map(intval(...), array_column($result->fetchAllAssociative(), 'uid'));
    }

    /**
     * @return list<string>
     */
    private function getProcessedVariantPaths(File $file): array
    {
        $paths = [];
        foreach ($this->processedFileRepository->findAllByOriginalFile($file) as $processedFile) {
            if ($processedFile->usesOriginalFile() || !$processedFile->exists()) {
                continue;
            }
            $path = $this->normalizePublicUrlToPath($processedFile->getPublicUrl());
            if ($path !== null) {
                $paths[] = $path;
            }
        }
        return $paths;
    }

    /**
     * Resolves the public URL path of the storage's processing folder without
     * creating it (ResourceStorage::getProcessingFolder() would), e.g.
     * "/fileadmin/_processed_/".
     */
    private function getProcessingFolderPublicPath(ResourceStorage $storage): ?string
    {
        $storageUid = $storage->getUid();
        if (array_key_exists($storageUid, $this->processingFolderPathCache)) {
            return $this->processingFolderPathCache[$storageUid];
        }

        $identifier = trim((string) ($storage->getStorageRecord()['processingfolder'] ?? ''));
        if ($identifier === '') {
            $identifier = ResourceStorage::DEFAULT_ProcessingFolder;
        }
        $targetStorage = $storage;
        if (str_contains($identifier, ':')) {
            // "<storageUid>:<folderIdentifier>" - processing folder in another storage
            [$targetStorageUid, $identifier] = explode(':', $identifier, 2);
            $targetStorage = $this->storageRepository->findByUid((int) $targetStorageUid);
        }

        $path = null;
        if ($targetStorage !== null
            && $targetStorage->isPublic()
            && $targetStorage->getDriverType() === 'Local'
        ) {
            $rootPath = $this->normalizePublicUrlToPath(
                $targetStorage->getRootLevelFolder(false)->getPublicUrl()
            );
            if ($rootPath !== null) {
                $encodedIdentifier = implode(
                    '/',
                    array_map(rawurlencode(...), explode('/', trim($identifier, '/')))
                );
                $path = rtrim($rootPath, '/') . '/' . $encodedIdentifier . '/';
            }
        }

        return $this->processingFolderPathCache[$storageUid] = $path;
    }
}
