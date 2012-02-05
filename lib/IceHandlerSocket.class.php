<?php

/**
 * @see https://code.google.com/p/php-handlersocket/wiki/Classes
 */
class IceHandlerSocket extends HandlerSocket
{
  /**
	 * Constant used to request a READ connection
   * @var string
	 */
	const CONNECTION_READ = 'read';

  /**
   * @var int
   */
  const PORT_READ = 9998;

  /**
	 * Constant used to request a WRITE connection
   * @var string
	 */
	const CONNECTION_WRITE = 'write';

  /**
   * @var int
   */
  const PORT_WRITE = 9999;

  /**
   * @param string $host
   * @param int $port
   * @param array $options
   */
  public function __construct($host = null, $port = self::PORT_READ, $options = array())
  {
    if (!$host)
    {
      $host = ($port == self::PORT_READ) ? 'ice-mysql-slave' : 'ice-mysql-master';
    }

    if (!in_array($port, array(self::PORT_READ, self::PORT_WRITE)))
    {
      $port = self::PORT_WRITE;
    }

    parent::__construct($host, $port, $options);
  }
}
