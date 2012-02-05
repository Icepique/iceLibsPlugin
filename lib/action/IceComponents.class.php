<?php

class IceComponents extends sfComponents
{
  protected function getCulture()
  {
    return $this->getUser()->getCulture();
  }

  protected function isMobileRequest()
  {
    return in_array($this->getRequest()->getRequestFormat(), array('iphone', 'mobile'));
  }

  protected function __($string, $args = array(), $catalogue = 'messages')
  {
    return $this->getContext()->getI18n()->__($string, $args, $catalogue);
  }
}