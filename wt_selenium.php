<?php
require 'vendor/autoload.php';
include_once 'walkthrough/connection.inc';
include_once 'walkthrough/commands.inc';

$command_line = new Commando\Command();

$command_line->option('username')
  ->aka('u')
  ->describedAs('Drupal user name');

$command_line->option('password')
  ->aka('p')
  ->describedAs('Drupal password');

$command_line->option('walkhub_url')
  ->aka('w')
  ->describedAs('Walkhub url')
  ->require();

$command_line->option('consumer key')
  ->aka('k')
  ->describedAs('OAuth Consumer Key');

$command_line->option('consumer secret')
  ->aka('s')
  ->describedAs('OAuth Consumer Secret');

$command_line->argument()
  ->referToAs('Action')
  ->describe('Action to perform. Possible options:
  * status
    Test connection to the walkhub.

  * get_queue
    Get the screenshot queue.

  * get_phpunit [walkthrough|walkthrough_set] [uuid]
    Get the phpunit export for a walkthrough.

  * process_queue
    Gets the first item of the queue and executes the phpunit test, when ready
    posts back the results and the screenshots to the screening.

  * flag [uuid] [0|1]
    Flags/unflags a Walkthrough or Walkthrough set.');

$command_line->option('debug')
  ->aka('d')
  ->describedAs('Debug mode')
  ->boolean();

$function_name = 'command__' . $command_line[0];
if (function_exists($function_name)) {
  $endpoint = $command_line['walkhub_url'] . '/api/v2';
  $connection = new Walkthrough\Connection();

  $authenticator = NULL;

  // Basic HTTP authentication.
  if ($command_line['username'] && $command_line['password']) {
    include_once 'walkthrough/authenticator/basic.inc';
    $authenticator = new Walkthrough\Authenticator\Basic();
    $authenticator->setUsername($command_line['username']);
    $authenticator->setPassword($command_line['password']);
  }

  // OAuth authentication.
  if ($command_line['consumer key'] && $command_line['consumer secret']) {
    include_once 'walkthrough/authenticator/oauth.inc';
    $authenticator = new \Walkthrough\Authenticator\Oauth();
    $authenticator->setConsumerKey($command_line['consumer key']);
    $authenticator->setConsumerSecret($command_line['consumer secret']);

    $endpoint .= '/oauth';
  }

  // If we don't have authentication we fall back to Anonymous session.
  if ($authenticator === NULL) {
    echo "Warning: Authentication not set, using anonymous session (most endpoints will not work).\n";
    echo "  Use the -u and -p flags for basic HTTP authentication.\n";
    echo "  Use the -k and -s flags for 2-legged OAuth authentication.\n\n";

    include_once 'walkthrough/authenticator/anonymous.inc';
    $authenticator = new \Walkthrough\Authenticator\Anonymous();
  }

  $connection->setAuthenticator($authenticator);

  if ($command_line['debug']) {
    echo "Endpoint set to: $endpoint\n";
  }
  $authenticator->setEndpoint($endpoint);
  $connection->setEndpoint($endpoint);

  call_user_func($function_name, $connection, $command_line);
}

