<?php
namespace Twire\Common;

/**
 * Array with fixed associative elements.
 *
 * Elements must be specified as an array of strings during instantiation.
 * All elements will initially be set to NULL. Elements being unset will revert back to NULL.
 * Accessing a non-existing element will throw an OutOfRangeException.
 * When setting an element: Uses a setter-method if able, otherwise sets the element directly.
 *
 * At "design-time", data classes can be created easily by extending FixedAssociativeArray.
 * <code>
 *     class UserData extends FixedAssociativeArray
 *     {
 *         public function __construct(array $data = [])
 *         {
 *             parent::__construct([
 *                 'ID',
 *                 'UserName',
 *                 'IsAdmin'
 * 		      ]);
 *
 *             $this->massUpdate($data);
 *         }
 *
 *         // When setting the UserData's IsAdmin property,
 *         // FixedAssociativeArray will detect and use this setter method.
 *         protected function setIsAdmin($value)
 *         {
 *             $this->data['IsAdmin'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
 *         }
 *     }
 * </code>
 *
 * At runtime, data objects can be created easily by using the provided static methods.
 * The freeze method copies the structure as well as data. The mirror method only copies the structure.
 * <code>
 *     $user = ['ID' => 1337, 'UserName' => 'H.Finch', 'IsAdmin' = true];
 *
 *     // $userData will have two fixed properties which are allready set with data after calling the freeze method:
 *     $user1337Data = FixedAssociativeArray::freeze($user);
 *
 *     // $userData will have two fixed properties which are initialized with NULL after calling the mirror method:
 *     $userData = FixedAssociativeArray::mirror($user);
 * </code>
 *
 * Implements ArrayAccess, Countable and Iterator allowing the FixedAssociativeArray to
 * be iterated and accessed like a native array.
 * <code>
 *     $fixedArray['elementOne'] = 'foo';
 * </code>
 *
 * Elements can also be accessed as regular object properties.
 * <code>
 *     $fixedArray->elementOne = 'bar';
 * </code>
 *
 * At any given moment an instance of FixedAssociativeArray may also:
 *
 * * export it's protected array using the toArray() method
 * * export it's protected array as an stdClass object using the toStdClass() method
 * * be type casted to string in order to display a comma-separated list of it's values
 * * mass update matching elements according to a specified source array.
 *
 * @author    Francois Raeven <francois@twire.nl>
 * @link      http://twire.nl/opensource
 * @license   http://opensource.org/licenses/MIT MIT License
 * @copyright (c) 2015, Twire Solutions
 *
 * @uses      \ArrayAccess
 * @uses      \Countable
 * @uses      \Iterator
 */
class FixedAssociativeArray implements \ArrayAccess, \Countable, \Iterator
{
    /**
     * The encapsulated array which is accessed and manipulated using magic methods.
     *
     * @var mixed[]
     */
    protected $data = [];


    /**
     * Creates a FixedAssociativeArray class with data using an existing array as input.
     *
     * @author Francois Raeven <francois@twire.nl>
     *
     * @param array $source Which will be entirely converted to a FixedAssociativeArray
     *
     * @return FixedAssociativeArray
     */
    public static function freeze(array $source)
    {
        $obj = new self(array_keys($source));
        $obj->massUpdate($source);

        return $obj;
    }


    /**
     * Creates a FixedAssociativeArray class based on an existing array.
     *
     * @author Francois Raeven <francois@twire.nl>
     *
     * @param array $source Whose structure (keys) will be used to create a FixedAssociativeArray
     *
     * @return FixedAssociativeArray
     */
    public static function mirror(array $source)
    {
        return new self(array_keys($source));
    }


    /**
     * Constructor magic method
     *
     * @author Francois Raeven <francois@twire.nl>
     *
     * @param  string[] $keys specifying which fixed array elements are needed.
     *
     * @throws \InvalidArgumentException Thrown if the array contains 0 valid elements.
     */
    public function __construct(array $keys)
    {
        if (0 < count($keys)) {
            foreach ($keys as $key) {
                if (false === empty($key) && is_string($key)) {
                    $this->data[$key] = null;
                }
            }
        }

        if (0 === count($this->data)) {
            throw new \InvalidArgumentException('Array passed to constructor contains 0 valid elements.');
        }
    }


    /**
     * Sets an element of the FixedAssociativeArray.
     *
     * Uses a setter-method if able, otherwise sets the element directly.
     * Accessing a non-existing element will throw an OutOfRangeException.
     *
     * @author Francois Raeven <francois@twire.nl>
     *
     * @param  string $key   The associative key being set.
     * @param  mixed  $value The new value for the specified key.
     *
     * @return void
     * @throws \OutOfRangeException Thrown if the specified key does not exist.
     */
    public function offsetSet($key, $value)
    {
        if (array_key_exists($key, $this->data)) {
            if (is_callable([$this, 'set' . $key])) {
                $this->{'set' . $key}($value);
            } else {
                $this->data[$key] = $value;
            }
        } else {
            throw new \OutOfRangeException(sprintf('%s not found within %s.', $key, __CLASS__));
        }
    }


    /**
     * Checks if FixedAssociativeArray element exists.
     *
     * @author Francois Raeven <francois@twire.nl>
     *
     * @param  string $key The FixedAssociativeArray element being checked.
     *
     * @return bool   TRUE if the requested element exists, otherwise FALSE.
     */
    public function offsetExists($key)
    {
        return isset($this->data[$key]);
    }


    /**
     * Sets a FixedAssociativeArray element to NULL.
     *
     * @author Francois Raeven <francois@twire.nl>
     *
     * @param  string $key The FixedAssociativeArray element being unset to NULL (key remains intact).
     *
     * @return void
     * @throws \OutOfRangeException Thrown if the specified key does not exist.
     */
    public function offsetUnset($key)
    {
        if (array_key_exists($key, $this->data)) {
            $this->data[$key] = null;
        } else {
            throw new \OutOfRangeException(sprintf('%s not found within %s.', $key, __CLASS__));
        }
    }


    /**
     * Retrieves specified FixedAssociativeArray element.
     *
     * Attempts to retrieve the specified FixedAssociativeArray element.
     * Returns NULL if the specified FixedAssociativeArray element is not found.
     *
     * @author Francois Raeven <francois@twire.nl>
     *
     * @param  string $key The FixedAssociativeArray element to retrieve the value from.
     *
     * @return mixed  The value of the specified FixedAssociativeArray element.
     * @throws \OutOfRangeException Thrown if the specified key does not exist.
     */
    public function offsetGet($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        } else {
            throw new \OutOfRangeException(sprintf('%s not found within %s.', $key, __CLASS__));
        }
    }


    /**
     * Returns the number of FixedAssociativeArray elements.
     *
     * @author Francois Raeven <francois@twire.nl>
     * @return int The number of fixed elements available in the FixedAssociativeArray.
     */
    public function count()
    {
        return count($this->data);
    }


    /**
     * Returns the number of FixedAssociativeArray elements.
     *
     * @author Francois Raeven <francois@twire.nl>
     * @return int The number of fixed elements available in the FixedAssociativeArray.
     */
    public function getSize()
    {
        return count($this->data);
    }


    /**
     * Resets the encapsulated array's pointer.
     *
     * @author Francois Raeven <francois@twire.nl>
     * @return void
     */
    public function rewind()
    {
        reset($this->data);
    }


    /**
     * Returns the current element referenced by the encapsulated array's pointer.
     *
     * @author Francois Raeven <francois@twire.nl>
     * @return mixed The value of the array element that's currently being pointed to by the internal pointer.
     */
    public function current()
    {
        return current($this->data);
    }


    /**
     * Returns the current element's key referenced by the encapsulated array's pointer.
     *
     * @author Francois Raeven <francois@twire.nl>
     * @return mixed The key of the array element that's currently being pointed to by the internal pointer.
     */
    public function key()
    {
        return key($this->data);
    }


    /**
     * Sets the encapsulated array's pointer to the next element.
     *
     * @author Francois Raeven <francois@twire.nl>
     * @return void
     */
    public function next()
    {
        next($this->data);
    }


    /**
     * Sets the encapsulated array's pointer to the previous element.
     *
     * @author Francois Raeven <francois@twire.nl>
     * @return void
     */
    public function prev()
    {
        prev($this->data);
    }


    /**
     * Checks if the current element, referenced by the encapsulated array's pointer, is valid.
     *
     * @author Francois Raeven <francois@twire.nl>
     * @return bool TRUE on success or FALSE on failure.
     */
    public function valid()
    {
        return array_key_exists(key($this->data), $this->data);
    }


    /**
     * Sets an element of the FixedAssociativeArray.
     *
     * Allows to set a value on an element as if accessing as a property.
     * Uses offsetSet() to perform the desired 'set'.
     *
     * @author Francois Raeven <francois@twire.nl>
     * @uses   FixedAssociativeArray::offsetSet()
     *
     * @param  string $key   The associative key being set.
     * @param  mixed  $value The new value for the specified key.
     *
     * @return void
     * @throws \OutOfRangeException Thrown if the specified key does not exist.
     */
    public function __set($key, $value)
    {
        $this->offsetSet($key, $value);
    }


    /**
     * Retrieves specified FixedAssociativeArray element.
     *
     * Allows to get a value of an element as if accessing as a property.
     * Uses offsetGet() to perform the desired 'get'.
     *
     * @author Francois Raeven <francois@twire.nl>
     * @uses   FixedAssociativeArray::offsetGet()
     *
     * @param  string $key The FixedAssociativeArray element to retrieve the value from.
     *
     * @return mixed  The value of the specified FixedAssociativeArray element.
     * @throws \OutOfRangeException Thrown if the specified key does not exist.
     */
    public function __get($key)
    {
        return $this->offsetGet($key);
    }


    /**
     * Checks if FixedAssociativeArray element exists.
     *
     * @author Francois Raeven <francois@twire.nl>
     * @uses   FixedAssociativeArray::offsetExists()
     *
     * @param  string $key The FixedAssociativeArray element being checked.
     *
     * @return bool   TRUE if the requested element exists, otherwise FALSE.
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }


    /**
     * Sets a FixedAssociativeArray element to NULL.
     *
     * @author Francois Raeven <francois@twire.nl>
     * @uses   FixedAssociativeArray::offsetUnset()
     *
     * @param  string $key The FixedAssociativeArray element being unset to NULL (key remains intact).
     *
     * @return void
     * @throws \OutOfRangeException Thrown if the specified key does not exist.
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }


    /**
     * Mass updates matching elements within the FixedAssociativeArray
     *
     * Updates matching elements within the FixedAssociativeArray according to the specified source array.
     *
     * **CAUTION:** Only matched elements will be used, other elements will be ignored!
     *
     * @author Francois Raeven <francois@twire.nl>
     *
     * @param  array $array Source array used to mass update matching elements within the FixedAssociativeArray.
     *
     * @return void
     */
    public function massUpdate(array $array)
    {
        if (0 < count($array)) {
            foreach ($array as $key => $value) {
                if (array_key_exists($key, $this->data)) {
                    @$this->offsetSet($key, $value);
                }
            }
        }
    }


    /**
     * Returns a native PHP array from the FixedAssociativeArray.
     *
     * @author Francois Raeven <francois@twire.nl>
     * @return mixed[] A regular PHP array.
     */
    public function toArray()
    {
        return $this->data;
    }


    /**
     * Returns a stdClass PHP object from the FixedAssociativeArray.
     *
     * @author Francois Raeven <francois@twire.nl>
     * @return \stdClass A stdClass PHP object.
     */
    public function toStdClass()
    {
        return (object)$this->data;
    }


    /**
     * Returns a comma-separated string containing all values from the FixedAssociativeArray.
     *
     * @author Francois Raeven <francois@twire.nl>
     * @return string A comma-separated string containing all FixedAssociativeArray values.
     */
    public function __toString()
    {
        return implode(', ', $this->data);
    }
}
