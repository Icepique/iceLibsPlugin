<?php

abstract class IceBaseTask extends sfBaseTask
{
  protected function createConfiguration($application, $env)
  {
    $configuration = parent::createConfiguration($application, $env);

    $_SERVER['PATH_TRANSLATED'] = $_SERVER['PHP_SELF']    = '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = $_SERVER['SCRIPT_NAME'] = '/index.php';

    $_SERVER['SERVER_NAME'] = sfConfig::get('app_www_domain');
    $_SERVER['HTTP_HOST']   = sfConfig::get('app_www_domain');
    $_SERVER['PATH_INFO']   = null;

    return $configuration;
  }
}