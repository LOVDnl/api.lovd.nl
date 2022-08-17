<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2022-08-16
 * Modified    : 2022-08-17
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

// We'll use some library functions. However, we'll need a trick to determine where to find the library.
// Since this file is executed using `php -f ...`, our cwd isn't where we are located.
define('ROOT_PATH', dirname(__FILE__) . '/../src/');
require ROOT_PATH . 'inc-init.php';

// Very basic tests, not using PHPUnit.
$sURL = 'http://localhost/git/api.lovd.nl/src';
$aTests = array(
    // V1 output. Using this structure, we can tests different API versions,
    //  as each version should be backwards-compatible.
    1 => array(
        // Prefixes.
        'g.123dup' => array(
            'messages' => array(
                'IOK' => 'This variant description is HGVS-compliant.',
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
        'c.123dup' => array(
            'messages' => array(
                'IOK' => 'This variant description is HGVS-compliant.',
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
        'm.123dup' => array(
            'messages' => array(
                'IOK' => 'This variant description is HGVS-compliant.',
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
        'n.123dup' => array(
            'messages' => array(
                'IOK' => 'This variant description is HGVS-compliant.',
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
        'g.-123dup' => array(
            'messages' => array(),
            'warnings' => array(),
            'errors' => array(
                'EFALSEUTR' => 'Only coding transcripts (c. prefix) have a UTR region. Therefore, position "-123" which describes a position in the 5\' UTR, is invalid when using the "g" prefix.',
            ),
            'data' => array(
                'position_start' => 0,
                'position_end' => 0,
                'type' => 'dup',
                'range' => false,
                'suggested_correction' => array(),
            ),
        ),
        'g.*123dup' => array(
            'messages' => array(),
            'warnings' => array(),
            'errors' => array(
                'EFALSEUTR' => 'Only coding transcripts (c. prefix) have a UTR region. Therefore, position "*123" which describes a position in the 3\' UTR, is invalid when using the "g" prefix.',
            ),
            'data' => array(
                'position_start' => 0,
                'position_end' => 0,
                'type' => 'dup',
                'range' => false,
                'suggested_correction' => array(),
            ),
        ),
        'm.123+4_124-20dup' => array(
            'messages' => array(),
            'warnings' => array(),
            'errors' => array(
                'EFALSEINTRONIC' => 'Only transcripts (c. or n. prefixes) have introns. Therefore, this variant description with a position in an intron is invalid when using the "m" prefix.',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 124,
                'position_start_intron' => 4,
                'position_end_intron' => -20,
                'type' => 'dup',
                'range' => true,
                'suggested_correction' => array(),
            ),
        ),
        'g.123000-125000dup' => array(
            'messages' => array(),
            'warnings' => array(),
            'errors' => array(
                'EFALSEINTRONIC' => 'Only transcripts (c. or n. prefixes) have introns. Therefore, this variant description with a position in an intron is invalid when using the "g" prefix. Did you perhaps try to indicate a range? If so, please use an underscore (_) to indicate a range.',
            ),
            'data' => array(
                'position_start' => 123000,
                'position_end' => 123000,
                'position_start_intron' => -125000,
                'position_end_intron' => -125000,
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

        // If the variant has no refseq, the output will remind the user.
        if (!lovd_variantHasRefSeq($sVariant)) {
            $aExpectedOutput['data'][$sVariant]['messages']['IREFSEQMISSING'] = 'Please note that your variant description is missing a reference sequence. Although this is not necessary for our syntax check, a variant description does need a reference sequence to be fully informative and HGVS-compliant.';
        }

        // Measure the actual output.
        $aOutput = array('Failed to decode JSON.');
        $sOutput = file_get_contents($sURL . '/v' . $nVersion . '/checkHGVS/' . rawurlencode($sVariant));
        if ($sOutput) {
            $aOutput = json_decode($sOutput, true);
        }

        if (!isset($aOutput['library_version']) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $aOutput['library_version'])) {
            // Error...
            $aDifferences[$nTests] = array($nVersion, $sVariant, $aExpectedOutput, $aOutput);
            echo 'E';
            $nTestsFailed ++;
            continue;
        }
        // Since our tests don't define it, unset it.
        unset($aOutput['library_version']);

        if ($aOutput !== $aExpectedOutput) {
            // Something's different. We don't mention that here yet.
            $aDifferences[$nTests] = array($nVersion, $sVariant, $aExpectedOutput, $aOutput);
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

if ($aDifferences) {
    foreach ($aDifferences as $nID => $aDifference) {
        list($nVersion, $sVariant, $aExpectedOutput, $aOutput) = $aDifference;

        echo "\n#$nID CheckHGVS API v$nVersion $sVariant\n\n";

        // Now catch the output of the var_dump()s of both arrays.
        ob_start();
        var_dump($aOutput);
        $aOutput = explode("\n", ob_get_clean());
        ob_start();
        var_dump($aExpectedOutput);
        $aExpectedOutput = explode("\n", ob_get_clean());

        // Generate a diff.
        $aPrefix = array();
        $aSuffix = array();

        // Collect the common prefix.
        while (isset($aOutput[0]) && isset($aExpectedOutput[0]) && $aOutput[0] === $aExpectedOutput[0]) {
            $aPrefix[] = array_shift($aOutput);
            array_shift($aExpectedOutput);
        }

        // Collect the common suffix.
        while ($aOutput && $aExpectedOutput && end($aOutput) === end($aExpectedOutput)) {
            array_unshift($aSuffix, array_pop($aOutput));
            array_pop($aExpectedOutput);
        }

        // Print the differences. We always have a prefix and suffix, because we always have an array.
        echo "  " . implode("\n  ", $aPrefix) . "\n";
        if ($aExpectedOutput) {
            echo "- " . implode("\n- ", $aExpectedOutput) . "\n";
        }
        if ($aOutput) {
            echo "+ " . implode("\n+ ", $aOutput) . "\n";
        }
        echo "  " . implode("\n  ", $aSuffix) . "\n";
    }
}
?>