<?php

class IceWebTestRequest extends IceWebRequest
{
  public function getRelativeUrlRoot()
  {
    return '';
  }

  public function isSecure()
  {
    return false;
  }

  public function getHost()
  {
    return 'www.example.org';
  }
}
