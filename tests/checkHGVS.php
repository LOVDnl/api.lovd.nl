<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2022-08-16
 * Modified    : 2022-08-16
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

// Very basic tests, not using PHPUnit.
$sURL = 'http://localhost/git/api.lovd.nl/src';
$aTests = array(
    // V1 output. Using this structure, we can tests different API versions,
    //  as each version should be backwards-compatible.
    1 => array(
        'g.123dup' => array(
            'messages' => array(
                'IOK' => 'This variant description is HGVS-compliant.',
                'IREFSEQMISSING' => 'Please note that your variant description is missing a reference sequence. Although this is not necessary for our syntax check, a variant description does need a reference sequence to be fully informative and HGVS-compliant.',
            ),
            'warnings' => array(),
            'errors' => array(),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => 'dup',
                'range' => false,
                'suggested_correction' => array(),
            ),
        ),
    ),
);





// Now loop through the tests and check them, one by one.
// Some simplifications from the tests will have to be handled here.
$aDifferences = array();
$nTests = 0;
$nTestsSucceeded = 0;
$nTestsFailed = 0;
foreach ($aTests as $nVersion => $aTestSet) {
    foreach ($aTestSet as $sVariant => $aExpectedOutput) {
        $nTests ++;
        // We also expect some other output.
        $aExpectedOutput = array(
            'version' => $nVersion,
            'messages' => array(
                'Successfully received 1 variant description.',
                'Note that this API does not validate variants on the sequence level, but only checks if the variant description follows the HGVS nomenclature rules.',
                'For sequence-level validation of DNA variants, please use https://variantvalidator.org.',
            ),
            'warnings' => array(),
            'errors' => array(),
            'data' => array(
                $sVariant => $aExpectedOutput,
            ),
        );

        // Measure the actual output.
        $aOutput = array('Failed to decode JSON.');
        $sOutput = file_get_contents($sURL . '/v' . $nVersion . '/checkHGVS/' . rawurlencode($sVariant));
        if ($sOutput) {
            $aOutput = json_decode($sOutput, true);
        }

        if (!isset($aOutput['library_version']) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $aOutput['library_version'])) {
            // Error...
            $aDifferences[] = array($nVersion, $sVariant, $aExpectedOutput, $aOutput);
            echo 'E';
            $nTestsFailed ++;
        }
        // Since our tests don't define it, unset it.
        unset($aOutput['library_version']);

        if ($aOutput !== $aExpectedOutput) {
            // Something's different. We don't mention that here yet.
            $aDifferences[] = array($nVersion, $sVariant, $aExpectedOutput, $aOutput);
            echo 'F';
            $nTestsFailed ++;
        } else {
            echo '.';
            $nTestsSucceeded ++;
        }
    }
}

echo "\n";
// Generate stats.
echo "Ran $nTests tests, $nTestsFailed failed, $nTestsSucceeded succeeded.\n";
?>
