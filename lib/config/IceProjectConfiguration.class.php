<?php

class IceProjectConfiguration extends sfProjectConfiguration
{
  public function setup()
  {
    mb_language('English');
    mb_internal_encoding('UTF-8');

    iconv_set_encoding('input_encoding', 'UTF-8');
    iconv_set_encoding('output_encoding', 'UTF-8');
    iconv_set_encoding('internal_encoding', 'UTF-8');

    sfConfig::set('sf_phing_path', sfConfig::get('sf_plugins_dir').'/sfPropelORMPlugin/lib/vendor/phing');
    sfConfig::set('sf_propel_path', sfConfig::get('sf_plugins_dir').'/sfPropelORMPlugin/lib/vendor/propel');

    $this->dispatcher->connect('application.throw_exception', array('IceErrorNotifier', 'notify'));
  }
}
