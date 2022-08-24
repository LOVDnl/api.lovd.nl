<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2022-08-24
 * Modified    : 2022-08-24         // When modified, also change info->version.
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

        $this->API = $oAPI;
        $this->API->aResponse = array(
            'openapi' => '3.0.3',
            'info' => array(
                'title' => 'Public LOVD API endpoints',
                'description' => 'These are the public LOVD API endpoints provided to the community. For more information, please see [our Github page](https://github.com/LOVDnl/api.lovd.nl/).',
                'termsOfService' => 'https://github.com/LOVDnl/api.lovd.nl/',
                'contact' => array(
                    'name' => 'Leiden Open Variation Database team',
                    'url' => 'https://LOVD.nl/contact?question',
                ),
                'license' => array(
                    'name' => 'GNU General Public License v3.0',
                    'url' => 'https://github.com/LOVDnl/api.lovd.nl/raw/main/LICENSE',
                ),
                'version' => '2022-08-24',
            ),
            'servers' => array(
                array(
                    'url' => 'https://api.lovd.nl/{version}',
                    'description' => 'Public LOVD APIs, production server.',
                    'variables' => array(
                        'version' => array(
                            'default' => 'v' . $this->API->nVersion,
                        ),
                    ),
                ),
            ),
            'paths' => array(), // To be filled in later.
            'components' => array(),
            'externalDocs' => array(
                'description' => 'Our Github page.',
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
        return true;
    }
}
?>
