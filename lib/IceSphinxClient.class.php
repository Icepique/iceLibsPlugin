<?php

if (!class_exists('SphinxClient'))
{
  include_once dirname(__FILE__) .'/vendor/SphinxClient.class.php';
}

class IceSphinxClient extends SphinxClient
{
  /**
   * @param  string  $q
   * @param  string  $index
   * @param  int     $retries
   *
   * @return mixed
   */
  public final function query($q, $index = '*', $retries = 3)
  {
    $_time_start = microtime(true);

    // Make sure we do not get into a loop here from a mistake in the parameter
    if (5 < $retries = (int) $retries)
    {
      $retries = 3;
    }

    $i = 1;

    // Clean the query string from any "&" characters
    $q = trim($q, ' &');

    do
    {
      // http://www.sphinxsearch.com/docs/current.html#api-func-query
      $results = parent::Query(!empty($q) ? $q : null, $index, ($i > 1) ? 'Try #'. $i : null);

      $error   = $this->GetLastError();
      $warning = $this->GetLastWarning();

      if (!empty($error))
      {
        IceStats::increment(IceFunctions::gethostname().'.services.sphinx.error');

        trigger_error($error, E_USER_WARNING);
      }
      else if (!empty($warning))
      {
        // Record the query error
        IceStats::increment(IceFunctions::gethostname().'.services.sphinx.warning');

        trigger_error($warning, E_USER_WARNING);
      }

      if ($error != '')
      {
        // Record that the connection attempt was retried
        IceStats::increment(IceFunctions::gethostname().'.services.sphinx.connection.retry');

        // Sleep for pow(2, $i-1) seconds so that we can retry the search with some delay
        sleep(pow(2, $i-1));
      }

      $i++;
    }
    while ($error != '' && $i <= $retries);

    // Record the response time of the query
    IceStats::timing(IceFunctions::gethostname().'.services.sphinx.response', microtime(true) - $_time_start);

    // Record the query
    IceStats::increment(IceFunctions::gethostname().'.services.sphinx.query');

    return $results;
  }

  /**
   * @param  string  $index
   * @return array
   */
  public function getFields($index)
  {
    $config = sfConfig::get('sf_config_dir') .'/sphinx/sphinx.'. $index .'.conf';
    $fields = array('uint' => array(), 'bool' => array(), 'timestamp' => array());

    /**
     * Read the contents of the config file for the specified sphinx index
     */
    if ($contents = @file_get_contents($config))
    {
      /**
       * Unsigned Integers
       */
      preg_match_all('/sql_attr_uint\s+=\s+([_\w]+)/i', $contents, $matches);

      if (!empty($matches[1]))
      foreach ($matches[1] as $m)
      {
        $fields['uint'][] = $m;
      }

      /**
       * Booleans
       */
      preg_match_all('/sql_attr_bool\s+=\s+([_\w]+)/i', $contents, $matches);

      if (!empty($matches[1]))
      foreach ($matches[1] as $m)
      {
        $fields['bool'][] = $m;
      }

      /**
       * Timestamps
       */
      preg_match_all('/sql_attr_timestamp\s+=\s+([_\w]+)/i', $contents, $matches);

      if (!empty($matches[1]))
      foreach ($matches[1] as $m)
      {
        $fields['timestamp'][] = $m;
      }
    }

    return $fields;
  }

  public function _Connect()
  {
    $connection = parent::_Connect();

    if ($connection === false)
    {
      // Record that the connection succeeded
      IceStats::increment(IceFunctions::gethostname().'.services.sphinx.connection.error');
    }
    else
    {
      // Record that the connection succeeded
      IceStats::increment(IceFunctions::gethostname().'.services.sphinx.connection.success');
    }

    return $connection;
  }
}
