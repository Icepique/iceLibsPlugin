<?php

class IceStatic
{
  static private $_function_cache = array();
  static private $_locks = array();

  static private $_memcache_clients = array();
  static private $_memcache_cache   = null;

  const FNV_prime_32  = 16777619;
  const FNV_prime_64  = 1099511628211;
  const FNV_prime_128 = 309485009821345068724781371;

  const FNV_offset_basis_32  = 2166136261;
  const FNV_offset_basis_64  = 14695981039346656037;
  const FNV_offset_basis_128 = 144066263297769815596495629667062367629;

  /**
   * Get a Memcache() object
   *
   * @param  array $servers
   * @return Memcache
   */
  static public function getMemcacheClient($servers = array())
  {
    // Unique key for the $server parameter
    $key = md5(serialize($servers));

    if (!isset(self::$_memcache_clients[$key]))
    {
      $servers = array_merge(
        array(
          'ice-memcached-01' => array(
            'host' => 'ice-memcached-01', 'port' => 11211,
            'persistent' => true, 'weight' => 1
          ),
          'ice-memcached-02' => array(
            'host' => 'ice-memcached-02', 'port' => 11211,
            'persistent' => true, 'weight' => 1
          )
        ),
        $servers
      );

      // Create the Memcache instance
      self::$_memcache_clients[$key] = new Memcache();

      foreach ($servers as $server)
      {
        $port       = isset($server['port']) ? $server['port'] : 11211;
        $persistent = isset($server['persistent']) ? $server['persistent'] : true;
        $weight     = isset($server['weight']) ? $server['weight'] : 1;
        $timeout    = isset($server['timeout']) ? $server['timeout'] : 1;

        self::$_memcache_clients[$key]->addServer($server['host'], $port, $persistent, $weight, $timeout);
      }
    }

    return self::$_memcache_clients[$key];
  }

  /**
   * Get an IceMemcacheCache() object
   *
   * @return  IceMemcacheCache
   */
  static public function getMemcacheCache()
  {
    if (self::$_memcache_cache === null)
    {
      self::$_memcache_cache = new IceMemcacheCache(array('memcache' => self::getMemcacheClient()));
    }

    return self::$_memcache_cache;
  }

  /**
   * Get a Basecamp() object
   *
   * @return Basecamp
   */
  static public function getBasecampClient()
  {
    include_once dirname(__FILE__).'/vendor/Basecamp.class.php';

    return new Basecamp('https://icepique.basecamphq.com/', 'api_access', '2sRqqP7HElFCjM');
  }

  /**
   * Get a SphinxClient() object
   *
   * @param  string  $hostname
   * @param  string  $culture
   *
   * @return IceSphinxClient
   */
  static public function getSphinxClient($hostname = 'ice-sphinx', $culture = 'bg_BG')
  {
    $sphinx = new IceSphinxClient();
    $sphinx->setServer($hostname, 9312);

    // http://www.sphinxsearch.com/docs/current.html#api-func-setmatchmode
    $sphinx->setMatchMode(SPH_MATCH_EXTENDED2);

    /**
     * Disabled ranking mode. This mode is the fastest.
     * It is essentially equivalent to boolean searching.
     * A weight of 1 is assigned to all matches.
     *
     * @see http://sphinxsearch.com/docs/current.html#api-func-setrankingmode
     */
    $sphinx->setRankingMode(SPH_RANK_NONE);

    // http://www.sphinxsearch.com/docs/current.html#api-func-setarrayresult
    $sphinx->setArrayResult(false);

    // http://www.sphinxsearch.com/docs/current.html#api-func-setconnecttimeout
    $sphinx->setConnectTimeout(3);

    // http://www.sphinxsearch.com/docs/current.html#api-func-resetfilters
    $sphinx->resetFilters();

    return $sphinx;
  }

  static public function getAkismetClient($website = null)
  {
    include_once dirname(__FILE__).'/vendor/Akismet.class.php';

    $api_key = sfConfig::get('app_credentials_akismet');
    $website = sfConfig::get('app_www_domain', $website);

    return new Akismet($website, $api_key);
  }

  static public function getFunctionCache($culture = 'bg_BG', $lifetime = 3600)
  {
    if (!isset(self::$_function_cache[$culture][$lifetime]))
    {
      $cache = new sfAPCCache(array(
        'lifetime' => $lifetime, 'prefix' => 'ice_functions_'. $culture
      ));

      self::$_function_cache[$culture][$lifetime] = new sfFunctionCache($cache);
    }

    return self::$_function_cache[$culture][$lifetime];
  }

  static public function getUserIpAddress()
  {
    return $_SERVER['REMOTE_ADDR'] != getenv('SERVER_ADDR') ? $_SERVER['REMOTE_ADDR'] : getenv('HTTP_X_FORWARDED_FOR');
  }

  static public function getUserUniqueString()
  {
    return implode('-', array(
      $_SERVER['HTTP_HOST'],
      $_SERVER['SF_APP'], $_SERVER['SF_ENV'],
      $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']
    ));
  }

  static public function getUniqueId($chars = 32)
  {
    return substr(md5(time().substr(md5(microtime()), 0, rand(5, 12))), 0, $chars);
  }

  static public function getUniquePassword($length = 8, $strength = 1, $chars = null)
  {
    if ($chars === null)
    {
      $vowels = 'aeuy';
      $consonants = 'bdghjmnpqrstvz';

      if ($strength & 1) {
        $consonants .= 'BDGHJLMNPQRSTVWXZ';
      }
      if ($strength & 2) {
        $vowels .= "AEUY";
      }
      if ($strength & 4) {
        $consonants .= '23456789';
      }
      if ($strength & 8) {
        $consonants .= '@#$%';
      }
    }
    else
    {
      $vowels = $consonants = $chars;
    }

    $password = '';
    $alt = time() % 2;

    for ($i = 0; $i < $length; $i++)
    {
      if ($alt == 1)
      {
        $password .= $consonants[(rand() % strlen($consonants))];
        $alt = 0;
      }
      else
      {
        $password .= $vowels[(rand() % strlen($vowels))];
        $alt = 1;
      }
    }

    return $password;
  }

  static public function getBitlyUrl($url)
  {
    // Bit.ly
    $bitly = sprintf(
      "http://api.bit.ly/shorten?version=2.0.1&longUrl=%s&login=%s&apiKey=%s&format=json&history=0",
      urlencode($url), 'icepique', 'R_61fd2647749e2b0dcab7a835bbc8a854'
    );

    $c = curl_init();
    curl_setopt($c,CURLOPT_URL, $bitly);
    curl_setopt($c,CURLOPT_HEADER, false);
    curl_setopt($c,CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($c);
    curl_close($c);

    $result = json_decode($result, true);

    return $result["results"][$url]["shortUrl"];
  }

  public static function getRequestHeaders()
  {
    if (function_exists('apache_request_headers'))
    {
      return apache_request_headers();
    }

    $out = null;
    foreach($_SERVER as $key=>$value)
    {
      if (substr($key,0,5) == "HTTP_")
      {
        $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key,5)))));
        $out[$key] = $value;
      }
      else
      {
        $out[$key] = $value;
      }
    }

    return $out;
  }

  public static function isCrawler()
  {
    // Under "cli" SAPI we know the answer immediately
    if (php_sapi_name() == 'cli')
    {
      return false;
    }

    $b = @get_browser(null, false);
    if ($b && $b->crawler === '1')
    {
      $crawler = Utf8::slugify($b->browser, '-', true, true);

      /**
       * We need to verify the top crawlers
       */
      $spiders = array(
        'googlebot' => "/\.googlebot\.com$/i",
        'msnbot' => "/search\.msn\.com$/i",
        'yahoo-slurp' => "/crawl\.yahoo\.net$/i"
      );

      foreach ($spiders as $name => $pattern)
      {
        if ($crawler == $name)
        {
          $ip = IceStatic::getUserIpAddress();
          $hostname = gethostbyaddr($ip);

          if (preg_match($pattern, $hostname))
          {
            // Now we have a hit that half-passes the check. One last go:
            $real_ip = gethostbyname($hostname);

            return ($ip == $real_ip) ? $name : false;
          }

          return false;
        }
      }

      return $crawler;
    }

    return false;
  }

  static public function cleanFilename($filename)
  {
    return preg_replace('/[^a-z0-9-]/', '-', strtolower($filename));
  }

  /**
   * Clean a string from garbage characters and double spaces
   *
   * @param  string   $v       The string to clean
   * @param  boolean  $decode  Whether to apply htmlspecialchars_decode() to the string before cleaning
   *
   * @return string
   */
  static public function cleanSpaces($v, $decode = false)
  {
    Utf8::loadFunction('trim');

    if ($decode)
    {
      $v = htmlspecialchars_decode($v);
    }

    $v = (string) $v;
    $v = preg_replace('/\s{2,}/iu', ' ', $v);
    $v = mb_ereg_replace('/\r\n|\n|\r/iu', ' ', $v);
    $v = mb_ereg_replace('/[^[:print:]]+/iu', '', $v);

    return utf8_trim(trim($v), ' ');
  }

  /**
   * Clean text against XSS attacks and from unneeded tags.
   *
   * @param  string   $text          The text to clean against XSS
   * @param  boolean  $decode        Whether to apply htmlspecialchars_decode() to the text before cleaning
   * @param  string   $allowed_tags  The tags allowed to stay in the resulting text/html
   *
   * @return string
   */
  static public function cleanText($text, $decode = false, $allowed_tags = 'b, u, i, strong, ul, li, br, p')
  {
    include_once dirname(__FILE__).'/vendor/HtmLawed.php';

    if ($decode)
    {
      $text = htmlspecialchars_decode($text);
    }

    return htmLawed(
      $text,
      array(
        'elements' => $allowed_tags,
        'safe' => 1, 'keep_bad' => 0,
        'comment' => 1, 'cdata' => 1,
        'deny_attribute' => 'on', ' clean_ms_char' => 1
      )
    );
  }

  /**
   * Truncates +text+ to the length of +length+ and replaces the last three characters with the +truncate_string+
   * if the +text+ is longer than +length+.
   *
   * @param  string   $text
   * @param  integer  $length
   * @param  string   $truncate_string
   * @param  boolean  $truncate_lastspace
   *
   * @return string
   */
  static public function truncateText($text, $length = 30, $truncate_string = '...', $truncate_lastspace = false)
  {
    if (empty($text))
    {
      return $text;
    }

    $mbstring = extension_loaded('mbstring');
    if($mbstring)
    {
      @mb_internal_encoding(mb_detect_encoding($text));
    }
    $strlen = ($mbstring) ? 'mb_strlen' : 'strlen';
    $substr = ($mbstring) ? 'mb_substr' : 'substr';

    if (call_user_func($strlen, $text) > $length)
    {
      $truncate_text = call_user_func_array($substr, array($text, 0, $length - call_user_func($strlen, $truncate_string)));
      if ($truncate_lastspace)
      {
        $truncate_text = preg_replace('/\s+?(\S+)?$/iu', '', $truncate_text);
      }

      $text = $truncate_text . $truncate_string;
    }

    return $text;
  }

  /**
   * Truncates +text+ to the length of +length+ and replaces the last three characters with the +truncate_string+
   * if the +text+ is longer than +length+.
   *
   * @param  string   $text
   * @param  integer  $length
   * @param  string   $reduce_string
   *
   * @return null|string
   */
  static public function reduceText($text, $length = 30, $reduce_string = '...')
  {
    if (empty($text))
    {
      return $text;
    }

    $mbstring = extension_loaded('mbstring');
    if($mbstring)
    {
      @mb_internal_encoding(mb_detect_encoding($text));
    }
    $strlen = ($mbstring) ? 'mb_strlen' : 'strlen';
    $substr = ($mbstring) ? 'mb_substr' : 'substr';

    if ($length <= call_user_func($strlen, $reduce_string) + 2)
    {
      return null;
    }
    else if (call_user_func($strlen, $text) > $length)
    {
      $offset1 = ceil($length / 2) - ceil(call_user_func($strlen, $reduce_string) / 2);
      $offset2 = -1 * ($length % 2 !== 0 ? $offset1 : $offset1 + 1);

      return call_user_func_array($substr, array($text, 0, $offset1)) . $reduce_string . call_user_func_array($substr, array($text, $offset2));
    }
    else
    {
      return $text;
    }
  }

  /**
   * @static
   * @param  string   $v
   * @param  integer  $precision
   *
   * @return double|float
   */
  static public function floatval($v, $precision = 2)
  {
    // Do not do anything if the variable is already a float
    if (is_float($v))
    {
      return round($v, $precision);
    }
    else if (!is_numeric($v))
    {
      foreach (array('лв', 'BGN', 'EUR', 'USD') as $currency)
      {
        if ($pos = stripos($v, $currency))
        {
          $v = substr($v, 0, $pos);
          break;
        }
      }

      $v = trim($v);
      $v = preg_replace('/^(\d+)\s([0-9][0-9])$/i', '\\1.\\2', $v);
      $v = preg_replace('/[^\d\.\,]/', '', $v);
      $v = trim($v, '.');
      $v = str_replace(',', '', substr($v, 0, -3)) . substr($v, -3);
      $v = str_replace('.', '', substr($v, 0, -3)) . substr($v, -3);
      $v = str_replace(',', '.', $v);
      $v = trim($v, '.');

      if (preg_match("/([0-9\.]+)/i", $v, $match))
      {
        $v = $match[0];
      }
    }

    return round((float) $v, $precision);
  }

  static public function extractTerms($text, $query = null)
  {
    $credentials = sfConfig::get('app_credentials_yahoo');
    $fields = array(
      'appid' => $credentials['appid'],
      'context' => $text,
      'query' => $query,
      'output' => 'xml'
    );

    try
    {
      $b = IceWebBrowser::getBrowser('utf-8', 5);
      $b->post('http://search.yahooapis.com/ContentAnalysisService/V1/termExtraction', $fields);

      if ($b->responseIsError())
      {
        throw new sfWebBrowserInvalidResponseException('There was an error from Yahoo Term Extraction');
      }

      $xml = $b->getResponseXML();
    }
    catch (Exception $e)
    {
      return array();
    }

    $terms = array();

    if ($xml->Result)
    foreach ($xml->Result as $term)
    {
      $terms[] = (string) $term;
    }

    return $terms;
  }

  /**
   * Extract valid phone numbers from a string
   *
   * @param  string  $text    Search for the numbers in this string
   * @param  bool    $strict  If $strict is true, it will filter out the invalid numbers
   * @param  int     $count   In this variable will be returned the number of valid numbers found
   * @param  string  $code    For incomplete numbers, use this default city code to try to make them valid
   *
   * @see   http://en.wikipedia.org/wiki/Telephone_numbers_in_Bulgaria#Area_codes
   *
   * @return array
   */
  static public function extractPhoneNumbers($text, $strict = false, &$count = 0, $code = null)
  {
    static $phone_codes = null;

    if ($strict && $phone_codes === null)
    {
      $phone_codes = @file(dirname(__FILE__).'/../data/phone_codes.txt');
      $phone_codes = array_map('trim', $phone_codes);

      uasort(
        $phone_codes,
        create_function('$x, $y', 'if (strlen($x) == strlen($y)) if (intval($x) > intval($y)) return -1; else return 1; else if (strlen($x) > strlen($y)) return -1; else return 1;')
      );
    }

    // Saving a copy of the input text before we manipulate it
    $original = $text;

    // We want the count to be an integer
    $count = (int) $count;

    // Initialize the numbers array
    $numbers = array();

    // Lowercase the input text
    $text = strtolower($text);

    foreach (array(',', ';', '.', 'gsm:', 'телефон:') as $s)
    {
      if (stripos($text, $s) !== false)
      {
        $parts = explode($s, $text);
        foreach ($parts as $part)
        {
          $numbers = array_merge($numbers, self::extractPhoneNumbers($part, $strict, $count, &$code));
        }

        // Remove any duplicates & sort
        $numbers = array_unique($numbers);
        sort($numbers, SORT_NUMERIC);

        return $numbers;
      }
    }

    $text = preg_replace('/(\+|00)[\s+]?359([\s|\/]+)?/i', '0', $text);
    $text = trim(preg_replace('/[^0-9\\/\_\-\+\,\;\.\?]/i', ' ', $text));

    if (substr($text, 0, 1) == '+')
    {
      return array($text);
    }

    // Let's find the current format mobile numbers
    if (preg_match_all('/0[8|9][7-9][\\/\s+\-]?[0-9][\\/\s+\-]?(?:\d\s?\-?){6}/', $text, $m))
    {
      $numbers = array_merge($numbers, $m[0]);
      $text = preg_replace('/0[8|9][7-9][\\/\s+\-]?[0-9][\\/\s+\-]?(?:\d\s?\-?){6}/', '', $text);
    }

    // Let's find the old pre 2003 mobile numbers first
    if (preg_match_all('/0[8-9][7-9][\\/\s+\-]?(?:\d\s?\-?){6}/', $text, $m))
    {
      foreach ($m[0] as $number)
      {
        $numbers[] = '08'. substr($number, 1);
      }
      $text = preg_replace('/0[8-9][7-9][\\/\s+\-]?(?:\d\s?\-?){6}/', '', $text);
    }

    // Let's find the landline numbers
    if (preg_match_all('/(^|\s+)(0[2-9][1-9]?[0-9]?)([\\/\s\-]+)?(?:\d\s?\-?){4,7}\d/', $text, $m))
    {
      if (!empty($m[2][0]))
      {
        $code = $m[2][0];
      }

      $numbers = array_merge($numbers, $m[0]);
      $text = preg_replace('/(^|\s+)0[2-9][2-9]?[0-9]?[\\/\s\-]+?(?:\d\s?\-?){4,7}\d/', '', $text);
    }

    // Special number for Sofia
    if (preg_match_all('/(^|\s+)[8-9](?:\d\s?\-?){6}/', $text, $m))
    {
      $numbers[] = '02' . $m[0][0];
      $text = preg_replace('/(^|\s+)[8-9](?:\d\s?\-?){6}/', '', $text);
    }

    $text = trim($text, ' .-/+,;');
    if (!empty($text))
    {
      $numbers[] = $text;
    }

    // Remove any duplicates & sort
    $numbers = array_unique($numbers);
    sort($numbers, SORT_NUMERIC);

    // This will clean the resulting numbers from everything but numbers
    $clean = create_function('$a', 'return preg_replace("/[^0-9]+/", "", $a);');
    $numbers = array_map($clean, $numbers);
    $numbers = array_filter($numbers);

    foreach ($numbers as $i => $number)
    {
      if (strlen($code . $number) == 9)
      {
        $numbers[$i] = $number = $code . $number;
      }

      $pass = true;

      if ($strict === true)
      {
        $pass = false;

        foreach ($phone_codes as $phone_code)
        {
          if (substr($number, 0, strlen($phone_code)) == $phone_code)
          {
            $pass = true; break;
          }
        }
      }

      if ($pass === false || strlen($number) < 8 || strlen($number) > 10)
      {
        if ($strict === true)
        {
          unset($numbers[$i]);
        }
        else
        {
          return array($original);
        }
      }
    }

    $numbers = array_unique($numbers);
    $numbers = array_values($numbers);

    $count += count($numbers);

    // We get only the array values to make sure the array keys start from 0
    return $numbers;
  }

  public static function formatPhoneNumber($number)
  {
    if (preg_match('/(0[8|9][7-9][0-9])[\\/\s+\-]?((?:\d\s?\-?){3})((?:\d\s?\-?){3})/', $number, $m))
    {
      $number = $m[1] .' '. $m[2] .' '. $m[3];
    }

    return $number;
  }

  public static function exractSearchEngineKeyword($url = null)
  {
    $url = ($url === null) ? $_SERVER['HTTP_REFERER'] : $url;

    if (empty($url) || !IceWebBrowser::isUrl($url))
    {
      return null;
    }

    $parts = parse_url($url);

    if (isset($parts['query']))
    {
      parse_str($parts['query'], $query);
    }

    $search_engines = array(
      'bing' => 'q',
      'google' => 'q',
      'yahoo' => 'p',
      'bezplatno' => 'keyword'
    );

    preg_match('/(' . implode('|', array_keys($search_engines)) . ')\./iu', $parts['host'], $m);

    // The URL is from a search engine
    if (isset($m[1]) && isset($query[$search_engines[$m[1]]]))
    {
      return $query[$search_engines[$m[1]]];
    }
    else if (preg_match('/\/tag-([а-я\%\-\d\w]+)/iu', $parts['path'], $m))
    {
      return $m[1];
    }

    return null;
  }

  public static function cleanTitle($title)
  {
    UTF8::loadFunction('trim');

    $punctuation = array(
      '±','§','!','@','$','%','^','&','*','(',')','_','=','`','~','"','\'',
      '\\',';',':','|','}','{','}',']','[','.',',','/','?','>','<','-',';'
    );
    $forbidden = array('§','±','^','*','=',']','[','}','{','|','\\','`','~');

    $title = utf8_trim($title, implode('', $punctuation) .' ');
    $title = preg_replace('/(['. preg_quote(implode('', $forbidden), '/') .'])+/ui', ' ', $title);
    foreach ($punctuation as $sign)
    {
      $title = preg_replace('/('. preg_quote($sign, '/') .')+/ui', '$1', $title);
    }
    $title = preg_replace('/(\s+)+/ui', ' ', $title);

    return trim($title);
  }

  function fnvhash($text)
  {
    $buf  = str_split($text);
    $hash = self::FNV_offset_basis_32;

    foreach ($buf as $chr)
    {
      $hash += ($hash << 1) + ($hash << 4) + ($hash << 7) + ($hash << 8) + ($hash << 24);
      $hash = $hash ^ ord($chr);
    }
    $hash = $hash & 0x0ffffffff;

    return $hash;
  }

  /**
   * @link http://www.php.net/manual/en/ref.mcrypt.php#78764
   *
   * @param  string  $text
   * @param  string  $password
   *
   * @return string
   */
  static public function crypt($text, $password = 'og3eec1kud9wik')
  {
    $size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $iv = mcrypt_create_iv($size, MCRYPT_RAND);

    return bin2hex(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $password, $text, MCRYPT_MODE_ECB, $iv));
  }

  /**
   * @link http://www.php.net/manual/en/ref.mcrypt.php#78764
   *
   * @param  string  $encrypted
   * @param  string  $password
   *
   * @return string
   */
  static public function decrypt($encrypted, $password = 'og3eec1kud9wik')
  {
    $size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $iv = mcrypt_create_iv($size, MCRYPT_RAND);

    return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $password, pack("H*", $encrypted), MCRYPT_MODE_ECB, $iv));
  }

  static public function destroy()
  {
    $args = func_get_args();

    foreach ($args as $arg)
    {
      /**
       * @var array $arg
       */
      if (is_array($arg))
      foreach ($arg as $k => $v)
      {
        if (is_object($arg[$k]) && method_exists($arg[$k], '__destruct'))
        {
          $arg[$k]->__destruct();
        }
        unset($arg[$k]);
      }

      /**
       * @var Object $arg
       */
      if (is_object($arg) && method_exists($arg, '__destruct'))
      {
        $arg->__destruct();
      }
      unset($arg);
    }
  }

  static public function getLock($name)
  {
    self::$_locks[$name]['file'] = sys_get_temp_dir().'/'. $name .'.lock';
    self::$_locks[$name]['resource'] = fopen(self::$_locks[$name]['file'], "wb+");

    if (is_resource(self::$_locks[$name]['resource']))
    {
      return flock(self::$_locks[$name]['resource'], LOCK_EX|LOCK_NB);
    }

    return false;
  }

  static public function releaseLock($name)
  {
    if (isset(self::$_locks[$name]) && is_resource(self::$_locks[$name]['resource']))
    {
      return (fclose(self::$_locks[$name]['resource']) && unlink(self::$_locks[$name]['file']));
    }

    return true;
  }

  public static function getParam($param_name, $default = null)
  {
    global $HTTP_POST_VARS, $HTTP_GET_VARS, $_POST, $_GET;

    if (!$HTTP_POST_VARS) $HTTP_POST_VARS = &$_POST;
    if (!$HTTP_GET_VARS)  $HTTP_GET_VARS  = &$_GET;

    if (isset($HTTP_POST_VARS[$param_name]))
    {
      $param_value = $HTTP_POST_VARS[$param_name];
    }
    else if (isset($HTTP_GET_VARS[$param_name]))
    {
      $param_value = $HTTP_GET_VARS[$param_name];
    }
    else
    {
      $param_value = $default;
    }

    return $param_value;
  }

  static public function loadZendFramework()
  {
    // Integrate Zend Framework
    require_once('Zend/Loader.php');
    spl_autoload_register(array('self', 'zendFrameworkAutoload'));
    Zend_Registry::set('Zend_Locale', new Zend_Locale('bg_BG.UTF-8'));

    return true;
  }

  /**
   * @see Zend_Loader
   *
   * @param  string $class
   * @return boolean
   */
  static public function zendFrameworkAutoload($class)
  {
    if (strtolower(substr($class, 0, 4)) != 'zend')
    {
      return false;
    }

    // supress include_once errors
    $err = error_reporting(0);
    $ret = Zend_Loader::autoload($class);
    error_reporting($err);

    return $ret;
  }
}
