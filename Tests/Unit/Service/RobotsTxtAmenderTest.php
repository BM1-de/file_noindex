<?php

declare(strict_types=1);

namespace Bm1\FileNoindex\Tests\Unit\Service;

use Bm1\FileNoindex\Service\RobotsTxtAmender;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RobotsTxtAmenderTest extends UnitTestCase
{
    private RobotsTxtAmender $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new RobotsTxtAmender();
    }

    #[Test]
    public function emptyPathListReturnsInputUnchanged(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /typo3/\n";
        self::assertSame($robotsTxt, $this->subject->amend($robotsTxt, []));
    }

    #[Test]
    public function disallowLinesAreInsertedIntoStarGroup(): void
    {
        $result = $this->subject->amend(
            "User-agent: *\nDisallow: /typo3/\n",
            ['/fileadmin/secret.jpg']
        );
        $expected = "User-agent: *\n"
            . "# Files excluded from indexing (EXT:file_noindex)\n"
            . "Disallow: /fileadmin/secret.jpg\n"
            . "Disallow: /typo3/\n";
        self::assertSame($expected, $result);
    }

    #[Test]
    public function lastStarGroupIsUsedWhenSeveralExist(): void
    {
        $result = $this->subject->amend(
            "User-agent: *\nDisallow: /a/\n\nUser-agent: Googlebot\nDisallow: /b/\n\nUser-agent: *\nDisallow: /c/\n",
            ['/fileadmin/secret.jpg']
        );
        $expected = "User-agent: *\n"
            . "Disallow: /a/\n"
            . "\n"
            . "User-agent: Googlebot\n"
            . "Disallow: /b/\n"
            . "\n"
            . "User-agent: *\n"
            . "# Files excluded from indexing (EXT:file_noindex)\n"
            . "Disallow: /fileadmin/secret.jpg\n"
            . "Disallow: /c/\n";
        self::assertSame($expected, $result);
    }

    #[Test]
    public function insertionSkipsFurtherUserAgentLinesOfTheSameGroup(): void
    {
        $result = $this->subject->amend(
            "User-agent: *\nUser-agent: Googlebot\nDisallow: /a/\n",
            ['/fileadmin/secret.jpg']
        );
        $expected = "User-agent: *\n"
            . "User-agent: Googlebot\n"
            . "# Files excluded from indexing (EXT:file_noindex)\n"
            . "Disallow: /fileadmin/secret.jpg\n"
            . "Disallow: /a/\n";
        self::assertSame($expected, $result);
    }

    #[Test]
    public function newGroupIsAppendedWhenNoStarGroupExists(): void
    {
        $result = $this->subject->amend(
            "User-agent: Googlebot\nDisallow: /a/\n",
            ['/fileadmin/secret.jpg']
        );
        $expected = "User-agent: Googlebot\n"
            . "Disallow: /a/\n"
            . "\n"
            . "User-agent: *\n"
            . "# Files excluded from indexing (EXT:file_noindex)\n"
            . "Disallow: /fileadmin/secret.jpg\n";
        self::assertSame($expected, $result);
    }

    #[Test]
    public function emptyInputGetsNewGroupWithoutLeadingBlankLine(): void
    {
        $result = $this->subject->amend('', ['/fileadmin/secret.jpg']);
        $expected = "User-agent: *\n"
            . "# Files excluded from indexing (EXT:file_noindex)\n"
            . "Disallow: /fileadmin/secret.jpg\n";
        self::assertSame($expected, $result);
    }

    #[Test]
    public function crlfInputIsHandled(): void
    {
        $result = $this->subject->amend(
            "User-agent: *\r\nDisallow: /typo3/\r\n",
            ['/fileadmin/secret.jpg']
        );
        $expected = "User-agent: *\n"
            . "# Files excluded from indexing (EXT:file_noindex)\n"
            . "Disallow: /fileadmin/secret.jpg\n"
            . "Disallow: /typo3/\n";
        self::assertSame($expected, $result);
    }

    #[Test]
    public function userAgentMatchingIsCaseInsensitiveAndToleratesWhitespace(): void
    {
        $result = $this->subject->amend(
            "USER-AGENT:   *  \nDisallow: /typo3/\n",
            ['/fileadmin/secret.jpg']
        );
        $expected = "USER-AGENT:   *  \n"
            . "# Files excluded from indexing (EXT:file_noindex)\n"
            . "Disallow: /fileadmin/secret.jpg\n"
            . "Disallow: /typo3/\n";
        self::assertSame($expected, $result);
    }

    #[Test]
    public function severalPathsResultInOneDisallowLineEach(): void
    {
        $result = $this->subject->amend("User-agent: *\n", ['/a.jpg', '/b.jpg']);
        self::assertStringContainsString("Disallow: /a.jpg\nDisallow: /b.jpg", $result);
    }

    #[Test]
    public function resultAlwaysEndsWithSingleNewline(): void
    {
        $result = $this->subject->amend('User-agent: *', ['/a.jpg']);
        self::assertStringEndsWith("Disallow: /a.jpg\n", $result);
        self::assertStringEndsNotWith("\n\n", $result);
    }
}
