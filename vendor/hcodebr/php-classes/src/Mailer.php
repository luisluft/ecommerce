<?php

namespace Hcode;

use Rain\Tpl;

class Mailer
{
    const NAME_FROM = "Foguinho Store";

    private $mail;

    /**
     * Read the config.json file inside of the project root folder
     * for the values of username and password of the email used
     * for mailing the 'forgot password' email to user
     * To use this feature just insert your credentials inside the 'config - example.json' file
     * and change the name of the file to 'config.json'
     *
     * @param string $value Either username, or password.
     *
     * @return string Value of the username or password.
     */
    public static function readConfigFile($value)
    {
        $strFileContents = file_get_contents("config.json");

        $array = json_decode($strFileContents, true);

        return $array['email'][$value];
    }

    public function __construct($toAddress, $toName, $subject, $tplName, $data = array())
    {
        $config = array(
            "tpl_dir"       => $_SERVER["DOCUMENT_ROOT"] . "/views/email/",
            "cache_dir"     => $_SERVER["DOCUMENT_ROOT"] . "/views-cache/",
            "debug"         => false // set to false to improve the speed
        );
    
        Tpl::configure($config);
    
        $tpl = new Tpl;
        
        foreach ($data as $key => $value) {
            $tpl->assign($key, $value);
        }

        $html = $tpl->draw($tplName, true);

        // Create a new PHPMailer instance
        $this->mail = new \PHPMailer;

        // Tell PHPMailer to use SMTP
        $this->mail->isSMTP();

        // Enable SMTP debugging
        // SMTP::DEBUG_OFF = off (for production use)
        // SMTP::DEBUG_CLIENT = client messages
        // SMTP::DEBUG_SERVER = client and server messages
        $this->mail->SMTPDebug = 0;

        //Ask for HTML-friendly debug output
        $this->mail->Debugoutput = 'html';
        
        //Set the hostname of the mail server
        $this->mail->Host = 'mail.tecluft.com.br';
        // use
        // $this->mail->Host = gethostbyname('smtp.gmail.com');
        // if your network does not support SMTP over IPv6

        //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        $this->mail->Port = 587;

        //Set the encryption mechanism to use - STARTTLS or SMTPS
        $this->mail->SMTPSecure = 'tls';

        //Whether to use SMTP authentication
        $this->mail->SMTPAuth = true;

        //Username to use for SMTP authentication - use full email address for gmail
        $this->mail->Username =  $this->readConfigFile('username');

        //Password to use for SMTP authentication
        $this->mail->Password = $this->readConfigFile('password');

        //Set who the message is to be sent from
        $this->mail->setFrom($this->readConfigFile('username'), Mailer::NAME_FROM);

        //Set an alternative reply-to address
        // $this->mail->addReplyTo('replyto@example.com', 'First Last');

        //Set who the message is to be sent to
        $this->mail->addAddress($toAddress, $toName);

        //Set the subject line
        $this->mail->Subject = $subject;

        //Read an HTML message body from an external file, convert referenced images to embedded,
        //convert HTML into a basic plain-text alternative body
        $this->mail->msgHTML($html);

        //Replace the plain text body with one created manually
        $this->mail->AltBody = 'This is a plain-text message body';

        //Attach an image file
        // $this->mail->addAttachment('images/phpmailer_mini.png');
    }

    public function send()
    {
        return $this->mail->send();
    }
}
