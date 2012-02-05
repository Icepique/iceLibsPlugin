<?php

class IceMemcacheCache extends sfMemcacheCache
{
  /** @var  $memcache  Memcache */

  /**
   * @param  array  $options
   */
  public function __construct($options = array())
  {
    $options = array_merge(
      array(
        'prefix' => 'icepique',
        'host' => 'ice-memcached'
      ),
      $options
    );

    $this->initialize($options);
  }

  /**
   * @param  string   $key
   * @param  mixed    $data
   * @param  boolean  $flag
   * @param  integer  $lifetime
   *
   * @return boolean
   */
  public function set($key, $data, $flag = false, $lifetime = null)
  {
    /**
     * If we pass the lifetime in the third parameter by mistake
     */
    if ($lifetime === null && ctype_digit($flag))
    {
      $lifetime = (int) $flag;
    }

    if (!$success = parent::set($key, $data, $lifetime))
    {
      if ($logger = sfContext::getInstance()->getLogger())
      {
        $logger->alert('Could not set memcache key for '. $key);
      }
    }

    return $success;
  }

  /**
   * @see  IceMemcacheCache::set()  An alias for IceMemcacheCache::set()
   *
   * @param  string  $key
   * @param  mixed    $data
   * @param  boolean  $flag
   * @param  integer  $lifetime
   *
   * @return boolean
   */
  public function replace($key, $data, $flag = false, $lifetime = null)
  {
    return $this->set($key, $data, $flag, $lifetime);
  }

  /**
   * @see http://abhinavsingh.com/blog/2009/12/how-to-use-locks-for-assuring-atomic-operation-in-memcached/
   *
   * @param  string  $key
   * @param  integer $i
   *
   * @return integer
   */
  public function increment($key, $i = 1)
  {
    // The operation will fail if the key does not exist
    if (false === $k = $this->memcache->increment($key, $i))
    {
      // Another request already created the key
      if (false === $this->memcache->add($key, $i, 0, 0))
      {
        $k = $this->memcache->increment($key, $i);
      }
      else
      {
        $k = $i;
      }
    }

    return $k;
  }

  /**
   * @see http://abhinavsingh.com/blog/2009/12/how-to-use-locks-for-assuring-atomic-operation-in-memcached/
   *
   * @param  string  $key
   * @param  integer $i
   *
   * @return integer
   */
  public function decrement($key, $i = 1)
  {
    // The operation will fail if the key does not exist
    if (false === $k = $this->memcache->decrement($key, $i))
    {
      // Another request already created the key
      if (false === $this->memcache->add($key, $i, 0, 0))
      {
        $k = $this->memcache->decrement($key, $i);
      }
      else
      {
        $k = 0;
      }
    }

    return $k;
  }

  /**
   * @param  string  $key
   * @return boolean
   */
  public function delete($key)
  {
    return $this->remove($key);
  }
}
