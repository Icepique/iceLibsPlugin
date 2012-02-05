<?php

class IceSphinxQL
{
  /**
   * @var string The address and port of the server this client is to connect to
   */
  protected $_server = 'ice-sphinx:9306';

  /**
   * @var resource A reference to the mysql link that this client will be using
   */
  protected $_handle = false;

  /**
   * @var boolean A flag to denote whether or not this client has tried to connect and failed
   */
  protected $_failed = false;

  /**
   * @var resource A reference to the mysql result returned by a query that this client has performed
   */
  protected $_result = false;

  /**
   * @var IceSphinxQL
   */
  protected static $_instance = null;

  /**
   * Constructor
   *
   * @param  string|null  $server The address and port of a sphinx server
   */
  public function __construct($server = null)
  {
    if ($server === null) {
      return;
    }

    $this->_server = $server;
  }

  public static function getInstance()
  {
    if (self::$_instance === null)
    {
      self::$_instance = new self();
    }

    return self::$_instance;
  }

  /**
   * Used to attempt connection to the sphinx server, keeps a record of whether it failed to connect or not
   *
   * @param  integer $retries
   * @return boolean Status of the connection attempt
   */
  protected function connect($retries = 3)
  {
    if (is_resource($this->_handle)) {
      return true;
    }
    else if ($this->_failed || $this->_server === false) {
      return false;
    }

    $i = 0;

    do
    {
      $this->_handle = @mysql_connect($this->_server);

      if (false === $this->_handle)
      {
        // Record that the connection attempt was retried
        IceStats::increment(IceFunctions::gethostname().'.services.sphinx.connection.retry');

        sleep(pow(2, $i++));
      }
      else
      {
        // Record that the connection succeeded
        IceStats::increment(IceFunctions::gethostname().'.services.sphinx.connection.success');

        return true;
      }
    }
    while ($i < $retries);

    // Record that the connection failed
    IceStats::increment(IceFunctions::gethostname().'.services.sphinx.connection.error');

    return false;
  }

  /**
   * Fetch the COUNT(*) number
   *
   * @param  string  $query The query to perform
   * @return integer
   */
  public function count($query)
  {
    $_time_start = microtime(true);

    $total = 0;

    if (is_string($query) && $this->connect())
    {
      $query = preg_replace('/SELECT (.*) FROM/iu', 'SELECT COUNT(*) AS `total` FROM', $query);
      $this->_result = mysql_query($query, $this->_handle);

      // Record the query error
      IceStats::increment(IceFunctions::gethostname().'.services.sphinx.count');

      if ($this->_result === false)
      {
        // Record the query error
        IceStats::increment(IceFunctions::gethostname().'.services.sphinx.error');

        return 0;
      }

      if ($counts = $this->fetchAll($this->_result))
      foreach ($counts as $count)
      {
        $total += (int) $count['total'];
      }
    }

    // Record the response time of the count
    IceStats::timing(IceFunctions::gethostname().'.services.sphinx.response', microtime(true) - $_time_start);

    return $total;
  }

  /**
   * Perform a query
   *
   * @param  string  $query The query to perform
   *
   * @throws Exception if the mysql client returns an error
   * @return IceSphinxQL|integer
   */
  public function query($query)
  {
    $this->_result = false;
    if (is_string($query) && $this->connect())
    {
      // Record the query
      IceStats::increment(IceFunctions::gethostname().'.services.sphinx.query');

      $_time_start = microtime(true);

      // Send the query to SphinxQL
      $this->_result = mysql_query($query, $this->_handle);

      // Record the response time of the query
      IceStats::timing(IceFunctions::gethostname().'.services.sphinx.response', microtime(true) - $_time_start);

      if ($this->_result === false)
      {
        // Record the query error
        IceStats::increment(IceFunctions::gethostname().'.services.sphinx.error');

        throw new Exception("Query failed with error: ". mysql_error($this->_handle));
      }

      // If the query is an updating query, return the rows affected
      if (preg_match('/$\s+(REPLACE|UPDATE|INSERT|DELETE)\s+/', $query))
      {
        return mysql_affected_rows($this->_result);
      }
    }

    return $this;
  }

  /**
   * Fetch one row of the result set
   *
   * @return array|false The row or an error
   */
  public function fetchRow()
  {
    if ($this->_result === false) {
      return false;
    }
    else if ($arr = mysql_fetch_assoc($this->_result)) {
      return $arr;
    }

    return false;
  }

  /**
   * Fetch the whole result set
   *
   * @return array|false The results or an error
   */
  public function fetchAll()
  {
    if ($this->_result === false) {
      return false;
    }

    $ret = array();
    while ($arr = mysql_fetch_assoc($this->_result))
    {
      $ret[] = $arr;
    }

    return $ret;
  }

  /**
   * @static
   *
   * @param  string  $index
   * @return IceSphinxQuery
   */
  public static function create($index)
  {
    $s = self::getInstance();
    $s->connect();

    return new IceSphinxQuery($s, $index);
  }
}

/**
 * Class for building queries to send to Sphinx
 */
class IceSphinxQuery
{
  /**
   * @var array The indexes that are to be searched
   */
  protected $_indexes = array();

  /**
   * @var array The fields that are to be returned in the result set
   */
  protected $_fields = array();

  /**
   * @var string A string to be searched for in the indexes
   */
  protected $_search = null;

  /**
   * @var array A set of WHERE conditions
   */
  protected $_wheres = array();

  /**
   * @var array The GROUP BY field
   */
  protected $_group = null;

  /**
   * @var array The IN GROUP ORDER BY options
   */
  protected $_group_order = null;

  /**
   * @var array A set of ORDER clauses
   */
  protected $_orders = array();

  /**
   * @var integer The offset to start returning results from
   */
  protected $_offset = 0;

  /**
   * @var integer The maximum number of results to return
   */
  protected $_limit = 20;

  /**
   * @var array A set of OPTION clauses
   */
  protected $_options = array();

  /**
   * @var IceSphinxQL A reference to a IceSphinxQL object, used for the execute() function
   */
  protected $_sphinx = null;

  /**
   * @var IceMemcacheCache
   */
  protected $_memcache_cache = null;

  /**
   * Constructor
   *
   * @param  IceSphinxQL  $sphinx
   * @param  string  $index
   */
  public function __construct(IceSphinxQL $sphinx, $index)
  {
    $this->sphinx($sphinx);
    $this->addIndex($index);
  }

  /**
   * Magic method, returns the result of build().
   *
   * @return string
   */
  public function __toString()
  {
    return $this->build();
  }

  /**
   * Sets or gets the IceSphinxQL object associated with this query.
   *
   * If you pass it nothing, it'll return $this->_sphinx
   * If you pass it a IceSphinxQL object, it'll return $this
   * If you pass it anything else, it'll return false
   *
   * @param  IceSphinxQL  $sphinx
   * @return IceSphinxQuery|IceSphinxQL|false
   */
  public function sphinx($sphinx = null)
  {
    if (is_a($sphinx, 'IceSphinxQL'))
    {
      $this->_sphinx = $sphinx;

      return $this;
    }
    elseif ($sphinx === null)
    {
      return $sphinx;
    }

    return false;
  }

  /**
   * Builds the query string from the information you've given.
   *
   * @return string The resulting query
   */
  public function build()
  {
    $fields = array();
    $wheres = array();
    $orders = array();
    $options = array();
    $query = '';

    foreach ($this->_fields as $field)
    {
      if (!isset($field['field']) OR !is_string($field['field'])) {
        continue;
      }

      if (isset($field['alias']) AND is_string($field['alias'])) {
        $fields[] = sprintf("`%s` AS `%s`", $field['field'], $field['alias']);
      } else {
        $fields[] = sprintf("`%s`", $field['field']);
      }
    }
    unset($field);

    if (is_string($this->_search)) {
      $wheres[] = sprintf("MATCH('%s')", addslashes($this->_search));
    }

    foreach ($this->_wheres as $where) {
      $wheres[] = sprintf("`%s` %s %s", $where['field'], $where['operator'], $where['value']);
    }
    unset($where);

    foreach ($this->_orders as $order) {
      $orders[] = sprintf("`%s` %s", $order['field'], $order['sort']);
    }
    unset($order);

    foreach ($this->_options as $option) {
      $options[] = sprintf("%s=%s", $option['name'], $option['value']);
    }
    unset($option);

    $query .= sprintf('SELECT %s ', count($fields) ? implode(', ', $fields) : '*');
    $query .= sprintf('FROM `%s` ', implode(',', $this->_indexes));

    if (count($wheres) > 0) {
      $query .= sprintf('WHERE %s ', implode(' AND ', $wheres));
    }
    if (is_string($this->_group)) {
      $query .= sprintf('GROUP BY `%s` ', $this->_group);
    }
    if (is_array($this->_group_order)) {
      $query .= sprintf('WITHIN GROUP ORDER BY `%s` %s ', $this->_group_order['field'], $this->_group_order['sort']);
    }
    if (count($orders) > 0) {
      $query .= sprintf('ORDER BY %s ', implode(', ', $orders));
    }

    $query .= sprintf('LIMIT %d, %d ', $this->_offset, $this->_limit);
    if (count($options) > 0) {
      $query .= sprintf('OPTION %s ', implode(', ', $options));
    }

    while (substr($query, -1, 1) == ' ') {
      $query = substr($query, 0, -1);
    }

    return $query;
  }

  /**
   * Adds an entry to the list of indexes to be searched.
   *
   * @param  string  $index The index to add
   * @return IceSphinxQuery
   */
  public function addIndex($index)
  {
    if (is_string($index)) {
      array_push($this->_indexes, $index);
    }

    return $this;
  }

  /**
   * Removes an entry from the list of indexes to be searched.
   *
   * @param  string  $index The index to remove
   * @return IceSphinxQuery
   */
  public function removeIndex($index)
  {
    if (is_string($index))
    {
      while ($pos = array_search($index, $this->_indexes)) {
        unset($this->_indexes[$pos]);
      }
    }

    return $this;
  }

  /**
   * Adds a entry to the list of fields to return from the query.
   *
   * @param  string  $field Field to add
   * @param  string  $alias Alias for that field, optional
   *
   * @return IceSphinxQuery
   */
  public function addField($field, $alias = null)
  {
    if (!is_string($alias)) {
      $alias = null;
    }

    if (is_string($field)) {
      $this->_fields[] = array('field' => $field, 'alias' => $alias);
    }

    return $this;
  }

  /**
   * Adds multiple entries at once to the list of fields to return.
   * Takes an array structured as so:
   * array(array('field' => 'user_id', 'alias' => 'user')), ...)
   *
   * The alias is optional.
   *
   * @param  array  $array Array of fields to add
   * @return IceSphinxQuery
   */
  public function addFields($array)
  {
    if (is_array($array))
    foreach ($array as $entry)
    {
      if (is_array($entry) AND isset($entry['field']))
      {
        if (!isset($entry['alias']) OR is_string($entry['alias'])) {
          $entry['alias'] = null;
          $this->addField($entry['field'], $entry['alias']);
        }
      }
    }

    return $this;
  }

  /**
   * Removes a field from the list of fields to search.
   *
   * @param  string  $alias Alias of the field to remove
   * @return IceSphinxQuery
   */
  public function removeField($alias)
  {
    if (is_string($alias) AND array_key_exists($this->_fields, $alias)) {
      unset($this->_fields[$alias]);
    }

    return $this;
  }

  /**
   * Removes multiple fields at once from the list of fields to search.
   *
   * @param  array  $array List of aliases of fields to remove
   * @return IceSphinxQuery
   */
  public function removeFields($array)
  {
    if (is_array($array))
    {
      foreach ($array as $alias) {
        $this->removeField($alias);
      }
    }

    return $this;
  }

  /**
   * Sets the text to be matched against the index(es)
   *
   * @param  string  $search Text to be searched
   * @return IceSphinxQuery
   */
  public function search($search)
  {
    if (is_string($search)) {
      $this->_search = $search;
    }

    return $this;
  }

  /**
   * Removes the search text from the query.
   *
   * @return IceSphinxQuery
   */
  public function removeSearch()
  {
    $this->_search = null;

    return $this;
  }

  /**
   * Sets the offset for the query
   *
   * @param  integer  $offset Offset
   * @return IceSphinxQuery
   */
  public function offset($offset)
  {
    if (is_integer($offset)) {
      $this->_offset = $offset;
    }

    return $this;
  }

  /**
   * Sets the limit for the query
   *
   * @param  integer  $limit
   * @return IceSphinxQuery
   */
  public function limit($limit)
  {
    if (is_integer($limit)) {
      $this->_limit = $limit;
    }

    return $this;
  }

  /**
   * Adds a WHERE condition to the query.
   *
   * @param  string   $field The field/expression for the condition
   * @param  string   $value The field/expression/value to compare the field to
   * @param  string   $operator The operator (=, <, >, etc)
   * @param  boolean  $quote Whether or not to quote the value, defaults to true
   *
   * @return IceSphinxQuery
   */
  public function where($field, $value, $operator = null, $quote = true)
  {
    if (!in_array($operator, array('=', '!=', '>', '<', '>=', '<=', 'AND', 'NOT IN', 'IN', 'BETWEEN'))) {
      $operator = '=';
    }
    if (!is_string($value) && !is_numeric($value)) {
      return false;
    }
    $quote = ($quote === true) ? true : false;

    $this->_wheres[] = array('field' => $field, 'operator' => $operator, 'value' => $value, 'quote' => $quote);

    return $this;
  }

  /**
   * Adds a WHERE <field> <not> IN (<value x>, <value y>, <value ...>)
   * condition to the query, mainly used for MVAs.
   *
   * @param  string  $field The field/expression for the condition
   * @param  array   $values The values to compare the field to
   * @param  string  $how Whether this is a match-all, match-any (default) or match-none condition
   *
   * @return IceSphinxQuery
   */
  public function in($field, $values, $how = 'any')
  {
    if (!is_array($values)) {
      $values = array($values);
    }

    if ($how == 'all')
    {
      foreach ($values as $value) {
        $this->where($field, $value, '=');
      }
    }
    elseif ($how == 'none')
    {
      foreach ($values as $value) {
        $this->where($field, $value, '!=');
      }
    }
    else
    {
      $this->where($field, '('. implode(', ', $values) .')', 'IN', false);
    }

    return $this;
  }

  /**
   * Sets the GROUP BY condition for the query.
   *
   * @param  string  $field The field/expression for the condition
   * @return IceSphinxQuery
   */
  public function groupBy($field)
  {
    if (is_string($field)) {
      $this->_group = $field;
    }

    return $this;
  }

  /**
   * Removes the GROUP BY condition from the query.
   *
   * @return IceSphinxQuery
   */
  public function removeGroupBy()
  {
    $this->_group = null;

    return $this;
  }

  /**
   * Adds an ORDER condition to the query.
   *
   * @param  string  $field The field/expression for the condition
   * @param  string  $sort The sort type (can be 'asc' or 'desc', capitals are also OK)
   *
   * @return IceSphinxQuery
   */
  public function orderBy($field, $sort)
  {
    if (is_string($field) AND is_string($sort)) {
      $this->_orders[] = array('field' => $field, 'sort' => strtolower($sort));
    }

    return $this;
  }

  /**
   * Sets the WITHIN GROUP ORDER BY condition for the query.
   * This is a Sphinx-specific extension to SQL.
   *
   * @param  string  $field The field/expression for the condition
   * @param  string  $sort The sort type (can be 'asc' or 'desc', capitals are also OK)
   *
   * @return IceSphinxQuery
   */
  public function groupOrder($field, $sort)
  {
    if (is_string($field) AND is_string($sort)) {
      $this->_group_order = array('field' => $field, 'sort' => $sort);
    }

    return $this;
  }

  /**
   * Removes the WITHIN GROUP ORDER BY condition for the query.
   * This is a Sphinx-specific extension to SQL.
   *
   * @return IceSphinxQuery $this
   */
  public function removeGroupOrder()
  {
    $this->_group_order = null;

    return $this;
  }

  /**
   * Adds an OPTION to the query. This is a Sphinx-specific extension to SQL.
   *
   * @param  string  $name The option name
   * @param  string  $value The option value
   *
   * @return IceSphinxQuery
   */
  public function option($name, $value)
  {
    if (is_string($name) AND is_string($value)) {
      $this->_options[] = array('name' => $name, 'value' => $value);
    }

    return $this;
  }

  /**
   * Removes an OPTION from the query.
   *
   * @param  string  $name The option name
   * @param  string  $value The option value, optional
   *
   * @return IceSphinxQuery
   */
  public function removeOption($name, $value = null)
  {
    $changed = false;

    if (is_string($name) AND (($value == null) OR is_string($value)))
    {
      foreach ($this->_options as $key => $option)
      {
        if (($option['name'] == $name) AND (($value == null) OR ($value == $option['value'])))
        {
          unset($this->_options[$key]);
          $changed = true;
        }
      }

      if ($changed) {
        array_keys($this->_options);
      }
    }

    return $this;
  }

  /**
   * @param  IceMemcacheCache  $memcache_cache
   * @return IceSphinxQuery
   */
  public function setMemcacheCache(IceMemcacheCache $memcache_cache)
  {
    $this->_memcache_cache = $memcache_cache;

    return $this;
  }

  /**
   * Executes the query and returns the results
   *
   * @param  integer  $lifetime
   * @return array  Results of the query
   */
  public function count($lifetime = -1)
  {
    if ($this->_group === null)
    {
      throw new LogicException('You must add groupBy() in order to use count() with SphinxQL');
    }
    else if ($lifetime > -1 && !($this->_memcache_cache instanceof IceMemcacheCache))
    {
      throw new LogicException('You must set IceMemcacheCache in order to cache the count() method');
    }

    $query = (string) $this;
    $key = 'sphinx_query_'. md5($query);

    if ($lifetime > -1 && null !== $total = $this->_memcache_cache->get($key, null))
    {
      return (int) $total;
    }
    else
    {
      $total = $this->_sphinx->count($query);
      if ($lifetime > -1)
      {
        $this->_memcache_cache->set($key, $total, false, $lifetime);
      }

		  return (int) $total;
    }
  }

  /**
   * Executes the query and returns the results
   *
   * @param  integer  $cache
   * @return array Results of the query
   */
  public function execute($cache = -1)
  {
    $results = $this->_sphinx->query((string) $this)->fetchAll();

    if (count($results) > 0 && count($results[0]) == 1)
    {
      foreach ($results as $i => $result)
      {
        $results[$i] = array_shift($result);
      }
    }

		return $results;
  }
}
