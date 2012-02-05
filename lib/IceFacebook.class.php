<?php
/**
 * Copyright 2011 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

require_once dirname(__FILE__)."/vendor/Facebook.class.php";

/**
 * Extends the BaseFacebook class with the intent of using
 * PHP sessions to store user ids and access tokens.
 */
class IceFacebook extends BaseFacebook
{
  /**
   * Provides the implementations of the inherited abstract
   * methods. The implementation uses PHP sessions to maintain
   * a store for user ids and access tokens.
   */
  protected static $kSupportedKeys = array('code', 'access_token', 'user_id');

  public function __construct($conf = array())
  {
    parent::__construct($conf);

    // Check if the user logged in with the Javascript API
    if (!empty($_COOKIE['fbs_'. $this->getAppId()]))
    {
      parse_str(trim($_COOKIE['fbs_'. $this->getAppId()], '"'), $cookies);
      $this->setAccessToken($cookies['access_token']);
      $this->setPersistentData('access_token', $cookies['access_token']);
    }
  }

  protected function setPersistentData($key, $value)
  {
    if (!in_array($key, self::$kSupportedKeys))
    {
      return;
    }

    $session_var_name = $this->constructSessionVariableName($key);
    $_SESSION[$session_var_name] = $value;
  }

  protected function getPersistentData($key, $default = false)
  {
    if (!in_array($key, self::$kSupportedKeys))
    {
      return $default;
    }

    $session_var_name = $this->constructSessionVariableName($key);

    return isset($_SESSION[$session_var_name]) ? $_SESSION[$session_var_name] : $default;
  }

  public function clearAllPersistentData()
  {
    foreach (self::$kSupportedKeys as $key)
    {
      $session_var_name = $this->constructSessionVariableName($key);
      unset($_SESSION[$session_var_name]);
    }
  }

  protected function constructSessionVariableName($key)
  {
    return implode('_', array('fb', $this->getAppId(), $key));
  }

  /**
   * Clear the data with $key from the persistent storage
   *
   * @param string $key
   * @return void
   */
  protected function clearPersistentData($key)
  {
    $session_var_name = $this->constructSessionVariableName($key);
    unset($_SESSION[$session_var_name]);
  }
}
