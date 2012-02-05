<?php

abstract class IceActions extends sfActions
{
  abstract protected function sendEmail($to, $subject, $body);

  public function preExecute()
  {
    parent::preExecute();

    if ('iphone' == $this->getRequest()->getRequestFormat() && $this->getRequest()->isXmlHttpRequest())
    {
      $this->setLayout(false);
    }
    else if ($this->getRequest()->getParameter('print') == 1)
    {
      $this->setLayout('layout-print');
    }
    else if ($this->getRequest()->getParameter('popup') == 1)
    {
      $this->setLayout('layout-popup');
    }
    else if ($this->getRequest()->isXmlHttpRequest())
    {
      $this->setLayout('layout-ajax');
      //$this->getRequest()->setRequestFormat('ajax');
    }
  }

  protected function loadHelpers($helpers)
  {
    $configuration = sfProjectConfiguration::getActive();
    $configuration->loadHelpers($helpers);
  }

  protected function addBreadcrumb($name, $url = null, $title = null, $is_last = false)
  {
    IceBreadcrumbs::getInstance()->addItem($name, $url, $title, $is_last);
  }

  public function prependTitle($title, $readonly = false)
  {
    if (sfConfig::get('app_title_readonly', false))
    {
      return;
    }
    sfConfig::set('app_title_readonly', $readonly);

    $response = $this->getResponse();
    $delimiter = sfConfig::get('app_title_delimiter', ' - ');
    $current_title = ($response->getTitle()) ? $response->getTitle() : $this->__(sfConfig::get('app_title'));
    $response->setTitle($title . $delimiter . $current_title, false);
  }

  protected function getCulture()
  {
    return $this->getUser()->getCulture();
  }

  protected function isMobileRequest()
  {
    return in_array($this->getRequest()->getRequestFormat(), array('iphone', 'mobile'));
  }

  /**
   * @param  string      $string
   * @param  array|null  $args
   * @param  string      $catalogue
   *
   * @return string
   */
  protected function __($string, $args = array(), $catalogue = 'messages')
  {
    return $this->getContext()->getI18n()->__($string, $args, $catalogue);
  }
}
