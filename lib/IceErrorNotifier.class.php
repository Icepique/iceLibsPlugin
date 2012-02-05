<?php

class IceErrorNotifier
{
  public static function notify(sfEvent $event)
  {
    $emails = sfConfig::get('app_ice_libs_emails');

    // We need valid emails here to continue
    if (empty($emails['sender']) || empty($emails['notify']))
    {
      return;
    }

    /**
     * @var Exception $exception
     */
    $exception = $event->getSubject();

    /**
     * @var sfContext $context
     */
    $context = sfContext::getInstance();

    /**
     * @var sfWebRequest $request
     */
    $request = $context->getRequest();

    $env = 'n/a';
    if ($conf = sfContext::getInstance()->getConfiguration())
    {
      $env = $conf->getEnvironment();
    }

    if ($env != 'prod')
    {
      return;
    }

    $data = array();
    $data['className'] = get_class($exception);
    $data['message'] = !is_null($exception->getMessage()) ? $exception->getMessage() : 'n/a';
    $data['moduleName'] = $context->getModuleName();
    $data['actionName'] = $context->getActionName();
    $data['uri'] = $request->getUri();

    $data['GET'] = var_export($_GET, true);
    $data['POST'] = var_export($_POST, true);
    $data['SERVER'] = var_export($_SERVER, true);

    $subject = "ERROR: {$_SERVER['HTTP_HOST']} Exception - $env - {$data['message']}";
    $body = "Exception notification for {$_SERVER['HTTP_HOST']}, environment $env - " . date('H:i:s j F Y'). "\n\n";
    $body .= $exception . "\n\n\n\n\n";
    $body .= "Additional data: \n\n";

    foreach($data as $key => $value)
    {
      $body .= $key . " => " . $value . "\n\n";
    }

    $mailer = sfContext::getInstance()->getMailer();
    $mailer->composeAndSend($emails['sender'], $emails['notify'], $subject, $body);
  }

  /**
   * @todo: To implement
   * @param string $message
   */
  public static function alert($message)
  {

  }
}
