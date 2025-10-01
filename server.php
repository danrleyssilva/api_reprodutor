<?php
require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class VideoSyncServer implements MessageComponentInterface
{
    protected $clients;
    protected $clientGroups;
    protected $clientConnectTime; // Novo: armazena timestamp de conexão
    protected $grupoVideos;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->clientGroups = [];
        $this->clientConnectTime = [];
        $this->grupoVideos = [];
        echo "Servidor WebSocket iniciado...\n";
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $this->clientConnectTime[$conn->resourceId] = (int) round(microtime(true) * 1000);
        echo "Novo dispositivo conectado ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        if (!$data || !isset($data['command'])) {
            echo "Mensagem inválida recebida de {$from->resourceId}: {$msg}\n";
            return;
        }

        // echo $data;


        switch ($data['command']) {

            case 'ready':
                $grupo = $data['grupo'] ?? 'default';
                $this->clientGroups[$from->resourceId] = $grupo;
                $url = $data['url'] ?? null;

                echo "Cliente {$from->resourceId} pronto no grupo {$grupo}\n";

                // Se ainda não houver vídeo definido para o grupo, define pelo ready
                if (!isset($this->grupoVideos[$grupo]) && $url) {
                    $this->grupoVideos[$grupo] = [
                        'url' => $url,
                        'startTimestamp' => (int) round(microtime(true) * 1000) + 3000
                    ];
                    echo "Grupo {$grupo} iniciado pelo cliente {$from->resourceId} com URL: {$url}\n";
                }

                // Se já existe vídeo em execução, calcula a posição atual
                if (isset($this->grupoVideos[$grupo])) {
                    $videoInfo = $this->grupoVideos[$grupo];
                    $now = (int) round(microtime(true) * 1000);
                    $position = max(0, $now - $videoInfo['startTimestamp']); // posição atual do vídeo
                    $from->send(json_encode([
                        'action' => 'sync',
                        'url' => $videoInfo['url'],
                        'position' => $position
                    ]));
                    echo "Cliente {$from->resourceId} sincronizado com vídeo do grupo {$grupo} na posição {$position}\n";
                }
                break;

            case 'start':
                $grupo = $data['grupo'] ?? 'default';
                $url = $data['url'] ?? null;

                if ($url) {
                    $this->grupoVideos[$grupo] = [
                        'url' => $url,
                        'startTimestamp' => (int) round(microtime(true) * 1000) + 3000
                    ];

                    $payload = json_encode([
                        'action' => 'play',
                        'url' => $url,
                        'startTimestamp' => $this->grupoVideos[$grupo]['startTimestamp']
                    ]);

                    $this->broadcast($payload, $grupo);
                    echo "Iniciando vídeo no grupo {$grupo}: {$url}\n";
                }
                break;

            case 'progress':
                $grupo = $this->clientGroups[$from->resourceId] ?? 'default';
                $position = $data['position'] ?? 0;
                $duration = $data['duration'] ?? 0;

                echo "Progresso do grupo {$grupo} enviado pelo cliente {$from->resourceId}: posição {$position}";
                if ($duration > 0) {
                    echo " / duração {$duration}";
                }
                echo "\n";

                if (isset($this->grupoVideos[$grupo])) {
                    $payload = json_encode([
                        'action' => 'sync',
                        'url' => $this->grupoVideos[$grupo]['url'],
                        'position' => $position
                    ]);

                    $now = (int) round(microtime(true) * 1000);

                    foreach ($this->clients as $client) {
                        if ($client !== $from) {
                            $clientGrupo = $this->clientGroups[$client->resourceId] ?? 'default';

                            // Só envia sync se cliente estiver conectado há mais de 3 segundos
                            $connectTime = $this->clientConnectTime[$client->resourceId] ?? 0;
                            if ($clientGrupo === $grupo && ($now - $connectTime > 3000)) {
                                $client->send($payload);
                            }
                        }
                    }
                }
                break;

            default:
                echo "Comando desconhecido de {$from->resourceId}: {$msg}\n";
                break;
        }
    }

    protected function broadcast($message, $grupo)
    {
        foreach ($this->clients as $client) {
            $clientGrupo = $this->clientGroups[$client->resourceId] ?? 'default';
            if ($clientGrupo === $grupo) {
                $client->send($message);
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        unset($this->clientGroups[$conn->resourceId]);
        unset($this->clientConnectTime[$conn->resourceId]);
        echo "Dispositivo desconectado ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Erro: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Rodando na porta 8080
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new VideoSyncServer()
        )
    ),
    8080
);

$server->run();