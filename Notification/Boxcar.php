<?php

namespace Sevenedge\Notification;

use Sevenedge\Utilities\CurlRequest;


class Notifier {
    const ENDPOINT = 'https://new.boxcar.io/api/notifications';

    /**
     * @param $recipientKey: secret api key of the recipient
     * @param $subject title for message
     * @param bool $messageExtended additional content for message
     * @param string $sound sound to be forced
     * @return bool true if ok, false if failed;
     */
    public static function send($recipientKey, $subject, $sender, $messageExtended = FALSE, $sound = "notifier-2") {


        $cr = new CurlRequest();
        $data = array(
            "user_credentials" => $recipientKey,
            "notification[title]" => $subject,
            "notification[long_message]" => $messageExtended ? $messageExtended : $subject,
            "notification[sound]" => $sound,
            "notification[source_name]" => $sender
        );
        $key = $cr->addRequest(
            self::ENDPOINT,
            $data
        );

        if ($cr->execute() === 0) {
            $res = $cr->getResponse($key);
            if ($res['http_code'] === '200') {
                return true;
            }
        }
        return false;

    }

}