<?php

declare(strict_types=1);

namespace Bm1\FileNoindex\Service;

/**
 * Inserts Disallow lines into an existing robots.txt body.
 *
 * The entries are added to the last existing "User-agent: *" group - not as
 * an additional "*" group, because not all parsers merge groups with the same
 * user agent (RFC 9309 requires it, real-world crawlers differ). If no "*"
 * group exists, a new one is appended at the end.
 */
class RobotsTxtAmender
{
    private const COMMENT_MARKER = '# Files excluded from indexing (EXT:file_noindex)';

    /**
     * @param list<string> $disallowPaths
     */
    public function amend(string $robotsTxt, array $disallowPaths): string
    {
        if ($disallowPaths === []) {
            return $robotsTxt;
        }

        $disallowLines = [self::COMMENT_MARKER];
        foreach ($disallowPaths as $path) {
            $disallowLines[] = 'Disallow: ' . $path;
        }

        $lines = preg_split('/\r\n|\r|\n/', $robotsTxt) ?: [];
        $lastStarUserAgentIndex = null;
        foreach ($lines as $index => $line) {
            if ($this->isStarUserAgentLine($line)) {
                $lastStarUserAgentIndex = $index;
            }
        }

        if ($lastStarUserAgentIndex === null) {
            // No "*" group present: append a new group at the end
            while ($lines !== [] && trim((string) end($lines)) === '') {
                array_pop($lines);
            }
            if ($lines !== []) {
                $lines[] = '';
            }
            $lines[] = 'User-agent: *';
            $lines = [...$lines, ...$disallowLines];
        } else {
            // Skip over further user-agent lines directly following (one group
            // may address several user agents), then insert. Rule order within
            // a group is irrelevant for robots.txt semantics.
            $insertAt = $lastStarUserAgentIndex + 1;
            while ($insertAt < count($lines) && $this->isUserAgentLine($lines[$insertAt])) {
                $insertAt++;
            }
            array_splice($lines, $insertAt, 0, $disallowLines);
        }

        return rtrim(implode("\n", $lines), "\n") . "\n";
    }

    private function isStarUserAgentLine(string $line): bool
    {
        return (bool) preg_match('/^\s*user-agent\s*:\s*\*\s*(#.*)?$/i', $line);
    }

    private function isUserAgentLine(string $line): bool
    {
        return (bool) preg_match('/^\s*user-agent\s*:/i', $line);
    }
}
