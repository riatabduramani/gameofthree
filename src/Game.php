<?php
/**
 * Created by Riat Abduramani.
 * Date: 11/13/2018
 * Time: 8:07 AM
 */

namespace GameOfThree;

class Game
{
    const CALL_SERVICE = 'games/';
    const TIMEOUT = 10;
    const STARTING_VALUE = [100, 500];
    public $player_name;
    protected $_channel;
    protected $_gameID;
    protected $_isOver = false;
    protected $_isWinner = false;
    protected $available_channels = 0;
    protected $_newgame = false;
    protected $_value;
    protected $_timeout = false;
    private $_conn;

    public function __construct()
    {
        $this->_conn = new Api();
        $this->_conn->call_service = self::CALL_SERVICE;
        $this->welcome();
        $this->joinGame();
    }

    public function welcome()
    {
        echo "\n\n";
        echo "***********\n\n";
        echo "Welcome to the GameOfThree. Enjoy your play!\n\n";
        echo "***********\n\n";
    }

    protected function joinGame()
    {
        $channels = $this->_conn->get();
        $response = [];

        if (!empty($channels) && is_array($channels)) {
            $key = 1;
            foreach ($channels as $channel) {
                if (!empty($channel['player_2']))
                    continue;

                $response[$key] = $channel;
                echo $key . ' => ' . $channel['channel'] . "\n";
                $key++;
            }
            $this->available_channels = count($response);
        }


        if ($this->available_channels > 0 && $this->_isOver === false) {

            echo "\n";
            $input = readline("Enter channel number to join the game or enter 'n' for new game: ");
            echo "\n";

            if ((is_numeric($input) && $input <= (count($response))) || $input == 'n') {
                switch ($input) {
                    case "n":
                        $this->createGame();
                        break;
                    case $input > 0:
                        $response = $response[$input];
                        $this->_gameID = $response['id'];
                        $request = $response;
                        $request['player_2'] = 'Player B';
                        $this->addPlayer($request);
                        $this->messageGameStarted($request['player_2']);
                        break;
                }


            } else {
                self::joinGame();
            }
        } else {
            echo "Not available Game Channels.\n\n";
            $this->createGame();
        }
    }

    protected function createGame(array $request = [])
    {
        echo "*** START NEW GAME ***\n\n";

        $request['player_1'] = 'Player A';
        $request['started_at'] = date('Y-m-d');
        $request['channel'] = uniqid('GAME_CHANNEL_');
        $request['value'] = rand(self::STARTING_VALUE[0], self::STARTING_VALUE[1]);
        $this->_value = $request['value'];
        $response = $this->_conn->post($request);

        if (isset($response['id'])) {
            $this->_channel = $response['channel'];
            $this->_gameID = $response['id'];
            $this->_newgame = true;

            print "Waiting for the second player to Join.\n";

            $playerFound = false;
            $i = 0;
            while ($playerFound == false && $this->_isOver == false) {
                $trying = $this->getGameByID();
                $i++;

                if (!empty($trying['player_2'])) {
                    $playerFound = true;
                    echo "'" . $trying['player_2'] . "' joined game.\n";
                    $this->messageGameStarted($trying['player_1']);
                    $this->_gameID = $trying['id'];
                }

                $this->timeout($i);
            }
        }
    }

    protected function getGameByID()
    {
        $service_url = self::CALL_SERVICE . $this->_gameID;
        $this->_conn->call_service = $service_url;

        return $this->_conn->get();
    }

    protected function messageGameStarted($player_name)
    {
        echo "\n";
        echo "GAME STARTED\n";
        echo "Good luck '" . $player_name . "!'\n";
        $this->player_name = $player_name;
    }

    protected function timeout($count)
    {
        if ($count >= self::TIMEOUT) {
            $this->_isOver = true;
            $this->_timeout = true;
            $this->endGame();
        }
    }

    protected function endGame()
    {
        if ($this->_isOver == true && $this->_isWinner === false) {
            $this->_conn->delete();
        }

        if ($this->_timeout === false) {
            if ($this->_isWinner === true) echo "\nYou win the game!\n";
            if ($this->_isWinner === false) echo "\nYou just lose!\n";
        }

        if ($this->_timeout === true) echo "\nTimeout!\n";

        echo "GAME OVER\n";
        return exit;
    }

    protected function addPlayer($request)
    {
        $service = self::CALL_SERVICE . $this->_gameID;
        $this->_conn->call_service = $service;
        $response = $this->_conn->put($request);

        return $response;
    }

}