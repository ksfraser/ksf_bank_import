<?php
/**
 * Generic FrontAccounting Interface Model Base Class
 * 
 * Provides base functionality for FrontAccounting model classes.
 * This is a legacy support class for model objects that need to inherit
 * common display/persistence functionality.
 */

class generic_fa_interface_model
{
    /**
     * Generic get method for accessing properties
     * 
     * @param string $name Property name
     * @return mixed Property value or null if not exists
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        return null;
    }

    /**
     * Generic set method for setting properties
     * 
     * @param string $name Property name
     * @param mixed $value Property value
     */
    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    /**
     * Generic isset check
     * 
     * @param string $name Property name
     * @return bool True if property is set and not null
     */
    public function __isset($name)
    {
        return isset($this->$name);
    }

    /**
     * Generic unset
     * 
     * @param string $name Property name
     */
    public function __unset($name)
    {
        unset($this->$name);
    }

    /**
     * Convert object to array representation
     * 
     * @return array Array representation of object
     */
    public function toArray()
    {
        return get_object_vars($this);
    }
}
