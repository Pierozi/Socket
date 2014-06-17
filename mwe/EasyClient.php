<?php

/**
 * Created by PhpStorm.
 * User: pierozi
 * Date: 17/05/14
 * Time: 21:34
 */

namespace PLAB\HoaSocket {

require_once(dirname(__DIR__)
    . DIRECTORY_SEPARATOR . 'vendor'
    . DIRECTORY_SEPARATOR . 'autoload.php');

class EasyClient
{
    const MODE_UNCRYPTED = 1;
    const MODE_CRYPTED = 2;

    public function __construct($mode = self::MODE_UNCRYPTED) {
        echo self::MODE_UNCRYPTED === $mode ? "[ MODE_UNCRYPTED ]\n\n" : "[ MODE_CRYPTED ]\n\n";

        $readline = new \Hoa\Console\Readline\Readline();

        do {
            $line = $readline->readLine('> ');

            $client   = new \Hoa\Socket\Client('tcp://127.0.0.1:1738');
            $client->connect();

            if (self::MODE_CRYPTED === $mode)
                $client->setEncryption(true, \Hoa\Socket\Client::ENCRYPTION_TLS);

            if (false === $line || 'quit' === $line)
                break;

            $client->writeString($line . "\r\n");
            $ack = $client->readString(20);

            echo "RECEIV => $ack\n";

            $client->disconnect();
        } while(true);
    }
}

new EasyClient(EasyClient::MODE_CRYPTED);

}


