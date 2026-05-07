<?php

declare(strict_types=1);

namespace UTP\Core;

/**
 * Application Status Enum
 *
 * Replaces magic strings for application statuses throughout the codebase.
 * Use ApplicationStatus::Submitted->value for database comparisons.
 */
enum ApplicationStatus: string
{
    case Submitted = 'submitted';
    case Processing = 'processing';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Get the CSS badge class for this status.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Processing => 'yellow',
            self::Submitted => 'blue',
        };
    }

    /**
     * Get a human-readable label.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}
