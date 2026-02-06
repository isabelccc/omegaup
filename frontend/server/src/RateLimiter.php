<?php

namespace OmegaUp;

class RateLimiter {
    const COURSE_LIMIT = 5;
    const CONTEST_LIMIT = 10;
    const PROBLEM_LIMIT = 20;
    const WINDOW_SECONDS = 3600; // 1 hour

    /**
     * Check if user can create content of given type
     *
     * @param int $userId
     * @param string $contentType 'course', 'contest', or 'problem'
     * @return bool true if allowed, false if rate limited
     * @throws \OmegaUp\Exceptions\RateLimitExceededException
     */
    public static function checkRateLimit(
        int $userId,
        string $contentType
    ): bool {
        $limit = self::getLimitForType($contentType);
        $key = self::getRateLimitKey($userId, $contentType);

        $cache = CacheAdapter::getInstance();
        $currentCount = $cache->fetch($key);

        if ($currentCount === false) {
            // First request in the window
            $cache->store($key, 1, self::WINDOW_SECONDS);
            return true;
        }

        $currentCount = (int)$currentCount;

        if ($currentCount >= $limit) {
            throw new \OmegaUp\Exceptions\RateLimitExceededException(
                'rateLimitExceeded',
                [
                    'contentType' => $contentType,
                    'limit' => $limit,
                    'window' => self::WINDOW_SECONDS
                ]
            );
        }

        // Increment counter
        $cache->store($key, $currentCount + 1, self::WINDOW_SECONDS);
        return true;
    }

    private static function getLimitForType(string $contentType): int {
        switch ($contentType) {
            case 'course':
                return self::COURSE_LIMIT;
            case 'contest':
                return self::CONTEST_LIMIT;
            case 'problem':
                return self::PROBLEM_LIMIT;
            default:
                throw new \InvalidArgumentException(
                    "Unknown content type: {$contentType}"
                );
        }
    }

    private static function getRateLimitKey(
        int $userId,
        string $contentType
    ): string {
        return "rate_limit:{$contentType}:user:{$userId}";
    }
}
