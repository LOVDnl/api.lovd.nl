<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2022-08-08
 * Modified    : 2022-08-11   // When modified, also change the library_version.
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



class LOVD_API_checkHGVS
{
    // This class defines the LOVD API object handling the checkHGVS API.

    private $API;                     // The API object.





    function __construct (&$oAPI)
    {
        // Links the API to the private variable.

        if (!is_object($oAPI) || !is_a($oAPI, 'LOVD_API')) {
            return false;
        }
        $this->API = $oAPI;
        $this->API->aResponse['library_version'] = '2022-08-11';

        return true;
    }





    public function processGET ($aURLElements, $bReturnBody)
    {
        // Handle GET and HEAD requests for the checkHGVS API.
        // For HEAD requests, we won't print any output.
        // We could just check for the HEAD constant but this way the code will
        //  be more independent on the rest of the infrastructure.
        // Note that LOVD API's sendHeaders() function does check for HEAD and
        //  automatically won't print any contents if HEAD is used.
        if (is_array($aURLElements)) {
            // Strip the padded elements off.
            $aURLElements = array_diff($aURLElements, array(''));
        }

        // Check URL structure.
        if (!$aURLElements || !is_array($aURLElements)) {
            // No variants passed.
            $this->API->nHTTPStatus = 400; // Send 400 Bad Request.
            $this->API->aResponse['errors'][] = 'Could not parse the given request. Did you submit a variant?';
            return false;
        }

        // Since variants can contain slashes, we should just glue everything back together.
        // Surely, they should have urlencode()d the slash, but well, you never know.
        // This disables the possibility to use /checkHGVS/variant1/variant2/variant3,
        //  but enables us to just mindlessly type /checkHGVS/variant without encoding.
        $sInput = implode('/', $aURLElements);

        // Now, we allow passing an array of variants as an JSON encoded string.
        // If it appears to be JSON, have PHP try and convert it into an array.
        if ($sInput[0] == '[') {
            $aInput = $this->API->jsonDecode($sInput);
            // If $aInput is false, we failed somewhere. Function should have set response and HTTP status.
            if ($aInput === false) {
                return false;
            }
        } else {
            $aInput = array($sInput);
        }

        // If we get here, we have the input successfully parsed. All that remains is actually checking the variants.
        // However, for HEAD requests, we're done here.
        if (!$bReturnBody) {
            return true;
        }

        return true;
    }
}
?>
