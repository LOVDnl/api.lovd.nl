<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2022-08-16
 * Modified    : 2022-08-19
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
        // lovd_getVariantInfo()'s prefix tests, as general tests for us.
        // We will not repeat all of lovd_getVariantInfo()'s tests;
        //  we ought to test the API, not the library.
        'g.123dup' => array(
            'messages' => array(
                'IOK' => 'This variant description is HGVS-compliant.',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => 'dup',
                'range' => false,
            ),
        ),
        'c.123dup' => array(
            'messages' => array(
                'IOK' => 'This variant description is HGVS-compliant.',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => 'dup',
                'range' => false,
            ),
        ),
        'm.123dup' => array(
            'messages' => array(
                'IOK' => 'This variant description is HGVS-compliant.',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => 'dup',
                'range' => false,
            ),
        ),
        'n.123dup' => array(
            'messages' => array(
                'IOK' => 'This variant description is HGVS-compliant.',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => 'dup',
                'range' => false,
            ),
        ),
        'g.-123dup' => array(
            'errors' => array(
                'EFALSEUTR' => 'Only coding transcripts (c. prefix) have a UTR region. Therefore, position "-123" which describes a position in the 5\' UTR, is invalid when using the "g" prefix.',
            ),
            'data' => array(
                'position_start' => 0,
                'position_end' => 0,
                'type' => 'dup',
                'range' => false,
            ),
        ),
        'g.*123dup' => array(
            'errors' => array(
                'EFALSEUTR' => 'Only coding transcripts (c. prefix) have a UTR region. Therefore, position "*123" which describes a position in the 3\' UTR, is invalid when using the "g" prefix.',
            ),
            'data' => array(
                'position_start' => 0,
                'position_end' => 0,
                'type' => 'dup',
                'range' => false,
            ),
        ),
        'm.123+4_124-20dup' => array(
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
            ),
        ),
        'g.123000-125000dup' => array(
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
            ),
        ),
        // lovd_fixHGVS()'s prefix-related tests.
        '123dup' => array(
            'errors' => array(
                'EFAIL' => 'Failed to recognize a variant description in your input.',
            ),
            'data' => array(
                'suggested_correction' => array(
                    'value' => 'g.123dup',
                    'confidence' => 'medium',
                ),
            ),
        ),
        '(123dup)' => array(
            'errors' => array(
                'EFAIL' => 'Failed to recognize a variant description in your input.',
            ),
            'data' => array(
                'suggested_correction' => array(
                    'value' => 'g.(123dup)',
                    'confidence' => 'medium',
                ),
            ),
        ),
        '.123dup' => array(
            'errors' => array(
                'EFAIL' => 'Failed to recognize a variant description in your input.',
            ),
            'data' => array(
                'suggested_correction' => array(
                    'value' => 'g.123dup',
                    'confidence' => 'medium',
                ),
            ),
        ),
        '123-5dup' => array(
            'errors' => array(
                'EFAIL' => 'Failed to recognize a variant description in your input.',
            ),
            'data' => array(
                'suggested_correction' => array(
                    'value' => 'c.123-5dup',
                    'confidence' => 'medium',
                ),
            ),
        ),

        // lovd_getVariantInfo()'s substitution-related tests that are fixable.
        'g.123A>GC' => array(
            'warnings' => array(
                'WWRONGTYPE' => 'A substitution should be a change of one base to one base. Did you mean to describe a deletion-insertion?',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => 'subst',
                'range' => false,
                'suggested_correction' => array(
                    'value' => 'g.123delinsGC',
                    'confidence' => 'high',
                ),
            ),
        ),
        'g.123AA>G' => array(
            'warnings' => array(
                'WWRONGTYPE' => 'A substitution should be a change of one base to one base. Did you mean to describe a deletion-insertion?',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => 'subst',
                'range' => false,
                'suggested_correction' => array(
                    'value' => 'g.123_124delinsG',
                    'confidence' => 'high',
                ),
            ),
        ),
        'g.123A>.' => array(
            'warnings' => array(
                'WWRONGTYPE' => 'A substitution should be a change of one base to one base. Did you mean to describe a deletion?',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => 'subst',
                'range' => false,
                'suggested_correction' => array(
                    'value' => 'g.123del',
                    'confidence' => 'high',
                ),
            ),
        ),
        'g.123_124AA>GC' => array(
            'warnings' => array(
                'WWRONGTYPE' => 'A substitution should be a change of one base to one base. Did you mean to describe a deletion-insertion?',
            ),
            'errors' => array(
                'ETOOMANYPOSITIONS' => 'Too many positions are given; a substitution is used to only indicate single-base changes and therefore should have only one position.'
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 124,
                'type' => 'subst',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.123_124delinsGC',
                    'confidence' => 'high',
                ),
            ),
        ),

        // lovd_getVariantInfo()'s duplication-related tests that are fixable.
        'g.123_125dupACG' => array(
            'warnings' => array(
                'WSUFFIXGIVEN' => 'Nothing should follow "dup".'
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 125,
                'type' => 'dup',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.123_125dup',
                    'confidence' => 'medium',
                ),
            ),
        ),

        // lovd_getVariantInfo()'s deletion-related tests that are fixable.
        'g.1delA' => array(
            'warnings' => array(
                'WSUFFIXGIVEN' => 'Nothing should follow "del".'
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 1,
                'type' => 'del',
                'range' => false,
                'suggested_correction' => array(
                    'value' => 'g.1del',
                    'confidence' => 'medium',
                ),
            ),
        ),

        // lovd_getVariantInfo()'s insertion-related tests that are fixable.
        'g.1_2ins(50)' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "ins" does not follow HGVS guidelines.'
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 2,
                'type' => 'ins',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.1_2insN[50]',
                    'confidence' => 'medium',
                ),
            ),
        ),
        'g.1_2insN[5_10]' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "ins" does not follow HGVS guidelines. Please rewrite "N[5_10]" to "N[(5_10)]".',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 2,
                'type' => 'ins',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.1_2insN[(5_10)]',
                    'confidence' => 'medium',
                ),
            ),
        ),
        'g.1_2insN[(10_5)]' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "ins" does not follow HGVS guidelines. Please rewrite "N[(10_5)]" to "N[(5_10)]".',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 2,
                'type' => 'ins',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.1_2insN[(5_10)]',
                    'confidence' => 'medium',
                ),
            ),
        ),
        'g.1_2insN[(10_10)]' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "ins" does not follow HGVS guidelines. Please rewrite "N[(10_10)]" to "N[10]".',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 2,
                'type' => 'ins',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.1_2insN[10]',
                    'confidence' => 'medium',
                ),
            ),
        ),
        'g.(1_2)insA' => array(
            'errors' => array(
                'EPOSITIONFORMAT' => 'The two positions do not indicate a range longer than two bases. Please remove the parentheses if the positions are certain.',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 2,
                'type' => 'ins',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.1_2insA',
                    'confidence' => 'medium',
                ),
            ),
        ),
        'c.(123+10_123+11)insA' => array(
            'errors' => array(
                'EPOSITIONFORMAT' => 'The two positions do not indicate a range longer than two bases. Please remove the parentheses if the positions are certain.',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'position_start_intron' => 10,
                'position_end_intron' => 11,
                'type' => 'ins',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'c.123+10_123+11insA',
                    'confidence' => 'medium',
                ),
            ),
        ),

        // lovd_getVariantInfo()'s deletion-insertion-related tests that are fixable.
        'g.123delAinsG' => array(
            'warnings' => array(
                'WWRONGTYPE' => 'A deletion-insertion of one base to one base should be described as a substitution. Please rewrite "delAinsG" to "A>G".',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => 'delins',
                'range' => false,
                'suggested_correction' => array(
                    'value' => 'g.123A>G',
                    'confidence' => 'high',
                ),
            ),
        ),
        'g.123delAinsGG' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "del" does not follow HGVS guidelines. Please rewrite "delAinsGG" to "delinsGG".',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => 'delins',
                'range' => false,
                'suggested_correction' => array(
                    'value' => 'g.123delinsGG',
                    'confidence' => 'medium',
                ),
            ),
        ),
        'g.100_200con400_500' => array(
            'warnings' => array(
                'WWRONGTYPE' => 'A conversion should be described as a deletion-insertion. Please rewrite "con" to "delins".',
            ),
            'data' => array(
                'position_start' => 100,
                'position_end' => 200,
                'type' => 'delins',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.100_200delins400_500',
                    'confidence' => 'high',
                ),
            ),
        ),
        'g.123conNC_000001.10:100_200' => array(
            'warnings' => array(
                'WWRONGTYPE' => 'A conversion should be described as a deletion-insertion. Please rewrite "con" to "delins".',
                'WSUFFIXFORMAT' => 'The part after "con" does not follow HGVS guidelines.',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => 'delins',
                'range' => false,
                'suggested_correction' => array(
                    'value' => 'g.123delins[NC_000001.10:g.100_200]',
                    'confidence' => 'medium',
                ),
            ),
        ),
        'g.1_5delins20_10' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "delins" does not follow HGVS guidelines.',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 5,
                'type' => 'delins',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.1_5delins10_20',
                    'confidence' => 'medium',
                ),
            ),
        ),

        // One of lovd_getVariantInfo()'s repeat-related tests as a general test
        //  to see how we handle the WNOTSUPPORTED.
        'g.1ACT[20]' => array(
            'messages' => array(
                'IOK' => 'This variant description is HGVS-compliant.',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 1,
                'type' => 'repeat',
                'range' => false,
            ),
        ),

        // lovd_getVariantInfo()'s wild type-related test that is fixable.
        'g.123A=' => array(
            'warnings' => array(
                'WBASESGIVEN' => 'When using "=", please remove the original sequence before the "=".',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => '=',
                'range' => false,
                'suggested_correction' => array(
                    'value' => 'g.123=',
                    'confidence' => 'medium',
                ),
            ),
        ),

        // One of lovd_getVariantInfo()'s unknown variant-related tests as a
        //  general test to see how we handle the question mark in the input.
        'c.123?' => array(
            'messages' => array(
                'IOK' => 'This variant description is HGVS-compliant.',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => null,
                'range' => false,
            ),
        ),

        // lovd_getVariantInfo()'s unsure variant-related test that is fixable.
        'g.((1_2insA)' => array(
            'warnings' => array(
                'WUNBALANCEDPARENTHESES' => 'The variant description contains unbalanced parentheses.'
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 2,
                'type' => 'ins',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.(1_2insA)',
                    'confidence' => 'low',
                ),
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

        // Tests don't need to define empty arrays, to compact the definition of tests.
        if (!isset($aExpectedOutput['messages'])) {
            $aExpectedOutput = array('messages' => array()) + $aExpectedOutput;
        }
        // Because I don't sort on key when comparing outputs, I need to maintain the order.
        $sPrevField = 'messages';
        foreach (array('warnings', 'errors') as $sField) {
            if (!isset($aExpectedOutput[$sField])) {
                lovd_arrayInsertAfter($sPrevField, $aExpectedOutput, $sField, array());
            }
            $sPrevField = $sField;
        }
        // We also don't get suggestions always.
        if (!isset($aExpectedOutput['data']['suggested_correction'])) {
            $aExpectedOutput['data']['suggested_correction'] = array();
        }

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

        // Make diff smaller, by ignoring differences in array size. Just remove those numbers.
        foreach (array('aOutput', 'aExpectedOutput') as $sVariable) {
            $$sVariable = array_map(function ($sValue) {
                return preg_replace('/^(\s*array)\([0-9]+\) \{$/', '\1 {', $sValue);
            }, $$sVariable);
        }

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

        // Clean up long diffs by shortening common pre- and suffixes.
        if (count($aPrefix) > 10) {
            $aPrefix = array_merge(
                array('...'),
                array_slice($aPrefix, -10)
            );
        }
        if (count($aSuffix) > 10) {
            $aSuffix = array_merge(
                array_slice($aSuffix, 0, 10),
                array('...')
            );
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
