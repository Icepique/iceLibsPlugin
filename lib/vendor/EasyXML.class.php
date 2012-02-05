<?php

/**
 * EasyXMLNode
 *
 * Basic EasyXML construct - it wraps DOMElement, and gives access to
 * it's children nodes and attributes. Serialization is also supported.
 *
 * @author Dariusz "njoy" Paciorek, Zbigniew "ShaXbee" Mandziejewicz
 * @version 1.0
 */
class EasyXMLNode implements ArrayAccess
{
   protected $m_root, $m_node;

   protected static $m_serializeRoot;

   /**
    * Construct
    *
    * @param $root
    * @param $node
    */
   public function __construct(DOMDocument $root, DOMNode $node)
   {
      $this->m_root = $root;
      $this->m_node = $node;
   }

   /**
    * Add node child
    *
    * @param $name
    * @param $value
    *
    * @return EasyXMLNode
    */
   public function addChild($name, $value = null)
   {
      if ($value)
      {
         $node = $this->m_root->createElement($name, $value);
      }
      else
      {
         $node = $this->m_root->createElement($name);
      }

      $node = $this->m_node->appendChild($node);

      return new EasyXMLNode($this->m_root, $node);
   }

   /**
    * Remove child from node
    *
    * @param $name
    */
   public function removeChild($name = null)
   {
      if ($name)
      {
         $node = $this->getElementByTagName($name);

         $this->m_node->removeChild($node);
      }
      else
      {
         $node = $this->m_node->cloneNode(false);

         $parent = $this->m_node->parentNode;
         $parent->removeChild($this->m_node);
         $parent->appendChild($node);
      }
   }

   /**
    * Load xml into node
    *
    * @param $xmlData
    */
   public function loadXMLString($xmlData)
   {
      $fragment = $this->m_root->createDocumentFragment();
      $fragment->appendXML($xmlData);

      $fragment->formatOutput = true;

      $node = $this->m_node->cloneNode(false);
      $node->appendChild($fragment);

      $parent = $this->m_node->parentNode;

      $parent->removeChild($this->m_node);
      $parent->appendChild($node);
   }

   /**
    * Return node or document as XML
    *
    * @return string
    */
   public function asXML()
   {
      $result = null;

      if ($this->m_node->isSameNode($this->m_node))
      {
         $result = $this->m_root->saveXML();
      }
      else
      {
         $result = $this->m_root->saveXML($this->m_node);
      }

      return $result;
   }

   /**
    * ArrayAccess method. Set node attribute
    */
   public function offsetSet($key, $value)
   {
      $this->m_node->setAttribute($key, $value);
   }

   /**
    * ArrayAccess .Get node attribute
    */
   public function offsetGet($key)
   {
      return $this->m_node->getAttribute($key);
   }

   /**
    * ArrayAccess. Remove node attribute
    */
   public function offsetUnset($key)
   {
      $this->m_node->removeAttribute($key);
   }

   /**
    * ArrayAccess . Check for node attribute exists
    */
   public function offsetExists($key)
   {
      return $this->m_node->hasAttribute($key);
   }

   /**
    * Return node
    *
    * @return object
    */
   public function getNode()
   {
      return $this->m_node;
   }

   /**
    * Return node with given name
    *
    * @param $name
    * @return mixed
    */
   protected function getElementByTagName($name)
   {
      $result = null;

      $node = $this->m_node->firstChild;

      while ($node && !$result)
      {
         if ($node->localName == $name || $node->nodeName == $name)
         {
            $result = $node;
         }

         $node = $node->nextSibling;
      }

      return $result;
   }

   /**
    * Execute Xpath query on current node
    *
    * @param $query xpath query to execute
    * @return EasyXMLResult
    */
   public function xpath($query)
   {
      $xpath = new DOMXpath($this->m_root);
      $entries = @$xpath->query($query, $this->m_node);

      if ($entries && !$entries->length)
      {
         $entries = NULL;
      }

      return ($entries ? new EasyXMLResult($this->m_root, $entries) : false);
   }

   /**
    * Get node by name or by name and key
    *
    * @param $name
    * @return object
    */
   public function __get($name)
   {
      $result = Array();

      $node = $this->m_node->firstChild;

      while ($node)
      {
         if ($name == $node->localName || $name == $node->nodeName)
         {
            $result[] = $node;
         }

         $node = $node->nextSibling;
      }

      return new EasyXMLResult($this->m_root, $result);
   }

   /**
    * Set node value
    *
    * @param $name
    * @param $value
    */
   public function __set($name, $value)
   {
      $this->__unset($name);

      if ($value instanceof EasyXMLNode)
      {
         $this->m_node->appendChild($value->getNode());
      }
      else
      {
         $node = $this->addChild($name);
         $node->loadXMLString($value);
      }
   }

   /**
    * Routes DOM method calls to underlaying DOMElement $node
    */
   public function __call($func, $args)
   {
      if (method_exists($this->m_node, $func))
      {
         call_user_func_array(Array($this->m_node, $func), $args);
      }
   }

   /**
    * Removes node(s) with given name - called on unset($obj->$name)
    *
    * @param $name
    */
   public function __unset($name)
   {
      $result = Array();

      $node = $this->m_node->firstChild;

      while ($node)
      {
         if ($node->localName == $name || $node->nodeName == $name)
         {
            $result[] = $node;
         }

         $node = $node->nextSibling;
      }

      foreach ($result as $node)
      {
         $this->m_node->removeChild($node);
      }
   }

   /**
    * Return node as XML or node value
    *
    * @return string
    */
   public function __toString()
   {
      if ($this->m_node->hasChildNodes() && $this->m_node->childNodes->length == 1 && ($this->m_node->firstChild->nodeType == XML_TEXT_NODE || $this->m_node->firstChild->nodeType == XML_CDATA_SECTION_NODE))
      {
         $result = $this->m_node->firstChild->nodeValue;
      }
      else
      {
         $result = $this->m_root->saveXML($this->m_node);
      }

      return $result;
   }

   /**
    * Convert node to Array
    *
    * @param $node Node to convert
    * @return Array with node attributes, childs and value
    */
   static protected function toArray($node)
   {
      $attributes = Array();

      if ($node->nodeType == XML_ELEMENT_NODE)
      {
         $name = $node->nodeName;
      }

      if (!$node instanceof DOMDocument)
      {
         $nodes = $node->attributes;

         if ($node->hasAttribute('xmlns'))
         {
            $attributes['xmlns'] = $node->getAttribute('xmlns');
         }

         for ($i = 0; $i < $nodes->length; $i++)
         {
            $attr = $nodes->item($i);
            $attributes[$attr->name] = $attr->value;
         }
      }

      $childs = Array();
      $nodes = $node->childNodes;

      for ($i = 0; $i < $nodes->length; $i++)
      {
         $node = $nodes->item($i);

         if ($node->nodeType == XML_ELEMENT_NODE)
         {
            $childs[] = self::toArray($node);
         }
         else if ($node->nodeType == XML_TEXT_NODE)
         {
            $value = $node->nodeValue;
         }
      }

      return compact('name', 'childs', 'attributes', 'value');
   }

   /**
    * Converts current node to Array
    *
    * @return Array with node attributes, childs and value
    */
   public function getArray()
   {
      return self::toArray($this->m_node);
   }

   /**
    * Unserialize serialized xml document
    *
    * @param $node
    * @param $data
    */
   protected static function fromArray($node, & $data)
   {
      extract($data);

      if (isset($value))
      {
         $textNode = self::$m_serializeRoot->createTextNode($value);
         $node->appendChild($textNode);
      }

      foreach ($childs as & $child)
      {
         $newNode = self::$m_serializeRoot->createElement($child['name']);
         $node->appendChild($newNode);

         self::fromArray($newNode, $child);
      }

      if (!$node instanceof DOMDocument)
      {
         foreach ($attributes as $key => & $value)
         {
            $node->setAttribute($key, $value);
         }
      }
   }
}

/**
 * @class EasyXMLResult
 * Queries result container, returned by EasyXMLNode::__get and EasyXMLNode::xpath
 *
 * @author Dariusz "njoy" Paciorek, Zbyszek "ShaXbee" Mandziejewicz
 * @version 1.0
 */
class EasyXMLResult extends EasyXMLNode
{
   protected $m_data;

   /**
    * Construct. Creates result object
    */
   public function __construct(DOMDocument $root, $data)
   {
      $this->m_root = $root;

      if ($data instanceof DOMNodeList)
      {
         $result = Array();

         foreach ($data as $row)
         {
            $result[] = $row;
         }

         $data = $result;
      }

      $this->m_node = count($data) ? $data[0] : NULL;
      $this->m_data = $data;
   }

   /**
    * Return node(s) with given name or node attribute
    *
    * @param $key
    * @return mixed
    */
   public function offsetGet($key)
   {
      $result = NULL;

      if (is_numeric($key))
      {
         if (isset($this->m_data[$key]))
         {
            $result = new EasyXMLNode($this->m_root, $this->m_data[$key]);
         }
      }
      else
      {
         $result = $this->m_node->getAttribute($key);
      }

      return $result;
   }

   /**
    * Remove node
    *
    * @param $key
    */
   public function offsetUnset($key)
   {
      if (is_numeric($key))
      {
         if (isset($this->m_data[$key]))
         {
            $this->m_data[$key]->parentNode->removeChild($this->m_data[$key]);
         }
      }
      else
      {
         $this->m_node->removeAttribute($key);
      }
   }

   /**
    * Return number of nodes
    *
    * @return integer
    */
   public function count()
   {
      return count($this->m_data);
   }
}

/**
 * EasyXML
 * 
 * DOMDocument wrapper, root for EasyXMLNode's
 *
 * @author Dariusz "njoy" Paciorek, Zbigniew "ShaXbee" Mandziejewicz
 * @version 1.0
 * @date 01-2007
 */
class EasyXML extends EasyXMLNode
{
   protected $m_serialData;

   /**
    * Construct
    *
    * @param $xmlData
    */
   public function __construct($xmlData = null)
   {
      if ($xmlData)
      {
         $this->m_root = @DOMDocument::loadXML($xmlData);
      }
      else
      {
         $this->m_root = new DOMDocument('1.0', 'utf-8');
      }

      $this->m_node = $this->m_root;

      $this->m_root->formatOutput = true;
      $this->m_root->preserveWhiteSpace = false;
   }

   /**
    * Create new EasyXML object and load XML data from file
    *
    * @param $fileName XML File
    * @return EasyXML
    */
   static public function loadXMLFile($fileName)
   {
      $result = NULL;

      if (file_exists($fileName))
      {
         $data = file_get_contents($fileName);
         $result = new EasyXML($data);
      }

      return $result;
   }

   /**
    * Serialization handler - called on serialize($obj);
    * @return List of fields to serialize
    */
   protected function __sleep()
   {
      $this->m_serialData = EasyXMLNode::toArray($this->m_node);
      return(Array('m_serialData'));
   }

   /**
    * Unserialization handler - called on $obj = unserialize($data);
    * Restores child nodes and document
    */
   protected function __wakeup()
   {
      $this->m_node = new DOMDocument('1.0', 'utf-8');
      $this->m_node->formatOutput = true;
      $this->m_root = $this->m_node;

      self::$m_serializeRoot = $this->m_node;
      self::fromArray($this->m_node, $this->m_serialData);

      // cleanup after unserialization
      self::$m_serializeRoot = NULL;
      $this->m_serialData = NULL;
   }

   /**
    * Return document as XML
    *
    * @return string
    */
   public function __toString()
   {
      return $this->m_root->saveXML();
   }
}
