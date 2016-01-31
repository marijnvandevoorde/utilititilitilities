<?php

namespace Marijnworks\Utilities\Mail;

use Cake\Error\Debugger;

class Mandril
{
    private $_apiKey;

    public function __construct($apiKey) {
        $this->_apiKey = $apiKey;
    }



    // $to_emails 	=> array('mail1@gmail.com', 'mail2@gmail.com', ...)
    // $html 		=> '<table><tr><td>html example</td><tr></table>'
    // $subject 	=> 'a regular string as subject'
    // $global_merge_vars AND $merge_vars : http://help.mandrill.com/entries/21678522-How-do-I-use-merge-tags-to-add-dynamic-content-
    // $async => if are more then 10 recipients, doesn't matter what you fill in here... see https://mandrillapp.com/api/docs/messages.JSON.html#method-send
    public function sendMail($to_emails, $html, $text, $subject, $from_mail, $from_name, $global_merge_vars = false, $merge_vars = false, $async = false, $additionalParams = array())
    {

        $to = array();
        foreach ($to_emails as $mail) {
            $to[] = array('email' => $mail);
        }
        // set SMTP data
        ini_set('SMTP', 'smtp.mandrillapp.com');
        ini_set('smtp_port', '587');
        ini_set('sendmail_from', $from_mail);

        // build json string
        $data_array = array(
            'key' => $this->_apiKey,
            'message' => array(
                'html' => $html,
                'text' => $text,
                'subject' => $subject,
                'from_email' => $from_mail,
                'from_name' => $from_name,
                'to' => $to,
                'preserve_recipients' => false,
            ),
            'async' => $async,
        );

        if (is_array($additionalParams)) {
            $data_array['message'] = array_merge($data_array['message'], $additionalParams);
        }


        if ($global_merge_vars && is_array($global_merge_vars) && $merge_vars && is_array($merge_vars)) {
            $data_array['message']['global_merge_vars'] = $global_merge_vars;
            $data_array['message']['merge_vars'] = $merge_vars;
        }

        $data_json = json_encode($data_array);

        // use curl to perform POST to mandrill
        $ch = curl_init('https://mandrillapp.com/api/1.0/messages/send.json');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_json))
        );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // for https:// to work, do not verify peer or host
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // perform POST
        $result = curl_exec($ch);
        // $this->log(print_r($result, true));
        curl_close($ch);
        $json_result = json_decode($result);
        // var_dump($json_result);
        $success = true;
        foreach ($json_result as $res) {
            if (!(isset($res->status) && ($res->status == 'sent' || $res->status == 'queued'))) {
                Debugger::log('mail not sent, mandrill returns: ' . print_r($res, true));
                $success = false;
            }
        }
        return $success;
    }
}