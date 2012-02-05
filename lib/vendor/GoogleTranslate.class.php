<?php

/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jose da Silva <jose@josedasilva.net>
 * @author Kiril Angov <kiril@icepique.com>
 * 
 * @since 2009/11/18
 * @version 0.7.4
 *
 * <code>
 * <?php
 *   require_once("GoogleTranslate.class.php");
 *   $google = new GoogleTranslate();
 *
 *   try {
 *     echo $google->english_to_german("hello world");
 *   }
 *   catch (GoogleTranslateException $e)
 *   {
 *     echo $e->getMessage();
 *   }
 * ?>
 * </code>
 */

/**
 * Exception class for GoogleTranslated Exceptions
 */
class GoogleTranslateException extends Exception
{
  public function __construct($string) {
    parent::__construct($string, 0);
  }

  public function __toString() {
    return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
  }
}

class GoogleTranslate
{
 /**
  * Google Translate(TM) Api endpoint
  *
  * @access private
  * @var string
  */
  private $url = "http://ajax.googleapis.com/ajax/services/language/translate";

 /**
  * Google Translate (TM) Api Version
  * @access private
  * @var string
  */
  private $api_version = "1.0";

 /**
  * Comunication Transport Method. Available: http / curl
  *
  * @access private
  * @var string
  */
  private $request_type = "curl";

 /**
  * Path to available languages file
  *
  * @access private
  * @var string
  */
  private $available_languages_file  = "google_translate_languages.ini";

 /**
  * Holder to the parse of the ini file
  * @access private
  * @var array
  */
  private $available_languages = array();

 /**
  * Google Translate api key
  *
  * @access private
  * @var string
  */
  private $api_key = null;

 /**
  * Constructor sets up {@link $available_languages}
  */
  public function __construct()
  {
    $this->available_languages = parse_ini_file(dirname(__FILE__) .'/../../data/'. $this->available_languages_file);
  }

 /**
  * URL Formater to use on request
  *
  * @param array $lang_pair
  * @param array $string
  *
  * @return string $url
  */
  private function urlFormat($lang_pair,$string)
  {
    $parameters = array(
      "v" => $this->api_version,
      "q" => $string,
      "langpair"=> implode("|",$lang_pair)
    );

    if(!empty($this->api_key))
    {
      $parameters["key"] = $this->api_key;
    }

    $url = "";

    foreach($parameters as $k=>$p)
    {
      $url .= $k."=".urlencode($p)."&";
    }

    return $url;
  }

 /**
  * Define the request type
  *
  * @param string $request_type
  * 
  * @return boolean
  */
  public function setRequestType($request_type = 'curl')
  {
    if (!empty($request_type))
    {
      $this->request_type = $request_type;
      return true;
    }

    return false;
  }

 /**
  * Define the Google Translate Api Key
  *
  * @param string $api_key
  *
  * @return boolean
  */
  public function setApiKey($api_key)
  {
    if (!empty($api_key))
    {
      $this->api_key = $api_key;
      return true;
    }

    return false;
  }

 /**
  * Query the Google(TM) endpoint
  *
  * @param  array  $lang_pair
  * @param  array  $string
  *
  * @return string
  */
  public function query($lang_pair,$string)
  {
    $query_url = $this->urlFormat($lang_pair,$string);
    $response = $this->{"request".ucwords($this->request_type)}($query_url);

    return $response;
  }

 /**
  * Query Wrapper for Http Transport
  *
  * @access  private
  *
  * @param   string  $url
  * @return  string  $response
  */
  private function requestHttp($fields)
  {
    return GoogleTranslate::evalResponse(json_decode(file_get_contents($this->url ."?". $fields)));
  }

 /**
  * Query Wrapper for Curl Transport
  *
  * @param string $url
  *
  * @return string
  */
  private function requestCurl($fields)
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_REFERER, !empty($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "");
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    $body = curl_exec($ch);
    curl_close($ch);

    return GoogleTranslate::evalResponse(json_decode($body));
  }

 /**
  * Response Evaluator, validates the response. Throws an exception on error
  *
  * @param  string  $json_response
  *
  * @throws GoogleTranslateException
  * @return string
  */
  private function evalResponse($json_response)
  {
    if ($json_response)
    switch($json_response->responseStatus)
    {
      case 200:
        return $json_response->responseData->translatedText;
        break;
      default:
        throw new GoogleTranslateException("Unable to perform Translation: ". $json_response->responseDetails);
      break;
    }
  }

 /**
  * Validates if the language pair is valid. Throws an exception on error

  * @param  array  $languages
  *
  * @return array Array with formated languages pair
  */
  private function isValidLanguage($languages)
  {
    $language_list = $this->available_languages;

    $languages = array_map("strtolower", $languages);
    $language_list_v = array_map("strtolower", array_values($language_list));
    $language_list_k = array_map("strtolower", array_keys($language_list));
    $valid_languages = false;

    if (true == in_array($languages[0], $language_list_v) && true == in_array($languages[1],$language_list_v))
    {
      $valid_languages = true;
    }

    if (false === $valid_languages && true == in_array($languages[0],$language_list_k) & true == in_array($languages[1],$language_list_k))
    {
      $languages = array($language_list[strtoupper($languages[0])],$language_list[strtoupper($languages[1])]);
      $valid_languages = true;
    }

    if (false === $valid_languages)
    {
      throw new GoogleTranslateException("Unsupported languages (". implode(',', $languages) .")");
    }

    return $languages;
  }

 /**
  * Magic method to understande translation comman. Evaluates methods like language_to_language
  *
  * @param  string  $name
  * @param  Array   $args
  *
  * @return string  $response Translated Text
  */
  public function __call($name, $args)
  {
    $languages_list  = explode("_to_", strtolower($name));
    $languages = $this->isValidLanguage($languages_list);

    $string = $args[0];
    $translated = null;

    if (mb_strlen($string, 'utf8') > 500)
    {
      $i = 0;
      while ($i < mb_strlen($string, 'utf8'))
      {
        $limit = mb_strripos(mb_strcut($string, $i, 500, 'utf8'), '.', 0, 'utf8');
        if (!$limit) $limit = mb_strlen($string, 'utf8');

        $part = mb_strcut($string, $i, $limit, 'utf8');
        $translated .= $this->query($languages, $part);

        $i = $limit;
      }
    }
    else
    {
      $translated = $this->query($languages, $string);
    }

    return $translated;
  }
}
