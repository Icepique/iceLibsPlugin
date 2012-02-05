<?php

class Mobio
{
  /**
   * @var array
   */
  private static $_ip_addresses = array('194.12.244.114', '87.120.176.216');

  /**
   * @var string
   */
  private static $_end_point = 'http://mobio.bg/paynotify/pnsendsms.php';

  private static $_service_ids = array(
    'BG' => array(
      'autohop'   => array('1092' => '7685',  '1094' => '7686',  '1096' => '14041'),
      'com'       => array('1092' => '13007', '1094' => '13008', '1096' => '15290'),
      'company'   => array('1092' => '7701',  '1094' => '7702',  '1096' => '15291'),
      'fix'       => array('1092' => '10672'),
      'hop'       => array('1092' => '14038', '1094' => '14039', '1096' => '14040'),
      'hoplist'   => array('1092' => '7689',  '1094' => '7690'),
      'icepique'  => array('1092' => '8277'),
      'pop'       => array('1092' => '12976', '1094' => '12977', '1096' => '12975'),
      'sup'       => array('1092' => '8320', '1094' => '8321', '1096' => '15549'),
      'super'     => array('1092' => '8248', '1094' => '8249'),
      'supersexy' => array('1092' => '8251', '1094' => '8250'),
      'upg'       => array('1092' => '7679', '1094' => '7680', '1096' => '15288'),
      'upgrade'   => array('1092' => '7683', '1094' => '7684'),
      'upq'       => array('1092' => '7681', '1094' => '7682', '1096' => '15289'),

      'outdevetdve'     => array('1092' => '10246'),
      'outdevettri'     => array('1093' => '10245'),
      'outdevetchetiri' => array('1094' => '10247'),
      'outdevetpet'     => array('1095' => '10244')
    ),
    'TR' => array(),
    'GR' => array(),
    'DE' => array(),
    'ES' => array()
  );

  /**
   * @static
   *
   * @param  string|null  $remote_address
   * @return boolean
   */
  public static function isValidRequest($remote_address = null)
  {
    return !in_array(
      $remote_address !== null ? $remote_address : $_SERVER['REMOTE_ADDR'],
      self::$_ip_addresses
    );
  }

  /**
   * @static
   *
   * @param  string  $message
   * @param  array   $codes
   * @param  string  $country
   *
   * @return array
   */
  public static function getMessageParts($message, $codes = array(), $country = 'BG')
  {
    $service_ids = isset(self::$_service_ids[$country]) ?
                     self::$_service_ids[$country] :
                     self::$_service_ids['BG'];

    if (empty($codes))
    {
      $codes = array_keys($service_ids);
    }
    else if (is_string($codes))
    {
      $codes = (array) $codes;
    }

    if (preg_match('/^(\s+)?('. implode('|', $codes) .')\s?([o\d]+)(\s+)?$/iu', $message, $m))
    {
      $service_ids = isset($service_ids[$m[2]]) ? $service_ids[$m[2]] : array();
      $id = (int) str_ireplace('o', '0', $m[3]);

      return array($service_ids, $id);
    }

    return array();
  }

  /**
   * @see Mobio::sendSms()
   * @static
   *
   * @param  integer  $to
   * @param  string   $message
   * @param  integer  $service_id
   *
   * @return boolean
   */
  public static function sendPaymentNotification($to, $message, $service_id)
  {
    return self::sendSms($to, $message, $service_id);
  }

  /**
   * @static
   *
   * @param  integer  $to
   * @param  string   $message
   * @param  integer  $service_id
   *
   * @return boolean
   */
  public static function sendSms($to, $message, $service_id = null)
  {
    if (!ctype_digit($service_id))
    {
      $service_id = 10246;
    }

    $opts = array('http' =>
      array(
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => http_build_query(array(
          'tonum' => $to, 'servID' => $service_id, 'message' => $message
        ))
      )
    );
    $context  = stream_context_create($opts);
    $response = file_get_contents(self::$_end_point, false, $context);

    return !empty($response);
  }
}
