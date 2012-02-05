<?php

class IceXMLElement extends SimpleXMLElement
{
  public function findOneString($path)
  {
    $all = $this->findAllStrings($path);
    if (!empty($all) && is_array($all))
    {
      return trim(array_shift($all));
    }

    return null;
  }

  public function findLastString($path)
  {
    $all = $this->findAllStrings($path);
    if (!empty($all) && is_array($all))
    {
      return trim(array_pop($all));
    }

    return null;
  }

  public function findOneInteger($path)
  {
    $all = $this->findAllIntegers($path);
    if (!empty($all) && is_array($all))
    {
      return array_shift($all);
    }

    return null;
  }

  public function findLastInteger($path)
  {
    $all = $this->findAllIntegers($path);
    if (!empty($all) && is_array($all))
    {
      return array_pop($all);
    }

    return null;
  }

  public function findOneFloat($path)
  {
    $all = $this->findAllFloats($path);
    if (!empty($all) && is_array($all))
    {
      return array_shift($all);
    }

    return null;
  }

  public function findLastFloat($path)
  {
    $all = $this->findAllFloats($path);
    if (!empty($all) && is_array($all))
    {
      return array_pop($all);
    }

    return null;
  }

  public function findAllStrings($path)
  {
    $strings = array();
    $objects = $this->xpath($path);

    if (is_array($objects))
    foreach ($objects as $object)
    {
      $strings[] = trim(trim((string) $object, "Â\xC2\xA0"));
    }

    return $strings;
  }

  public function findAllIntegers($path)
  {
    $integers = array();
    $objects = $this->xpath($path);

    if (is_array($objects))
    foreach ($objects as $object)
    {
      $integers[] = (int) IceStatic::cleanSpaces((string) $object);
    }

    return $integers;
  }

  public function findAllFloats($path)
  {
    $floats = array();
    $objects = $this->xpath($path);

    if (is_array($objects))
    foreach ($objects as $object)
    {
      $floats[] = IceStatic::floatval((string) $object);
    }

    return $floats;
  }

  public function findOne($path)
  {
    $objects = $this->xpath($path);

    if (is_array($objects) && !empty($objects))
    {
      return array_shift($objects);
    }

    return null;
  }
  
  public static function join(SimpleXMLElement $root, SimpleXMLElement $append) 
  {
    if ($root === null || $append === null)
    {
      return false; 
    }

    if (strlen(trim((string) $append)) == 0)
    {
      $xml = $root->addChild($append->getName());
      foreach($append->children() as $child) 
      {
        self::join($xml, $child);
      }
    } 
    else 
    {
      $xml = $root->addChild($append->getName(), (string) $append);
    }

    foreach($append->attributes() as $n => $v) 
    {
      $xml->addAttribute($n, $v);
    }
    
    return true;
  }
}
