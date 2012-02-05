<?php

class IceGateKeeper
{
  public static function open($feature)
  {
    $feature = strtolower(sfInflector::underscore(str_replace(' ', '', $feature)));

    return sfConfig::get('gatekeeper_features_'. $feature, true);
  }

  public static function locked($feature)
  {
    return !self::open($feature);
  }
}