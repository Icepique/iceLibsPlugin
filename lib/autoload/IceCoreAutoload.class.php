<?php

require_once '/www/libs/symfony-1.4.x/lib/autoload/sfCoreAutoload.class.php';

class IceCoreAutoload extends sfCoreAutoload
{
  /**
   * Retrieves the singleton instance of this class.
   *
   * @return sfCoreAutoload A sfCoreAutoload implementation instance.
   */
  static public function getInstance()
  {
    if (!isset(self::$instance))
    {
      self::$instance = new IceCoreAutoload();
    }

    return self::$instance;
  }

  /**
   * Register sfCoreAutoload in spl autoloader.
   *
   * @return void
   */
  static public function register()
  {
    if (self::$registered)
    {
      return;
    }

    ini_set('unserialize_callback_func', 'spl_autoload_call');
    if (false === spl_autoload_register(array(IceCoreAutoload::getInstance(), 'autoload')))
    {
      throw new sfException(sprintf('Unable to register %s::autoload as an autoloading method.', get_class(self::getInstance())));
    }

    self::$registered = true;
  }

  public function getClassPath($class)
  {
    if ($class == 'IceProjectConfiguration')
    {
      return dirname(__FILE__) .'/../config/IceProjectConfiguration.class.php';
    }
    else if ($class == 'IceApplicationConfiguration')
    {
      return dirname(__FILE__) .'/../config/IceApplicationConfiguration.class.php';
    }
    else
    {
      return parent::getClassPath($class);
    }
  }
}
