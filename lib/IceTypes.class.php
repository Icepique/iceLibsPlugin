<?php

abstract class IceTypes extends ArrayObject
{
  public function __construct($values = array())
  {
    if (!is_array($values))
    {
      $values = array('culture' => $values);
    }
    else if (is_array($values))
    {
      ksort($values, SORT_STRING);

      $tree = array();
      foreach ($values as $key => $value)
      {
        $parts = explode('__', $key);
        $k = $parts[0]; unset($parts[0]);
        $kk = implode('__', $parts);

        if ($kk != '')
        {
          $tree[$k][$kk] = $value;
        }
        else
        {
          $tree[$k] = $value;
        }

        unset($values[$key]);
      }

      $ice_type_objects = array();
      foreach ($tree as $key => $value)
      {
        if (is_array($value) && array_key_exists($key, $this->_structure))
        {
          if ('IceType' == substr($this->_structure[$key], 0, 9))
          {
            $values[$key] = new $this->_structure[$key]($value);
          }
          else if ('string' == $this->_structure[$key] || 'mixed' == $this->_structure[$key])
          {
            $values[$key] = implode('', $value);
          }
          else if ('bool' == $this->_structure[$key] && !is_bool($value))
          {
            $values[$key] = ('1' == $value) ? true : false;
          }
          else
          {
            $values[$key] = $value;
          }
        }
        else if ('enum' == substr($this->_structure[$key], 0, 4))
        {
          $choices = explode(', ', substr($this->_structure[$key], 5, -1));
          if (!in_array($value, $choices))
          {
            // Use the first ENUM value as the default
            $values[$key] = $choices[0];
          }
          else
          {
            $values[$key] = $value;
          }
        }
        else if (array_key_exists($key, $this->_structure) && $this->_structure[$key] == 'float')
        {
          $values[$key] = IceStatic::floatval($value);
        }
        else
        {
          $values[$key] = $value;
        }
      }
    }

    parent::__construct($values);
  }

  public function __toString()
  {
    return 'Array';
  }

  public function getStructure()
  {
    return $this->_structure;
  }

  public function offsetSet($key, $value)
  {
    // We can have the case where we pass 'some__key' as the $key so we can handle this for arrays and objects
    if (false !== $pos = strpos($key, '__'))
    {
      list($prefix, $suffix) = explode('__', $key);
      if (in_array($prefix, array_keys($this->_structure), true))
      {
        if ('array' == $this->_structure[$prefix])
        {
          if (!isset($this[$prefix]))
          {
            $this[$prefix] = array();
          }
          $this[$prefix] = array_merge($this[$prefix], array($suffix => $value));

          return;
        }
        else if ('IceType' == substr($this->_structure[$prefix], 0, 9))
        {
          if (!isset($this[$prefix]))
          {
            $this[$prefix] = new $this->_structure[$prefix]();
          }
          $this[$prefix][$suffix] = $value;

          return;
        }
      }
    }

    if (!in_array($key, array_keys($this->_structure), true))
    {
      return trigger_error(
        sprintf('%s does not recognize the key %s', get_class($this), $key),
        E_USER_WARNING
      );
    }
    else if (!is_null($value))
    {
      $func = 'is_'. $this->_structure[$key];
      if (function_exists($func) && !call_user_func($func, $value))
      {
        // Special case for booleans because there is no boolval() function
        if ('bool' == $this->_structure[$key])
        {
          $value = ('1' == $value) ? true : false;
        }
        else
        {
          $func = $this->_structure[$key].'val';
          $value = (function_exists($func)) ? call_user_func($func, $value) : $value;
        }
      }
      else if (class_exists($this->_structure[$key], false) && !($value instanceof $this->_structure[$key]))
      {
        return trigger_error(
          sprintf('%s: the value for field %s must be an instance of %s', get_class($this), $key, $this->_structure[$key]),
          E_USER_WARNING
        );
      }
      else if ('enum' == substr($this->_structure[$key], 0, 4))
      {
        $choices = explode(', ', substr($this->_structure[$key], 5, -1));
        if (!in_array($value, $choices))
        {
          // Use the first ENUM value as the default
          $value = $choices[0];
        }
      }
      else if ('url' == $this->_structure[$key] && !IceWebBrowser::isUrl($value))
      {
        return trigger_error(
          sprintf('%s: the value for field %s must be must be a valid URL (with http://)', get_class($this), $key),
          E_USER_WARNING
        );
      }
      else if ('float' == $this->_structure[$key])
      {
        $value = IceStatic::floatval($value);
      }
    }

    parent::offsetSet($key, $value);
  }

  public function offsetGet($key)
  {
    if (in_array($key, array_keys($this->_structure), true) && !isset($this[$key]))
    {
      return null;
    }

    return (parent::offsetExists($key)) ? parent::offsetGet($key) : null;
  }

  public function equalTo($object)
  {
    $equal = true;

    if ($this != $object || !($object instanceof IceTypes))
    {
      $equal = false;
    }
    else
    {
      $keys = array_keys($this->_structure);
      foreach ($keys as $key)
      {
        if (is_object($this[$key]) && $this[$key] instanceof IceTypes)
        {
          $equal = $this[$key]->equalTo($object[$key]);
        }
        else if ($this[$key] !== $object[$key])
        {
          $equal = false;
        }

        if ($equal == false)
        {
          break;
        }
      }
    }

    return $equal;
  }

  public function merge(IceTypes $object)
  {
    foreach ($this->_structure as $key => $type)
    {
      if (!isset($this[$key]) && isset($object[$key]))
      {
        $this[$key] = $object[$key];
      }
      else if ($this[$key] instanceof IceTypes && $object[$key] instanceof IceTypes)
      {
        $this[$key]->merge($object[$key]);
      }
    }

    return true;
  }

  public function toArray($prefix = null)
  {
    $array = array();

    foreach ($this->_structure as $key => $type)
    {
      if (isset($this[$key]))
      {
        if ($this[$key] instanceof IceTypes)
        {
          $array = array_merge($array, $this[$key]->toArray(!empty($prefix) ? $prefix .'__'. $key : $key));
        }
        else if (is_array($this[$key]))
        {
          foreach ($this[$key] as $k => $v)
          {
            $array[(!empty($prefix) ? $prefix .'__'. $key : $key) .'__'. $k] = $v;
          }
        }
        else
        {
          $value = ($this[$key] instanceof DateTime) ? $this[$key]->format(DateTime::ISO8601) : $this[$key];
          $array[!empty($prefix) ? $prefix .'__'. $key : $key] = $value;
        }
      }
    }

    return $array;
  }

  public function __destruct()
  {
    foreach ($this as $index => $value)
    {
      unset($this->$index);
    }
  }
}

class IceTypeMultimedia extends IceTypes
{
  protected $_structure = array(
    'images' => 'array',
    'pdf'    => 'array'
  );
}

class IceTypePrice extends IceTypes
{
  protected $_structure = array(
    'amount'       => 'float',
    'decimal'      => 'integer',
    'currency'     => 'enum(BGN, USD, EUR, GPB)',
    'vat_included' => 'bool',
    'updated_at'   => 'DateTime',
    'culture'      => 'enum(bg_BG, en_US, tr_TR, ru_RU)'
  );

  private $i18n_currencies = array(
    'bg' => array('BGN' => 'лв',  'USD' => '$', 'EUR' => '€'),
    'en' => array('BGN' => 'BGN', 'USD' => '$', 'EUR' => '€'),
    'tr' => array('BGN' => 'BGN', 'USD' => '$', 'EUR' => '€'),
    'ru' => array('BGN' => 'BGN', 'USD' => '$', 'EUR' => '€')
  );

  public function __construct($values = array())
  {
    if (!is_array($values))
    {
      $values = array('amount' => $values);
    }

    parent::__construct($values);

    // This is to make sure ::setOffset() is called
    $this['amount'] = $this['amount'];
  }

  public function __toString()
  {
    if (!isset($this['amount']))
    {
      return 'n/a';
    }

    switch ($this['culture'])
    {
      case 'en':
      case 'en_US':
        $amount = number_format($this['amount'], 2, '.', ',');
        $currency = $this->i18n_currencies['en'][$this['currency']];
        break;
      case 'bg':
      case 'bg_BG':
      default:
        $amount = number_format($this['amount'], 2, ',', '');
        $currency = $this->i18n_currencies['bg'][$this['currency']];
        break;
    }

    if ('BGN' == $this['currency'])
    {
      return sprintf('%s %s', $amount, $currency);
    }
    else
    {
      return sprintf('%s%s', $currency, $amount);
    }
  }

  public function offsetSet($key, $value)
  {
    // We need to call the parent first as it will validate the value
    parent::offsetSet($key, $value);

    if ('amount' == $key)
    {
      $value = IceStatic::floatval($value);
      $this['decimal'] = round(($value - floor($value)) * 100);
    }
  }
}

class IceTypeUrl extends IceTypes
{
  protected $_structure = array(
    'location'      => 'url',
    'parameters'    => 'array',
    'cookies'       => 'array',
    'method'        => 'enum(get, post)',

    'parts'         => 'array'
  );

  public function __construct($values = array())
  {
    if (!is_array($values))
    {
      $values = array('location' => (string) $values);
    }

    parent::__construct($values);

    // This is to make sure ::setOffset is called
    $this['location'] = $this['location'];
  }

  public function __toString()
  {
    return (isset($this['location'])) ? $this['location'] : parent::__toString();
  }

  public function offsetSet($key, $value)
  {
    // We need to call the parent first as it will validate the value
    parent::offsetSet($key, $value);

    if ('location' == $key)
    {
      $this['parts'] = parse_url($value);
    }
  }

  public function offsetGet($key)
  {
    if ('method' == $key && empty($this['method']))
    {
      return 'get';
    }

    return parent::offsetGet($key);
  }
}
