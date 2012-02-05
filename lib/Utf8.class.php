<?php

require dirname(__FILE__).'/vendor/utf8/utf8.php';

class Utf8
{
  private static $tables = array(
    'metodii' => array(
      'я' => 'ja',
      'ю' => 'ju',
      'щ' => 'wt',
      'а' => 'a',
      'е' => 'e',
      'к' => 'k',
      'п' => 'p',
      'ф' => 'f',
      'б' => 'b',
      'ж' => 'x',
      'л' => 'l',
      'р' => 'r',
      'х' => 'h',
      'ъ' => 'y',
      'в' => 'v',
      'з' => 'z',
      'м' => 'm',
      'с' => 's',
      'ц' => 'c',
      'й' => 'j',
      'ь' => 'j',
      'г' => 'g',
      'и' => 'i',
      'н' => 'n',
      'т' => 't',
      'ч' => 'q',
      'д' => 'd',
      'о' => 'o',
      'у' => 'u',
      'ш' => 'w'
    ),
    'natural' => array(
      'я' => 'ya',
      'ю' => 'yu',
      'щ' => 'sht',
      'ш' => 'sh',
      'ц' => 'ts',
      'ж' => 'zh',
      'ч' => 'ch',
      'а' => 'a',
      'е' => 'e',
      'к' => 'k',
      'п' => 'p',
      'ф' => 'f',
      'б' => 'b',
      'л' => 'l',
      'р' => 'r',
      'х' => 'h',
      'ъ' => 'a',
      'в' => 'v',
      'з' => 'z',
      'м' => 'm',
      'с' => 's',
      'й' => 'y',
      'ь' => 'y',
      'г' => 'g',
      'и' => 'i',
      'н' => 'n',
      'т' => 't',
      'д' => 'd',
      'о' => 'o',
      'у' => 'u'
    ),
    'keyboard' => array(
      'я' => 'ya',
      'ю' => 'q',
      'щ' => ']',
      'а' => 'a',
      'е' => 'e',
      'к' => 'k',
      'п' => 'p',
      'ф' => 'f',
      'б' => 'b',
      'ж' => 'v',
      'л' => 'l',
      'р' => 'r',
      'х' => 'h',
      'ъ' => 'y',
      'в' => 'v',
      'з' => 'z',
      'м' => 'm',
      'с' => 's',
      'ц' => 'c',
      'й' => 'j',
      'ь' => 'x',
      'г' => 'g',
      'и' => 'i',
      'н' => 'n',
      'т' => 't',
      'ч' => '`',
      'д' => 'd',
      'о' => 'o',
      'у' => 'u',
      'ш' => '['
    ),
    'chat' => array(
      'я' => 'ya',
      'ю' => 'yu',
      'щ' => 'sht',
      'ц' => 'ts',
      'ж' => 'zh',
      'ш' => '6',
      'ч' => '4',
      'а' => 'a',
      'е' => 'e',
      'к' => 'k',
      'п' => 'p',
      'ф' => 'f',
      'б' => 'b',
      'л' => 'l',
      'р' => 'r',
      'х' => 'h',
      'ъ' => 'a',
      'в' => 'v',
      'з' => 'z',
      'м' => 'm',
      'с' => 's',
      'й' => 'y',
      'ь' => 'y',
      'г' => 'g',
      'и' => 'i',
      'н' => 'n',
      'т' => 't',
      'д' => 'd',
      'о' => 'o',
      'у' => 'u'
    )
  );

 /**
  * Tests whether a string contains only 7-bit ASCII bytes. This is used to
  * determine when to use native functions or UTF-8 functions.
  *
  *     $ascii = Utf8::is_ascii($str);
  *
  * @param   string  string to check
  * @return  bool
  */
  public static function is_ascii($str)
  {
    return !preg_match('/[^\x00-\x7F]/S', $str);
  }

  public static function is_english($str)
  {
    $unicode = self::utf8_to_unicode($str);
    while(list(,$char) = each($unicode)) {
      if ($char > 128) return false;
    }

    return true;
  }

  public static function is_cyrillic($str, $with_numbers = false)
  {
    return ($with_numbers) ? preg_match("/^[0-9А-Яа-я\s]+$/iu", $str) : preg_match("/^[А-Яа-я\s]+$/iu", $str);
  }

  public static function utf8_to_unicode($str)
  {
    $unicode = array();
    $values = array();
    $lookingFor = 1;

    for ($i = 0; $i < strlen($str); $i++)
    {
      $thisValue = ord($str[$i]);

      if ($thisValue < 128 ) {
        $unicode[] = $thisValue;
      }
      else
      {
        if (count($values) == 0) {
          $lookingFor = ($thisValue < 224) ? 2 : 3;
        }
        $values[] = $thisValue;

        if (count($values) == $lookingFor)
        {
          $number = ($lookingFor == 3) ? (($values[0] % 16) * 4096) + (($values[1] % 64) * 64) + ($values[2] % 64) : (($values[0] % 32) * 64) + ($values[1] % 64);

          $unicode[] = $number;
          $values = array();
          $lookingFor = 1;
        }
      }
    } // end for

    return $unicode;
  } // end func

  public static function cyrillic2latin($string, $method = 'php')
  {
    if (isset(self::$tables[$method]))
    {
      $search = array_keys(self::$tables[$method]);
      $search = array_map('preg_quote', $search);
      $replace = array_values(self::$tables[$method]);

      $string = str_ireplace(array_keys(self::$tables[$method]), array_values(self::$tables[$method]), $string);
    }
    else if (function_exists('transliterate'))
    {
      $string = transliterate($string,
        array('greek_transliterate', 'cyrillic_transliterate_bulgarian', 'diacritical_remove', 'normalize_punctuation'),
        'utf-8', 'utf-8'
      );
    }
    else
    {
      $string = null;
    }

    return $string;
  }

  public static function latin2cyrillic($string, $method = 'php')
  {
    if (isset(self::$tables[$method]))
    {
      $search = array_values(self::$tables[$method]);
      $search = array_map('preg_quote', $search);
      $replace = array_keys(self::$tables[$method]);

      self::loadFunction('str_ireplace');
      $string = utf8_ireplace($search, $replace, $string);
    }

    return $string;
  }

  public static function cp1251_to_utf8($s)
  {
    if ((mb_detect_encoding($s,'UTF-8, CP1251')) == "WINDOWS-1251")
    {
      $c209 = chr(209);
      $c208 = chr(208);
      $c129 = chr(129);

      $t = null;

      for ($i=0; $i<strlen($s); $i++)
      {
        $c = ord($s[$i]);
        if ($c>=192 and $c<=239) $t.=$c208.chr($c-48);
        elseif ($c>239) $t.=$c209.chr($c-112);
        elseif ($c==184) $t.=$c209.$c209;
        elseif ($c==168)    $t.=$c208.$c129;
        else $t.=$s[$i];
      }

      return $t;
    }
    else
    {
      return $s;
    }
  }

  public static function utf8_to_cp1251($s)
  {
    if ((mb_detect_encoding($s,'UTF-8,CP1251')) == "UTF-8")
    {
      $out = null;
      $byte2 = null;
      $c1 = null;

      for ($c=0; $c < strlen($s); $c++)
      {
        $i = ord($s[$c]);
        if ($i <= 127) $out .= $s[$c];
        if ($byte2)
        {
          $new_c2=($c1&3)*64+($i&63);
          $new_c1=($c1>>2)&5;
          $new_i=$new_c1*256+$new_c2;
          if ($new_i == 1025)
          {
            $out_i=168;
          }
          else
          {
            if ($new_i==1105)
            {
              $out_i=184;
            }
            else
            {
              $out_i=$new_i-848;
            }
          }

          $out .= chr($out_i);
          $byte2 = false;
        }
        if (($i>>5) == 6)
        {
          $c1 = $i;
          $byte2 = true;
        }
      }

      return $out;
    }
    else
    {
      return $s;
    }
  }

  public static function translit($text, $method = null)
  {
    $methods = array_keys(self::$tables);

    if (in_array($method, $methods))
    {
      $translit = Utf8::latin2cyrillic(mb_strtolower($text, 'utf8'), $method);
    }
    else
    {
      foreach ($methods as $method)
      {
        if (Utf8::is_ascii($text))
        {
          $translit[] = Utf8::latin2cyrillic(mb_strtolower($text, 'utf8'), $method);
        }
        else
        {
          $translit[] = Utf8::cyrillic2latin(mb_strtolower($text, 'utf8'), $method);
        }
      }

      $translit = array_filter($translit);
      $translit = array_unique($translit);
      $translit = implode(', ', $translit);
    }

    return $translit;
  }

  public static function slugify($string, $separator = '-', $lower = false, $ascii_only = false, $default = null)
  {
    // Make lower case if requested
    if ($lower === true)
    {
      Utf8::loadFunction('strtolower');

      $string = utf8_strtolower($string, 'UTF-8');
    }

    // Leave it ascii only if requested
    if ($ascii_only === true && !self::is_ascii($string))
    {
      // Transliterate non-ASCII characters
      $string = self::cyrillic2latin($string, 'natural');

      // Transliterate one more time just in case
      $string = iconv('UTF-8', 'us-ascii//TRANSLIT', $string);

      // Remove all characters that are not the separator, a-z, 0-9, dot, or whitespace
      $string = preg_replace('/[^'.preg_quote($separator).'\w\d\s\/\_:=]+/ui', '', $string);
    }
    else
    {
      if (function_exists('transliterate'))
      {
        // Transliterate
        $string = transliterate($string, array('diacritical_remove', 'normalize_punctuation', 'greek_transliterate'), 'utf-8', 'utf-8');
      }

      // Remove all characters that are not the separator, letters, numbers, or whitespace
      $string = preg_replace('/[^'.preg_quote($separator).'a-zA-Zа-яА-Я\d\s\/\_:=]+/iu', '', $string);
    }

    // Replace all separator characters, "/", ":", "=" and whitespace by a single separator
    $string = preg_replace('/['.preg_quote($separator).'\s\/:=]+/iu', $separator, $string);

    // Trim the separator
    $string = trim($string, $separator);

    return !empty($string) ? mb_substr($string, 0, 245, 'UTF-8') : $default;
  }

  /**
   * NOTE: It is very ofter slow so should not be used for now!
   *
   * @static
   *
   * @param $text
   * @param $words
   * @param int $length
   * @param string $prefix
   * @param null $suffix
   * @param array $options
   *
   * @return string
   */
  public static function excerpt($text, $words, $length = 250, $prefix = "...", $suffix = null, $options = array())
  {
    // Set default score modifiers [tweak away...]
    $options = sfToolkit::arrayDeepMerge(array(
      'exact_case_bonus'  => 2,
      'exact_word_bonus'  => 3,
      'abs_length_weight' => 0.0,
      'rel_length_weight' => 1.0,

      'debug' => false
    ), $options);

    // Null suffix defaults to same as prefix
    if (is_null($suffix)) {
      $suffix = $prefix;
    }

    // Not enough to work with?
    if (mb_strlen($text, 'utf8') <= $length) {
      return $text;
    }

    // Just in case
    if (!is_array($words)) {
      $words = explode(' ', (string) $words);
    }

    // Build the event list
    // [also calculate maximum word length for relative weight bonus]
    $events = array();
    $maxWordLength = 0;

    $k = 1;
    foreach ($words as $word)
    {
      // Skip words less than 3 characters for performance reasons
      if (mb_strlen($word) < 3 || is_numeric($word)) continue;

      // Break if we already looped enough
      if ($k > 3) break;

      if (mb_strlen($word, 'utf8') > $maxWordLength) {
        $maxWordLength = mb_strlen($word, 'utf8');
      }

      $i = -1;
      while (false !== $i = mb_stripos($text, $word, $i+1, 'utf8'))
      {
        // Basic score for a match is always 1
        $score = 1;

        // Apply modifiers
        if (mb_substr($text, $i, mb_strlen($word, 'utf8'), 'utf8') == $word) {
          // Case matches exactly
          $score += $options['exact_case_bonus'];
        }
        if ($options['abs_length_weight'] != 0.0) {
          // Absolute length weight (longer words count for more)
          $score += mb_strlen($word, 'utf8') * $options['abs_length_weight'];
        }
        if ($options['rel_length_weight'] != 0.0) {
          // Relative length weight (longer words count for more)
          $score += mb_strlen($word, 'utf8') / $maxWordLength * $options['rel_length_weight'];
        }
        if (preg_match('/\W/xu', mb_substr($text, $i-1, 1, 'utf8'))) {
          // The start of the word matches exactly
          $score += $options['exact_word_bonus'];
        }
        if (preg_match('/\W/xu', mb_substr($text, $i + mb_strlen($word, 'utf8'), 1, 'utf8'))) {
          // The end of the word matches exactly
          $score += $options['exact_word_bonus'];
        }

        // Push event occurs when the word comes into range
        $events[] = array(
          'type'  => 'push',
          'word'  => $word,
          'pos'   => max(0, $i + mb_strlen($word, 'utf8') - $length),
          'score' => $score
        );

        // Pop event occurs when the word goes out of range
        $events[] = array(
          'type' => 'pop',
          'word' => $word,
          'pos'  => $i + 1,
          'score' => $score
        );

        // Bump event makes it more attractive for words to be in the
        // middle of the excerpt [@todo: this needs work]
        $events[] = array(
          'type' => 'bump',
          'word' => $word,
          'pos'  => max(0, $i + floor(mb_strlen($word, 'utf8')/2) - floor($length/2)),
          'score' => 0.5
        );
      }

      $k++;
    }

    // If nothing is found then just truncate from the beginning
    if (empty($events)) {
      return mb_substr($text, 0, $length, 'utf8') . $suffix;
    }

    // We want to handle each event in the order it occurs in
    // [i.e. we want an event queue]
    $events = IceFunctions::array_key_sort($events, 'pos');

    $scores = array();
    $score = 0;
    $current_words = array();

    // Process each event in turn
    foreach ($events as $event)
    {
      $thisPos = (int) floor($event['pos']);

      $word = mb_strtolower($event['word'], 'utf8');

      switch ($event['type'])
      {
        case 'push':
          if (empty($current_words[$word]))
          {
            // First occurence of a word gets full value
            $current_words[$word] = 1;
            $score += $event['score'];
          }
          else
          {
            // Subsequent occurrences mean less and less
            $current_words[$word]++;
            $score += $event['score'] / sizeof($current_words[$word]);
          }
          break;
        case 'pop':
          if (($current_words[$word])==1)
          {
            unset($current_words[$word]);
            $score -= ($event['score']);
          }
          else
          {
            $current_words[$word]--;
            $score -= $event['score'] / sizeof($current_words[$word]);
          }
          break;
        case 'bump':
          if (!empty($event['score'])) {
            $score += $event['score'];
          }
          break;
      }

      // Close enough for government work...
      $score = round($score, 2);

      // Store the position/score entry
      $scores[$thisPos] = $score;

      // For use with debugging
      $debugWords[$thisPos] = $current_words;

      // Remove score bump
      if ($event['type'] == 'bump') {
        $score -= $event['score'];
      }
    }

    // Calculate the best score
    // Yeah, could have done this in the main event loop
    // but it's better here
    $bestScore = 0;
    foreach ($scores as $score)
    {
      if ($score > $bestScore) {
        $bestScore = $score;
      }
    }

    // Find all positions that correspond to the best score
    $positions = array();
    foreach ($scores as $pos => $score)
    {
      if ($score == $bestScore) {
        $positions[] = $pos;
      }
    }

    if (sizeof($positions) > 1)
    {
      // Scores are tied => do something clever to choose one
      // @todo: Actually do something clever here
      $pos = $positions[0];
    }
    else
    {
      $pos = $positions[0];
    }

    // Extract the excerpt from the position, (pre|ap)pend the (pre|suf)fix
    $excerpt = mb_substr($text, $pos, $length, 'utf8');
    if ($pos > 0) {
      $excerpt = $prefix . $excerpt;
    }
    if ($pos + $length < mb_strlen($text, 'utf8')) {
      $excerpt .= $suffix;
    }

    return $excerpt;
  }

  public static function loadFunction($function)
  {
    $file = dirname(__FILE__).'/vendor/utf8/'. $function .'.php';

    if (!is_file($file))
    {
      return false;
    }

    include_once($file);
    return true;
  }
}
