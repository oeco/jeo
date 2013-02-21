<?php

/*
 * Plugins support and fixes
 */

// qTranslate

if(function_exists('qtrans_getLanguage'))
	include(TEMPLATEPATH . '/plugins/qtranslate/qtranslate-fixes.php');