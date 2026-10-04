<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Enums;

enum DeploymentStatus: string
{
    case Ready = 'ready';
    case Requesting = 'requesting';
    case Pending = 'pending';
    case Queued = 'queued';
    case Deploying = 'deploying';
    case Finished = 'finished';
    case Failed = 'failed';
    case FailedBuild = 'failed-build';
    case Cancelled = 'cancelled';
    case Unknown = 'unknown';

    public static function fromProvider(?string $status): self
    {
        if ($status === null || trim($status) === '') {
            return self::Ready;
        }

        return self::tryFrom(mb_strtolower(trim($status))) ?? self::Unknown;
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::Requesting,
            self::Pending,
            self::Queued,
            self::Deploying,
            self::Unknown,
        ], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Finished,
            self::Failed,
            self::FailedBuild,
            self::Cancelled,
        ], true);
    }
}
