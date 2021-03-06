<?php
include_once 'Cfg.php';
include_once 'Inc/Enums.php';
include_once 'Protocol/BlockInvalid.php';
include_once 'Protocol/Block.php';
include_once 'Protocol/BlockLine.php';
include_once 'Protocol/BlockHydra.php';
include_once 'Protocol/GenericNode.php';
include_once 'Protocol/Packet.php';
include_once 'Protocol/Head.php';
include_once 'Protocol/GenericSync.php';
include_once 'Protocol/SyncHead.php';
include_once 'Protocol/SyncPacket.php';
include_once 'Protocol/Dynamic.php';

class SocketServer {

    private $Base64Regex = "[a-zA-Z0-9+/=]";


    public function __construct() {

    }

    public function Listener() {
        set_time_limit(0);
        ob_implicit_flush();

        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            echo "socket_create() falló: razón: " . socket_strerror(socket_last_error()) . "\n";
        }

        if (socket_bind($sock, SOCKET_SERVERADDRESS, SOCKET_SERVERPORT) === false) {
            echo "socket_bind() falló: razón: " . socket_strerror(socket_last_error($sock)) . "\n";
        }

        if (socket_listen($sock, 5) === false) {
            echo "socket_listen() falló: razón: " . socket_strerror(socket_last_error($sock)) . "\n";
        }

        //clients array
        $clients = array();

        do {
            $read = array();
            $read[] = $sock;

            $read = array_merge($read, $clients);
            $write = null;
            $except = null;
            // Set up a blocking call to socket_select
            if (socket_select($read, $write, $except, $tv_sec = 5) < 1) {
                //SocketServer::debug("Problem blocking socket_select?");
                continue;
            }

            // Handle new Connections
            if (in_array($sock, $read)) {

                if (($msgsock = socket_accept($sock)) === false) {
                    echo "socket_accept() falló: razón: " . socket_strerror(socket_last_error($sock)) . "\n";
                    break;
                }
                $clients[] = $msgsock;
                $key = array_keys($clients, $msgsock);
                $IP = null;
                socket_getpeername($clients[0], $IP);

                /* Enviar instrucciones. */
                $msg = "\nBienvenido al Servidor De Prueba de PHP. \n" . "Usted es el cliente numero: {$key[0]}, su IP es: $IP \n" . "Para salir, escriba 'quit'. Para cerrar el servidor escriba 'shutdown'.\n\n";
                socket_write($msgsock, $msg, strlen($msg));
            }

            foreach ($clients as $key => $client) { // for each client
                if (in_array($client, $read)) {
                    if (false === ($buf = @socket_read($client, 2048))) {
                        echo "socket_read() falló: razón: " . socket_strerror(socket_last_error($client)) . "\n";
                        unset($clients[$key]);
                        socket_close($client);
                        //break 2;
                    }
                    if (!$buf = trim($buf)) {
                        continue;
                    }
                    if ($buf == 'quit') {
                        unset($clients[$key]);
                        socket_close($client);
                        break;
                    }

                    $Blocks = $this->DecodeBlocks($buf, $IP);
                    $BlocksResp = array();
                    foreach ($Blocks as $Block) $BlocksResp[] = $Block->ToResponse();
                    $Response = join("\n", $BlocksResp) . "\n\n";
                    socket_write($client, $Response, strlen($Response));
                    //$talkback = "Cliente {$key}: Usted dijo\r\n$buf.\n\n";
                    //socket_write($client, $talkback, strlen($talkback));
                    //Block::NewBlockByHeaderContentHash();

                    echo "$buf\n";
                }
            }
        } while (true);
    }

    /**
     * @param $ReceivedBlocks
     * @return array|block[]|Block
     */
    public function DecodeBlocks($ReceivedBlocks, $IP) {
        $Matches = null;
        $Blocks = Array();
        $Pattern = "@^(?<HydraBlock>§Hydra(?<ContentHydra>.*?)Hydra§(?=\n§|$))|" . "(?<Header>§(?<LeftHeader>[a-zA-Z0-9\-:|]+)§(?<Pbk>$this->Base64Regex+))\n" . "(?:(?<ContentBlock>.*?)\n|)" . "(?<Signature>$this->Base64Regex+?)(?=\n§|$)@si";
        preg_match_all($Pattern, $ReceivedBlocks, $Matches, PREG_SET_ORDER);
        if (count($Matches) == 0 || substr($ReceivedBlocks, 0, strlen($Matches[0][0])) != $Matches[0][0]) return -1;//Formato Invalido//return $this->ServerResponse($Hydra, $Blocks);
        foreach ($Matches as $BlockMatch) {
            if ($BlockMatch['HydraBlock'] != '') {
                //try {
                $NewBlock = new BlockHydra($this->GetBlockLines($BlockMatch['ContentHydra']));//Aun no defino si tendra catch este bloque,
                /*} catch (BlockInvalid $InvalidBlock) {

                }catch (Exception $Exception){}*/
            } else {
                try {
                    $NewBlock = Block::NewBlockByHeaderContentHash($BlockMatch['Header'], $BlockMatch['ContentBlock'], base64_decode($BlockMatch['Signature']));
                    $NewBlock->SetIP($IP);
                } catch (BlockInvalid $InvalidBlock) {
                    $NewBlock = $InvalidBlock;
                } catch (Exception $Exception) {
                    $NewBlock = new BlockInvalid(NewBlockResponse::UnexpectedErrorInCipher);
                }
            }

            $Blocks[] = $NewBlock;
        }
        return $Blocks;
    }

    public function GetBlockLines($BlockContent) {
        $BlockLines = Array();
        $Lines = explode("\n", $BlockContent);
        foreach ($Lines as $Line) {
            $NameValue = explode(':', $Line, 1);
            $BlockLines[trim($NameValue[0])] = array_key_exists(1, $NameValue) ? $NameValue[1] : null;
        }
    }

    public
    function ServerResponse($Hydra, $Blocks) {

    }

}
