<?php

/**
 * This is a class to "overwrite" default PHP function to make them
 * either work on all PHP versions or to extend them in some way
 */
class IceFunctions
{
  /**
   * @static
   *
   * @param  array   $values
   * @param  string  $callback
   *
   * @return array
   */
  public static function array_filter_recursive($values, $callback = null)
  {
    foreach ($values as $key => $value)
    {
      if (is_array($value))
      {
        $values[$key] = self::array_filter_recursive($value, $callback);
      }
    }

    return array_filter($values);
  }

  /**
   * Implementation of a recursive version of PHP's array_unique
   *
   * @static
   *
   * @param  array $array
   * @return array
   */
  public static function array_unique_recursive($array)
  {
    $result = array_map("unserialize", array_unique(array_map("serialize", $array)));

    foreach ($result as $key => $value)
    {
      if (is_array($value))
      {
        $result[$key] = self::array_unique_recursive($value);
      }
    }

    return $result;
  }

  /**
   * @static
   *
   * @param  array  $values
   * @return array
   */
  public static function array_power_set($values)
  {
    // Initialize by adding the empty set
    $results = array(array());

    foreach ($values as $value)
    {
      foreach ($results as $combination)
      {
        array_push($results, array_merge(array($value), $combination));
      }
    }

    return $results;
  }

  /**
   * @static
   *
   * @param  array|PropelObjectCollection  $items
   * @param  integer  $columns
   * @param  boolean  $keep_keys
   *
   * @return array
   */
  public static function array_vertical_sort($items, $columns, $keep_keys = false)
  {
    $sorted = array();
    $total = count($items);
    $keys = ($items instanceof PropelObjectCollection) ? range(0, $items->count() - 1) : array_keys((array) $items);

    $rowCount = ceil($total / $columns);
    for ($i = 0; $i < $rowCount * $columns; $i++)
    {
      $index = ($i % $columns) * $rowCount + floor($i / $columns);

      if ($keep_keys === true)
      {
        $key = isset($keys[$index]) ? $keys[$index] : max($keys) + 1;
        $sorted[$key] = ($index < $total) ? $items[$key] : null;
      }
      else
      {
        $sorted[] = ($index < $total) ? $items[$index] : null;
      }
    }

    return $sorted;
  }

  /**
   * @static
   * 
   * @param  array    $array  Array to sort
   * @param  string   $sortby  Sort by this key
   * @param  string   $order  Sort order asc/desc (ascending or descending).
   * @param  integer  $type  Type of sorting to perform
   *
   * @return array
   */
  public static function array_key_sort($array, $sortby, $order = 'asc', $type = SORT_NUMERIC)
  {
    if (!is_array($array))
    {
      return $array;
    }

    $out = array();

    foreach ($array as $key => $val) {
      $sa[$key] = $val[$sortby];
    }

    if ($order == 'asc') {
      asort($sa, $type);
    } else {
      arsort($sa, $type);
    }

    foreach ($sa as $key => $val) {
      $out[] = $array[$key];
    }

    return $out;
  }

  /**
   * @param  array    $array
   * @param  boolean  $keep_keys
   *
   * @return array
   */
  public static function array_flatten($array, $keep_keys = false)
  {
    if (!is_array($array))
    {
      return array();
    }

    $result = array();
    foreach ($array as $key => $value)
    {
      if (is_array($value))
      {
        $result = array_merge($result, self::array_flatten($value, $keep_keys));
      }
      else if ($keep_keys == true)
      {
        $result[$key] = $value;
      }
      else
      {
        $result[] = $value;
      }
    }

    return $result;
  }

  /**
   * @static
   *
   * @param  int|float  $number
   * @param  integer    $decimals
   * @param  string     $culture
   *
   * @return string
   */
  public static function number_format($number, $decimals = 0, $culture = 'bg_BG')
  {
    switch ($culture)
    {
      case 'en_US':
        $number = number_format($number, $decimals, '.', ',');
        break;
      case 'bg_BG':
      default:
        $number = number_format($number, $decimals, ',', ' ');
        break;
    }

    return $number;
  }

  /**
   * @static
   *
   * @param  string  $str1
   * @param  string  $str2
   *
   * @return int
   */
  public static function levenshtein($str1, $str2)
  {
    $str1 = mb_strtolower($str1, 'utf8');
    $str2 = mb_strtolower($str2, 'utf8');

    $len1 = mb_strlen($str1, 'utf8');
    $len2 = mb_strlen($str2, 'utf8');

    // strip common prefix
    $i = 0;
    do
    {
      if (mb_substr($str1, $i, 1, 'utf8') != mb_substr($str2, $i, 1, 'utf8'))
      {
        break;
      }

      $i++;

      $len1--;
      $len2--;
    }
    while($len1 > 0 && $len2 > 0);

    if ($i > 0)
    {
      $str1 = mb_substr($str1, $i, mb_strlen($str1, 'utf8'), 'utf8');
      $str2 = mb_substr($str2, $i, mb_strlen($str2, 'utf8'), 'utf8');
    }

    // strip common suffix
    $i = 0;
    do
    {
      if (mb_substr($str1, $len1-1, 1, 'utf8') != mb_substr($str2, $len2-1, 1, 'utf8'))
      {
        break;
      }
      $i++;
      $len1--;
      $len2--;
    }
    while($len1 > 0 && $len2 > 0);

    if ($i > 0)
    {
      $str1 = mb_substr($str1, 0, $len1, 'utf8');
      $str2 = mb_substr($str2, 0, $len2, 'utf8');
    }

    if ($len1 == 0)
    {
      return $len2;
    }
    if ($len2 == 0)
    {
      return $len1;
    }

    $v0 = range(0, $len1);
    $v1 = array();

    for ($i = 1; $i <= $len2; $i++)
    {
      $v1[0] = $i;
      $str2j = mb_substr($str2, $i - 1, 1, 'utf8');

      for ($j = 1; $j <= $len1; $j++)
      {
        $cost = (mb_substr($str1, $j - 1, 1, 'utf8') == $str2j) ? 0 : 1;

        $m_min = $v0[$j] + 1;
        $b = $v1[$j - 1] + 1;
        $c = $v0[$j - 1] + $cost;

        if ($b < $m_min)
        {
          $m_min = $b;
        }
        if ($c < $m_min)
        {
          $m_min = $c;
        }

        $v1[$j] = $m_min;
      }

      $vTmp = $v0;
      $v0 = $v1;
      $v1 = $vTmp;
    }

    return (int) @$v0[$len1];
  }

  /**
   * @static
   *
   * @param  string  $data
   * @param  string  $passwd
   * @param  string  $algo (sha1 or md5)
   *
   * @return string
   */
  public static function hmac($data, $passwd, $algo = 'sha1')
  {
    $algo = strtolower($algo);
    $p = array('md5' => 'H32', 'sha1' => 'H40');

    if (strlen($passwd) > 64)
    {
      $passwd = pack($p[$algo], $algo($passwd));
    }
    else if (strlen($passwd) < 64)
    {
      $passwd = str_pad($passwd, 64, chr(0));
    }

    $ipad = substr($passwd, 0, 64) ^ str_repeat(chr(0x36), 64);
    $opad = substr($passwd, 0, 64) ^ str_repeat(chr(0x5C), 64);

    return $algo($opad . pack($p[$algo], $algo($ipad . $data)));
  }

  /**
   * @static
   *
   * @return string
   */
  public static function gethostname()
  {
    if (version_compare(PHP_VERSION, '5.3.0') >= 0)
    {
      $host = gethostname();
    }
    else
    {
      $host = php_uname('n');
    }

    return (string) $host;
  }

  /**
   * @static
   * @param  integer $number
   * @param  integer $length
   * 
   * @return string
   */
  public static function udihash($number, $length = 5)
  {
    $golden_primes = array(
      1,41,2377,147299,9132313,566201239,35104476161,2176477521929
    );

    $chars = array(
      0=>48,1=>49,2=>50,3=>51,4=>52,5=>53,6=>54,7=>55,8=>56,9=>57,10=>65,
      11=>66,12=>67,13=>68,14=>69,15=>70,16=>71,17=>72,18=>73,19=>74,20=>75,
      21=>76,22=>77,23=>78,24=>79,25=>80,26=>81,27=>82,28=>83,29=>84,30=>85,
      31=>86,32=>87,33=>88,34=>89,35=>90,36=>97,37=>98,38=>99,39=>100,40=>101,
      41=>102,42=>103,43=>104,44=>105,45=>106,46=>107,47=>108,48=>109,49=>110,
      50=>111,51=>112,52=>113,53=>114,54=>115,55=>116,56=>117,57=>118,58=>119,
      59=>120,60=>121,61=>122
    );

    // Make sure the $length is right
    if ($length > 8 || $length < 1)
    {
      $length = 5;
    }

    $ceil = pow(62, $length);
    $prime = $golden_primes[$length];
    $dec = ($number * $prime) - floor($number * $prime/$ceil) * $ceil;
    $hash = self::base62($dec, $chars);

    return str_pad($hash, $length, "0", STR_PAD_LEFT);
  }

  /**
   * @static
   * @param  integer $int
   * @param  array $chars
   * 
   * @return string
   */
  private static function base62($int, $chars = array())
  {
    $key = '';

    while($int > 0)
    {
      $mod  = $int - floor($int / 62) * 62;
      $key .= chr($chars[$mod]);
      $int  = floor($int / 62);
    }

    return strrev($key);
  }
}
