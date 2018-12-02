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

                if (!isset($gameExists['id'])) {
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

        if ($this->_newgame == false && $this->_isOver == false && empty($this->play_id)) {
            $this->setPlayId();
        }

        $return_value = $this->_value;
        echo "\n[x] Value to play: " . $return_value . "\n";

        $option = $this->findNumber();

        if (!empty($option) && $option != false) {
            $return_value += $option;
            echo "\nValue changed to: " . $return_value . " with option: " . $option . "\n";
        }

        $request = $this->sendRequest($this->request($option));

        if ($request === true) {

            $this->checkValue();

            echo "Waiting for the next player!\n";

            return true;
        }

        return false;
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
        $input = '';
        foreach (self::INPUT_OPTIONS as $option) {
            if (($this->_value + ($option)) % 3 == 0) {
                $input = $option;
                $this->_value += $option;
                break;
            }
        }

        $this->_value /= 3;
        return (!empty($input)) ? $input : false;
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