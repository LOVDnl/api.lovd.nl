<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2022-08-08
 * Modified    : 2025-02-17   // When modified, also change the library_version.
 * For LOVD    : 3.0-29
 *
 * Copyright   : 2004-2023 Leiden University Medical Center; http://www.LUMC.nl/
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

        // We have the option to be asked for the schema, instead of being asked to process a variant.
        if ($sInput == 'schema.json') {
            // Call method that provides the schema.
            $sMethod = 'v' . $this->API->nVersion . '_getJSONSchema';
            $this->API->aResponse = call_user_func(array($this, $sMethod));
            return true;
        }



        // Check the current version and run that method.
        $sMethod = 'v' . $this->API->nVersion . '_checkHGVS';
        return call_user_func(array($this, $sMethod), $aInput);
    }





    public function v1_checkHGVS ($aInput)
    {
        // Run the validations, using the old lovd_getVariantInfo() and lovd_fixHGVS() approach.

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
            }
            $aResponse['data']['suggested_correction'] = array();

            if (empty($aResponse['messages'])) {
                // Not HGVS-compliant, and not unsupported, so let's see if we can suggest something better.
                $sFixedVariant = lovd_fixHGVS($sVariant);
                $aFixedVariantInfo = lovd_getVariantInfo($sFixedVariant, false);
                unset($aFixedVariantInfo['warnings']['WNOTSUPPORTED']);

                // We normally don't show non-HGVS compliant suggestions. Exception 1;
                // Treat the result as HGVS compliant (i.e., accept suggestion and show)
                //  when all we had was a WTOOMUCHUNKNOWN and now we get a ESUFFIXMISSING.
                // The issue is that WTOOMUCHUNKNOWN suggests a fix, so it's stupid to then not show it.
                // Also, add the error to the current list, so it's not lost.
                if ($aVariantInfo && $aFixedVariantInfo
                    && array_keys($aVariantInfo['errors'] + $aVariantInfo['warnings']) == array('WTOOMUCHUNKNOWN')
                    && array_keys($aFixedVariantInfo['errors'] + $aFixedVariantInfo['warnings']) == array('ESUFFIXMISSING')) {
                    $aVariantInfo['errors'] += $aFixedVariantInfo['errors']; // For the confidence calculation.
                    $aResponse['errors'] += $aFixedVariantInfo['errors']; // For the output.
                    unset($aFixedVariantInfo['errors']['ESUFFIXMISSING']);
                }

                // Exception 2; Treat the result as HGVS compliant (i.e., accept
                //  suggestion and show) when all we have now is a EWRONGREFERENCE and
                //  that was anyway already part of what we had.
                // Obviously, this only applies when the fixed variant is different from what we had.
                if ($aVariantInfo && $aFixedVariantInfo
                    && array_keys($aFixedVariantInfo['errors'] + $aFixedVariantInfo['warnings']) == array('EWRONGREFERENCE')
                    && isset($aVariantInfo['errors']['EWRONGREFERENCE'])
                    && $sVariant != $sFixedVariant) {
                    unset($aFixedVariantInfo['errors']['EWRONGREFERENCE']);
                }

                // Now check the confidence.
                // This already checks if $sFixedVariant is different, and error-free. If not, it will return false.
                $sConfidence = lovd_fixHGVSGetConfidence(
                    $sVariant,
                    $sFixedVariant,
                    $aVariantInfo,
                    $aFixedVariantInfo
                );

                if ($sConfidence) {
                    // So, we suggest some changes, and the result is HGVS-compliant.
                    // We choose not to show non-HGVS compliant suggestions here.
                    // We anyway pass on the errors and warnings to the user,
                    //  so they can always try to fix things and try again.
                    $aResponse['data']['suggested_correction']['value'] = $sFixedVariant;

                    // Now, let's add a confidence score.
                    // High, for corrections that we're very sure about.
                    // Medium, for corrections that are a bit more complex.
                    // Low, for everything else.
                    $aResponse['data']['suggested_correction']['confidence'] = $sConfidence;
                }
            }

            if (!lovd_variantHasRefSeq($sVariant)) {
                $aResponse['messages']['IREFSEQMISSING'] = 'Please note that your variant description is missing a reference sequence. ' .
                    'Although this is not necessary for our syntax check, a variant description does ' .
                    'need a reference sequence to be fully informative and HGVS-compliant.';
            }

            $this->API->aResponse['data'][$sVariant] = $aResponse;
        }
        $this->API->aResponse['library_version'] = '2024-05-31';
        return true;
    }





    public function v2_checkHGVS ($aInput)
    {
        // Run the validations, using the new HGVS class approach.
        if (!file_exists(ROOT_PATH . 'libs/HGVS-syntax-checker/HGVS.php')) {
            // This API requires the HGVS.php class file from https://github.com/LOVDnl/HGVS-syntax-checker.
            // If not found, double-check if you ran `git submodule init && git submodule update`.
            // This repository will not duplicate the code.
            $this->API->aResponse['errors'][] = 'Could not load the HGVS library.';
            return false;
        }

        require ROOT_PATH . 'libs/HGVS-syntax-checker/HGVS.php';
        $this->API->aResponse['versions'] = HGVS::getVersions();

        // v1 used to have a check here for unique input, but since we format the output differently, we don't care.
        $nInput = count($aInput);
        $this->API->aResponse['messages'][] = "Successfully received $nInput variant description" . ($nInput == 1? "." : "s.");
        $this->API->aResponse['messages'][] = 'Note that this API does not validate variants on the sequence level, but only checks if the variant description follows the HGVS nomenclature rules.';
        $this->API->aResponse['messages'][] = 'For sequence-level validation of DNA variants, please use https://variantvalidator.org.';

        // Now actually handle the request.
        foreach ($aInput as $sVariant) {
            $aResponse = HGVS::checkVariant($sVariant)->allowMissingReferenceSequence()->getInfo();

            // In case it's set, we don't care about WNOTSUPPORTED. We won't validate anyway,
            //  and this warning is thrown only for HGVS-compliant descriptions.
            unset($aResponse['warnings']['WNOTSUPPORTED']);

            if (isset($aResponse['errors']['ENOTSUPPORTED'])) {
                // Catch and convert ENOTSUPPORTED.
                // We don't actually know whether this is HGVS compliant or not.
                // The library allows for ENOTSUPPORTED, and flags it as valid.
                $aResponse['messages']['INOTSUPPORTED'] = 'This variant description contains unsupported syntax.' .
                    ' Although we aim to support all of the HGVS nomenclature rules,' .
                    ' some complex variants are not fully implemented yet in our syntax checker.' .
                    ' We invite you to submit your variant description here, so we can have a look: https://github.com/LOVDnl/api.lovd.nl/issues.';
                // And remove the ENOTSUPPORTED.
                unset($aResponse['errors']['ENOTSUPPORTED']);
            }

            if (isset($aResponse['messages']['IREFSEQMISSING'])) {
                // Our version is more informative.
                $aResponse['messages']['IREFSEQMISSING'] = 'Please note that your variant description is missing a reference sequence. ' .
                    'Although this is not necessary for our syntax check, a variant description does ' .
                    'need a reference sequence to be fully informative and HGVS-compliant.';
            }

            $this->API->aResponse['data'][] = $aResponse;
        }
        return true;
    }





    // NOTE: Don't just change this function's name, it's called through call_user_func().
    public function v1_getJSONSchema ()
    {
        // Return the JSON Schema for the v1 checkHGVS response format.
        global $_PE;

        return array(
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            '$id' => lovd_getInstallURL() . rtrim(implode('/', $_PE), '/'),
            'title' => 'LOVD checkHGVS API',
            'description' => 'The response object of the LOVD checkHGVS API.',
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => array(
                'version' => array(
                    'description' => 'The version of the API specification.',
                    'type' => 'integer',
                    'minimum' => 1,
                ),
                'messages' => array(
                    'description' => 'A list of messages, simply providing information and not indicating any kind of error.',
                    'type' => 'array',
                    'items' => array(
                        'type' => 'string',
                    ),
                ),
                'warnings' => array(
                    'description' => 'A list of warnings, indicating something went wrong with the request. Warnings are usually recoverable.',
                    'type' => 'array',
                    'items' => array(
                        'type' => 'string',
                    ),
                ),
                'errors' => array(
                    'description' => 'A list of errors, indicating something went wrong with the request. Errors are usually non-recoverable.',
                    'type' => 'array',
                    'items' => array(
                        'type' => 'string',
                    ),
                ),
                'data' => array(
                    'description' => 'The data that is the result of the API request. This is empty if a problem occurred while handling the request.',
                    'oneOf' => array(
                        array(
                            'type' => 'array',
                            'maxContains' => 0,
                        ),
                        array(
                            'type' => 'object',
                            'patternProperties' => array(
                                '^.+$' => array(
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'properties' => array(
                                        'messages' => array(
                                            'description' => 'An object for messages, simply providing information and not indicating any kind of error.',
                                            'oneOf' => array(
                                                array(
                                                    'type' => 'array',
                                                    'maxContains' => 0,
                                                ),
                                                array(
                                                    'type' => 'object',
                                                    'additionalProperties' => false,
                                                    'patternProperties' => array(
                                                        '^I.+$' => array(
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                        'warnings' => array(
                                            'description' => 'An object for warnings, indicating something is wrong with the submitted variant description. Warnings are usually recoverable.',
                                            'oneOf' => array(
                                                array(
                                                    'type' => 'array',
                                                    'maxContains' => 0,
                                                ),
                                                array(
                                                    'type' => 'object',
                                                    'additionalProperties' => false,
                                                    'patternProperties' => array(
                                                        '^W.+$' => array(
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                        'errors' => array(
                                            'description' => 'An object for errors, indicating something is wrong with the submitted variant description. Errors are usually non-recoverable.',
                                            'oneOf' => array(
                                                array(
                                                    'type' => 'array',
                                                    'maxContains' => 0,
                                                ),
                                                array(
                                                    'type' => 'object',
                                                    'additionalProperties' => false,
                                                    'patternProperties' => array(
                                                        '^E.+$' => array(
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                        'data' => array(
                                            'description' => 'Data representing the submitted variant description.',
                                            'type' => 'object',
                                            'additionalProperties' => false,
                                            'properties' => array(
                                                'position_start' => array(
                                                    'description' => 'The start position of the submitted variant.',
                                                    'type' => 'integer',
                                                ),
                                                'position_end' => array(
                                                    'description' => 'The end position of the submitted variant.',
                                                    'type' => 'integer',
                                                ),
                                                'position_start_intron' => array(
                                                    'description' => 'The offset from the nearest exon for the start position of the submitted variant.',
                                                    'type' => 'integer',
                                                ),
                                                'position_end_intron' => array(
                                                    'description' => 'The offset from the nearest exon for the end position of the submitted variant.',
                                                    'type' => 'integer',
                                                ),
                                                'type' => array(
                                                    'description' => 'The type of the submitted variant.',
                                                    'type' => 'string',
                                                    'enum' => array(
                                                        '',
                                                        ';',
                                                        '=',
                                                        '^',
                                                        'chimeric',
                                                        'del',
                                                        'delins',
                                                        'dup',
                                                        'ins',
                                                        'inv',
                                                        'met',
                                                        'mosaic',
                                                        null,
                                                        'repeat',
                                                        'subst',
                                                    ),
                                                ),
                                                'range' => array(
                                                    'description' => 'Whether the variant was submitted as a range (multiple positions) or not.',
                                                    'type' => 'boolean',
                                                ),
                                                'suggested_correction' => array(
                                                    'description' => 'In case the variant description was not fully valid, this object might contain a suggestion how to fix the given description.',
                                                    'oneOf' => array(
                                                        array(
                                                            'type' => 'array',
                                                            'maxContains' => 0,
                                                        ),
                                                        array(
                                                            'type' => 'object',
                                                            'properties' => array(
                                                                'value' => array(
                                                                    'description' => 'The suggested corrected variant description.',
                                                                    'type' => 'string',
                                                                ),
                                                                'confidence' => array(
                                                                    'description' => 'The confidence in which the API suggests this corrected variant description.',
                                                                    'type' => 'string',
                                                                    'enum' => array(
                                                                        'high',
                                                                        'medium',
                                                                        'low',
                                                                    ),
                                                                ),
                                                            ),
                                                            'required' => array(
                                                                'value',
                                                                'confidence',
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                            'required' => array(
                                                'position_start',
                                                'position_end',
                                                'type',
                                                'range',
                                                'suggested_correction',
                                            ),
                                        ),
                                    ),
                                    'required' => array(
                                        'messages',
                                        'warnings',
                                        'errors',
                                        'data',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'library_version' => array(
                    'description' => 'The date that the library that powers this API, has been updated.',
                    'type' => 'string',
                    'pattern' => '^[0-9]{4}-[0-9]{2}-[0-9]{2}$',
                ),
            ),
            'required' => array(
                'version',
                'messages',
                'warnings',
                'errors',
                'library_version',
            ),
        );
    }
}
?>
