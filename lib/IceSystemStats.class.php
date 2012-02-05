<?php

class IceSystemStats
{
  private static
    $load   = array(),
    $cpu    = array(),
    $memory = array();

  public static function getCpuLoad()
  {
    return self::checkCpuLoad() ? self::$cpu : array();
  }

  public static function getLoadAvg()
  {
    return self::checkLoadAvg() ? self::$load : array();
  }

  public static function getMemory()
  {
    return self::checkMemory() ? self::$memory : array();
  }
  
  private static function checkCpuLoad()
  {
    if ($stats = file("/proc/stat"))
    {
      foreach($stats as $line)
      {
        $info = explode(" ", $line);
        
        if ($info[0] == "cpu")
        {
          array_shift($info);
          if (!$info[0])
          {
            array_shift($info);
          }

          self::$cpu = array(
            'user'   => $info[0],
            'nice'   => $info[1],
            'system' => $info[2],
            'idle'   => $info[3]
          );

          return true;
        }
      }
    }

    return false;
  }

  private static function checkLoadAvg()
  {
    if ($loadavg = file("/proc/loadavg"))
    {
      $averages = explode(' ', $loadavg[0]);
      
      self::$load = array(
        '1-minute'  => $averages[0],
        '5-minute'  => $averages[1],
        '15-minute' => $averages[2]
      );

      return true;
    }

    return false;
  }

  private static function checkMemory()
  {
    self::$memory = array(
      'current' => memory_get_usage(true),
      'peak' => memory_get_peak_usage(true)
    );

    return true;
  }
}
