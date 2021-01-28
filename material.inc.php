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
 * material.inc.php
 *
 * RoseKing game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */


/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/

$this->card_directions = array(
	1 => array(
		'name' => clienttranslate('N') ,
		'nametr' => self::_('N')
	) ,
	2 => array(
		'name' => clienttranslate('NE') ,
		'nametr' => self::_('NE')
	) ,
	3 => array(
		'name' => clienttranslate('E') ,
		'nametr' => self::_('E')
	) ,
	4 => array(
		'name' => clienttranslate('SE') ,
		'nametr' => self::_('SE')
	) ,
	5 => array(
		'name' => clienttranslate('S') ,
		'nametr' => self::_('S')
	) ,
	6 => array(
		'name' => clienttranslate('SW') ,
		'nametr' => self::_('SW')
	) ,
	7 => array(
		'name' => clienttranslate('W') ,
		'nametr' => self::_('W')
	) ,
	8 => array(
		'name' => clienttranslate('NW') ,
		'nametr' => self::_('NW')
	)
);

// HTML Icons for Wind Arrow directions

$this->icons = array(
	1 => '<span>'.json_decode('"' . '\u2B06' . '"').'</span>',	// North Arrow
	2 => '<span>'.json_decode('"' . '\u2B08' . '"').'</span>',	// North East Arrow
	3 => '<span>'.json_decode('"' . '\u2B95' . '"').'</span>',	// East Arrow
	4 => '<span>'.json_decode('"' . '\u2B0A' . '"').'</span>',	// South East Arrow
	5 => '<span>'.json_decode('"' . '\u2B07' . '"').'</span>',	// South Arrow
	6 => '<span>'.json_decode('"' . '\u2B0B' . '"').'</span>',	// South West Arrow
	7 => '<span>'.json_decode('"' . '\u2B05' . '"').'</span>',	// West Arrow	
	8 => '<span>'.json_decode('"' . '\u2B09' . '"').'</span>'	// North West Arrow
);
