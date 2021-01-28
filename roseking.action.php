<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * RoseKing implementation : © Iwan Tomlow <iwan.tomlow@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * roseking.action.php
 *
 * RoseKing main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/roseking/roseking/myAction.html", ...)
 *
 */
  
  
  class action_roseking extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "roseking_roseking";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 
  	
  	// All defines for action entry points here

    /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */

    public function moveToLocation()
    {
        self::setAjaxMode();     
        $x = self::getArg( "x", AT_posint, true );
        $y = self::getArg( "y", AT_posint, true );
        $result = $this->game->tryMoveToLocation( $x, $y );
        self::ajaxResponse( );
    }

    public function moveWithCard()
    {
        self::setAjaxMode();     
        $card_id = self::getArg( "id", AT_posint, true );
        $result = $this->game->tryMoveWithCard( $card_id );
        self::ajaxResponse( );
    }

    public function drawCard()
    {
        self::setAjaxMode();     
        $result = $this->game->drawCard();
        self::ajaxResponse( );
    }

  }
  

