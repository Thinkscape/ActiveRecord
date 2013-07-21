<?php
namespace Thinkscape\ActiveRecord;

use Thinkscape\ActiveRecord\Exception\ConfigException;

/**
 * Filters property values before storing them in internal storage.
 *
 * @staticvar array $_filters An array of filter to apply to properties
 */
trait PropertyFilter
{
    /**
     * Initializes PropertyFilter feature
     */
    public static function initARPropertyFilter()
    {
        static::$_AREM[get_called_class()]['Core.beforeSet'][] = function (&$instance, &$value, $propertyName) {
            // Call the filterProperty() method in the scope of instance
            $value = $instance->filterProperty($propertyName, $value);
        };
    }

    /**
     * Return a filtered property value.
     *
     * You can override this function to provide fine-grained control over filtering.
     *
     * @param  string                    $property
     * @param  mixed                     $value
     * @throws Exception\ConfigException
     * @return mixed
     */
    protected function filterProperty($property, $value)
    {
        // Return early if there are no filters for this property
        if (!isset(static::$_filters) || !isset(static::$_filters[$property])) {
            return $value;
        }

        // If there's only a single filter, run through it and return
        if (is_callable(static::$_filters[$property])) {
            return call_user_func(static::$_filters[$property], $value);
        }

        // If we don't have an array of filters, then we must have had an invalid callable
        if (!is_array(static::$_filters[$property])) {
            throw new ConfigException('Invalid filter definition for property ' . $property);
        }

        // Run the value through all filters
        foreach (static::$_filters[$property] as $key => $filter) {
            if (!is_callable($filter)) {
                $what = is_object($filter) ? get_class($filter) :
                        (is_scalar($filter) ? (string) $filter : gettype($filter));

                throw new ConfigException(sprintf(
                    'Invalid filter #%s for property %s - expecting callable but got %s',
                    $key,
                    $property,
                    $what
                ));
            }

            $value = call_user_func($filter, $value);
        }

        // Return filtered value
        return $value;
    }
}
