<?php

class IceWebDebugLogger extends sfWebDebugLogger
{
  /**
   * Listens for the context.load_factories event.
   *
   * @param sfEvent $event
   */
  public function listenForLoadFactories(sfEvent $event)
  {
    if (substr(sfConfig::get('sf_web_debug_web_dir'), 0, 7) == 'http://')
    {
      $path = sfConfig::get('sf_web_debug_web_dir').'/images';

      $this->webDebug = new $this->webDebugClass($this->dispatcher, $this, array(
        'image_root_path'    => $path,
        'request_parameters' => $event->getSubject()->getRequest()->getParameterHolder()->getAll(),
      ));
    }
    else
    {
      parent::listenForLoadFactories($event);
    }
  }
}