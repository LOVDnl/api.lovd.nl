<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 * Adapted from /src/inc-init.php in the LOVD3 project.
 *
 * Created     : 2022-08-08
 * Modified    : 2022-08-09
 * For LOVD    : 3.0-29
 *
 * Copyright   : 2004-2022 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *
 *
 * This file is part of LOVD.
 *
 * LOVD is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * LOVD is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with LOVD.  If not, see <http://www.gnu.org/licenses/>.
 *
 *************/

// Don't allow direct access.
if (!defined('ROOT_PATH')) {
    exit;
}

// This instance is JSON-only. Catch all errors and warnings and return these as JSON.
function lovd_API_handleError ($nError, $sError, $sFile, $nLine)
{
    // Based on the example given within the documentation text
    //  at https://www.php.net/set_error_handler.
    // License, according to https://www.php.net/license/index.php,
    //  is CC3.0-BY, compatible with GPL.
    if (!(error_reporting() & $nError)) {
        // This error code is not included in error_reporting, so let it
        //  fall through to the standard PHP error handler
        return false;
    }

    $aReturn = array(
        'version' => '',
        'messages' => array(),
        'warnings' => array(),
        'errors' => array(),
        'data' => array(),
    );

    switch ($nError) {
        case E_NOTICE:
            $aReturn['messages'][] = "PHP notice: \"$sError\" in $sFile on line $nLine.";
            $aReturn['warnings'][] = "Unhandled PHP notice in $sFile on line $nLine.";
            break;

        case E_WARNING:
            $aReturn['warnings'][] = "PHP warning: \"$sError\" in $sFile on line $nLine.";
            break;

        case E_ERROR:
        default:
            $aReturn['errors'][] = "PHP error: \"$sError\" in $sFile on line $nLine.";
            break;
    }

    header('HTTP/1.0 500 Internal Server Error', true, 500);
    die(json_encode($aReturn, JSON_PRETTY_PRINT));
}
set_error_handler('lovd_API_handleError');

function lovd_API_handleException ($oException)
{
    return lovd_API_handleError(
        E_ERROR, // All Exceptions will be handled as Errors.
        $oException->getMessage(),
        $oException->getFile(),
        $oException->getLine()
    );
}
set_exception_handler('lovd_API_handleException');

// Sometimes inc-init.php gets run over CLI (LOVD+, external scripts, etc.).
// Handle that here, instead of building lots of code in many different places.
if (!isset($_SERVER['HTTP_HOST'])) {
    // To prevent notices...
    $_SERVER = array_merge($_SERVER, array(
        'HTTP_HOST' => 'localhost',
        'REQUEST_URI' => '/' . basename(__FILE__),
        'QUERY_STRING' => '',
        'REQUEST_METHOD' => 'GET',
    ));
}

// Require library standard functions.
require_once ROOT_PATH . 'inc-lib-init.php';

// Set error_reporting if necessary. We don't want notices to show. This will do
// fine most of the time.
if (ini_get('error_reporting') == E_ALL) {
    error_reporting(E_ALL ^ E_NOTICE);
}

// DMD_SPECIFIC!!! - Testing purposes only.
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    error_reporting(E_ALL | E_STRICT);
}





// Define constants needed throughout LOVD.
// Find out whether or not we're using SSL.
if ((!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || !empty($_SERVER['SSL_PROTOCOL'])) {
    // We're using SSL!
    define('SSL', true);
    define('PROTOCOL', 'https://');
} else {
    define('SSL', false);
    define('PROTOCOL', 'http://');
}

// Prevent some troubles with the menu or lovd_getProjectFile() when the URL contains double slashes or backslashes.
$_SERVER['SCRIPT_NAME'] = lovd_cleanDirName(str_replace('\\', '/', $_SERVER['SCRIPT_NAME']));

// Our output formats: application/json by default.
$aFormats = array('application/json'); // Key [0] is default. Other values may not always be allowed. It is checked in the Template class' printHeader() and in Objects::viewList().
if (!empty($_GET['format']) && in_array($_GET['format'], $aFormats)) {
    define('FORMAT', $_GET['format']);
} else {
    define('FORMAT', $aFormats[0]);
}
// @ is to suppress errors in Travis test.
@header('Content-type: ' . FORMAT . '; charset=UTF-8');



// Initiate Database Connection.
$_DB = false;



@ini_set('default_charset','UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}



// The following applies only if the system is fully installed.
if (!defined('NOT_INSTALLED')) {
    // Define $_PE ($_PATH_ELEMENTS) and CURRENT_PATH.
    // FIXME: Running lovd_cleanDirName() on the entire URI causes it to run also on the arguments.
    //  If there are arguments with ../ in there, this will take effect and arguments or even the path itself is eaten.
    $sPath = preg_replace('/^' . preg_quote(lovd_getInstallURL(false), '/') . '/', '', lovd_cleanDirName(html_entity_decode(rawurldecode($_SERVER['REQUEST_URI']), ENT_HTML5))); // 'login' or 'genes?create' or 'users/00001?edit'
    $sPath = strip_tags($sPath); // XSS tag removal on entire string (and no longer on individual parts).
    $sPath = strstr($sPath . '?', '?', true); // Cut off the Query string, that will be handled later.
    foreach (array("'", '"', '`', '+') as $sChar) {
        // All these kind of quotes that we'll never have unless somebody is messing with us.
        if (strpos($sPath, $sChar) !== false) {
            // XSS attack. Filter everything out.
            $sPath = strstr($sPath, $sChar, true);
            // Also overwrite $_SERVER['REQUEST_URI'] as it's used more often (e.g., gene switcher) and we want it cleaned.
            $_SERVER['REQUEST_URI'] = strstr($_SERVER['REQUEST_URI'], rawurlencode($sChar), true) .
                (empty($_SERVER['QUERY_STRING'])? '' : '?' . $_SERVER['QUERY_STRING']);
        }
    }
    $_PE = explode('/', rtrim($sPath, '/')); // array('login') or array('genes') or array('users', '00001')

    if (isset($_SETT['objectid_length'][$_PE[0]]) && isset($_PE[1]) && ctype_digit($_PE[1])) {
        $_PE[1] = sprintf('%0' . $_SETT['objectid_length'][$_PE[0]] . 'd', $_PE[1]);
    } elseif (isset($_PE[2]) && $_PE[0] == 'phenotypes' && $_PE[1] == 'disease') {
        // Disease-specific list of phenotypes; /phenotypes/disease/00001.
        $_PE[2] = sprintf('%0' . $_SETT['objectid_length'][$_PE[1] . 's'] . 'd', $_PE[2]);
    }
    define('CURRENT_PATH', implode('/', $_PE));
    define('PATH_COUNT', count($_PE)); // So you don't need !empty($_PE[1]) && ...

    // Define ACTION.
    if ($_SERVER['QUERY_STRING'] && preg_match('/^([\w-]+)(&.*)?$/', $_SERVER['QUERY_STRING'], $aRegs)) {
        define('ACTION', $aRegs[1]);
    } else {
        define('ACTION', false);
    }

    // STUB; This should be implemented properly later on.
    define('OFFLINE_MODE', false);

    // Define constant for request method.
    define($_SERVER['REQUEST_METHOD'], true);
    @define('GET', false);
    @define('HEAD', false);
    @define('POST', false);
    @define('PUT', false);
    @define('DELETE', false);

} else {
    define('ACTION', false);
}
?>
