<?php

class IceWebRequest extends sfWebRequest
{
  private
    $_canonical_url = null,
    $_print_url = null,
    $_short_url = null;

  private $_h1 = null;

  public function setCanonicalUrl($url)
  {
    // Reset the print and short urls so that they can be regenerated
    $this->_print_url = null;
    $this->_short_url = null;

    // Set the canonical url to the one provided
    $this->_canonical_url = $url;
  }

  public function getCanonicalUrl()
  {
    return ($this->_canonical_url !== null) ? $this->_canonical_url : $this->getUri();
  }

  public function getPrintUrl()
  {
    if ($this->_print_url === null)
    {
      $url = $this->getCanonicalUrl();
      $this->_print_url = $url . (stripos($url, '?') === false ? '?' : '&') .'print=1';
    }

    return $this->_print_url;
  }

  public function setShortUrl($url)
  {
    $this->_short_url = $url;
  }

  public function getShortUrl()
  {
    if ($this->_short_url === null)
    {
      $url = $this->getCanonicalUrl();
      $this->_short_url = iceModelShortUrlPeer::createShortUrl($url, true, true);
    }

    return $this->_short_url;
  }

  public function setPageH1($h1)
  {
    $this->_h1 = $h1;
  }

  public function getPageH1()
  {
    return $this->_h1;
  }
}
