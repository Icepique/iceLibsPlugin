<?php

class IceBaseObject extends BaseObject
{
  /**
   * These are all the supported cultures
   *
   * @var array
   */
  public static $cultures = array('bg_BG', 'en_US');

  /**
   * These are all the supported languages (must match the ones above)
   *
   * @var array
   */
  public static $languages = array('bg_BG' => 'bulgarian', 'en_US' => 'english');

  /**
   * Returns a valid regex to be used in the routing
   * for limiting the cultures option to the ones supported
   *
   * @return string
   */
  public static function getRoutingCulturesRegex()
  {
    return '(?:'. implode('|', self::$cultures) .')';
  }

  /**
   * Returns a valid regex to be used in the routing for defining the slug format
   *
   * @return string
   */
  public static function getRoutingSlugRegex()
  {
    return '^[А-Яа-я\.\%\-\w\d]+$';
  }

  public function __destruct()
  {
    foreach ($this as $index => $value)
    {
      if ($index) unset($this->$index);
    }
  }

  static public function destroy()
  {
    $args = func_get_args();

    foreach ($args as $arg)
    {
      if (is_array($arg))
      foreach ($arg as $k => $v)
      {
        if (is_object($arg[$k]) && method_exists($arg[$k], '__destruct'))
        {
          $arg[$k]->__destruct();
        }
        unset($arg[$k]);
      }

      if (is_object($arg) && method_exists($arg, '__destruct'))
      {
        $arg->__destruct();
      }
      unset($arg);
    }
  }
}