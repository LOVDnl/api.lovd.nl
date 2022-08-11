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

        // For non-unique input, throw a warning, but continue.
        $aInputUnique = array_unique($aInput, SORT_STRING);
        if ($aInput != $aInputUnique) {
            // Just throw a warning, but continue.
            $this->API->aResponse['warnings'][] = 'One or more variant descriptions have been repeated in the input. This API will only handle the first submission of each variant description.';
        }

        $nInput = count($aInputUnique);
        $this->API->aResponse['messages'][] = "Successfully received $nInput variant description" . ($nInput == 1? "." : "s.");
        $this->API->aResponse['messages'][] = 'Note that this API does not validate variants on the sequence level, but only checks if the variant description follows the HGVS nomenclature rules.';
        $this->API->aResponse['messages'][] = 'For sequence-level validation of DNA variants, please use https://variantvalidator.org.';

        // Now actually handle the request.
        foreach ($aInputUnique as $sVariant) {
            $aResponse = array(
                'messages' => array(),
                'warnings' => array(),
                'errors' => array(),
                'data' => array(),
            );

            // Start with checking the input.
            // Note that this will generate weird 3' UTR positions, but so be it.
            // I don't want to create a database here, and anyway they might not have sent a transcript.
            $aVariantInfo = lovd_getVariantInfo($sVariant, false);
            if (!$aVariantInfo) {
                // No recognition at all.
                $aResponse['errors']['EFAIL'] = 'Failed to recognize a variant description in your input.';
            } else {
                // In case it's set, we don't care about WNOTSUPPORTED. We won't validate anyway,
                //  and this warning is thrown only for HGVS-compliant descriptions.
                unset($aVariantInfo['warnings']['WNOTSUPPORTED']);

                // Copy the data. This can be simplified if we choose to drop the "data" in $aResponse.
                // We choose not to copy "messages" over. They're for LOVD internal use.
                $aResponse['errors'] = $aVariantInfo['errors'];
                $aResponse['warnings'] = $aVariantInfo['warnings'];
                $aResponse['data']['position_start'] = $aVariantInfo['position_start'];
                $aResponse['data']['position_end'] = $aVariantInfo['position_end'];
                if (!empty($aVariantInfo['position_start_intron']) || !empty($aVariantInfo['position_end_intron'])) {
                    $aResponse['data']['position_start_intron'] = $aVariantInfo['position_start_intron'];
                    $aResponse['data']['position_end_intron'] = $aVariantInfo['position_end_intron'];
                }
                $aResponse['data']['type'] = $aVariantInfo['type'];
                $aResponse['data']['range'] = $aVariantInfo['range'];
                $aResponse['data']['suggested_correction'] = array();

                // Check if HGVS-compliant or not.
                if (empty($aVariantInfo['errors']) && empty($aVariantInfo['warnings'])) {
                    // This means that we're considering WPOSITIONLIMIT non-HGVS compliant,
                    //  which is different from LOVD's isHGVS() function.
                    // Note that WTRANSCRIPTFOUND and WDIFFERENTREFSEQ can't be thrown because
                    //  of the way we call lovd_getVariantInfo().
                    $aResponse['messages']['IOK'] = 'This variant description is HGVS-compliant.';

                } elseif (isset($aVariantInfo['errors']['ENOTSUPPORTED'])
                    && count($aVariantInfo['errors']) == 1
                    && empty($aVariantInfo['warnings'])) {
                    // Non-HGVS, but only has an ENOTSUPPORTED and no warnings.
                    // We don't actually know whether this is HGVS compliant or not.
                    $aResponse['messages']['INOTSUPPORTED'] = 'This variant description contains unsupported syntax.' .
                        ' Although we aim to support all of the HGVS nomenclature rules,' .
                        ' some complex variants are not fully implemented yet in our syntax checker.' .
                        ' We invite you to submit your variant description here, so we can have a look: https://github.com/LOVDnl/api.lovd.nl/issues.';
                }

                if (!lovd_variantHasRefSeq($sVariant)) {
                    $aResponse['messages']['IREFSEQMISSING'] = 'Please note that your variant description is missing a reference sequence. ' .
                        'Although this is not necessary for our syntax check, a variant description does ' .
                        'need a reference sequence to be fully informative and HGVS-compliant.';
                }
            }

            $this->API->aResponse['data'][$sVariant] = $aResponse;
        }
        return true;
    }
}
?>
