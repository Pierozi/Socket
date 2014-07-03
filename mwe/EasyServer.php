<?php
/**
 * Created by PhpStorm.
 * User: pierozi
 * Date: 17/05/14
 * Time: 17:08
 */

namespace PLAB\HoaSocket {

require_once(dirname(__DIR__)
    . DIRECTORY_SEPARATOR . 'vendor'
    . DIRECTORY_SEPARATOR . 'autoload.php');

class Node extends \Hoa\Socket\Node
{
    public function getPeerName($wantPeer = true) {

        return stream_socket_get_name($this->getSocket(), $wantPeer);
    }
}

class EasyServer
{
    const MODE_UNCRYPTED = 1;
    const MODE_CRYPTED = 2;

    /**
     * Initialise Server in specific mode, Uncrypted or Crypted
     * @param int $mode
     */
    public function __construct($mode = self::MODE_UNCRYPTED) {
        switch ($mode) {
            case self::MODE_UNCRYPTED:
                echo "[ MODE_UNCRYPTED ]\n\n";
                $this->uncrypted();
                break;

            case self::MODE_CRYPTED:
                echo "[ MODE_CRYPTED ]\n\n";
                $this->crypted();
                break;
        }
    }

    /**
     * Listen server with uncrypted mode
     */
    protected function uncrypted() {

        $Server = new \Hoa\Socket\Server('tcp://127.0.0.1:1738', 30, -1);
        $Server->setNodeName('\PLAB\HoaSocket\Node');
        $Server->connectAndWait();

        echo "[Connection WAIT]\n\n";

        while(true) foreach($Server->select() as $Node)
            $this->hoaRead($Node);
    }

    /**
     * Listen server with crypted mode
     */
    protected function crypted() {

        $Context = \Hoa\Stream\Context::getInstance("EasyServer/SSL/PLAB");

        //TODO OPEN HOA ISSUE FOR Options compatibility
        $Context->setOptions(array(
            'ssl' => array(
                'local_cert' => __DIR__ . '/cert/server.pem',
                'passphrase' => 'CERT_PASSPHRASE',
                'allow_self_signed' => true,
                'verify_peer' => false
            )
        ));

        $Server = new \Hoa\Socket\Server('tcp://0.0.0.0:1738', 30, -1, "EasyServer/SSL/PLAB");
        $Server->setNodeName('\PLAB\HoaSocket\Node');

        $Server->connectAndWait();
        echo "[Connection WAIT]\n\n";

        while(true) foreach($Server->select() as $Node) {
            $this->hoaRead($Node, true);
            //$this->nativeRead($Node->getSocket());
        }
    }

    /**
     * Read data with Hoa way
     * @param \App\EasyServer\Node $Node
     */
    protected function hoaRead($Node, $isCrypted = false) {
        $NodeConnection = $Node->getConnection();
        echo "Connection from [" . $Node->getPeerName() . "|". $NodeConnection->getRemoteAddress() ."]\n";

        if ($isCrypted && !$Node->getEncryptionType())
            $NodeConnection->enableEncryption(true, \Hoa\Socket\Server::ENCRYPTION_TLS);

        $line = $NodeConnection->read(20);
        echo ' < ', $line, "\n";

        $NodeConnection->writeString('ACK');
        $NodeConnection->disconnect();
    }

    /**
     * Read data with php native way
     * @param resource $socket
     */
    protected function nativeRead($socket) {
        $PeerName = stream_socket_get_name($socket, true);
        echo "Connection from " . $PeerName . "\n";

        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_SERVER);

        $buf = fread($socket, 20);

        echo "Receive { $buf }\n";
        echo "Sending ACK...", PHP_EOL;

        fwrite($socket, "ACK");
        fclose($socket);
    }

    /**
     * Save data in log file
     * @param string $raw
     */
    public function writeLog($raw) {

        $date = date('d-m-Y');
        $handle = fopen("./log/$date.log", "a+");

        fwrite($handle, date('[d-m-Y:H:i:s] ') . $raw . "\n");
    }
}

new EasyServer(EasyServer::MODE_CRYPTED);

}







