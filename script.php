<?php

//Dependencies

// https://github.com/PHPMailer/PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './lib/PHPMailer/Exception.php';
require './lib/PHPMailer/PHPMailer.php';
require './lib/PHPMailer/SMTP.php';

// https://simplehtmldom.sourceforge.io/
require './lib/simpleHTMLdom/simple_html_dom.php';

//Include config.php
require './config.php';

$jsos_user = urlencode($jsos_user);
$jsos_pass = urlencode($jsos_pass);

//Check for unseen emails with Edukacja.CL notification subject via IMAP
$mb = imap_open('{student.pwr.edu.pl:143}', $smail_user, $smail_pass);
$unread = imap_search($mb, 'SUBJECT "[Edukacja.CL] powiadomienie o otrzymaniu nowego komunikatu" UNSEEN', SE_UID);

//If new emails exist run the rest of the script
if($unread)
{
    //Retrieve oauth tokens from JSOS redirect URL
    $ch = curl_init('https://jsos.pwr.edu.pl/index.php/site/loginAsStudent');
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    curl_exec($ch);

    $redirect_url =  curl_getinfo($ch, CURLINFO_REDIRECT_URL);

    curl_close($ch);
    
    //Parse url to get tokens' values
    $url_parts = parse_url($redirect_url);
    parse_str($url_parts['query'], $query_parts);

    $post_tokens = [];
    foreach($query_parts as $name => $part){
        $post_tokens[$name] = $part;
    }

    //Setup static POST fields for login form
    $post_static = [
        "authenticateButton" => "Zaloguj",
        "oauth_callback_url" => urlencode("https://jsos.pwr.edu.pl/index.php/site/loginAsStudent"),
        "oauth_request_url" => urlencode("http://oauth.pwr.edu.pl/oauth/authenticate"),
        "oauth_symbol" => "EIS",
        "id1_hf_0" => null,
    ];

    //Setup JSOS credentials
    $post_credentials = [
        "username" => $jsos_user,
        "password" => $jsos_pass,
    ];

    //Full POST query
    $full_post_query = array_merge($post_tokens, $post_static, $post_credentials);
    $string_post_query = http_build_query($full_post_query);

    //POST action URL
    $post_action_url = 'https://oauth.pwr.edu.pl/oauth/authenticate'.'?0-1.IFormSubmitListener-authenticateForm&'.http_build_query($post_tokens);
 
    //Login to JSOS
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $post_action_url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $string_post_query);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $output0 = curl_exec($ch);
    
    curl_close($ch);

    //Go to the inbox
    $ch = curl_init('https://jsos.pwr.edu.pl/index.php/student/wiadomosci');
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $output1 = curl_exec($ch);

    curl_close($ch);

    if($html = str_get_html($output1))
    {
                
        //Find unread messages and retrieve their URLs
        $property = 'data-url';
        foreach($html->find('tr[class=unread]') as $element)
        {
            //Go to each message's URL
            $ch = curl_init("https://jsos.pwr.edu.pl".$element->$property);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $output2 = curl_exec($ch);

            curl_close($ch);
            
            //Find message's header and body
            if($html = str_get_html($output2))
            {
                $subject = null;
                $body = null;
                if($header = $html->find("div[id=podgladWiadomosci] h4", 0)) $subject = $header->plaintext;
                if($div = $html->find("div[id=podgladWiadomosci] div", 0)) $body =  $div->plaintext;

                //Send message via SMTP
                if($subject && $body)
                {
                    echo $subject."\n";

                    $mail = new PHPMailer();
                    $mail->CharSet = 'UTF-8';
                    $mail->Encoding = 'base64';
                    $mail->isSMTP();
                    $mail->Host = 'student.pwr.edu.pl';
                    $mail->SMTPAuth = true;
                    $mail->Username = $smail_user;
                    $mail->Password = $smail_pass;
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('jsos-noreply@student.pwr.edu.pl', 'JSOS');
                    $mail->addReplyTo('jsos-noreply@student.pwr.edu.pl', 'JSOS');
                    $mail->addAddress($smail_user);
                    $mail->Subject = $subject;
                    $mail->Body = $body;

                    if(!$mail->send()){
                        echo 'Message could not be sent.';
                        echo 'Mailer Error: ' . $mail->ErrorInfo."\n\n";
                    }else{
                        echo 'Message has been sent'."\n\n";
                    }
                }
            }
        }
    }
    else echo "Couldn't load message list from JSOS.";

    //Delete cookie file
    @unlink($cookie);

    //Mark Edukacja.CL emails as read
    foreach($unread as $mail)
    {
        $status = imap_setflag_full($mb, $mail, "\\Seen", ST_UID);
    }

    //Close the IMAP stream
    imap_close($mb);
}
else echo 'No new messages!';

