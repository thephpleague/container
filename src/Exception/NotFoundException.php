<?php

declare(strict_types=1);

namespace League\Container\Exception;

use InvalidArgumentException;
use League\Container\Definition\Definition;
use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
    /**
     * @param list<string> $knownIds
     * @param list<string> $resolutionChain
     */
    public static function forAlias(string $id, array $knownIds = [], array $resolutionChain = []): self
    {
        $normalisedId = Definition::normaliseAlias($id);
        $message = sprintf('Alias (%s) is not being managed by the container or delegates', $id);

        $suggestion = self::findClosestMatch($normalisedId, $knownIds);

        if ($suggestion !== null) {
            $message .= sprintf('. Did you mean (%s)?', $suggestion);
        }

        if (count($resolutionChain) > 1) {
            $message .= sprintf('. Resolution chain: %s', implode(' -> ', $resolutionChain));
        }

        return new self($message);
    }

    /** @param list<string> $candidates */
    private static function findClosestMatch(string $id, array $candidates): ?string
    {
        $bestMatch = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($candidates as $candidate) {
            if (strtolower($candidate) === strtolower($id)) {
                return $candidate;
            }

            $distance = levenshtein($id, $candidate);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestMatch = $candidate;
            }
        }

        if ($bestDistance <= 3) {
            return $bestMatch;
        }

        return null;
    }
}
