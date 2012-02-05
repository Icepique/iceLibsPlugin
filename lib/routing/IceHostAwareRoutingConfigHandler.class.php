<?php

/**
 * Adds interpolation of config values to some rules' url parameter.
 *
 * @package iceLibsPlugin
 *
 * @author Kris Wallsmith <kris.wallsmith@symfony-project.com>
 * @author Kiril Angov <kiril.angov@symfony-project.com>
 */
class IceHostAwareRoutingConfigHandler extends sfRoutingConfigHandler
{
  /**
   * @see sfRoutingConfigHandler
   */
  protected function parse($configFiles)
  {
    return array_map(array($this, 'filterRoute'), parent::parse($configFiles));
  }

  /**
   * Replaces config constants in the url for
   * {@link IceHostAwareRoute} and {@link IceHostAwarePropelRoute} routes.
   *
   * @param  array  $route A parsed route array
   * @return array  The filter array
   */
  protected function filterRoute($route)
  {
    list($class, $args) = $route;

    if ('IceHostAwareRoute' == $class || is_subclass_of($class, 'IceHostAwareRoute'))
    {
      $args[0] = $this->replaceConstants($args[0]);
    }
    else if ('IceHostAwarePropelRoute' == $class || is_subclass_of($class, 'IceHostAwarePropelRoute'))
    {
      $args[0] = $this->replaceConstants($args[0]);
    }

    return array($class, $args);
  }
}
