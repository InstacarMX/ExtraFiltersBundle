<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Common\Generator;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\PropertyHelperTrait;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

trait MatchGeneratorTrait
{
    use PropertyHelperTrait;

    private IriConverterInterface $iriConverter;
    private PropertyAccessorInterface $propertyAccessor;

    abstract protected function getLogger(): LoggerInterface;

    abstract protected function getIriConverter(): IriConverterInterface;

    abstract protected function getPropertyAccessor(): PropertyAccessorInterface;

    /**
     * Gets the ID from an IRI or a raw ID.
     *
     * @param string $value
     * @return mixed
     */
    protected function getIdFromValue(string $value)
    {
        try {
            $item = $this->getIriConverter()->getItemFromIri($value, ['fetch_data' => false]);

            return $this->getPropertyAccessor()->getValue($item, 'id');
        } catch (InvalidArgumentException $e) {
            // Do nothing, return the raw value
        }

        return $value;
    }

    /**
     * Normalize the values array.
     *
     * @param mixed[] $values
     * @param string $property
     * @return mixed[]|null
     */
    protected function normalizeValues(array $values, string $property): ?array
    {
        foreach ($values as $key => $value) {
            if (!is_int($key) || !(is_string($value) || is_int($value))) {
                unset($values[$key]);
            }
        }

        if (empty($values)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('At least one value is required, multiple values should be in "%1$s[]=firstvalue&%1$s[]=secondvalue" format', $property)),
            ]);

            return null;
        }

        return array_values($values);
    }

    /**
     * When the field should be an integer, check that the given value is a valid one.
     *
     * @param mixed[] $values
     * @param mixed|null $type
     */
    protected function hasValidValues(array $values, $type = null): bool
    {
        foreach ($values as $value) {
            if (null !== $value && in_array($type, (array) self::DOCTRINE_INTEGER_TYPE, true) && false === filter_var($value, \FILTER_VALIDATE_INT)) {
                return false;
            }
        }

        return true;
    }
}
