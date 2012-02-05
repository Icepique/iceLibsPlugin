<?php

class IceApplicationConfiguration extends sfApplicationConfiguration
{
  public function initialize()
  {
    parent::initialize();

    $this->dispatcher->connect('request.filter_parameters', array($this, 'filterRequestParameters'));
    $this->dispatcher->connect('view.configure_format', array($this, 'configureMobileFormat'));
  }

  public function initConfiguration()
  {
    parent::initConfiguration();

    if ($file = $this->getConfigCache()->checkConfig('config/gatekeeper.yml', true))
    {
      include($file);
    }
  }

  public function filterRequestParameters(sfEvent $event, $parameters)
  {
    /**
     * @param  $request  sfWebRequest
     */
    $request = $event->getSubject();

    if ('m.' == substr($_SERVER['HTTP_HOST'], 0, 2))
    {
      if (preg_match('#(Mobile/.+Safari|AppleWebKit/)#i', $request->getHttpHeader('User-Agent')))
      {
        $request->setRequestFormat('iphone');
      }
      else
      {
        $request->setRequestFormat('mobile');
      }

      unset($_COOKIE);
    }
    else if ('off' == $request->getParameter('mobile', $request->getCookie('mobile')))
    {
      @setcookie('mobile', 'off');
    }

    return $parameters;
  }

  public function configureMobileFormat(sfEvent $event)
  {
    if ('iphone' == $event['format'])
    {
      // add some CSS, stylesheet
    }
    else if ('mobile' == $event['format'])
    {
      // add some CSS, stylesheet
    }
  }
}