<?php

class IceRequestHistory
{
  public static function getHistory()
  {
    return sfContext::getInstance()->getUser()->getAttribute('request_history', '', 'IceRequestHistory');
  }

  public static function getCurrentRequestKey()
  {
    return sfContext::getInstance()->getUser()->getAttribute('current_request_key', '', 'IceRequestHistory');
  }

  public static function getHistorySize()
  {
    return count(self::getHistory());
  }
  
  public static function getRequestKeyUri($key)
  {
    $history = self::getHistory();
    
    if( array_key_exists($key, $history) )
    {
      return $history[$key];
    } else {
      return false;
    }
  }

  public static function getCurrentUri()
  {
    return self::getRequestKeyUri(self::getCurrentRequestKey());
  }

  public static function getRequestUriFromCurrent($num)
  {
    $history = self::getHistory();
    $currentRequestKey = self::getCurrentRequestKey();

    $newKey = $currentRequestKey + $num;

    return self::getRequestKeyUri($newKey);
  }

  public static function goUrl($num)
  {
    return self::getRequestUriFromCurrent($num);
  }
  
  public static function canGo($num)
  {
    return self::getRequestUriFromCurrent($num) ? true:false;
  }

  public static function go($num)
  {
    $uri = self::getRequestUriFromCurrent($num);

    if( ! $uri )
    {
      $history = self::getHistory();
      $uri = array_pop($history);
    }

    sfContext::getInstance()->getController()->redirect($uri);
    exit;
  }

  public static function back($num = 1)
  {
    $num = $num * -1;

    self::go($num);
  }

  public static function forward($num = 1)
  {
    self::go($num);
  }

  public static function debug()
  {
    echo '<pre>';
    echo 'Current Request Key: '.self::getCurrentRequestKey()."\n";
    echo 'Current Request URI: '.self::getCurrentUri()."\n";
    echo 'Back Request Key: '.self::goUrl(-1)."\n\n";
    print_r(self::getHistory());
    echo '</pre>';
  }
}
