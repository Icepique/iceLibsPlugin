<?php

abstract class IceAjaxAction extends sfAction
{
  abstract protected function getObject(sfWebRequest $request);

  public function execute($request)
  {
    // In development, for non-ajax requests, show the HTML output rather than the JSON one
    if (SF_ENV == 'dev' && !$this->getRequest()->isXmlHttpRequest())
    {
      $this->getRequest()->setRequestFormat('html');
    }

    // Turning off the Symfony debug toolbar
    sfConfig::set('sf_web_debug', false);

    // Do we have an object to work with?
    $object = $this->getObject($request);

    $section = $request->getParameter('section');
    $page = $request->getParameter('page');

    $template = str_replace(' ', '', ucwords(str_replace('-', ' ', $section) .' '. $page));
    $method = 'execute'. $template;

    if ($section == 'partial')
    {
      return $this->renderPartial($this->getModuleName() .'/'. $page, array('object' => $object));
    } 
    else if ($section == 'component')
    {
      return $this->renderComponent($this->getModuleName(), $page, array('object' => $object));
    }

    $this->object = $object;

    return $this->$method($request, $template);
  }

  protected function success($fastcgi_finish_request = false)
  {
    $json = $this->json(array('Success' => true));

    if ($fastcgi_finish_request === true)
    {
      echo $json;
      fastcgi_finish_request();
    }
    else
    {
      $this->output($json);
    }
  }

  protected function error($title, $message, $fastcgi_finish_request = false)
  {
    $json = $this->json(array(
      'Error' => array('Title' => $title, 'Message' => $message)
    ));

    if ($fastcgi_finish_request === true)
    {
      echo $json;
      fastcgi_finish_request();
    }
    else
    {
      $this->output($json);
    }
  }

  protected function json($data)
  {
    $json = json_encode($data);

    if (SF_ENV == 'dev' && !$this->getRequest()->isXmlHttpRequest())
    {
      $this->getRequest()->setRequestFormat('html');

      $this->getContext()->getConfiguration()->loadHelpers('Partial');
      $json = get_partial('ajax/json', array('data' => $data));
    }
    else
    {
      $this->getRequest()->setRequestFormat('json');
      $this->getResponse()->setHttpHeader('Content-type', 'application/json');
      if (strlen($json) < 4028)
      {
        $this->getResponse()->setHttpHeader('X-JSON', $json);
      }
    }

    return $json;
  }

  protected function output($text)
  {
    if (is_array($text))
    {
      $text = $this->json($text);
    }

    $this->renderText($text);
    return sfView::NONE;
  }

  protected function loadHelpers($helpers)
  {
    $configuration = sfProjectConfiguration::getActive();
    $configuration->loadHelpers($helpers);
  }

  protected function __($string, $args = array(), $catalogue = 'messages')
  {
    return $this->getContext()->getI18n()->__($string, $args, $catalogue);
  }
}
