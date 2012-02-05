<?php

/**
 * Represents a Propel object host-aware route.
 *
 * Example usage:
 *
 *     dashboard:
 *       url:   :username.%APP_DOMAIN%/dashboard/:section
 *       class: IceHostAwarePropelRoute
 *       param: { module: dashboard, action: showSection }
 *
 * The `%APP_DOMAIN%` token will be replaced by the return value of a call to
 * `sfConfig::get('app_host')`. You can interpolate any config value into the
 * `url` value in this way.
 *
 * @package iceLibsPlugin
 *
 * @author Kris Wallsmith <kris.wallsmith@symfony-project.com>
 * @author Kiril Angov <kiril.angov@icepique.com>
 */
class IceHostAwarePropelRoute extends IcePropelRoute
{
  /**
   * @see sfRequestRoute
   *
   * @param $pattern
   * @param array $defaults
   * @param array $requirements
   * @param array $options
   */
  public function __construct($pattern, array $defaults = array(), array $requirements = array(), array $options = array())
  {
    if ('/' != $pattern[0])
    {
      list($host, $pattern) = explode('/', $pattern, 2);

      $options['host_route'] = $this->createHostRoute($host, $defaults, $requirements, $options);
    }

    parent::__construct($pattern, $defaults, $requirements, $options);
  }

  /**
   * @see sfRequestRoute
   *
   * @param $url
   * @param array $context
   *
   * @return array|boolean
   */
  public function matchesUrl($url, $context = array())
  {
    if (isset($this->requirements['sf_host']) && $this->requirements['sf_host'] != $context['host'])
    {
      return false;
    }

    if (false === $parameters = parent::matchesUrl($url, $context))
    {
      return false;
    }

    if (isset($this->options['host_route']))
    {
      /** @var $host_route sfRoute */
      $host_route = $this->options['host_route'];

      if (false === $hostParameters = $host_route->matchesUrl('/'.$context['host'], $context))
      {
        // host does not match
        return false;
      }

      $parameters = array_merge($parameters, $hostParameters);
    }

    return $parameters;
  }

  /**
   * @see sfRequestRoute
   *
   * @param $params
   * @param array $context
   *
   * @return boolean
   */
  public function matchesParameters($params, $context = array())
  {
    if (!isset($this->options['host_route']))
    {
      return parent::matchesParameters($params, $context);
    }

    $hostParams = $this->extractHostParams($params);

    /** @var $host_route sfRoute */
    $host_route = $this->options['host_route'];

    return parent::matchesParameters($params, $context) && $host_route->matchesParameters($hostParams, $context);
  }

  /**
   * @see sfRequestRoute
   *
   * @param $params
   * @param array $context
   * @param boolean $absolute
   *
   * @return string
   */
  public function generate($params, $context = array(), $absolute = false)
  {
    if (!isset($this->options['host_route']))
    {
      $url = parent::generate($params, $context, $absolute);

      if (isset($this->requirements['sf_host']) && $this->requirements['sf_host'] != @$context['host'])
      {
        // apply the required host
        $protocol = !empty($context['is_secure']) ? 'https' : 'http';
        $url = $protocol .'://'. $this->requirements['sf_host'] . $url;
      }

      return $url;
    }

    $hostParams = $this->extractHostParams($params);

    /** @var $host_route sfRoute */
    $host_route = $this->options['host_route'];

    /**
     * Special case for :subdomain
     */
    if (in_array(':subdomain', $host_route->getVariables()))
    {
      if (!isset($hostParams['subdomain']) && !empty($context['host']))
      {
        $parts = explode('.', $context['host']);
        $hostParams['subdomain'] = $parts[0];
      }
    }

    if (isset($this->requirements['sf_host']) && $this->requirements['sf_host'] != $context['host'])
    {
      $host = $this->requirements['sf_host'];
    }
    else
    {
      $host = $host_route->generate($hostParams, $context, false);
    }

    $protocol = isset($context['is_secure']) && $context['is_secure'] ? 'https' : 'http';
    $prefix = isset($context['prefix']) ? $context['prefix'] : '';
    $uri = parent::generate($params, $context, false);

    // Let's make sure %APP_DOMAIN% gets properly replaced
    $host = str_replace('%APP_DOMAIN%', sfConfig::get('app_domain'), $host);

    return $protocol.':/'.$host.$prefix.$uri;
  }

  /**
   * Returns the internal route used for inspecting and generating host values.
   *
   * @return sfRoute The internal route
   */
  public function getHostRoute()
  {
    return isset($this->options['host_route']) ? $this->options['host_route'] : null;
  }

  /**
   * Returns a new route object for inspecting and generating the host.
   *
   * @param string $pattern      The host pattern
   * @param array  $defaults     All defaults for the current route
   * @param array  $requirements All requirements for the current route
   * @param array  $options      All options for the current route
   *
   * @return sfRoute
   */
  protected function createHostRoute($pattern, $defaults, $requirements, $options)
  {
    $filteredDefaults = array();
    $filteredRequirements = array();

    // this temporary route is just for extracting variables from the pattern
    $tmp = new sfRoute($pattern);

    foreach (array_keys($tmp->getVariables()) as $name)
    {
      if (isset($defaults[$name]))
      {
        $filteredDefaults[$name] = $defaults[$name];
      }

      if (isset($requirements[$name]))
      {
        $filteredRequirements[$name] = $requirements[$name];
      }
    }

    return new sfRoute($pattern, $filteredDefaults, $filteredRequirements, $options);
  }

  /**
   * Removes parameters use by the host route from the supplied array and returns them.
   *
   * @param array $params All parameters for the current route
   *
   * @return array An array of parameters for the internal host route
   */
  protected function extractHostParams(& $params)
  {
    /** @var $host_route sfRoute */
    $host_route = $this->options['host_route'];

    $hostParams = array();
    foreach (array_keys($host_route->getVariables()) as $name)
    {
      if (isset($params[$name]))
      {
        $hostParams[$name] = $params[$name];
        unset($params[$name]);
      }
    }

    return $hostParams;
  }
}
