<?php

namespace Instacar\ExtraFiltersBundle\Util;

/**
 * @internal
 */
final class IterableUtil
{
    /**
     * @template T as mixed
     * @param iterable<T> $iterable
     * @return array<T>
     */
    public static function iterableToArray(iterable $iterable): array
    {
        return $iterable instanceof \Traversable ? iterator_to_array($iterable) : (array) $iterable;
    }
}
