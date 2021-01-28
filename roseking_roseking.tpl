{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- RoseKing implementation : © Iwan Tomlow <iwan.tomlow@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    roseking_roseking.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->
<div id="rk_gametable">
    <div id="board">
        <!-- BEGIN square -->
        <div id="square_{X}_{Y}" class="square" style="left: {LEFT}px; top: {TOP}px;"></div>
        <!-- END square -->
    </div>
    <div id="hands">
        <div id="myhand_wrap" class="myHand whiteblock">
            <h3 class="handHeader" style="color:#{MY_COLOR}">{MY_HAND}</h3>
            <div class="handrow">
                <div class="cardrow">
                    <div id="myhand" class="handcards"></div>
                </div>
            </div>            
        </div>
        <div id="cardsplayed_wrap" class="cardsPlayed whiteblock">
            <div id="redknights" class="counter textoverlay cardstack">?</div>
            <div id="tokenpiles">
                <div id="tokensLeft" class="textoverlay">?</div>
            </div>
            <div id="cardpiles" class="cardrow">
                <div id="discardpile"></div>
                <div id="drawpile" class="textoverlay cardstack">??</div>
            </div>
            <div id="whiteknights" class="counter textoverlay cardstack">?</div>
        </div>
        <div id="opponenthand_wrap" class="wrapHand opponentHand whiteblock">
            <h3 class="handHeader" style="color:#{OPPONENT_COLOR}">{OPPONENT_HAND}</h3>
            <div class="handrow">
                <div class="cardrow">
                    <div id="opponenthand" class="handcards"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/

var jstpl_token='<div class="token token_${tokentype}" id="token_${x_y}"></div>';
var jstpl_king='<div class="token token_${tokentype}" id="king"></div>';

var jstpl_discarded='<div class="discarded" id="discarded_${card_id}" style="position:inherit;background-position:-${x}% -${y}%"></div>';

</script>  

{OVERALL_GAME_FOOTER}
