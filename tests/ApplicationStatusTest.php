<?php

use PHPUnit\Framework\TestCase;
use UTP\Core\ApplicationStatus;

class ApplicationStatusTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $this->assertCount(4, ApplicationStatus::cases());
        $this->assertSame('submitted', ApplicationStatus::Submitted->value);
        $this->assertSame('processing', ApplicationStatus::Processing->value);
        $this->assertSame('approved', ApplicationStatus::Approved->value);
        $this->assertSame('rejected', ApplicationStatus::Rejected->value);
    }

    public function testBadgeClassMapping(): void
    {
        $this->assertSame('blue', ApplicationStatus::Submitted->badgeClass());
        $this->assertSame('yellow', ApplicationStatus::Processing->badgeClass());
        $this->assertSame('green', ApplicationStatus::Approved->badgeClass());
        $this->assertSame('red', ApplicationStatus::Rejected->badgeClass());
    }

    public function testLabel(): void
    {
        $this->assertSame('Submitted', ApplicationStatus::Submitted->label());
        $this->assertSame('Processing', ApplicationStatus::Processing->label());
        $this->assertSame('Approved', ApplicationStatus::Approved->label());
        $this->assertSame('Rejected', ApplicationStatus::Rejected->label());
    }

    public function testTryFromValidValues(): void
    {
        $this->assertSame(ApplicationStatus::Submitted, ApplicationStatus::tryFrom('submitted'));
        $this->assertSame(ApplicationStatus::Approved, ApplicationStatus::tryFrom('approved'));
    }

    public function testTryFromInvalidReturnsNull(): void
    {
        $this->assertNull(ApplicationStatus::tryFrom('unknown'));
        $this->assertNull(ApplicationStatus::tryFrom(''));
    }
}
