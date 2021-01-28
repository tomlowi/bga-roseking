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
  * roseking.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class RoseKing extends Table
{
    const BOARD_SIZE = 9;
    const HAND_MAX_CARDS = 5;
    const PLAYER_MAX_KNIGHTS = 4;
    const TOKEN_TOTAL_COUNT = 52;

	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels( array( 
            "tokensLeft" => 10,
            "kingPositionX" => 11,
            "kingPositionY" => 12,
            "redKnightCount" => 13,
            "whiteKnightCount" => 14,
            "lastPlayedCardId" => 15
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ));

        $this->cards = self::getNew("module.common.deck");
		$this->cards->init("card");
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "roseking";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );

        //$random_startPlayer = bga_rand(1, 2);

        // Rose King = always red/white, no point in supporting player preferences
        // according to the rules, red player will always start
        //self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();        
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::setGameStateInitialValue('tokensLeft', self::TOKEN_TOTAL_COUNT);
        self::setGameStateInitialValue('kingPositionX', (self::BOARD_SIZE+1)/2);
        self::setGameStateInitialValue('kingPositionY', (self::BOARD_SIZE+1)/2);
        self::setGameStateInitialValue('redKnightCount', self::PLAYER_MAX_KNIGHTS);
        self::setGameStateInitialValue('whiteKnightCount', self::PLAYER_MAX_KNIGHTS);
        self::setGameStateInitialValue('lastPlayedCardId', null);
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        self::initStat( 'table', 'turns_number', 0 );    // Init a table statistics
        self::initStat( 'table', 'tokens_left', self::TOKEN_TOTAL_COUNT );
        self::initStat( 'player', 'turns_number', 0 );
        self::initStat( 'player', 'tokens_placed', 0 );
        self::initStat( 'player', 'cards_drawn', 0 );
        self::initStat( 'player', 'knights_left', self::PLAYER_MAX_KNIGHTS );
        self::initStat( 'player', 'largest_region_end', 0 );
        self::initStat( 'player', 'largest_region_any', 0 );

        // Create cards, they are organized in 3 rows with distance 1 / 2 / 3,
        // each having directions N / NE / E / SE / S / SW / W / NW
        $cards = array();
		foreach($this->card_directions as $direction_id => $direction)
		{
			for ($value = 1; $value <= 3; $value++) // 3 distances
            {
                $cards[] = array(
                    'type' => $direction_id,
                    'type_arg' => $value,
                    'nbr' => 1
                );
            }			
		}
        $this->cards->createCards($cards, 'deck');
        $this->cards->shuffle('deck');
        // and deal 5 to each player to start with
        foreach($players as $player_id => $player) {
            $initPlayerCards = $this->cards->pickCards(self::HAND_MAX_CARDS, 'deck', $player_id);
        }

        // Init the board
        $sql = "INSERT INTO board (board_x,board_y,board_player) VALUES ";
        $sql_values = array();
        list( $redplayer_id, $whiteplayer_id ) = array_keys( $players );
        for( $x=1; $x<=self::BOARD_SIZE; $x++ )
        {
            for( $y=1; $y<=self::BOARD_SIZE; $y++ )
            {
                $token_value = "NULL";
                // // example: show some tokens on initial positions
                // if( ($x==4 && $y==4) || ($x==5 && $y==5) )
                //     $token_value = "'$whiteplayer_id'";
                // else if( ($x==4 && $y==5) || ($x==5 && $y==4) )
                //     $token_value = "'$redplayer_id'";                    
                $sql_values[] = "('$x','$y',$token_value)";
            }
        }
        $sql .= implode( $sql_values, ',' );
        self::DbQuery( $sql );

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $players = self::getCollectionFromDb( $sql );
        $result['players'] = $players;

        list( $redplayer_id, $whiteplayer_id ) = array_keys( $players );
        $result['redplayer'] = $redplayer_id;
        $result['whiteplayer'] = $whiteplayer_id;
        // Gather all information about current game situation (visible by player $current_player_id).
        $showToSpectator = false;
        if (!isset($players[$current_player_id])) {
            $showToSpectator = true;
        }
        // Cards in player hands      
        foreach($players as $player_id => $player) {
           $nextPlayerHand = $this->cards->getCardsInLocation('hand', $player_id);
           if ($player_id == $current_player_id || $showToSpectator) {
            $result['myHand'] = $nextPlayerHand;
            $showToSpectator = false;
           } else {
            $result['opponentHand'] = $nextPlayerHand;
           }
        }
        // Cards already on discard pile 
        $lastPlayedCardId = self::getGameStateValue('lastPlayedCardId');      
        $result['lastCardPlayed'] = $this->cards->getCard($lastPlayedCardId);
        // Remaining cards in deck
        $result['drawDeckCnt'] = $this->cards->countCardsInLocation('deck');

        // Tokens already on board
        $result['board'] = self::getObjectListFromDB( "SELECT board_x x, board_y y, board_player player_id
                                                       FROM board
                                                       WHERE board_player IS NOT NULL" );
        // King position
        $result['kingTokenX'] = self::getGameStateValue('kingPositionX');
        $result['kingTokenY'] = self::getGameStateValue('kingPositionY');
        // Situation counters
        $result['tokensLeft'] = (int)self::getGameStateValue('tokensLeft');
        $result['redKnightCnt'] = (int)self::getGameStateValue('redKnightCount');
        $result['whiteKnightCnt'] = (int)self::getGameStateValue('whiteKnightCount');
  
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // compute and return the game progression
        // game ends when last token is played => easy
        // game can also end when both players have no more valid moves, but that is impossible to predict

        $playedTokenCount = self::TOKEN_TOTAL_COUNT - (int)self::getGameStateValue('tokensLeft');

        return ($playedTokenCount / self::TOKEN_TOTAL_COUNT) * 100;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function getViewCurrentPlayerId() {
        return self::getCurrentPlayerId();
    }

    function drawpileEmptyCheck() {
		if ($this->cards->countCardInLocation('deck') == 0)
		{
			$this->cards->moveAllCardsInLocation('discardPile', 'deck');
			$this->cards->shuffle('deck');
		}
    }

    // Get the complete board with a double associative array
    function getBoard()
    {
        return self::getDoubleKeyCollectionFromDB( "SELECT board_x x, board_y y, board_player player
                                                       FROM board", true );
    }
    
    // check if this player has any actual plays they can make, or should forced pass
    function canDrawCard( $player_id ) {
        $playerHandCount = $this->cards->countCardsInLocation('hand', $player_id);
        return $playerHandCount < self::HAND_MAX_CARDS;
    }

    function canActuallyPlay( $player_id ) {
        // if player can still draw cards, they still have options
        if (self::canDrawCard( $player_id )) {
            return true;
        }
        // else check the possible moves
        $playerKnightCount = self::getRemainingKnightCountForPlayer($player_id);
        $possibleMoves = self::getPossibleMoves($player_id);
        // if no possible moves at all, and can't draw card => can't play
        if (count($possibleMoves) == 0) {
            return false;
        }
        // if any possible moves and player still has knights => ok, can play
        if ($playerKnightCount > 0) {
            return true;
        }
        // if no knights, we should verify that there is any possible move
        // that doesn't require a knight
        foreach( $possibleMoves as $card_id => $card_move ) {
            if ($card_move['knight'] == false) {
                return true;
            }
        }
        // out of options => can't play
        return false;
    }

    function getPlayerKnightCountLabel($player_id) {
        $players = self::loadPlayersBasicInfos();
        $playerColor = $players[$player_id]['player_color'];
        if ($playerColor == 'ffffff') {
            $labelKnightCount = 'whiteKnightCount';
        } else {
            $labelKnightCount = 'redKnightCount';                                        
        }
        return $labelKnightCount;
    }
    
    function getRemainingKnightCountValue($gameStateLabel) {        
        $currentKnightCount = (int)self::getGameStateValue($gameStateLabel);
        return $currentKnightCount;
    }

    function getRemainingKnightCountForPlayer($player_id) {    
        return self::getRemainingKnightCountValue( self::getPlayerKnightCountLabel($player_id) );
    }

    // determine possible moves for this player (regardless of knights available)
    function getPossibleMoves( $player_id )
    {
        $result = array();
        // get the current hand for this player
        $playerHandCards = $this->cards->getCardsInLocation('hand', $player_id);
        // get the current board situation
        $board = self::getBoard();
        $kingTokenX = (int)self::getGameStateValue('kingPositionX');
        $kingTokenY = (int)self::getGameStateValue('kingPositionY');

        // 8 directions impact X/Y coordinates like this
        // N, NE, E, SE, S, SW, W, NW
        $directions = array(
            array( 0,-1 ), array( 1,-1 ), array( 1, 0 ), array( 1,1 ),
            array( 0,1 ), array( -1,1), array( -1,0 ), array( -1, -1 )
        );

        // for each card, check if this card move would end in a valid position
        // also indicate this move would need a knight (if player still has knights to play)
        foreach($playerHandCards as $card_id => $card) {
            $moveDirection = $card['type'];
            $moveDistance = $card['type_arg'];

            $nextTokenX = $kingTokenX + $directions[$moveDirection-1][0] * $moveDistance;
            $nextTokenY = $kingTokenY + $directions[$moveDirection-1][1] * $moveDistance;

            $moveIsValid = true;
            $moveNeedsKnight = false;
            if ($nextTokenX < 1 || $nextTokenX > 9 || $nextTokenY < 1 || $nextTokenY > 9) {
                // move would fall off the board
                $moveIsValid = false;
            } else {
                // ok, move ends on the board: what is there now?
                if ($board[ $nextTokenX ][ $nextTokenY ] === null) {
                    // empty square = valid move
                } else if ($board[ $nextTokenX ][ $nextTokenY ] == $player_id) {
                    // player already has token there > impossible to go there again
                    $moveIsValid = false;
                } else {
                    // opponent is there > valid move if knight available
                    $moveNeedsKnight = true;
                }
            }

            if ($moveIsValid) {
                $result[$card_id] = array(
                    'x' => $nextTokenX, 'y' => $nextTokenY,
                    'knight' => $moveNeedsKnight
                );
            }            
        }
            
        return $result;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in roseking.action.php)
    */

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    function tryMoveToLocation($x, $y)
    {
        // Check that this player is active and move action is possible at this moment
        self::checkAction( 'move' );
        $player_id = self::getActivePlayerId();
        $possibleMoves = self::getPossibleMoves( $player_id );

        $validMove = false;
        // find the card we need to play to get to this location
        foreach( $possibleMoves as $card_id => $card_move ) {

            if ($card_move['x'] == $x && $card_move['y'] == $y) {
                $validMove = true;
                self::checkMoveWithCard($card_id, $player_id, 
                    $card_move['x'], $card_move['y'], $card_move['knight']);
            }
        }

        if (!$validMove) {
            throw new BgaUserException( self::_("No valid move to this location") );
        }
        
    }

    function tryMoveWithCard($card_id)
    {
        // Check that this player is active and move action is possible at this moment
        self::checkAction( 'move' ); 
        $player_id = self::getActivePlayerId();
        $possibleMoves = self::getPossibleMoves( $player_id );

        if (array_key_exists($card_id, $possibleMoves)) {
            $card_move = $possibleMoves[$card_id];
            $needKnight = $card_move['knight'];

            self::checkMoveWithCard($card_id, $player_id, 
                $card_move['x'], $card_move['y'], $needKnight);            
        } else {
            throw new BgaUserException( self::_("No valid move with this card") );
        }        
    }

    function checkMoveWithCard($card_id, $player_id, $x, $y, $needKnight) {
        $currentKnightCount = 0;
        $labelKnightCount = '';
        if ($needKnight) {   
            $labelKnightCount = self::getPlayerKnightCountLabel($player_id);
            $currentKnightCount = self::getRemainingKnightCountValue($labelKnightCount);
            if ($currentKnightCount <= 0) {
                throw new BgaUserException( self::_("You have no heroes left to fight") );
            }
        }

        self::moveWithCard($card_id, $player_id, $x, $y, 
            $needKnight, $labelKnightCount, $currentKnightCount);
    }

    function moveWithCard($card_id, $player_id, $x, $y, 
        $needKnight, $labelKnightCount, $currentKnightCount)
    {
        // verify this card is currently in player's hand        
        $playerhands = $this->cards->getCardsInLocation('hand', $player_id);
        $bIsInHand = false;
        $cardPlayed = null;
        foreach($playerhands as $card) {
			if ($card['id'] == $card_id) {
				$bIsInHand = true;
				$cardPlayed = $card;
            }
        }
        if (!$bIsInHand) throw new BgaVisibleSystemException( self::_("This card is not in your hand") );

        // discard the card played from current player's hand
        $this->cards->moveCard($card_id, 'discardPile', $player_id);

        // place player token on the board
        $sql = "UPDATE board SET board_player='$player_id'
                    WHERE ( board_x, board_y) = ";
        $sql .= "('$x','$y') ";
        self::DbQuery( $sql );

        self::incStat(1, "tokens_placed", $player_id);

        // move the king to the new location
        self::setGameStateValue('kingPositionX', $x);
        self::setGameStateValue('kingPositionY', $y);

        // keep track of the last played card to show on discard pile
        self::setGameStateValue('lastPlayedCardId', $card_id);

        // discard knight if we needed to fight instead of just move
        if ($needKnight) {
            $knightsLeft = $currentKnightCount - 1;
            self::setGameStateValue($labelKnightCount, $knightsLeft);
            self::setStat($knightsLeft, "knights_left", $player_id);
            self::notifyAllPlayers( $labelKnightCount, clienttranslate( '${player_name} uses a hero to fight' ), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'knights_left' => $knightsLeft
            ) );
        } else {
            $tokenCount = (int)self::getGameStateValue('tokensLeft');
            $tokensLeft = $tokenCount - 1;
            self::setGameStateValue('tokensLeft', $tokensLeft);
            self::setStat($tokensLeft, "tokens_left");
        }     
        // send client notifications
        self::notifyAllPlayers( "playedCard", clienttranslate( '${player_name} plays power card ${card_distance} ${name_direction}${arrow}' ), array(
            'i18n' => array(
				'card_direction'
			),
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_id' => $card_id,
            'card_direction' => $cardPlayed['type'],            
            'name_direction' => $this->card_directions[$cardPlayed['type']]['name'],
            'arrow' => $this->icons[$cardPlayed['type']],
            'card_distance' => $cardPlayed['type_arg']
        ) );
        self::notifyAllPlayers( "playedMove", clienttranslate( '${player_name} moves to x=${x},y=${y}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'x' => $x,
            'y' => $y
        ) );
        self::notifyAllPlayers( "placedToken", '', array(
            'player_id' => $player_id,
            'x' => $x,
            'y' => $y,
            'tokensLeft' => (int)self::getGameStateValue('tokensLeft')
        ) );
        self::notifyAllPlayers( "movedKing", '', array(
            'player_id' => $player_id,
            'x' => $x,
            'y' => $y
        ) );

        // calculate scores for each player for the new board situation
        self::calculatePlayerScores();
        
        // update & notify about scores
        $newScores = self::getCollectionFromDb( "SELECT player_id, player_score FROM player", true );
            self::notifyAllPlayers( "newScores", "", array(
                "scores" => $newScores
            ) );

        // Then, go to the next state
        $this->gamestate->nextState( 'move' );
    }

    function drawCard()
    {
        // Check that this player is active and draw action is possible at this moment
        self::checkAction( 'move' ); 
        $player_id = self::getActivePlayerId();

        if (!self::canDrawCard($player_id)) {
            throw new BgaUserException( self::_("You cannot draw more cards") );
        }
        $playerhands = $this->cards->getCardsInLocation('hand', $player_id);

        // draw new card and add to active player hand
        self::drawpileEmptyCheck();
        $cardDrawn = $this->cards->getCardOnTop('deck');
        $card_id = $cardDrawn['id'];
        $this->cards->moveCard($card_id, 'hand', $player_id);

        $drawpileCnt = $this->cards->countCardInLocation('deck');

        // notify about card that was drawn
        self::notifyAllPlayers( "drawnCard", clienttranslate( '${player_name} draws power card ${card_distance} ${name_direction}${arrow}' ), array(
            'i18n' => array(
				'card_direction'
			),
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_id' => $card_id,
            'card_direction' => $cardDrawn['type'],
            'name_direction' => $this->card_directions[$cardDrawn['type']]['name'],
            'arrow' => $this->icons[$cardDrawn['type']],
            'card_distance' => $cardDrawn['type_arg'],
            'drawpileCnt' => $drawpileCnt
        ) );

        self::incStat(1, "cards_drawn", $player_id);

        // Then, go to the next state
        $this->gamestate->nextState( 'draw' );
    }

    function calculatePlayerScores()
    {
        // get the current board situation
        $board = self::getDoubleKeyCollectionFromDB( "SELECT board_x x, board_y y, board_player player_id
                                                       FROM board
                                                       WHERE board_player IS NOT NULL" );    
        // arrays to keep track of "visited" squares
        $visitedBoard = array_fill(1, self::BOARD_SIZE, array_fill(1, self::BOARD_SIZE, 0));
        // arrays to keep track of adjacent regions per player
        $players = self::loadPlayersBasicInfos();
        $playerRegions = array();
        $playerScores = array();
        foreach( $players as $player_id => $player ) {
            $playerRegions[$player_id] = array();
            $playerScores[$player_id] = 0;
        }

        // flood fill the squares by assigning unique nrs for adjacent tokens of same color
        $regionIndex = 0;
        for( $x=1; $x<=self::BOARD_SIZE; $x++ )
        {
            for( $y=1; $y<=self::BOARD_SIZE; $y++ )
            {
                if (!isset($board[$x]) || !isset($board[$x][$y])) {
                    // no token placed here
                    continue;
                }
                if ($visitedBoard[$x][$y] > 0) {
                    // we already visited this square as part of other region
                    continue;
                }

                $regionPlayer = $board[$x][$y]['player_id'];
                // starting a new region for this player
                $regionIndex = 1 + count($playerRegions[$regionPlayer]);
                $playerRegions[$regionPlayer][$regionIndex] = 0;                
                // else : visit this square and recursively adjacent squares of same player
                self::visitSquare($board, $visitedBoard, $playerRegions, $x, $y, $regionPlayer, $regionIndex);
            }
        }

        // now check how large regions for each player are and score squared per region size
        // players score all their regions squared
        $playerTiebreaker1 = array();
        $playerTiebreaker2 = array();
        foreach( $players as $player_id => $player ) {            
            // first tiebreaker
            $largestRegionSize = 0;
            // second tiebreaker
            $numberOfRoses = 0;

            foreach( $playerRegions[$player_id] as $region_index => $region_size ) {
                $numberOfRoses += $region_size;
                if ($largestRegionSize < $region_size) {
                    $largestRegionSize = $region_size;
                }
                $playerScores[$player_id] += ($region_size * $region_size);
            }

            $playerTiebreaker1[$player_id] = $largestRegionSize;
            $playerTiebreaker2[$player_id] = $numberOfRoses;
            $total_player_score = $playerScores[$player_id];
            // set player_score_aux so that 1st and 2nd tiebreaker both work in 1 go
            $sql = "UPDATE player SET player_score = $total_player_score, 
                    player_score_aux = ($largestRegionSize * 100 + $numberOfRoses)
                    WHERE player_id='$player_id' ";
            self::DbQuery($sql);

            $largestRegionSoFar = self::getStat("largest_region_any", $player_id);
            if ($largestRegionSize > $largestRegionSoFar) {
                self::setStat($largestRegionSize, "largest_region_any", $player_id);
            }            
            self::setStat($largestRegionSize, "largest_region_end", $player_id);
        }        
    }

    // since we need to modify the visitedBoard & playerRegions arrays with these visits,
    // they must be explicitly passed as reference, or PHP will clone/copy them when they are modified!
    function visitSquare($board, & $visitedBoard, & $playerRegions, $x, $y, $regionPlayer, $regionIndex) {
        // $visitTrace = sprintf('visit: %d/%d for player region %d/%d ', $x, $y, $regionPlayer, $regionIndex);
        // self::trace($visitTrace);

        // make sure we haven't visited this square yet
        if ($visitedBoard[$x][$y] > 0) {
            //self::trace(sprintf('already visited %d/%d = %d ', $x, $y, $visitedBoard[$x][$y]));
            return;
        }
        // mark this square as visited and part of region i
        $visitedBoard[$x][$y] = $regionIndex;
        // region size becomes 1 bigger
        $playerRegions[$regionPlayer][$regionIndex] = 1 + $playerRegions[$regionPlayer][$regionIndex];

        // recursively visit adjacent squares if they belong to the same player
        for( $adjX=$x-1; $adjX<=$x+1; $adjX++ )
        {
            for( $adjY=$y-1; $adjY<=$y+1; $adjY++ )
            {
                // diagonally adjacent does not count! (and we also don't need to visit ourself again)
                if (($adjX != $x && $adjY != $y) || ($adjX == $x && $adjY == $y))
                    continue;
                // does this square belong to the same player? if so, same region
                if (isset($board[$adjX]) && isset($board[$adjX][$adjY]) 
                    && $board[$adjX][$adjY]['player_id'] == $regionPlayer) {                    
                    self::visitSquare($board, $visitedBoard, $playerRegions, $adjX, $adjY, $regionPlayer, $regionIndex);
                }
            }
        }        
    }
    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    function argsPlayerTurn()
    {
        $activePlayerId = self::getActivePlayerId();
        $playerHandCount = $this->cards->countCardsInLocation('hand', $activePlayerId);

        return array(
            'playerId' => $activePlayerId,
            'canDraw' => $playerHandCount < self::HAND_MAX_CARDS,
            'possibleMoves' => self::getPossibleMoves( $activePlayerId )
        );
    }

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stNextPlayer()
    {
        // Active next player
        $player_id = self::activeNextPlayer();

        // check for end-game situations =
        // either all 52 tokens have been used, 
        $tokenCount = (int)self::getGameStateValue('tokensLeft');        
        if ($tokenCount <= 0) {
            $this->gamestate->nextState( 'endGame' );
            return;
        }
        // or both players cannot perform any valid move and already have 5 cards in hand
        if (!self::canActuallyPlay( $player_id )) {
            // check if the next player has possible valid moves
            $opponent_id = self::getUniqueValueFromDb( "SELECT player_id FROM player WHERE player_id!='$player_id' " );
            if (!self::canActuallyPlay( $opponent_id )) {
                // both players cannot do any valid move
                self::notifyAllPlayers( "cantPlay", clienttranslate( 'Nobody can play anymore valid moves' ), array(
                    'player_id' => $player_id
                ) ); 
                $this->gamestate->nextState( 'endGame' );
            } else {
                // active player cannot play, but next player can still do valid moves
                self::notifyAllPlayers( "cantPlay", clienttranslate( '${player_name} has no valid moves' ), array(
                    'player_id' => $player_id,
                    'player_name' => self::getActivePlayerName(),
                ) );                
                $this->gamestate->nextState( 'cantPlay' );
            }
        } else {
            // This player can just play. Give him some extra time

            self::incStat(1, "turns_number");
            self::incStat(1, "turns_number", $player_id);

            self::giveExtraTime( $player_id );
            $this->gamestate->nextState( 'nextTurn' );
        }
    }
    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new BgaVisibleSystemException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
