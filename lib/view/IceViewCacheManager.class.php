<?php

class IceViewCacheManager extends sfViewCacheManager
{
  /**
   * Gets a partial template from the cache.
   *
   * @param  string $module    The module name
   * @param  string $action    The action name
   * @param  string $cacheKey  The cache key
   *
   * @return string The cache content
   */
  public function getPartialCache($module, $action, $cacheKey)
  {
    $uri = $this->getPartialUri($module, $action, $cacheKey);

    if (!$this->isCacheable($uri))
    {
      return null;
    }

    // retrieve content from cache
    $cache = $this->get($uri);

    if (null === $cache)
    {
      return null;
    }

    $cache = function_exists('igbinary_unserialize') ? igbinary_unserialize($cache) : unserialize($cache);
    $content = $cache['content'];
    $this->context->getResponse()->merge($cache['response']);

    if (sfConfig::get('sf_web_debug'))
    {
      $content = $this->dispatcher->filter(new sfEvent($this, 'view.cache.filter_content', array('response' => $this->context->getResponse(), 'uri' => $uri, 'new' => false)), $content)->getReturnValue();
    }

    return $content;
  }
  
  /**
   * Sets an action template in the cache.
   *
   * @param  string $module    The module name
   * @param  string $action    The action name
   * @param  string $cacheKey  The cache key
   * @param  string $content   The content to cache
   *
   * @return string The cached content
   */
  public function setPartialCache($module, $action, $cacheKey, $content)
  {
    $uri = $this->getPartialUri($module, $action, $cacheKey);
    if (!$this->isCacheable($uri))
    {
      return $content;
    }

    if (function_exists('igbinary_serialize'))
    {
      $cache = igbinary_serialize(array('content' => $content, 'response' => $this->context->getResponse()));
    }
    else
    {
      $cache = serialize(array('content' => $content, 'response' => $this->context->getResponse()));
    }
    $saved = $this->set($cache, $uri);

    if ($saved && sfConfig::get('sf_web_debug'))
    {
      $content = $this->dispatcher->filter(new sfEvent($this, 'view.cache.filter_content', array('response' => $this->context->getResponse(), 'uri' => $uri, 'new' => true)), $content)->getReturnValue();
    }

    return $content;
  }
  
  /**
   * Sets an action template in the cache.
   *
   * @param  string $uri                The internal URI
   * @param  string $content            The content to cache
   * @param  string $decoratorTemplate  The view attribute holder to cache
   *
   * @return string The cached content
   */
  public function setActionCache($uri, $content, $decoratorTemplate)
  {
    if (!$this->isCacheable($uri) || $this->withLayout($uri))
    {
      return $content;
    }

    if (function_exists('igbinary_serialize'))
    {
      $cache = igbinary_serialize(array('content' => $content, 'decoratorTemplate' => $decoratorTemplate, 'response' => $this->context->getResponse()));
    }
    else
    {
      $cache = serialize(array('content' => $content, 'decoratorTemplate' => $decoratorTemplate, 'response' => $this->context->getResponse()));
    }
    $saved = $this->set($cache, $uri);

    if ($saved && sfConfig::get('sf_web_debug'))
    {
      $content = $this->dispatcher->filter(new sfEvent($this, 'view.cache.filter_content', array('response' => $this->context->getResponse(), 'uri' => $uri, 'new' => true)), $content)->getReturnValue();
    }

    return $content;
  }
  
  /**
   * Gets an action template from the cache.
   *
   * @param  string $uri  The internal URI
   *
   * @return array  An array composed of the cached content and the view attribute holder
   */
  public function getActionCache($uri)
  {
    if (!$this->isCacheable($uri) || $this->withLayout($uri))
    {
      return null;
    }

    // retrieve content from cache
    $cache = $this->get($uri);

    if (null === $cache)
    {
      return null;
    }

    $cache = function_exists('igbinary_unserialize') ? igbinary_unserialize($cache) : unserialize($cache);
    $content = $cache['content'];
    $cache['response']->setEventDispatcher($this->dispatcher);
    $this->context->getResponse()->copyProperties($cache['response']);

    if (sfConfig::get('sf_web_debug'))
    {
      $content = $this->dispatcher->filter(new sfEvent($this, 'view.cache.filter_content', array('response' => $this->context->getResponse(), 'uri' => $uri, 'new' => false)), $content)->getReturnValue();
    }

    return array($content, $cache['decoratorTemplate']);
  }
  
  /**
   * Sets a page in the cache.
   *
   * @param string $uri  The internal URI
   */
  public function setPageCache($uri)
  {
    if (sfView::RENDER_CLIENT != $this->controller->getRenderMode())
    {
      return;
    }

    // save content in cache
    $cache = function_exists('igbinary_serialize') ? igbinary_serialize($this->context->getResponse()) : serialize($this->context->getResponse());
    $saved = $this->set($cache, $uri);

    if ($saved && sfConfig::get('sf_web_debug'))
    {
      $content = $this->dispatcher->filter(new sfEvent($this, 'view.cache.filter_content', array('response' => $this->context->getResponse(), 'uri' => $uri, 'new' => true)), $this->context->getResponse()->getContent())->getReturnValue();

      $this->context->getResponse()->setContent($content);
    }
  }
  
  /**
   * Gets a page from the cache.
   *
   * @param  string $uri  The internal URI
   *
   * @return string The cached page
   */
  public function getPageCache($uri)
  {
    $retval = $this->get($uri);

    if (null === $retval)
    {
      return false;
    }

    $cachedResponse = function_exists('igbinary_unserialize') ? igbinary_unserialize($retval) : unserialize($retval);
    $cachedResponse->setEventDispatcher($this->dispatcher);

    if (sfView::RENDER_VAR == $this->controller->getRenderMode())
    {
      $this->controller->getActionStack()->getLastEntry()->setPresentation($cachedResponse->getContent());
      $this->context->getResponse()->setContent('');
    }
    else
    {
      $this->context->setResponse($cachedResponse);

      if (sfConfig::get('sf_web_debug'))
      {
        $content = $this->dispatcher->filter(new sfEvent($this, 'view.cache.filter_content', array('response' => $this->context->getResponse(), 'uri' => $uri, 'new' => false)), $this->context->getResponse()->getContent())->getReturnValue();

        $this->context->getResponse()->setContent($content);
      }
    }

    return true;
  }
}
