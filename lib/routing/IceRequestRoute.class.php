<?php

class IceRequestRoute extends sfRequestRoute
{
  public function compile()
  {
    parent::compile();

    // Symfony's regex does not support utf8 characters, so adding that here
    $this->regex = str_replace('#x' , '#ixu', $this->regex);
  }
}