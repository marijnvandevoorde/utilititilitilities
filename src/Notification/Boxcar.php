<?php

namespace Sevenedge\Utilities\Notification;

use Sevenedge\Utilities\Utilities\CurlRequest;


class Boxcar {
    const ENDPOINT = 'https://new.boxcar.io/api/notifications';

    /**
     * @param $recipientKey: secret api key of the recipient
     * @param $subject title for message
     * @param bool $bodyText additional content for message
     * @param string $sound sound to be forced
     * @return bool true if ok, false if failed;
     */
    public static function send($recipients, $subject, $origin, $bodyText = FALSE, $link = false, $sound = "notifier-2") {
        if (!is_array($recipients)) {
            $recipients = array($recipients);
        }
        $keys = array();

        $cr = new CurlRequest();
        foreach ($recipients as $recipient) {
            $data = array(
                "user_credentials" => $recipient,
                "notification[title]" => $subject,
                "notification[long_message]" => $bodyText ? $bodyText : $subject,
                "notification[sound]" => $sound,
                "notification[source_name]" => $origin
            );
            if ($link) {
                $data["notification[open_url]"] = $link;
            }
            $keys[$recipient] = $cr->addRequest(
                self::ENDPOINT,
                $data,
                array(),
                true,
                array(CURLOPT_POST => true, CURLOPT_BINARYTRANSFER => 1, CURLOPT_HEADER => true)
            );
        }
        $cr->execute();


        foreach ($keys as $recipient => $key) {
            $msg = $cr->getResponse($key);
            if ($msg['http_code'] === 201) {
                $keys[$recipient] = array('status' => 'success');
            } else {
                $msg = json_decode($msg['response'], 1);
                $keys[$recipient] = array('status' => 'error', $msg['Response']);
            }
        }
        $cr->__destruct();

        return $keys;
    }

}