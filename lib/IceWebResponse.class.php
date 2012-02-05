<?php

class IceWebResponse extends sfWebResponse
{
  /**
   * This is not Symfony's context but rather the application from Icepique (Autoho, Bezplatno, etc)
   *
   * @var string
   */
  protected $_context = 'Icepique';

  private $_delayed_functions = array();

  /**
   * Sends the HTTP headers and the content.
   */
  public function send()
  {
    $this->sendHttpHeaders();
    $this->sendContent();

    /**
     * @see http://php-fpm.org/wiki/Features#fastcgi_finish_request.28.29
     */
    fastcgi_finish_request();

    if ($functions = $this->getDelayedFunctions())
    foreach ($functions as $function)
    {
      if (is_array($function['name']) && in_array($function['name'][1], array('setNumberOf', 'setNumViews')))
      {
        if (is_object($function['name'][0]) && method_exists($function['name'][0], 'getId'))
        {
          $memcache = IceStatic::getMemcacheCache();

          $key = $this->_context .'-'. get_class($function['name'][0]) .'-'. $function['name'][0]->getId() .'-'. $function['name'][1] .'-'. $function['params'][0];
          $operator = $param = $number = null;

          if (count($function['params']) == 2 && in_array(substr($function['params'][1], 0, 1), array('+', '-')))
          {
            $operator = substr($function['params'][1], 0, 1);

            $number = (int) substr($function['params'][1], 1);
            $param  = &$function['params'][1];
          }
          else if (count($function['params']) == 1 && in_array(substr($function['params'][0], 0, 1), array('+', '-')))
          {
            $operator = '+';

            $number = (int) substr($function['params'][0], 1);
            $param  = &$function['params'][0];
          }

          if ($number !== null && $param !== null)
          {
            $i = (int) $memcache->increment($key, $number);

            if ($i % 5 == 0)
            {
              $number = ($i === 0) ? 1 : $i;
              $memcache->decrement($key, $number);
            }
            else
            {
              continue;
            }

            $param = $operator . $number;
          }
        }
      }

      try
      {
        call_user_func_array($function['name'], $function['params']);

        if (($function['name'][0] instanceof BaseObject) && method_exists($function['name'][0], 'save'))
        {
          if ($function['name'][0]->isModified()) { $function['name'][0]->save(); }
        }
      }
      catch (Exception $e) { ; }
    }
  }

  public function addDelayedFunction($name, $params)
  {
    // In development we do not want to delay the execution
    if (sfConfig::get('sf_environment') == 'prod')
    {
      $this->_delayed_functions[] = array('name' => $name, 'params' => $params);
    }
    else
    {
      call_user_func_array($name, $params);

      if (($name[0] instanceof BaseObject) && $name[1] != 'save' && method_exists($name[0], 'save'))
      {
        if ($name[0]->isModified()) { $name[0]->save(); }
      }
    }
  }

  private function getDelayedFunctions()
  {
    return $this->_delayed_functions;
  }

  public function setGeoLocation($geo_location)
  {
    if ($geo_location instanceof iceModelGeoCity || $geo_location instanceof iceModelGeoRegion)
    {
      $this->addMeta('geo.region', 'BG');
      $this->addMeta('geo.placename', $geo_location->getName());
      $this->addMeta('geo.position', $geo_location->getLatitude().';'. $geo_location->getLongitude());
      $this->addMeta('ICBM', $geo_location->getLatitude().','. $geo_location->getLongitude());
    }
  }
}
