<?php
/**
 * Created by Riat Abduramani.
 * Date: 11/13/2018
 * Time: 8:05 AM
 */

namespace GameOfThree;

class Player extends Game
{
    const CALL_SERVICE = 'playGames/';
    const INPUT_OPTIONS = [-1, 0, 1];
    protected $allowedOptions = [-1, 0, 1];
    protected $play_id;
    private $_conn;

    public function __construct()
    {
        parent::__construct();
        $this->_conn = new Api;
        $this->_conn->call_service = self::CALL_SERVICE;
        $this->run();
    }

    public function run()
    {
        $i = 0;
        do {

            if ($this->_newgame == true) {
                $this->nextPlayer();
            } else {
                $i++;

                $response = $this->getPlayById();

                $gameExists = $this->getGameByID();

                if (isset($gameExists['value']) && ($gameExists['value'] == 1)) {
                    $this->_isOver = true;
                    $this->_isWinner = false;
                    $this->endGame();
                }

                if (isset($response['id']) && ($this->player_name != $response['player_name'])) {
                    $this->_value = $response['value'];

                    if ($response['value'] == 1) {
                        $this->_isOver = true;
                        $this->endGame();
                    }

                    $this->nextPlayer();
                }

                $this->timeout($i);
            }

        } while ($this->_isOver == false);


    }

    public function nextPlayer()
    {
        $return_value = $this->_value;
        if ($this->_newgame == true) {
            echo "\n[x] Send value: " . $return_value . "\n";
            $option = null;
        } else {
            echo "\n[x] Received value: " . $return_value;

            if (empty($this->play_id)) {
                $this->setPlayId();
            }

            $option = $this->findNumber();

            $return_value += $option;
            echo "\n[x] Value changed to: " . $return_value . " with option: " . $option;
            echo "\n[x] Send value: " . $return_value / 3 . "\n";

        }

        $request = $this->sendRequest($this->request($option));

        if ($request === true) {
            $this->checkValue();
            echo "[!] Waiting for the next player...\n";
        }


    }

    public function setPlayId()
    {

        if ($this->_newgame == false) {
            $i = 0;
            do {
                $games = $this->_conn->get();
                $i++;
                if (!empty($games) && is_array($games)) {
                    foreach ($games as $game) {
                        if (isset($game['game_id']) && ($game['game_id'] == $this->_gameID)) {
                            $this->play_id = $game['id'];
                            $this->_value = $game['value'];
                        }
                    }
                }

                $this->timeout($i);
            } while ($this->play_id == null);
        }
    }

    public function findNumber()
    {
        $input = null;
        foreach ($this->allowedOptions as $option) {
            $number = $this->_value;
            if (((($number + ($option))) % 3) == 0) {
                $input = $option;
                $this->_value += $input;
                break;
            }
        }

        $this->_value /= 3;
        return $input;
    }

    private function sendRequest($request)
    {
        $this->_conn->call_service = self::CALL_SERVICE . $this->play_id;

        if ($this->_newgame == true) {
            $response = $this->_conn->post($request);

            if (isset($response['id']) && !empty($response['id'])) {
                $this->play_id = $response['id'];
                $this->_newgame = false;
                return true;
            }
        }

        if ($this->_newgame == false) {
            $response = $this->_conn->put($request);
            if (isset($response['id']) && !empty($response['id'])) {
                return true;
            }
        }

        return false;
    }

    public function request($option)
    {
        $request = [
            "id" => $this->play_id,
            "game_id" => $this->_gameID,
            "player_name" => $this->player_name,
            "option" => $option,
            "value" => $this->_value,
            "played_at" => date('Y-m-d')
        ];

        return $request;
    }

    protected function checkValue()
    {
        if ($this->_value == 1) {
            $this->_isOver = true;
            $this->_isWinner = true;
            $this->endGame();
        }
    }

    public function getPlayById()
    {
        $this->setPlayId();
        $this->_conn->call_service = self::CALL_SERVICE . $this->play_id;
        return $this->_conn->get();
    }
}