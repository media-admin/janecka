<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\Utility;

use Doctrine\Instantiator\Exception\InvalidArgumentException;

/**
 * Class JsonSerializer
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\Utility
 */
class JsonSerializer extends Serializer
{
    /**
     * Performs concrete serialization.
     *
     * @param mixed $data
     *
     * @return string
     *
     * @throws \Prophecy\Exception\Doubler\MethodNotFoundException
     */
    protected function doSerialize($data)
    {
        if (!method_exists($data, 'toArray')) {
            if ($data instanceof \stdClass) {
                $data->className = get_class($data);
            }

            return json_encode($data, true);
        }

        $preparedArray = $data->toArray();
        $preparedArray['className'] = get_class($data);

        return json_encode($preparedArray);
    }

    /**
     * Performs concrete unserialization.
     *
     * @param string $data
     *
     * @return mixed
     */
    protected function doUnserialize($data)
    {
        $unserialized = json_decode($data, true);

        if (!is_array($unserialized) || !array_key_exists('className', $unserialized)) {
            return $unserialized;
        }

        $class = $unserialized['className'];
        if (!\class_exists($class)) {
            throw new InvalidArgumentException('Invalid class name!');
        }

        unset($unserialized['className']);

        if (!method_exists($class, 'fromArray')) {
            return (object) $unserialized;
        }

        return $class::fromArray($unserialized);
    }
}
