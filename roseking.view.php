<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * RoseKing implementation : © Iwan Tomlow <iwan.tomlow@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * roseking.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in roseking_roseking.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
  
  require_once( APP_BASE_PATH."view/common/game.view.php" );
  
  class view_roseking_roseking extends game_view
  {
    function getGameName() {
        return "roseking";
    }

    function getCurrentPlayerColor($players) {
        $currentPlayerId = $this->game->getViewCurrentPlayerId();
        if (!isset($players[$currentPlayerId])) {
            return 'ff0000';
        }
        return $players[$currentPlayerId]['player_color'];
    }

    function getCurrentPlayerName($players) {
        $currentPlayerId = $this->game->getViewCurrentPlayerId();
        if (isset($players[$currentPlayerId])) {
            return self::_("My Hand");
        }
        $firstPlayerName = null;
        foreach($players as $player_id => $player) {
            $firstPlayerName = $players[$player_id]['player_name'];
            return $firstPlayerName;            
        }         
    }

    function getOpponentPlayerColor($players) {
        $currentPlayerId = $this->game->getViewCurrentPlayerId();
        if (!isset($players[$currentPlayerId])) {
            return 'ffffff';
        }
        foreach($players as $player_id => $player) {
            if ($currentPlayerId != $player_id) {
                return $players[$player_id]['player_color'];
            }            
        }        
        return 'ffffff';
    }

    function getOpponentPlayerName($players) {
        $currentPlayerId = $this->game->getViewCurrentPlayerId();
        if (isset($players[$currentPlayerId])) {
            return self::_("Opponent Hand");
        }
        $lastPlayerName = null;
        foreach($players as $player_id => $player) {
            $lastPlayerName = $players[$player_id]['player_name'];                       
        } 
        return $lastPlayerName;
    }    

  	function build_page( $viewArgs )
  	{		
  	    // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        /*********** Place your code below:  ************/

        $this->page->begin_block( "roseking_roseking", "square" );
        
        $hor_offset = 132;
        $ver_offset = 84;
        $hor_scale = 52;
        $ver_scale = 52;
        for( $x=1; $x<=9; $x++ )
        {
            for( $y=1; $y<=9; $y++ )
            {
                $this->page->insert_block( "square", array(
                    'X' => $x,
                    'Y' => $y,
                    'LEFT' => round( $hor_offset + ($x-1)*($hor_scale + 1.5) ),
                    'TOP' => round( $ver_offset + ($y-1)*($ver_scale + 2) )
                ) );
            }        
        }
        
        $this->tpl['MY_COLOR'] = $this->getCurrentPlayerColor($players);
        $this->tpl['MY_HAND'] = $this->getCurrentPlayerName($players);
                
        $this->tpl['OPPONENT_COLOR'] = $this->getOpponentPlayerColor($players);
        $this->tpl['OPPONENT_HAND'] = $this->getOpponentPlayerName($players);
        /*
        
        // Examples: set the value of some element defined in your tpl file like this: {MY_VARIABLE_ELEMENT}

        // Display a specific number / string
        $this->tpl['MY_VARIABLE_ELEMENT'] = $number_to_display;

        // Display a string to be translated in all languages: 
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::_("A string to be translated");

        // Display some HTML content of your own:
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::raw( $some_html_code );
        
        */
        
        /*
        
        // Example: display a specific HTML block for each player in this game.
        // (note: the block is defined in your .tpl file like this:
        //      <!-- BEGIN myblock --> 
        //          ... my HTML code ...
        //      <!-- END myblock --> 
        

        $this->page->begin_block( "roseking_roseking", "myblock" );
        foreach( $players as $player )
        {
            $this->page->insert_block( "myblock", array( 
                                                    "PLAYER_NAME" => $player['player_name'],
                                                    "SOME_VARIABLE" => $some_value
                                                    ...
                                                     ) );
        }
        
        */



        /*********** Do not change anything below this line  ************/
  	}
  }
  

