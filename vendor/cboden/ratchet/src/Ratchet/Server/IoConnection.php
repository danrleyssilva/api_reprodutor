<?php
namespace Ratchet\Server;
use Ratchet\ConnectionInterface;
use React\Socket\ConnectionInterface as ReactConn;

/**
 * {@inheritdoc}
 */
class IoConnection implements ConnectionInterface {
    /**
     * Unique identifier assigned by IoServer
     *
     * @var int
     */
    public $resourceId;

    /**
     * Resolved remote address for logging
     *
     * @var string|null
     */
    public $remoteAddress;

    /**
     * Tracks HTTP handshake state
     *
     * @var bool
     */
    public $httpHeadersReceived = false;

    /**
     * Buffers handshake payload when needed
     *
     * @var string|null
     */
    public $httpBuffer;

    /**
     * Holds the current PSR-7 request
     *
     * @var \Psr\Http\Message\RequestInterface|null
     */
    public $httpRequest;

    /**
     * WebSocket session metadata storage
     *
     * @var object|null
     */
    public $WebSocket;

    /**
     * @var \React\Socket\ConnectionInterface
     */
    protected $conn;


    /**
     * @param \React\Socket\ConnectionInterface $conn
     */
    public function __construct(ReactConn $conn) {
        $this->conn = $conn;
    }

    /**
     * {@inheritdoc}
     */
    public function send($data) {
        $this->conn->write($data);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function close() {
        $this->conn->end();
    }
}

