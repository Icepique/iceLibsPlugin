<?php

class IceTimer extends sfTimer
{
  /**
   * @var IceTimer|null
   */
  private static $_timer = null;

  /**
   * @static
   * @return IceTimer
   */
  public static function getInstance()
  {
    if (self::$_timer === null)
    {
      self::$_timer = new self();
    }

    return self::$_timer;
  }
}
