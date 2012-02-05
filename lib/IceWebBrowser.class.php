<?php

require dirname(__FILE__) .'/vendor/sfWebBrowser/sfWebBrowserInvalidResponseException.class.php';
require dirname(__FILE__) .'/vendor/sfWebBrowser/sfCurlAdapter.class.php';
require dirname(__FILE__) .'/vendor/sfWebBrowser/sfWebBrowser.class.php';

class IceWebBrowser extends sfWebBrowser
{
  static private $_browser = array();
  static private $_browser_recycle = array();
  static private $_tor_recycle = array();

  /**
   * @param   string   $url
   * @return  boolean
   */
  public static function isUrl($url)
  {
    if (empty($url))
    {
      return false;
    }

    return (!preg_match('/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $url)) ? false : true;
  }

  static public function formatUrl($url)
  {
    $parsed = parse_url($url);

    if (!is_array($parsed))
    {
      return null;
    }

    $url = null;
    $url = isset($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '':'//'): 'http://';
    $url .= isset($parsed['user']) ? $parsed['user'].($parsed['pass']? ':'.$parsed['pass']:'').'@':'';
    $url .= isset($parsed['host']) ? $parsed['host'] : '';
    $url .= isset($parsed['port']) ? ':'.$parsed['port'] : '';
    $url .= isset($parsed['path']) ? $parsed['path'] : '';
    $url .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
    $url .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

    return $url;
  }

  /**
   * Fetch a page by the given $url and return the sfWebBrowser object
   *
   * @param  IceTypeUrl  $url
   * @param  string      $encoding
   * @param  integer     $timeout
   * @param  integer     $retries
   * @param  array       $options
   * 
   * @return  sfWebBrowser | null
   */
  public static function fetch(IceTypeUrl $url, $encoding = 'utf-8', $timeout = 30, $retries = 3, $options = array())
  {
    $b = self::getBrowser($encoding, $timeout, $options);

    $location = $url['location'];
    $parameters = (isset($url['parameters'])) ? $url['parameters'] : array();

    $method = isset($url['method']) ? strtolower($url['method']) : 'get';
    if (!in_array($method, array('get', 'post')))
    {
      $method = 'get';
    }

    do
    {
      try
      {
        $b->$method($location, $parameters);
      }
      catch (Exception $e)
      {
        ;
      }
    }
    while (--$retries > 0 && $b->getResponseCode() != 200);

    if (200 == $b->getResponseCode())
    {
      return $b;
    }

    return null;
  }

  /**
   * Return a URI's response body as SimpleXMLElement
   *
   * @param  string | array  $url
   * @param  string          $encoding
   * @param  integer         $timeout
   * @param  integer         $retries
   *
   * @return  SimpleXMLElement | false
   */
  public static function fetchAsXml($url, $encoding = 'utf-8', $timeout = 30, $retries = 3, $options = array())
  {
    if (!($url instanceof IceTypeUrl))
    {
      $url = new IceTypeUrl($url);
    }

    if (isset($options['allowed_tags']))
    {
      $allowed_tags = $options['allowed_tags'];
      unset($options['allowed_tags']);
    }

    if ($b = self::fetch($url, $encoding, $timeout, $retries, $options))
    {
      $text = $b->getResponseText();
      $text = self::purify($text, $encoding);
      if (preg_match('/<body.*?>(.*)<\/body>/si', $text, $m))
      {
        $text = $m[1];
      }

      if (isset($allowed_tags) && is_array($allowed_tags))
      {
        $text = strip_tags($text, '<'.implode('><', $allowed_tags).'>');
      }

      return @simplexml_load_string('<body>'. $text .'</body>', 'IceXMLElement', LIBXML_COMPACT);
    }

    return false;
  } // end func

  /**
   * Return a URI's response body as SimpleHtmlDom
   *
   * @param  string | array  $url
   * @param  string          $encoding
   * @param  integer         $timeout
   * @param  integer         $retries
   *
   * @return SimpleHtmlDom | false
   */
  static public function fetchAsHtml($url, $encoding = 'utf-8', $timeout = 30, $retries = 3, $options = array())
  {
    include_once dirname(__FILE__).'/vendor/SimpleHtmlDom.class.php';

    if (!($url instanceof IceTypeUrl))
    {
      $url = new IceTypeUrl($url);
    }

    if (isset($options['allowed_tags']))
    {
      $allowed_tags = $options['allowed_tags'];
      unset($options['allowed_tags']);
    }

    if ($b = self::fetch($url, $encoding, $timeout, $retries, $options))
    {
      $text = $b->getResponseText();
      if (strtolower($encoding) != 'utf-8' && strtolower($encoding) != 'utf8')
      {
        $text = mb_convert_encoding($text, 'utf8', $encoding);
      }

      if (isset($allowed_tags) && is_array($allowed_tags))
      {
        $text = strip_tags($text, '<'.implode('><', $allowed_tags).'>');
      }

      return str_get_html($text);
    }

    return false;
  }

  /**
   * Get a sfWebBrowser instance
   *
   * @param string $encoding
   * @param int $timeout
   * @return sfWebBrowser
   */
  static public function getBrowser($encoding = 'UTF-8', $timeout = 30, $options = array())
  {
    $hash = md5(serialize($options) .'_'. $encoding .'_'. $timeout);

    if (!isset($options['recycle']))            { $options['recycle'] = false; }
    if (!isset(self::$_tor_recycle[$hash]))     { self::$_tor_recycle[$hash] = 0; }
    if (!isset(self::$_browser_recycle[$hash])) { self::$_browser_recycle[$hash] = 0; }

    if (empty(self::$_browser[$hash]) || ++self::$_browser_recycle[$hash] > 10 || $options['recycle'] == true)
    {
      self::$_browser_recycle[$hash] = 0;
      unset($options['recycle']);

      $agent = self::getRandomUserAgent();
      $options = array_merge(
        array(
          'cookies'        => true,
          'cookies_dir'    => sprintf('%s/sfWebBrowser', sfConfig::get('sf_cache_dir')),
          'cookies_file'   => sprintf('%s/sfWebBrowser/cookies-%s.txt', sfConfig::get('sf_cache_dir'), IceStatic::getUniqueId()),
          'FOLLOWLOCATION' => true,
          'MAXREDIRS'      => 3,
          'USERAGENT'      => $agent,
          'TIMEOUT'        => $timeout,
          'SSL_VERIFYPEER' => false
        ),
        $options
      );

      if (isset($options['anonymous']) && $options['anonymous'] == true)
      {
        $options = array_merge(array('PROXY' => '127.0.0.1:9050', 'PROXYTYPE' => CURLPROXY_SOCKS5), $options);
      }
      unset($options['anonymous']);

      self::$_browser[$hash] = new sfWebBrowser(array(), 'sfCurlAdapter', $options);
    }

    if (++self::$_tor_recycle[$hash] > 50)
    {
      if ($fp = fsockopen('127.0.0.1', '9051', $errno, $errstr, 30))
      {
        fputs($fp, "AUTHENTICATE\r\n");
        fread($fp, 1024);
        fputs($fp, "signal NEWNYM\r\n");
        fread($fp, 1024);
        fputs($fp, "QUIT\r\n");
        fclose($fp);
      }

      self::$_tor_recycle[$hash] = 0;
    }

    return self::$_browser[$hash];
  }

  static public function getHttpHeader(IceTypeUrl $url, $header)
  {
    $ch = curl_init($url['location']);

    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $error    = curl_errno($ch);
    curl_close($ch);

    if ($error === 0)
    {
      $header = ucfirst($header) .": ";
      $pos = strpos($response, $header) + strlen($header);

      return substr($response, $pos, strpos($response, "\r\n", $pos) - $pos);
    }

    return null;
  }

  static public function purify($html, $encoding = 'utf8')
  {
    if (strtolower($encoding) != 'utf-8' && strtolower($encoding) != 'utf8')
    {
      $html = mb_convert_encoding($html, 'utf8', $encoding);
    }

    if (class_exists('Tidy'))
    {
      // Specify configuration
      $config = array(
        'indent'          => false,
        'output-xhtml'    => true,
        'hide-comments'   => true,
        'show-body-only'  => true,
        'quote-nbsp'      => false,
        'char-encoding'   => 'utf8',
        'input-encoding'  => 'utf8',
        'output-encoding' => 'utf8'
      );

      $tidy = new Tidy();
      $tidy->parseString($html, $config, 'utf8');
      $tidy->cleanRepair();

      return (string) $tidy;
    }

    return $html;
  }

  /**
   * @return array
   */
  static public function getRandomProxy()
  {
    $proxy_file = file_get_contents(sfConfig::get('sf_data_dir').'/scrapers/proxies.txt');

    $proxies = explode("\n\n", $proxy_file);
    $proxy = $proxies[array_rand($proxies)];
    $proxy = explode("\n", trim($proxy));

    return array($proxy[1], $proxy[2]);
  }

  static public function getRandomUserAgent()
  {
    $user_agents = file(dirname(__FILE__) .'/../data/user_agents.txt');

    return trim($user_agents[array_rand($user_agents)]);
  }
}
