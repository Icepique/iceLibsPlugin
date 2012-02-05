<?php

/**
 * IceSessionStorage based on HandlerSocket session handler
 *
 * Table schema:
 * CREATE TABLE `session` (
 *   `id` varchar(32) NOT NULL DEFAULT '',
 *   `data` TEXT NULL DEFAULT NULL,
 *   `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
 *    PRIMARY KEY (id),
 *    KEY updated_at (updated_at)
 * ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;
 */
class IceHandlerSocketSessionStorage extends sfSessionStorage
{
  /**
   * Default Values
   */
  const DEFAULT_HOST    = null;
  const DEFAULT_PORT    = IceHandlerSocket::PORT_WRITE;
  const DEFAULT_DBNAME  = 'icepique_com';
  const DEFAULT_DBTABLE = 'session';

  /**
   * Database fileds and index
   */
  const DB_FIELDS = 'id,data,updated_at';
  const DB_GC_INDEX = 'updated_at';

  /**
   * HandlerSocket object
   */
  protected $_hs;

  /**
   * HandlerSocket index number
   */
  private $_hsIndex = 1;

  /**
   * Stores session data results
   */
  private $_fields = array();

  /**
   * Class constructor.
   *
   * @see initialize()
   * @param array $options
   *
   * @return \IceHandlerSocketSessionStorage
   */
  public function __construct($options = array())
  {
    try
    {
      parent::__construct($options);
    }
    catch (Exception $e)
    {
      // Stop the session so that we can fallback to files
      try
      {
        session_destroy();
      }
      catch (Exception $e) { ; }

      ini_set('session.save_handler', 'files');
      ini_set('session.save_path', '/www/tmp');

      parent::__construct($options);
    }
  }

  /**
   * Desstructor
   *
   * @return void
   */
  public function __destruct()
  {
    session_write_close();
  }

  /**
   * Initialize HandlerSocket.
   *
   * @param array $options associative array of options
   * @return void
   */
  public function initialize($options = null)
  {
    if (!extension_loaded('handlersocket'))
    {
      throw new Exception('The handlersocket extension must be loaded for using session !');
    }

    $session_id = isset($_POST['_session_id']) ? $_POST['_session_id'] : @$_GET['_session_id'];

    if (!empty($session_id))
    {
      session_id($session_id);
    }

    // Merge the options with the ones specified in factory.yml
    $this->_options = sfToolkit::arrayDeepMerge(array('server' => array(
      'host' => self::DEFAULT_HOST, 'port' => self::DEFAULT_PORT,
      'dbname' => self::DEFAULT_DBNAME, 'dbtable' => self::DEFAULT_DBTABLE
    )), $options);

    // disable auto_start
    $options['auto_start'] = false;

    parent::initialize($options);

    // use this object as the session handler
    session_set_save_handler(
      array($this, 'sessionOpen'),
      array($this, 'sessionClose'),
      array($this, 'sessionRead'),
      array($this, 'sessionWrite'),
      array($this, 'sessionDestroy'),
      array($this, 'sessionGC')
    );

    // start our session
    session_start();
  }

  /**
   * Open Session
   *
   * @param string $save_path
   * @param string $name
   *
   * @return true
   */
  public function sessionOpen($save_path, $name)
  {
    // Load HandlerSocket server
    $this->_hs = new IceHandlerSocket($this->_options['server']['host'], $this->_options['server']['port']);

    // Open HandlerSocket index
    if (!($this->_hs->openIndex($this->_hsIndex, $this->_options['server']['dbname'], $this->_options['server']['dbtable'], IceHandlerSocket::PRIMARY, self::DB_FIELDS)))
    {
      throw new Exception('The HandlerSocket database specified in the options does not exist.');
    }

    return true;
  }

  /**
   * Close Session
   *
   * @return true
   */
  public function sessionClose()
  {
    return true;
  }

  /**
   * Read session data
   *
   * @param string $id
   * @return string
   */
  public function sessionRead($id)
  {
    $retval = $this->_hs->executeSingle($this->_hsIndex, '=', array($id), 1, 0);

    if (isset($retval[0], $retval[0][1]))
    {
      $this->_fields['id'] = $retval[0][0];
      $this->_fields['data'] = '';
      $this->_fields['updated_at'] = $retval[0][2];

      return $retval[0][1];
    }
    else
    {
      return '';
    }
  }

  /**
   * Write session data
   *
   * @param string $id
   * @param string $data
   * @return true
   */
  public function sessionWrite($id, $data)
  {
    if (isset($this->_fields['id']) && $this->_fields['id'] != $id)
    {
      $this->_fields = array();
    }

    if (empty($this->_fields))
    {
      $this->_hs->executeInsert($this->_hsIndex, array($id, $data, date('Y-m-d H:i:s')));
    }
    else
    {
      $this->_hs->executeUpdate($this->_hsIndex, '=', array($id), array($id, $data, date('Y-m-d H:i:s')), 1, 0);
    }

    return true;
  }

  /**
   * Regenerates id that represents this storage.
   *
   * @param  boolean $destroy Destroy session when regenerating?
   * @return boolean True if session regenerated, false if error
   */
  public function sessionRegenerate($destroy = false)
  {
    if (self::$sessionIdRegenerated)
    {
      return false;
    }

    $currentId = session_id();

    parent::regenerate($destroy);

    $newId = session_id();
    $this->read($newId);

    return $this->write($newId, $this->read($currentId));
  }

  /**
   * Destroy session
   *
   * @param string $id
   * @return true
   */
  public function sessionDestroy($id)
  {
    $this->_hs->executeDelete($this->_hsIndex, '=', array($id), 1, 0);

    return true;
  }

  /**
   * Garbage Collection
   *
   * @param int $maxlifetime
   * @return true
   */
  public function sessionGC($maxlifetime)
  {
    $time = date('Y-m-d H:i:s', strtotime("- $maxlifetime seconds"));
    $index = $this->_hsIndex + 1;

    $this->_hs->openIndex($index, $this->_options['server']['dbname'], $this->_options['server']['dbtable'], self::DB_GC_INDEX, '');
    $this->_hs->executeDelete($index, '<', array($time), 1000, 0);

    return true;
  }

  /**
   * Executes the shutdown procedure.
   */
  public function shutdown()
  {
    parent::shutdown();
  }
}
