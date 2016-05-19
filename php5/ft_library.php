<?php


/**
 * Sends the test email using Swift for PHP5 installations.
 *
 * @param array $settings
 * @param array $info
 * @return array
 */
function swift_php_ver_send_test_email($settings, $info) {
  global $L;

  $success = true;
  $message = $L["notify_email_sent"];

  try {
    $smtp = swift_make_smtp_connection($settings);

    // if required, set the server timeout (Swift Mailer default == 15 seconds)
    if (isset($settings["server_connection_timeout"]) && !empty($settings["server_connection_timeout"])) {
      $smtp->setTimeout($settings["server_connection_timeout"]);
    }

    if ($settings["requires_authentication"] == "yes") {
      $smtp->setUsername($settings["username"])
        ->setPassword($settings["password"]);
    }

    $swift = Swift_Mailer::newInstance($smtp);

    // now send the appropriate email
    switch ($info["test_email_format"]) {
      case "text":
        $email = Swift_Message::newInstance($L["phrase_test_plain_text_email"], $L["notify_plain_text_email_sent"]);
        break;
      case "html":
        $email = Swift_Message::newInstance($L["phrase_test_html_email"], $L["notify_html_email_sent"], "text/html");
        break;
      case "multipart":
      default:
        $email = Swift_Message::newInstance($L["phrase_test_multipart_email"]);
        $email->addPart($L["phrase_multipart_email_text"]);
        $email->addPart($L["phrase_multipart_email_html"], "text/html");
        break;
    }

    $email->setTo($info['recipient_email']);
    $email->setFrom($info['from_email']);

    $swift->send($email);
  } catch (Swift_TransportException $e) {
    $success = FALSE;
    $message = $L["notify_smtp_problem"] . " " . $e->getMessage();
  } catch (Swift_RfcComplianceException $e) {
    $success = FALSE;
    $message = $L["notify_smtp_problem"] . " " . $e->getMessage();
  } catch (Swift_SwiftException $e) {
    $success = false;
    $message = $L["notify_problem_building_email"] . " " . $e->getMessage();
  }

  return array($success, $message);
}


/**
 * This makes the connection in the main send- email function (swift_send_email()). It creates the
 * SMTP connection based on the user settings: the port, encryption type and so on. This is handled
 * in the separate PHP version folder because it differs between version 4 and 5.
 */
function swift_make_smtp_connection($settings) {
  $use_encryption = (isset($settings["use_encryption"]) && $settings["use_encryption"] == "yes") ? true : false;
  $encryption_type = isset($settings["encryption_type"]) ? $settings["encryption_type"] : "";
  $port = $settings['port'] ? $settings['port'] : $use_encryption ? $encryption_type == 'SSL' ? 443 : 587 : 25;
  $security = $use_encryption ? $encryption_type == 'SSL' ? 'ssl' : 'tls' : null;

  $smtp = Swift_SmtpTransport::newInstance($settings["smtp_server"], $port, $security);

  return $smtp;
}
