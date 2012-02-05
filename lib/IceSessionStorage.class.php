<?php

/**
 * IceSessionStorage based on sfSessionStorage session handler
 *
 */
class IceSessionStorage extends sfSessionStorage
{
  /**
   * Class constructor.
   *
   * @param  array $options
   * @return \IceSessionStorage
   */
  public function __construct($options = array())
  {
    /**
     * Sessions should not be used in "cli" mode
     */
    if (php_sapi_name() == 'cli')
    {
      return;
    }
    /**
     * We do not want to create bogus session data for crowlers
     */
    else if (false !== IceStatic::isCrawler())
    {
      return;
    }
    else
    {
      if (($b = get_browser(null, false)) && ($b->cookies === '0'))
      {
        return;
      }

      $unique = IceStatic::getUserUniqueString();
      $hits_key = 'ice_hits_'. md5($unique);
      $requests_key = 'ice_requests_'. md5($unique);

      $m = IceStatic::getMemcacheClient();
      $values = $m->get(array($requests_key, $hits_key));

      $hits = isset($values[$hits_key]) ? (int) $values[$hits_key] : 0;
      $requests = isset($values[$requests_key]) ? (int) $values[$requests_key] : 0;

      /**
       * Do not start the session if we do not have more than 10 hits
       * even after 100 normal requests
       */
      if ($requests > 100 && $hits < 10)
      {
        return;
      }
    }

    $start = microtime(true);

    if (isset($options['session_cookie_domain']))
    {
      $parts = explode('.', $options['session_cookie_domain']);
      $context = $parts[1];
    }
    else
    {
      $context = 'global';
    }

    try
    {
      parent::__construct($options);
    }
    catch (Exception $e)
    {
      // Stop the session so that we can fallback to files
      try
      {
        session_destroy();
      }
      catch (Exception $e) { ; }

      ini_set('session.save_handler', 'files');
      ini_set('session.save_path', '/www/tmp');

      parent::__construct($options);
    }

    // Write the unique user string to the session
    if (!$this->read('unique'))
    {
      $this->write('unique', IceStatic::getUserUniqueString());
    }

    IceStats::timing(IceFunctions::gethostname() .'.'. $context .'.sessions', microtime(true) - $start);
  }

  /**
   * Initialize the Session
   *
   * @param array $options associative array of options
   */
  public function initialize($options = null)
  {
    $session_id = isset($_POST['_session_id']) ? $_POST['_session_id'] : @$_GET['_session_id'];

    if (!empty($session_id))
    {
      session_id($session_id);
    }

    parent::initialize($options);
  }
}
