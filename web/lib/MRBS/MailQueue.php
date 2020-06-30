<?php
namespace MRBS;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require_once MRBS_ROOT . '/functions_mail.inc';

// A class for handling a mail queue.   Normally the queue is flushed on shutdown using a
// register_shutdown_function() callback.  However the class could easily be extended so that the
// mail queue is held in the database and emptied by a cron job.  This would enable rate limits to
// be adhered to more accurately, as there would be just one queue.
class MailQueue
{
  protected static $mails = array();


  public static function add($addresses, $subject, $text_body, $html_body, $attachment, $charset = 'us-ascii')
  {
    global $mail_settings;

    // Don't do anything if mail has been disabled.   Useful for testing MRBS without
    // sending emails to those who don't want them
    if ($mail_settings['disabled'])
    {
      mail_debug("Mail disabled: not adding message to queue");
      return;
    }
    
    // And no point in doing anything if there are no recipients
    if (self::getNRecipients($addresses) == 0)
    {
      mail_debug("No recipients: not adding message to queue");
      return;
    }

    $mail = array();
    foreach(array('addresses', 'subject', 'text_body', 'html_body', 'attachment', 'charset') as $var)
    {
      $mail[$var] = $$var;
    }
    self::$mails[] = $mail;
  }


  public static function flush()
  {
    foreach (self::$mails as $mail)
    {
      self::sendMail(
        $mail['addresses'],
        $mail['subject'],
        $mail['text_body'],
        $mail['html_body'],
        $mail['attachment'],
        $mail['charset']
      );
    }
  }


  protected static function getNRecipients($addresses)
  {
    if (empty($addresses))
    {
      return 0;
    }
    
    $recipients = (!empty($addresses['to'])) ? $addresses['to'] : '';
    $recipients .= (!empty($addresses['cc'])) ? ',' . $addresses['cc'] : '';
    $recipients .= (!empty($addresses['bcc'])) ? ',' . $addresses['bcc'] : '';
    $parsed_addresses = PHPMailer::parseAddresses($recipients);
    
    return count($parsed_addresses);
  }

  
  /**
   * Send an email
   *
   * @param array   $addresses        an array of addresses, each being a comma
   *                                  separated list of email addresses.  Indexed by
   *                                    'from'
   *                                    'to'
   *                                    'cc'
   *                                    'bcc'
   * @param string  $subject          email subject
   * @param array   $text_body        text part of body, an array consisting of
   *                                    'content'  the content itself
   *                                    'cid'      the content id
   * @param array   $html_body        HTML part of body, an array consisting of
   *                                    'content'  the content itself
   *                                    'cid'      the content id
   * @param array   $attachment       file to attach.   An array consisting of
   *                                    'content' the file or data to attach
   *                                    'method'  the iCalendar METHOD
   *                                    'name'    the name to give it
   * @param string  $charset          character set used in body
   * @return bool                     TRUE or PEAR error object if fails
   */
  protected static function sendMail($addresses, $subject, $text_body, $html_body, $attachment, $charset = 'us-ascii')
  {
    set_include_path(get_include_path() . PATH_SEPARATOR . MRBS_ROOT);

    require_once 'Mail/mimePart.php';

    // We use the PHPMailer class to handle the sending of mail.   However PHPMailer does not
    // provide the versatility we need to be able to construct the MIME parts necessary for
    // iCalendar applications, so we use the PEAR Mail_Mime package to do that.   See the
    // discussion at https://github.com/PHPMailer/PHPMailer/issues/175

    global $mail_settings, $sendmail_settings, $smtp_settings;

    static $last_mail_sent = null;
    static $last_n_addresses = null;

    $mail = new PHPMailer;

    mail_debug("Preparing to send email ...");
    if ($mail_settings['debug'])
    {
      $mail->Debugoutput = ($mail_settings['debug_output'] == 'log') ? 'error_log' : 'html';
      $mail->SMTPDebug = SMTP::DEBUG_CONNECTION; // show connection status, client -> server and server -> client messages
    }

    $eol = "\n";  // EOL sequence to use in mail headers.  Need "\n" for mail backend

    // for cases where the mail server refuses
    // to send emails with cc or bcc set, put the cc
    // addresses on the to line
    if (!empty($addresses['cc']) && $mail_settings['treat_cc_as_to'])
    {
      $recipients_array = array_merge(explode(',', $addresses['to']),
        explode(',', $addresses['cc']));
      $addresses['to'] = get_address_list($recipients_array);
      $addresses['cc'] = NULL;
    }
    if (empty($addresses['from']))
    {
      if (isset($mail_settings['from']))
      {
        $addresses['from'] = $mail_settings['from'];
      }
      else
      {
        trigger_error('$mail_settings["from"] has not been set in the config file.', E_USER_NOTICE);
      }
    }

    switch ($mail_settings['admin_backend'])
    {
      case 'mail':
        $mail->isMail();
        break;
      case 'qmail':
        $mail->isQmail();
        if (isset($mail_settings['qmail']['qmail-inject-path']))
        {
          $mail->Sendmail = $mail_settings['qmail']['qmail-inject-path'];
        }
        break;
      case 'sendmail':
        $mail->isSendmail();
        $mail->Sendmail = $sendmail_settings['path'];
        if (isset($sendmail_settings['args']) && ($sendmail_settings['args'] !== ''))
        {
          $mail->Sendmail .= ' ' . $sendmail_settings['args'];
        }
        break;
      case 'smtp':
        $mail->isSMTP();
        $mail->Host = $smtp_settings['host'];
        $mail->Port = $smtp_settings['port'];
        $mail->SMTPAuth = $smtp_settings['auth'];
        $mail->SMTPSecure = $smtp_settings['secure'];
        $mail->Username = $smtp_settings['username'];
        $mail->Password = $smtp_settings['password'];
        if ($smtp_settings['disable_opportunistic_tls'])
        {
          $mail->SMTPAutoTLS = false;
        }
        $mail->SMTPOptions = array
        (
          'ssl' => array
          (
            'verify_peer' => $smtp_settings['ssl_verify_peer'],
            'verify_peer_name' => $smtp_settings['ssl_verify_peer_name'],
            'allow_self_signed' => $smtp_settings['ssl_allow_self_signed']
          )
        );
        break;
      default:
        $mail->isMail();
        trigger_error("Unknown mail backend '" . $mail_settings['admin_backend'] . "'." .
          " Defaulting to 'mail'.",
          E_USER_WARNING);
        break;
    }

    $mail->CharSet = get_mail_charset();  // PHPMailer defaults to 'iso-8859-1'
    $mail->AllowEmpty = true;  // remove this for production
    $mail->addCustomHeader('Auto-Submitted', 'auto-generated');

    if (isset($addresses['from']))
    {
      $from_addresses = PHPMailer::parseAddresses($addresses['from']);
      $mail->setFrom($from_addresses[0]['address'], $from_addresses[0]['name']);
    }

    $to_addresses = PHPMailer::parseAddresses($addresses['to']);
    foreach ($to_addresses as $to_address)
    {
      $mail->addAddress($to_address['address'], $to_address['name']);
    }

    if (isset($addresses['cc']))
    {
      $cc_addresses = PHPMailer::parseAddresses($addresses['cc']);
      foreach ($cc_addresses as $cc_address)
      {
        $mail->addCC($cc_address['address'], $cc_address['name']);
      }
    }

    if (isset($addresses['bcc']))
    {
      $bcc_addresses = PHPMailer::parseAddresses($addresses['bcc']);
      foreach ($bcc_addresses as $bcc_address)
      {
        $mail->addBCC($bcc_address['address'], $bcc_address['name']);
      }
    }

    $mail->Subject = $subject;


    // Build the email.   We're going to use the "alternative" subtype which means
    // that we order the sub parts according to how faithful they are to the original,
    // putting the least faithful first, ie the ordinary plain text version.   The
    // email client then uses the most faithful version that it can handle.
    //
    // If we are also adding the iCalendar information then we enclose this alternative
    // mime subtype in an outer mime type which is mixed.    This is necessary so that
    // the widest variety of calendar applications can access the calendar information.
    // So depending on whether we are sending iCalendar information we will have a Mime
    // structure that looks like this:
    //
    //    With iCalendar info                 Without iCalendar info
    //    -------------------                 ----------------------
    //
    //    multipart/mixed                     mutlipart/alternative
    //      multipart/alternative               text/plain
    //        text/plain                        text/html
    //        text/html
    //        text/calendar
    //      application/ics

    // First of all build the inner mime type, ie the multipart/alternative type.
    // If we're not sending iCalendar information this will become the outer,
    // otherwise we'll then enclose it in an outer mime type.
    $mime_params = array();
    $mime_params['eol'] = $eol;
    $mime_params['content_type'] = "multipart/alternative";
    $mime_inner = new \Mail_mimePart('', $mime_params);

    // Add the text part
    $mime_params['content_type'] = "text/plain";
    $mime_params['encoding']     = "8bit";
    $mime_params['charset']      = $charset;
    $mime_inner->addSubPart($text_body['content'], $mime_params);

    // Add the HTML mail
    if (!empty($html_body))
    {
      $mime_params['content_type'] = "text/html";
      $mime_params['cid'] = $html_body['cid'];
      $mime_inner->addSubPart($html_body['content'], $mime_params);
      unset($mime_params['cid']);
    }

    if (empty($attachment))
    {
      // If we're not sending iCalendar information we've now got everything,
      // so we'll make the "inner" section the complete mime.
      $mime = $mime_inner;
    }
    else
    {
      // Otherwise we need to carry on and add the text version of the iCalendar
      $mime_params['content_type'] = "text/calendar; method=" . $attachment['method'];
      // The encoding needs to be base64, because Postfix will otherwise not preserve
      // CRLF sequences and these are mandatory terminators in the iCalendar file
      $mime_params['encoding'] = "base64";
      $mime_inner->addSubPart($attachment['content'], $mime_params);

      // and then enclose the inner section in a multipart/mixed outer section.
      // First create the outer section
      $mime_params = array();
      $mime_params['eol'] = $eol;
      $mime_params['content_type'] = "multipart/mixed";
      $mime = new \Mail_mimePart('', $mime_params);

      // Now add the inner section as the first sub part
      $mime_inner = $mime_inner->encode();
      $mime_params = array();
      $mime_params['eol'] = $eol;
      $mime_params['encoding'] = "8bit";
      $mime_params['content_type'] = $mime_inner['headers']['Content-Type'];
      $mime->addSubPart($mime_inner['body'], $mime_params);

      // And add the attachment as the second sub part
      $mime_params['content_type'] = "application/ics";
      $mime_params['encoding']     = "base64";
      $mime_params['disposition']  = "attachment";
      $mime_params['dfilename']    = $attachment['name'];
      $mime->addSubPart($attachment['content'], $mime_params);
    }

    // Encode the result
    $mime = $mime->encode();

    // Construct the MIMEHeader and then call preSend() which sets up the
    // headers.   However it also sets up the body, which we don't want,
    // so we have to set the MIMEBody after calling preSend().
    //
    // This is not ideal since $MIMEHeader and $MIMEBody are protected
    // properties of the PHPMailer class.  In the future we may have to extend
    // the class to do what we want, unless the features we need are added.

    $mime_header = '';
    foreach ($mime['headers'] as $name => $value)
    {
      if ($name == 'Content-Type')
      {
        $mail->ContentType = $value;
      }
      $mime_header .= $mail->headerLine($name, $value);
    }
    $mail->set('MIMEHeader', $mime_header);

    if ($mail->preSend())
    {
      $mail->set('MIMEBody', $mime['body']);
      mail_debug("Using backend '" . $mail_settings['admin_backend'] . "'");
      mail_debug("From: " . (isset($addresses['from']) ? $addresses['from'] : ''));
      mail_debug("To: " . (isset($addresses['to']) ? $addresses['to'] : ''));
      mail_debug("Cc: " . (isset($addresses['cc']) ? $addresses['cc'] : ''));
      mail_debug("Bcc: " . (isset($addresses['bcc']) ? $addresses['bcc'] : ''));

      // Throttle the rate of mail sending if required
      if (!empty($mail_settings['rate_limit']))
      {
        $microtime_now = round(microtime(true), 6);
        if (isset($last_mail_sent))
        {
          $diff = round($microtime_now - $last_mail_sent, 6);
          mail_debug("Last mail sent $diff seconds ago to $last_n_addresses addresses");
          $min_gap = round($last_n_addresses/$mail_settings['rate_limit'], 6);
          if ($min_gap > $diff)
          {
            $sleep_seconds = round($min_gap - $diff, 6);
            mail_debug("Too soon to send the next mail; sleeping for $sleep_seconds seconds");
            usleep(intval($sleep_seconds * 1000000));
          }
        }
        $last_n_addresses = self::getNRecipients($addresses);
        $last_mail_sent = $microtime_now;
      }

      // PHPMailer uses escapeshellarg() and escapeshellcmd().   In many installations these will
      // have been disabled.    If they have been disabled PHP will generate a warning and the functions
      // will return NULL.   PHPMailer uses the functions to test if the sender address is shell safe
      // and can be used with the -f option.   If the escapeshell*() functions are disabled, mail
      // will still be sent, but -f will not be used.   [Note that if a function is disabled you cannot
      // redeclare it, so writing emulations of escapeshellarg() and escapeshellcmd() is not an option.]

      // As mail still gets through, the warning message will cause error logs to fill up rapidly, so we
      // suppress the standard errors in the cases when they will be generated and issue our own NOTICE error.

      $disabled_functions = ini_get('disable_functions');

      if (!empty($disabled_functions) && (strpos($disabled_functions, 'escapeshell') !== FALSE)  &&
        in_array($mail_settings['admin_backend'], array('mail', 'sendmail')))
      {
        $message = "Your PHP system has one or both of the escapeshellarg() and escapeshellcmd() functions " .
          "disabled and you are using the '" . $mail_settings['admin_backend'] . "' backend.  " .
          "PHPMailer will therefore not have used the -f option when sending mail.";
        mail_debug($message);
        trigger_error($message, E_USER_NOTICE);
        $result = @$mail->postSend();
      }
      else
      {
        $result = $mail->postSend();
      }

      if ($result)
      {
        mail_debug('Email sent successfully');
        return true;
      }
    }

    error_log('Error sending email: ' . $mail->ErrorInfo);
    mail_debug('Failed to send email: ' . $mail->ErrorInfo);

    return false;
  }

}
