/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * RoseKing implementation : © Iwan Tomlow <iwan.tomlow@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * roseking.js
 *
 * RoseKing user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.roseking", ebg.core.gamegui, {
        constructor: function(){
            console.log('roseking constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;

            this.myHand = null;
            this.opponentHand = null;
            this.cardwidth = 63;
            this.cardheight = 96;

            this.kingPositionX = 0;
            this.kingPositionY = 0;

            this.redplayer = null;
            this.whiteplayer = null;
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );
            
            var lastPlayerId = 0;
            // Setting up player boards
            this.redplayer = gamedatas.redplayer;
            this.whiteplayer = gamedatas.whiteplayer;
            for( var player_id in gamedatas.players )
            {                
                var player = gamedatas.players[player_id];
                // Setting up players boards if needed
                // ...
            }
            
            // Set up your game interface here, according to "gamedatas"
            this.kingPositionX = gamedatas.kingTokenX;
            this.kingPositionY = gamedatas.kingTokenY;
            this.addKingTokenOnBoard(gamedatas.kingTokenX, gamedatas.kingTokenY, this.redplayer);
            for( var i in gamedatas.board )
            {
                var token = gamedatas.board[i];
                
                if (token.player_id !== null )
                {
                    this.addPlayerTokenOnBoard(token.x, token.y, token.player_id);
                }
            }

            // Also allow to play cards by clicking move-to square on the board
            dojo.query( '.square' ).connect( 'onclick', this, 'onPlayerMoveToSquare' );

            // Player hands of cards
            this.myHand = new ebg.stock(); // new stock object for hand
            this.myHand.create( this, $('myhand'), this.cardwidth, this.cardheight );
            this.myHand.setSelectionMode(1);
            this.myHand.item_margin=5;
            this.myHand.centerItems = true;
            dojo.connect(this.myHand, 'onChangeSelection', this, 'onPlayerHandSelectionChanged');

            this.opponentHand = new ebg.stock(); // new stock object for hand
            this.opponentHand.create( this, $('opponenthand'), this.cardwidth, this.cardheight );
            this.opponentHand.setSelectionMode(0);
            this.opponentHand.item_margin=5;
            this.opponentHand.centerItems = true;

            this.initializeCardTypes(this.myHand);
            this.initializeCardTypes(this.opponentHand);
            
            // add cards in player hands
            var myHandCnt = 0;            
            for (var i in this.gamedatas.myHand) {
                var card = this.gamedatas.myHand[i];
                var direction = card.type;
                var distance = card.type_arg;
                this.myHand.addToStockWithId( this.getCardUniqueId(distance, direction), card.id);
                myHandCnt++;
            }
            var opponentHandCnt = 0;
            for (var i in this.gamedatas.opponentHand) {
                var card = this.gamedatas.opponentHand[i];
                var direction = card.type;
                var distance = card.type_arg;
                this.opponentHand.addToStockWithId( this.getCardUniqueId(distance, direction), card.id);
                opponentHandCnt++;
            }
            // show last card played
            if (gamedatas.lastCardPlayed) {
                var card_id = gamedatas.lastCardPlayed['id'];
                var card_direction = gamedatas.lastCardPlayed['type'];
                var card_distance = gamedatas.lastCardPlayed['type_arg'];
                this.updateLastCardPlayed(this.redplayer, card_id, card_direction, card_distance);
            }            

            // current situation counters
            this.updateDrawpileCounter(gamedatas.drawDeckCnt);
            this.updateTokenRemainingCount(gamedatas.tokensLeft);
            this.updateRedKnightRemainingCount(gamedatas.redKnightCnt);
            this.updateWhiteKnightRemainingCount(gamedatas.whiteKnightCnt);

            // setup tooltips for most important parts
            this.addTooltip( 'myhand', '', _('Use your power cards to move') );
            this.addTooltip( 'opponenthand', '', _('Opponent power cards') );
            this.addTooltip( 'redknights', '', _('Red player hero cards') );
            this.addTooltip( 'whiteknights', '', _('White player hero cards') );
            this.addTooltip( 'tokensLeft', '', _('Power tokens left') );
            this.addTooltip( 'discardpile', '', _('Discard pile') );
            this.addTooltip( 'drawpile', '', _('Draw pile (reshuffled with discards when empty)') );
            this.addTooltip( 'king', '', _('Crown token') );            
 
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },
       
        initializeCardTypes: function(playerHand) {
            playerHand.image_items_per_row = 8;            
            // create card types
            for (var distance = 1; distance <= 3; distance++) {
                for (var direction = 1; direction <= 8; direction++) {
                    // Build card type id
                    var card_type_id = this.getCardUniqueId(distance, direction);
                    playerHand.addItemType(card_type_id, card_type_id, g_gamethemeurl + 'img/cards.png', card_type_id);
                }
            }
        },

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            case 'playerTurn':
                this.displayPossibleMoves( args.args.playerId, args.args.possibleMoves );
                break;            
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                    case 'playerTurn':
                        if (args['canDraw']) {
                            this.addActionButton( 'button_drawCard', _('Draw'), 'onDrawCard' );
                        }                        
                        break;
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

        currentPlayerIsSpectator: function( )
        {
            return (this.player_id != this.redplayer)
                    && (this.player_id != this.whiteplayer);
        },

        addPlayerTokenOnBoard: function( x, y, player )
        {                        
            if ($('token_'+x+'_'+y)) {
                dojo.destroy('token_'+x+'_'+y);
            }

            var tokenType = this.gamedatas.players[player].color;
            dojo.place( this.format_block( 'jstpl_token', {
                x_y: x+'_'+y,
                tokentype: tokenType
            } ) , 'tokenpiles' );
                        
            this.placeOnObject( 'token_'+x+'_'+y, 'overall_player_board_'+player );
            this.slideToObject( 'token_'+x+'_'+y, 'square_'+x+'_'+y ).play();

            // also allow click on token to move (for knight/hero actions),
            // because the clickacble area of the square itself is very small
            // if there is already an opponent token on it
            dojo.query( '#token_'+x+'_'+y ).connect( 'onclick', this, 'onPlayerMoveToSquare' );
        },

        addKingTokenOnBoard: function( x, y, player )
        {
            dojo.place( this.format_block( 'jstpl_king', {
                x_y: x+'_'+y,
                tokentype: 'king'
            } ) , 'tokenpiles' );
            
            this.placeOnObject( 'king', 'overall_player_board_'+player );
            this.slideToObject( 'king', 'square_'+x+'_'+y ).play();
        },

        // Get card unique identifier based on its color and value
        getCardUniqueId : function(distance, direction) {
            return (distance - 1) * 8 + (direction - 1);
        },

        updateDrawpileCounter: function(cnt) {
            var drawpileElement = dojo.query('#drawpile')[0];
            // Update value
            drawpileElement.innerHTML = cnt;
        },
        updateTokenRemainingCount: function(cnt) {
            var tokensLeftElement = dojo.query('#tokensLeft')[0];
            tokensLeftElement.innerHTML = cnt;
        },
        updateRedKnightRemainingCount: function(cnt) {
            var redKnightsElement = dojo.query('#redknights')[0];
            redKnightsElement.innerHTML = cnt;
        },
        updateWhiteKnightRemainingCount: function(cnt) {
            var whiteKnightsElement = dojo.query('#whiteknights')[0];
            whiteKnightsElement.innerHTML = cnt;
        },
        updateLastCardPlayed: function(player_id, card_id, card_direction, card_distance) {            
            var idPrefix = 'myhand';
            var useOpponentBlock = (!this.currentPlayerIsSpectator() && player_id != this.player_id)
                || (this.currentPlayerIsSpectator() && player_id == this.whiteplayer);
            if (useOpponentBlock) {
                idPrefix = 'opponenthand';
            }

            // clear previous elements in the discard pile
            dojo.empty('discardpile');

            // create template for the card being played
            dojo.place( this.format_block( 'jstpl_discarded', {
                card_id: card_id,
                x: (card_direction - 1) * 100,
                y: (card_distance - 1) * 100,
            } ) , 'discardpile' );

            // place the card from player's hand on the discarded card template
            if ($(idPrefix + '_item_' + card_id)) {
                this.placeOnObject('discarded_' + card_id, idPrefix + '_item_' + card_id);

                // remove it from the player's hand stock
                if (useOpponentBlock) {
                    this.opponentHand.removeFromStockById(card_id);
                } else {
                    this.myHand.removeFromStockById(card_id);
                } 
            }
                        
            // finally, move the card to its final location
            this.slideToObject('discarded_' + card_id, 'discardpile', 500, 0).play(); 
        },

        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/roseking/roseking/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

       onPlayerMoveToSquare: function( evt ) {
        // Stop this event propagation
        dojo.stopEvent( evt );

        // Get the clicked square x and y
        // Note: square id format is "square_X_Y"
        // and token id format is "token_X_Y", so coords[0] = 'square' or 'token'
        var coords = evt.currentTarget.id.split('_');
        var x = coords[1];
        var y = coords[2];

        // only act on click if moving to this location is allowed
            if( ! dojo.hasClass( 'square_'+x+'_'+y, 'movePossible' ) )
            {
                // This is not a possible move => the click does nothing
                return ;
            }
            
            // Check that this action is possible at this moment in state machine
            if( this.checkAction( 'move' ) )
            {            
                this.myHand.unselectAll();
                this.ajaxcall( "/roseking/roseking/moveToLocation.html", {
                    lock: true,
                    x:x,
                    y:y
                }, this, function( result ) {} );
            } 
       },

       onPlayerHandSelectionChanged: function() {
            var items = this.myHand.getSelectedItems();
            if (items.length != 1) {
                this.myHand.unselectAll();
            //} else if (this.isCurrentPlayerActive()) {
            } else {
                var card = items[0];
                console.log('selected card id=' + card.id + '/pos=' + card.type);    
                if( this.checkAction( 'move' ) ) {
                    this.myHand.unselectAll();
                    this.ajaxcall( "/roseking/roseking/moveWithCard.html", {
                        lock: true,
                        id: card.id
                    }, this, function( result ) {} );
                }
            }
        },

        displayPossibleMoves: function(activePlayerId, possibleMoves) {            
        
            var targetColor = this.gamedatas.players[ activePlayerId ].color;
            // Remove current possible move displays
            dojo.query( '.movePossible' ).removeClass( 'movePossible' );
            dojo.query( '.movePossible_ffffff' ).removeClass( 'movePossible_ffffff' );
            dojo.query( '.movePossible_ff0000' ).removeClass( 'movePossible_ff0000' );
            dojo.query( '.moveNeedsKnight' ).removeClass( 'moveNeedsKnight' );

            console.log('king=square_'+this.kingPositionX+'_'+this.kingPositionY);
            
            for( var card_id in possibleMoves )
            {
                console.log('card ' + card_id + ' is a possible move');
                var x = possibleMoves[card_id]['x'];
                var y = possibleMoves[card_id]['y'];
                var needKnight = possibleMoves[card_id]['knight'];
                // x,y is a possible move
                var targetSquare = 'square_'+x+'_'+y;
                dojo.addClass(targetSquare, 'movePossible');
                dojo.addClass(targetSquare, 'movePossible_' + targetColor);
                if (needKnight) {
                    dojo.addClass(targetSquare, 'moveNeedsKnight');
                }                                
            }            

            this.addTooltipToClass( 'movePossible', '', _('Move here') );
        },

        onDrawCard: function() {
            if (this.checkAction('draw', true)) {
                this.ajaxcall("/roseking/roseking/drawCard.html", {
                    lock: true
                }, this, function(result) {}, function(is_error) {});
            }
        },
        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your roseking.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // associate game notifications with local methods

            dojo.subscribe('redKnightCount', this, "notif_redKnightPlayed");
            dojo.subscribe('whiteKnightCount', this, "notif_whiteKnightPlayed");

            dojo.subscribe('playedCard', this, "notif_playedCard");
            this.notifqueue.setSynchronous( 'playedCard', 500 ); // wait before also moving token
            dojo.subscribe('playedMove', this, "notif_playedMove");            
            dojo.subscribe('placedToken', this, "notif_placedToken");
            this.notifqueue.setSynchronous( 'placedToken', 500 ); // wait before also moving king
            dojo.subscribe('movedKing', this, "notif_movedKing");

            dojo.subscribe('drawnCard', this, "notif_drawnCard");

            dojo.subscribe( 'newScores', this, "notif_newScores" );
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
        },  
        
        // from this point and below, you can write your game notifications handling methods
        
        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
        },    
        
        */

        notif_redKnightPlayed: function(notif) {
            this.updateRedKnightRemainingCount(notif.args.knights_left);
        },

        notif_whiteKnightPlayed: function(notif) {
            this.updateWhiteKnightRemainingCount(notif.args.knights_left);
        },
        notif_playedCard: function(notif) {
            var player_id = notif.args.player_id;
            var card_id = notif.args.card_id;
            var card_direction = notif.args.card_direction;
            var card_distance = notif.args.card_distance;

            this.updateLastCardPlayed(notif.args.player_id, notif.args.card_id,
                notif.args.card_direction, notif.args.card_distance);                      
        },
        notif_playedMove: function(notif) {
            // nothing, only game log
        },
        notif_placedToken: function(notif) {
            this.addPlayerTokenOnBoard(notif.args.x, notif.args.y, notif.args.player_id);
            this.updateTokenRemainingCount(notif.args.tokensLeft);
        },
        notif_movedKing: function(notif) {
            var x = notif.args.x;
            var y = notif.args.y;
            this.slideToObject( 'king', 'square_'+x+'_'+y ).play();
        },
        notif_drawnCard: function(notif) {
            var player_id = notif.args.player_id;
            var card_id = notif.args.card_id;
            var direction = notif.args.card_direction;
            var distance = notif.args.card_distance;

            // always mark the new/last card drawn by each player
            var targetColor = this.gamedatas.players[ player_id ].color;
            var markerClass = 'lastCard_' + targetColor;
            dojo.query( '.' + markerClass ).removeClass( markerClass );            

            var useOpponentBlock = (!this.currentPlayerIsSpectator() && player_id != this.player_id)
                || (this.currentPlayerIsSpectator() && player_id == this.whiteplayer);
            if (useOpponentBlock) {                
                this.opponentHand.addToStockWithId( this.getCardUniqueId(distance, direction), card_id);
                dojo.addClass('opponenthand_item_' + card_id, markerClass);
            } else {                            
                this.myHand.addToStockWithId( this.getCardUniqueId(distance, direction), card_id);
                dojo.addClass('myhand_item_' + card_id, markerClass);
            } 

            this.updateDrawpileCounter(notif.args.drawpileCnt);
        },
        notif_newScores: function( notif )
        {
            for( var player_id in notif.args.scores )
            {
                var newScore = notif.args.scores[ player_id ];
                this.scoreCtrl[ player_id ].toValue( newScore );
            }
        }
   });             
});
