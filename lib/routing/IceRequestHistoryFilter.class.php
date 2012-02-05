<?php

class IceRequestHistoryFilter extends sfFilter
{
  public function execute($filterChain)
  {
    $user = $this->getContext()->getUser();
    $request = $this->getContext()->getRequest();
    
    if( $request->getMethod() == sfRequest::POST )
    {
      $filterChain->execute();
      
      return;
    }

    $requestHistory = $user->getAttribute('request_history', array(), 'IceRequestHistory');
    $requestHistory = $requestHistory ? $requestHistory : array();

    $last = array_pop($requestHistory);
    $current = $request->getUri();

    if ($last == $current || stripos($current, '/ajax/') !== false)
    {
      $filterChain->execute();

      return;
    }

    array_push($requestHistory, $last);
    array_push($requestHistory, $current);

    // We want to keep only the last 10 requests
    $requestHistory = array_slice($requestHistory, -10);

    $user->setAttribute('request_history', $requestHistory, 'IceRequestHistory');
    $user->setAttribute('current_request_key', (count($requestHistory) - 1), 'IceRequestHistory');

    $filterChain->execute();
  }
}
