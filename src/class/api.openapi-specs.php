<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2022-08-24
 * Modified    : 2025-03-26         // When modified, also change info->version.
 *
 * Copyright   : 2004-2025 Leiden University Medical Center; http://www.LUMC.nl/
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



class LOVD_API_OpenAPISpecs
{
    // This class defines the LOVD API object handling the checkHGVS API.

    private $API;                     // The API object.





    function __construct (&$oAPI)
    {
        // Links the API to the private variable.

        if (!is_object($oAPI) || !is_a($oAPI, 'LOVD_API')) {
            return false;
        }

        // Determine the list of current available versions.
        $aVersions = array_values(
            array_map(
                function ($sMethod) {
                    return strstr($sMethod, '_', true);
                },
                array_filter(
                    get_class_methods($this),
                    function ($sMethod) {
                        return preg_match('/^v[0-9]+_getOpenAPISpecs$/', $sMethod);
                    }
                )
            )
        );

        $this->API = $oAPI;
        // Good validators are https://editor.swagger.io/ or https://editor-next.swagger.io/.
        // Their "normal" validator provides unreadable error messages and is of no use.
        $this->API->aResponse = array(
            'openapi' => '3.0.3',
            'info' => array(
                'title' => 'Public LOVD API endpoints',
                'description' => 'These are the public LOVD API endpoints provided to the community. For more information, please see [our Github page](https://github.com/LOVDnl/api.lovd.nl/). The human interface to this functionality can be accessed on [LOVD.nl/HGVS](https://LOVD.nl/HGVS).',
                'termsOfService' => 'https://github.com/LOVDnl/api.lovd.nl/',
                'contact' => array(
                    'name' => 'Leiden Open Variation Database team',
                    'url' => 'https://LOVD.nl/contact?question',
                ),
                'license' => array(
                    'name' => 'GNU General Public License v3.0',
                    'url' => 'https://github.com/LOVDnl/api.lovd.nl/raw/main/LICENSE',
                ),
            ),
            'servers' => array(
                array(
                    'url' => lovd_getInstallURL() . 'v' . $this->API->nVersion,
                    'description' => 'Public LOVD APIs, production server, API v' . $this->API->nVersion . '.',
                ),
            ),
            'paths' => array(), // NOTE: Will be filled in later by v#_getOpenAPISpecs().
            'components' => array(
                'responses' => array(), // NOTE: Will be filled in later by v#_getOpenAPISpecs().
            ),
            'externalDocs' => array(
                'description' => 'More documentation can be found on our Github page.',
                'url' => 'https://github.com/LOVDnl/api.lovd.nl/',
            ),
        );

        return true;
    }





    public function processGET ($aURLElements, $bReturnBody)
    {
        // Handle GET and HEAD requests for the OpenAPI specs.
        // We're ignoring all other input ($aURLElements).
        // For HEAD requests, we won't print any output.
        // We could just check for the HEAD constant but this way the code will
        //  be more independent on the rest of the infrastructure.
        // Note that LOVD API's sendHeaders() function does check for HEAD and
        //  automatically won't print any contents if HEAD is used.
        if (!$bReturnBody) {
            return true;
        }

        // Call the method for the currently used version.
        $sMethod = 'v' . $this->API->nVersion . '_getOpenAPISpecs';
        $this->API->aResponse = call_user_func(array($this, $sMethod));
        return true;
    }





    public function v1_getOpenAPISpecs ()
    {
        // Fill in the v1-specific data.
        $aResponse = $this->API->aResponse;

        // Add the version (date).
        $aResponse['info']['version'] = '2022-11-29'; // Copied in swagger-initializer.js.

        // Provide the available paths.
        $aResponse['paths'] = array(
            '/checkHGVS/{variant}' => array(
                'get' => array(
                    'tags' => array(
                        'Public LOVD API endpoints'
                    ),
                    'summary' => 'Method to validate variant descriptions using the HGVS nomenclature rules.',
                    'description' => 'Validate a single variant description or a set of variant descriptions using this API. It will return informative messages, warnings, and/or errors about the variant description and may suggest improvements in case an issue has been identified.',
                    'operationId' => 'getCheckHGVS',
                    'parameters' => array(
                        array(
                            'name' => 'variant',
                            'in' => 'path',
                            'description' => 'A single variant description or a JSON-formatted list of variant descriptions, following the HGVS nomenclature guidelines.',
                            'required' => true,
                            'schema' => array(
                                'oneOf' => array(
                                    // These schemas can also contain examples, but Swagger doesn't show them.
                                    // So pulled out the examples, and stored them separately.
                                    array(
                                        'title' => 'A single variant description following the HGVS nomenclature guidelines.',
                                        'type' => 'string',
                                    ),
                                    array(
                                        'title' => 'A JSON-formatted list of variant descriptions following the HGVS nomenclature guidelines.',
                                        'type' => 'string',
                                        'pattern' => '^\[".+"\]$',
                                    ),
                                ),
                            ),
                            'examples' => array(
                                // The key name doesn't seem to matter.
                                'single' => array(
                                    'summary' => 'A single variant description.',
                                    'value' => 'NM_002225.3:c.157C>T',
                                ),
                                'multiple' => array(
                                    'summary' => 'A JSON-formatted list of variant descriptions.',
                                    'value' => '["NM_002225.3:c.157C>T","NC_000015.9:g.40699840C>T"]',
                                ),
                            ),
                        ),
                    ),
                    'responses' => array(
                        '200' => array(
                            '$ref' => '#/components/responses/200_checkHGVS',
                        ),
                        '4XX' => array(
                            '$ref' => '#/components/responses/4XX_checkHGVS',
                        ),
                        '500' => array(
                            '$ref' => '#/components/responses/500_checkHGVS',
                        ),
                    ),
                ),
            ),
            '/hello' => array(
                'get' => array(
                    'tags' => array(
                        'Public LOVD API endpoints'
                    ),
                    'summary' => 'Method to check whether the API is alive or not.',
                    'operationId' => 'getHello',
                    'responses' => array(
                        '200' => array(
                            'description' => 'The API is up and functional.',
                            'content' => array(
                                'application/json' => array(
                                    'example' => array(
                                        'version' => $this->API->nVersion,
                                        'messages' => array(
                                            'Hello!',
                                        ),
                                        'warnings' => array(),
                                        'errors' => array(),
                                        'data' => array(),
                                    ),
                                ),
                            ),
                        ),
                        '4XX' => array(
                            'description' => 'A result of a processing error. The API has not been queried properly.',
                        ),
                        '500' => array(
                            'description' => 'A result of an internal error.',
                        ),
                    ),
                ),
            ),
        );

        // Provide the available responses.
        $aResponse['components']['responses'] = array(
            '200_checkHGVS' => array(
                'description' => 'A result of a successfully processed variant or list of variants. This does not mean that the input variant(s) are using valid nomenclature.',
                'content' => array(
                    'application/json' => array(
                        'schema' => array(
                            '$ref' => 'checkHGVS/schema.json',
                        ),
                        'example' => array(
                            'version' => $this->API->nVersion,
                            'messages' => array(
                                'Successfully received 1 variant description.',
                                'Note that this API does not validate variants on the sequence level, but only checks if the variant description follows the HGVS nomenclature rules.',
                                'For sequence-level validation of DNA variants, please use https:\/\/variantvalidator.org.'
                            ),
                            'warnings' => array(),
                            'errors' => array(),
                            'data' => array(
                                'NM_002225.3:c.157C>T' => array(
                                    'messages' => array(
                                        'IOK' => 'This variant description is HGVS-compliant.'
                                    ),
                                    'warnings' => array(),
                                    'errors' => array(),
                                    'data' => array(
                                        'position_start' => 157,
                                        'position_end' => 157,
                                        'type' => 'subst',
                                        'range' => false,
                                        'suggested_correction' => array()
                                    )
                                )
                            ),
                            'library_version' => '2022-08-24'
                        ),
                    ),
                ),
            ),
            '4XX_checkHGVS' => array(
                'description' => 'A result of a processing error. The API has not been queried properly, and the data could not be processed.',
                'content' => array(
                    'application/json' => array(
                        'schema' => array(
                            '$ref' => 'checkHGVS/schema.json',
                        ),
                        'example' => array(
                            'version' => $this->API->nVersion,
                            'messages' => array(),
                            'warnings' => array(),
                            'errors' => array(
                                'Could not parse the given request. Did you submit a variant?'
                            ),
                            'data' => array(),
                            'library_version' => '2022-08-24'
                        ),
                    ),
                ),
            ),
            '500_checkHGVS' => array(
                'description' => 'A result of an internal error. The library could somehow not handle the request.',
                'content' => array(
                    'application/json' => array(
                        'schema' => array(
                            '$ref' => 'checkHGVS/schema.json',
                        ),
                        'example' => array(
                            'version' => $this->API->nVersion,
                            'messages' => array(),
                            'warnings' => array(),
                            'errors' => array(
                                'Request not handled well by any handler.'
                            ),
                            'data' => array(),
                            'library_version' => '2022-08-24'
                        ),
                    ),
                ),
            ),
        );

        return $aResponse;
    }





    public function v2_getOpenAPISpecs ()
    {
        // Fill in the v2-specific data.
        // Takes the basics from the previous version, then makes changes.
        $aResponse = $this->v1_getOpenAPISpecs();

        // Add the version (date).
        $aResponse['info']['version'] = '2025-02-19'; // Copied in swagger-initializer.js.

        // Provide the available responses, they are different from v1.
        // First, some general changes.
        unset($aResponse['components']['responses']['200_checkHGVS']['content']['application/json']['example']['library_version']);
        unset($aResponse['components']['responses']['4XX_checkHGVS']['content']['application/json']['example']['library_version']);
        unset($aResponse['components']['responses']['500_checkHGVS']['content']['application/json']['example']['library_version']);

        // Update the 200 OK output.
        $aResponse['components']['responses']['200_checkHGVS']['content']['application/json']['example']['data'] = array(
            array(
                'input' => 'NM_002225.3:c.157C>T',
                'identified_as' => 'full_variant_DNA',
                'identified_as_formatted' => 'full variant (DNA)',
                'valid' => true,
                'messages' => array(),
                'warnings' => array(),
                'errors' => array(),
                'data' => array(
                    'position_start' => 157,
                    'position_end' => 157,
                    'position_start_intron' => 0,
                    'position_end_intron' => 0,
                    'range' => false,
                    'type' => ">"
                ),
                'corrected_values' => array(
                    'NM_002225.3:c.157C>T' => 1
                )
            )
        );
        $aResponse['components']['responses']['200_checkHGVS']['content']['application/json']['example']['versions'] = array(
            'library_date' => '2025-03-26',
            'library_version' => '0.4.2',
            'HGVS_nomenclature_versions' => array(
                'input' => array(
                    'minimum' => '15.11',
                    'maximum' => '21.1.2'
                ),
                'output' => '21.1.2'
            ),
            'caches' => array(
                'genes' => '2025-06-17',
            ),
        );

        return $aResponse;
    }
}
?>
