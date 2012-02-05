<?php

/**
 * Sends statistics to the stats daemon over UDP
 */
class IceStats
{
  /**
   * @var string The hostname of the StatsD server
   */
  const STATSD_HOST = 'statsd';

  /**
   * @var integer The port of the StatsD server
   */
  const STATSD_PORT = 8125;

  /**
   * Log timing information
   *
   * @param  string  $stat  The metric to in log timing info for
   * @param  string  $time  The elapsed time (ms) to log
   * @param  mixed   $rate  The rate (0-1) for sampling.
   */
  public static function timing($stat, $time, $rate = 1)
  {
    // Make sure the $time has no comma instead of decimal point
    $time = str_replace(',', '.', (string) $time);

    self::send(array((string) $stat => $time ."|ms"), $rate);
  }

  /**
   * Increments one or more stats counters
   *
   * @param  string|array  $stats  The metric(s) to increment.
   * @param  integer       $rate  The rate (0-1) for sampling
   *
   * @return boolean
   */
  public static function increment($stats, $rate = 1)
  {
    self::updateStats($stats, 1, $rate);
  }

  /**
   * Decrements one or more stats counters.
   *
   * @param  string|array   $stats  The metric(s) to decrement.
   * @param  float|integer  $rate  The rate (0-1) for sampling.
   */
  public static function decrement($stats, $rate = 1)
  {
    self::updateStats($stats, -1, $rate);
  }

  /**
   * Updates one or more stats counters by arbitrary amounts.
   *
   * @param  string|array  $stats       The metric(s) to update. Should be either a string or array of metrics.
   * @param  integer       $delta       The amount to increment/decrement each metric by.
   * @param  integer       $rate  The rate (0-1) for sampling.
   */
  public static function updateStats($stats, $delta=1, $rate=1)
  {
    if (!is_array($stats))
    {
      $stats = array($stats);
    }

    $data = array();
    foreach ($stats as $stat)
    {
      $data[$stat] = "$delta|c";
    }

    self::send($data, $rate);
  }

  /**
   * Squirt the metrics over UDP
   *
   * @static
   *
   * @param  array    $data
   * @param  integer  $rate
   *
   * @return boolean
   */
  public static function send($data, $rate = 1)
  {
    // Sampled data
    $sampled = array();

    if ($rate < 1)
    {
      foreach ($data as $stat => $value)
      if ((mt_rand() / mt_getrandmax()) <= $rate)
      {
        $sampled[$stat] = "$value|@$rate";
      }
    }
    else
    {
      $sampled = $data;
    }

    if (empty($sampled))
    {
      return false;
    }

    /**
     * Wrap this in a try/catch - failures in any of this should be silently ignored
     */
    try
    {
      // Send and forget
      if ($fp = fsockopen("udp://". self::STATSD_HOST, self::STATSD_PORT, $errno, $errstr))
      {
        foreach ($sampled as $stat => $value)
        {
          fwrite($fp, "$stat:$value");
        }

        fclose($fp);

        return true;
      }
    }
    catch (Exception $e) { ; }

    return false;
  }
}
