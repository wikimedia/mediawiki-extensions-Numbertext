<?php

/**
 * Numbertext - An extension for number to text conversion based on http://www.numbertext.org/
 *
 * @link https://www.mediawiki.org/wiki/Extension:Numbertext Documentation
 * @file Numbertext.php
 * @defgroup Numbertext
 * @ingroup Extensions
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence LGPL/BSD dual-license
 */

// Check to see if we are being called as an extension or directly
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is an extension to MediaWiki and thus not a valid entry point.' );
}

// Register this extension on Special:Version
$wgExtensionCredits['parserhook'][] = array(
	'path'           => __FILE__,
	'name'           => 'Numbertext',
	'version'        => '0.9.5.2',
	'url'            => 'https://www.mediawiki.org/wiki/Extension:Numbertext',
	'author'         => array( '[[mw:User:Pastakhov|Pavel Astakhov]]', ),
	'descriptionmsg' => 'numbertext-desc'
);

// Tell the whereabouts of files
$dir = __DIR__;

// Allow translations for this extension
$wgExtensionMessagesFiles['Numbertext'] = $dir . '/Numbertext.i18n.php';
$wgExtensionMessagesFiles['NumbertextMagic'] = $dir . '/Numbertext.i18n.magic.php';

//Preparing classes for autoloading
$wgAutoloadClasses['Numbertext'] = $dir . '/Numbertext.body.php';
$wgAutoloadClasses['Soros'] = $dir . '/Soros.php';

// Specify the function that will initialize the parser function.
$wgHooks['ParserFirstCallInit'][] = 'NumbertextSetupParserFunction';

/** Tell MediaWiki that the parser function exists.
 *
 * @param Parser $parser
 * @return boolean
 */
function NumbertextSetupParserFunction( &$parser ) {

   // Create a function hook associating the "numbertext" and "moneytext" magic words with the
   // Numbertext::numbertext() and umbertext::moneytext() functions.
   $parser->setFunctionHook( 'MAG_NUMBERTEXT', 'Numbertext::numbertext' );
   $parser->setFunctionHook( 'MAG_MONEYTEXT', 'Numbertext::moneytext' );

   // Return true so that MediaWiki continues to load extensions.
   return true;
}