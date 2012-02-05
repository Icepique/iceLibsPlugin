<?php

class IcePatternRouting extends sfPatternRouting
{
  /** @var sfI18N */
  static private $i18n = null;

  /**
   * @see sfPatternRouting
   *
   * @throws sfStopException
   * @param  string $url
   *
   * @return array|false
   */
  public function findRoute($url)
  {
    // Fix double encoded URLs
    $url = preg_replace('/\%25(\w{2})/iu', '%$1', $url);

    // Decoding the URL because many times the cyrillic letters will be messed up
    $url = urldecode($url);

    // Create a copy of the URL
    $_url = $url;

    // Checking for URL encryption
    if (0 === stripos($url, '/ex/v1;'))
    {
      $config = sfConfig::get('app_ice_libs_routing', array('secret' => 'yWJ2wUvHwZDnub7MtZLh2Zknd8TFXQGa'));

      list($version, $string, $iv) = explode(';', substr($url, 4));

      // Decrypt to the original URL
      $data = unserialize(mcrypt_decrypt(
        MCRYPT_TWOFISH,
        $config['secret'],
        gzuncompress(base64_decode(strtr($string, '-_', '+/'))),
        MCRYPT_MODE_CBC,
        base64_decode(strtr($iv, '-_', '+/'))
      ));

      // Valid requests are within an hour of generating the URL
      if (is_array($data) && $data['version'] == $version && $data['time'] > time() - 3600)
      {
        $url = (string) $data['url'];
        @list($path, $query_string) = explode('?', $url);

        parse_str($query_string, $_GET);
        $this->setDefaultParameters($_GET);

        foreach (array_keys($_SERVER) as $name)
        {
          $_SERVER[$name] = str_replace($_url, $url, $_SERVER[$name]);
        }
        $_SERVER['QUERY_STRING'] = $query_string;
      }
    }

    $suffix = null;
    if (substr($url, -5) == '.html')
    {
      $url = substr($url, 0, -5);
      $suffix = '.html';
    }

    // The i18n instance
    if (self::$i18n === null)
    {
      self::$i18n = new sfI18N(sfContext::getInstance()->getConfiguration(), $this->getCache());
    }

    $culture = sfConfig::get('sf_default_culture', 'bg_BG');
    if (preg_match('/^\/(?:'. implode('|', sfConfig::get('app_cultures_extended')) .')\//iu', $url, $m))
    {
      $culture = trim($m[0], '/');
    }
    self::$i18n->setCulture($culture);

    if ($url != '/' && !preg_match('/^\/(?:'. implode('|', sfConfig::get('app_cultures_extended')) .')\//iu', $url))
    {
      $url_i18n = $url = '/'. sfConfig::get('sf_default_culture', 'bg_BG') . '/' . ltrim($url, '/');
    }
    else
    {
      $url_i18n = $url;
    }

    $parts = explode('/', $url_i18n);
    $parts = array_filter($parts);
    $parts = array_values($parts);

    for ($i = count($parts); $i > 0; $i--)
    {
      // "Translate" the route
      $i18n_string = self::$i18n->__('/'. implode('/', array_slice($parts, 0, count($parts) - (count($parts) - $i))), array(), 'routing.find');
      $i18n_string = str_replace(array('[T]', '[/T]'), '', $i18n_string);
      $url_i18n = @rtrim($i18n_string .'/'. implode('/', array_slice($parts, count($parts) - (count($parts) - $i))), '/');

      if ($url_i18n != $url) break;
    }

    // The culture and route do not match, redirect to the right i18n route
    if ($url_i18n != $url)
    {
      $culture_i18n = sfConfig::get('sf_default_culture', 'bg_BG');
      if (preg_match('/^\/(?:'. implode('|', sfConfig::get('app_cultures_extended')) .')\//iu', $url_i18n, $m))
      {
        $culture_i18n = trim($m[0], '/');
      }

      if (sfConfig::get('sf_default_culture', 'bg_BG') != $culture_i18n)
      {
        /** @var $controller sfWebController */
        $controller = sfContext::getInstance()->getController();
        $controller->redirect($url_i18n, 0, '301');

        throw new sfStopException();
      }
    }

    $route = parent::findRoute($url_i18n . $suffix);
    $route_i18n = null;

    if ((!$route || $route['name'] == 'cms_page') && !preg_match('/^\/(?:'. implode('|', sfConfig::get('app_cultures_extended')) .')\//i', $url_i18n))
    {
      $url_i18n = '/'. sfConfig::get('sf_default_culture', 'bg_BG') . '/' . ltrim($url_i18n, '/');
      $route_i18n = parent::findRoute($url_i18n);
    }

    return !empty($route_i18n) ? $route_i18n : $route;
  }

  /**
   * @see sfPatternRouting
   *
   * @param  string  $name
   * @param  array   $params
   * @param  bool    $absolute
   *
   * @return mixed|string
   */
  public function generate($name, $params = array(), $absolute = false)
  {
    $url = parent::generate($name, $params, $absolute);

    $suffix = null;
    if ((false !== $pos = stripos($url, '.html')) || (false !== $pos = stripos($url, '?')))
    {
      $suffix = substr($url, $pos);
      $url = substr($url, 0, $pos);
    }

    // The i18n instance
    if (self::$i18n === null)
    {
      self::$i18n = new sfI18N(sfContext::getInstance()->getConfiguration(), $this->getCache());
    }

    // Split the URL in its parts
    $parts = explode('/', $url);

    $k = 0; $prefix = null;
    if ($absolute == true || 0 === stripos($url, 'http'))
    {
      $k = 2;
      $prefix = implode('/', array_slice($parts, 0, 3));
    }

    $parts = array_filter($parts);
    $parts = array_values($parts);

    $url_i18n = '/'. implode('/', array_slice($parts, $k));
    for ($i = count($parts) - $k; $i >= $k; $i--)
    {
      // "Translate" the route
      $i18n_string = self::$i18n->__('/'. implode('/', array_slice($parts, $k, count($parts) - (count($parts) - $i))), array(), 'routing.generate');
      $i18n_string = str_replace(array('[T]', '[/T]'), '', $i18n_string);
      $url_i18n = '/'. trim($i18n_string .'/'. implode('/', array_slice($parts, $k + count($parts) - (count($parts) - $i))), '/');

      if ($prefix . $url_i18n != $url) break;
    }

    // Removing the default culture from the route to make it shorter
    $url_i18n = preg_replace('/^\/'. sfConfig::get('sf_default_culture', 'bg_BG') .'\//i', '/', $url_i18n);

    $url = $prefix . $url_i18n . $suffix;

    // Check if we need to encrypt the route
    if (stripos($url, '&encrypt=1') || stripos($url, '?encrypt=1'))
    {
      $config = sfConfig::get('app_ice_libs_routing', array('secret' => 'yWJ2wUvHwZDnub7MtZLh2Zknd8TFXQGa'));
      $time = time();

      // Encryption Algorithm
      $alg = MCRYPT_TWOFISH;

      // Create the initialization vector for increased security.
      $iv = mcrypt_create_iv(mcrypt_get_iv_size($alg, MCRYPT_MODE_ECB), MCRYPT_RAND);

      $string = serialize(array(
        'version' => 'v1', 'url' => $url, 'time' => (int) $time
      ));

      $string = mcrypt_encrypt($alg, $config['secret'], $string, MCRYPT_MODE_CBC, $iv);
      $string = strtr(base64_encode(gzcompress($string, 9)), '+/', '-_');
      $hash = sprintf("v1;%s;%s", $string, strtr(base64_encode($iv), '+/', '-_'));

      $url = '/ex/'. $hash;
    }

    return !empty($url) ? $url : '/';
  }
}
