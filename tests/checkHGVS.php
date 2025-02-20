<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2022-08-16
 * Modified    : 2025-02-19
 * For LOVD    : 3.0-29
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

// We'll use some library functions. However, we'll need a trick to determine where to find the library.
// Since this file is executed using `php -f ...`, our cwd isn't where we are located.
define('ROOT_PATH', dirname(__FILE__) . '/../src/');
require ROOT_PATH . 'inc-init.php';
restore_error_handler();

// Very basic tests, not using PHPUnit.
$sURL = 'http://localhost/git/api.lovd.nl/src';
$aTests = array(
    // V1 output. Using this structure, we can test different API versions,
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
                    'value' => 'c.123dup',
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
                    'value' => 'c.(123dup)',
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
                    'value' => 'c.123dup',
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
                'WSUFFIXFORMAT' => 'The part after "ins" does not follow HGVS guidelines. Do you mean to indicate inserted positions (e.g., "ins50_60") or an inserted fragment with an unknown sequence but a given length (e.g., "insN[50]")?'
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
                'WSUFFIXFORMAT' => 'The part after "con" does not follow HGVS guidelines. Failed to recognize a valid sequence or position in "NC_000001.10:100_200".',
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
                'WSUFFIXFORMAT' => 'The part after "delins" does not follow HGVS guidelines. The positions are not given in the correct order. Please verify your description and try again.',
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

        // lovd_getVariantInfo()'s question mark-related tests that are fixable.
        'g.?_?del' => array(
            'warnings' => array(
                'WTOOMUCHUNKNOWN' => 'This variant description contains redundant question marks. Please rewrite the positions ?_? to ?.',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 4294967295,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.?del',
                    'confidence' => 'medium',
                ),
            ),
        ),
        'g.(?_?)del' => array(
            'warnings' => array(
                'WTOOMUCHUNKNOWN' => 'This variant description contains redundant question marks. Please rewrite the positions (?_?) to ?.',
            ),
            'errors' => array(
                'ESUFFIXMISSING' => 'The length must be provided for variants which took place within an uncertain range.',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 4294967295,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.?del',
                    'confidence' => 'low',
                ),
            ),
        ),
        'g.(5_?)_?del' => array(
            'warnings' => array(
                'WTOOMUCHUNKNOWN' => 'This variant description contains redundant question marks. Please rewrite the positions (5_?)_? to (5_?).',
            ),
            'errors' => array(
                'ESUFFIXMISSING' => 'The length must be provided for variants which took place within an uncertain range.',
            ),
            'data' => array(
                'position_start' => 5,
                'position_end' => 4294967295,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.(5_?)del',
                    'confidence' => 'low',
                ),
            ),
        ),
        'g.(?_?)_10del' => array(
            'warnings' => array(
                'WTOOMUCHUNKNOWN' => 'This variant description contains redundant question marks. Please rewrite the positions (?_?)_10 to ?_10.',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 10,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.?_10del',
                    'confidence' => 'medium',
                ),
            ),
        ),
        'g.(?_?)_(10_?)del' => array(
            'warnings' => array(
                'WTOOMUCHUNKNOWN' => 'This variant description contains redundant question marks. Please rewrite the positions (?_?)_(10_?) to ?_(10_?).',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 10,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.?_(10_?)del',
                    'confidence' => 'medium',
                ),
            ),
        ),
        'g.(?_?)_(?_10)del' => array(
            'warnings' => array(
                'WTOOMUCHUNKNOWN' => 'This variant description contains redundant question marks. Please rewrite the positions (?_?)_(?_10) to (?_10).',
            ),
            'errors' => array(
                'ESUFFIXMISSING' => 'The length must be provided for variants which took place within an uncertain range.',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 10,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.(?_10)del',
                    'confidence' => 'low',
                ),
            ),
        ),
        'g.?_(?_10)del' => array(
            'warnings' => array(
                'WTOOMUCHUNKNOWN' => 'This variant description contains redundant question marks. Please rewrite the positions ?_(?_10) to (?_10).',
            ),
            'errors' => array(
                'ESUFFIXMISSING' => 'The length must be provided for variants which took place within an uncertain range.',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 10,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.(?_10)del',
                    'confidence' => 'low',
                ),
            ),
        ),
        'g.5_(?_?)del' => array(
            'warnings' => array(
                'WTOOMUCHUNKNOWN' => 'This variant description contains redundant question marks. Please rewrite the positions 5_(?_?) to 5_?.',
            ),
            'data' => array(
                'position_start' => 5,
                'position_end' => 4294967295,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.5_?del',
                    'confidence' => 'medium',
                ),
            ),
        ),
        'g.(5_?)_(?_?)del' => array(
            'warnings' => array(
                'WTOOMUCHUNKNOWN' => 'This variant description contains redundant question marks. Please rewrite the positions (5_?)_(?_?) to (5_?).',
            ),
            'errors' => array(
                'ESUFFIXMISSING' => 'The length must be provided for variants which took place within an uncertain range.',
            ),
            'data' => array(
                'position_start' => 5,
                'position_end' => 4294967295,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.(5_?)del',
                    'confidence' => 'low',
                ),
            ),
        ),
        'g.(?_5)_(?_?)del' => array(
            'warnings' => array(
                'WTOOMUCHUNKNOWN' => 'This variant description contains redundant question marks. Please rewrite the positions (?_5)_(?_?) to (?_5)_?.',
            ),
            'data' => array(
                'position_start' => 5,
                'position_end' => 4294967295,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.(?_5)_?del',
                    'confidence' => 'medium',
                ),
            ),
        ),
        'g.(5_?)_(?_10)del' => array(
            'warnings' => array(
                'WTOOMUCHUNKNOWN' => 'This variant description contains redundant question marks. Please rewrite the positions (5_?)_(?_10) to (5_10).',
            ),
            'errors' => array(
                'ESUFFIXMISSING' => 'The length must be provided for variants which took place within an uncertain range.',
            ),
            'data' => array(
                'position_start' => 5,
                'position_end' => 10,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.(5_10)del',
                    'confidence' => 'low',
                ),
            ),
        ),
        'g.(?_?)_(?_?)del' => array(
            'warnings' => array(
                'WTOOMUCHUNKNOWN' => 'This variant description contains redundant question marks. Please rewrite the positions (?_?)_(?_?) to ?.',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 4294967295,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.?del',
                    'confidence' => 'medium',
                ),
            ),
        ),

        // lovd_getVariantInfo()'s tests for fixable challenging positions.
        'c.-010+01del' => array(
            'warnings' => array(
                'WPOSITIONFORMAT' => 'Variant positions should not be prefixed by a 0. Please rewrite "-010" to "-10". Please rewrite "+01" to "+1".'
            ),
            'data' => array(
                'position_start' => -10,
                'position_end' => -10,
                'position_start_intron' => 1,
                'position_end_intron' => 1,
                'type' => 'del',
                'range' => false,
                'suggested_correction' => array(
                    'value' => 'c.-10+1del',
                    'confidence' => 'medium',
                ),
            )
        ),
        'g.1_1del' => array(
            'warnings' => array(
                'WPOSITIONFORMAT' => 'This variant description contains two positions that are the same. Please verify your description and try again.'
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 1,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.1del',
                    'confidence' => 'medium',
                ),
            )
        ),
        'g.2_1del' => array(
            'warnings' => array(
                'WPOSITIONFORMAT' => 'The positions are not given in the correct order. Please verify your description and try again.'
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 2,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.1_2del',
                    'confidence' => 'medium',
                ),
            )
        ),
        'c.*2_1del' => array(
            'warnings' => array(
                'WPOSITIONFORMAT' => 'The positions are not given in the correct order. Please verify your description and try again.'
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 1000002,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'c.1_*2del',
                    'confidence' => 'medium',
                ),
            )
        ),
        'c.(*50_500)_(100_1)del' => array(
            'warnings' => array(
                'WPOSITIONFORMAT' => 'The positions are not given in the correct order. Please verify your description and try again.'
            ),
            'data' => array(
                'position_start' => 100,
                'position_end' => 1000050,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'c.(1_100)_(500_*50)del',
                    'confidence' => 'medium',
                ),
            )
        ),
        'c.(500_*50)_(1_100)del' => array(
            'warnings' => array(
                'WPOSITIONFORMAT' => 'The positions are not given in the correct order. Please verify your description and try again.'
            ),
            'data' => array(
                'position_start' => 100,
                'position_end' => 1000050,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'c.(1_100)_(500_*50)del',
                    'confidence' => 'medium',
                ),
            )
        ),
        'c.123-5_123-10del' => array(
            'warnings' => array(
                'WPOSITIONFORMAT' => 'The intronic positions are not given in the correct order. Please verify your description and try again.'
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'position_start_intron' => -10,
                'position_end_intron' => -5,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'c.123-10_123-5del',
                    'confidence' => 'medium',
                ),
            )
        ),

        // lovd_getVariantInfo()'s tests for fixable challenging insertions.
        'g.1_2ins(5_10)' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "ins" does not follow HGVS guidelines. Do you mean to indicate inserted positions (e.g., "ins5_10") or an inserted fragment with an unknown sequence but a given length (e.g., "insN[(5_10)]")?',
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
            )
        ),
        'g.1_2ins[A]' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "ins" does not follow HGVS guidelines. Only use square brackets for complex insertions.',
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
            )
        ),
        'g.1_2insNC123456.1:g.1_10' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "ins" does not follow HGVS guidelines. Failed to recognize a valid sequence or position in "NC123456.1:g.1_10".',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 2,
                'type' => 'ins',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.1_2ins[NC_123456.1:g.1_10]',
                    'confidence' => 'medium',
                ),
            )
        ),

        // lovd_getVariantInfo()'s tests for fixable suffixes with other affected sequences.
        'g.1_10delAAAAAAAAAA' => array(
            'warnings' => array(
                'WSUFFIXGIVEN' => 'Nothing should follow "del".'
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 10,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.1_10del',
                    'confidence' => 'medium',
                ),
            )
        ),
        'g.(1_100)del50' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "del" does not follow HGVS guidelines. Please rewrite "50" to "N[50]".',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 100,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.(1_100)delN[50]',
                    'confidence' => 'medium',
                ),
            )
        ),
        'g.(1_100)del(30)' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "del" does not follow HGVS guidelines. Please rewrite "(30)" to "N[30]".',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 100,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.(1_100)delN[30]',
                    'confidence' => 'medium',
                ),
            )
        ),
        'g.(1_100)del(30_30)' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "del" does not follow HGVS guidelines. Please rewrite "(30_30)" to "N[30]".',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 100,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.(1_100)delN[30]',
                    'confidence' => 'medium',
                ),
            )
        ),
        'g.(1_100)del(30_50)' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "del" does not follow HGVS guidelines. Please rewrite "(30_50)" to "N[(30_50)]".',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 100,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.(1_100)delN[(30_50)]',
                    'confidence' => 'medium',
                ),
            )
        ),
        'g.(1_100)del(50_30)' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "del" does not follow HGVS guidelines. Please rewrite "(50_30)" to "N[(30_50)]".',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 100,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.(1_100)delN[(30_50)]',
                    'confidence' => 'medium',
                ),
            )
        ),
        'g.(1_100)delN[30_50]' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "del" does not follow HGVS guidelines. Please rewrite "N[30_50]" to "N[(30_50)]".',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 100,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.(1_100)delN[(30_50)]',
                    'confidence' => 'medium',
                ),
            )
        ),
        'g.(100_200)_(400_500)del300' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "del" does not follow HGVS guidelines. Please rewrite "300" to "N[300]".',
            ),
            'data' => array(
                'position_start' => 200,
                'position_end' => 400,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.(100_200)_(400_500)delN[300]',
                    'confidence' => 'medium',
                ),
            )
        ),
        'g.(1_200)_(400_500)del(300)' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "del" does not follow HGVS guidelines. Please rewrite "(300)" to "N[300]".',
            ),
            'data' => array(
                'position_start' => 200,
                'position_end' => 400,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.(1_200)_(400_500)delN[300]',
                    'confidence' => 'medium',
                ),
            )
        ),
        'g.(1_100)inv(30)' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "inv" does not follow HGVS guidelines. Please rewrite "(30)" to "N[30]".',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 100,
                'type' => 'inv',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.(1_100)invN[30]',
                    'confidence' => 'medium',
                ),
            )
        ),

        // lovd_getVariantInfo()'s tests for fixable methylation-related changes.
        'g.123lom' => array(
            'errors' => array(
                'EPIPEMISSING' => 'Please place a "|" between the positions and the variant type (lom).',
            ),
            'data' => array(
                'position_start' => 0,
                'position_end' => 0,
                'type' => '',
                'range' => false,
                'suggested_correction' => array(
                    'value' => 'g.123|lom',
                    'confidence' => 'high',
                ),
            )
        ),

        // One of lovd_getVariantInfo()'s EUNSUPPORTED-related tests as a general test
        //  to see how we handle the error.
        'g.123^124A>C' => array(
            'messages' => array(
                'INOTSUPPORTED' => 'This variant description contains unsupported syntax. Although we aim to support all of the HGVS nomenclature rules, some complex variants are not fully implemented yet in our syntax checker. We invite you to submit your variant description here, so we can have a look: https://github.com/LOVDnl/api.lovd.nl/issues.',
            ),
            'errors' => array(
                'ENOTSUPPORTED' => 'Currently, variant descriptions using "^" are not yet supported. This does not necessarily mean the description is not valid HGVS.',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => '^',
                'range' => false,
            )
        ),

        // Some of lovd_getVariantInfo()'s tests containing reference sequences.
        'NM_123456.1:c.1-1del' => array(
            'errors' => array(
                'EWRONGREFERENCE' =>
                    'The variant is missing a genomic reference sequence required to verify the intronic positions.',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 1,
                'position_start_intron' => -1,
                'position_end_intron' => -1,
                'type' => 'del',
                'range' => false,
            )
        ),
        'LRG:g.1del' => array(
            'errors' => array(
                'EREFERENCEFORMAT' => 'The reference sequence could not be recognised. Supported reference sequence IDs are from NCBI Refseq, Ensembl, and LRG.',
            ),
            'data' => array(
                'position_start' => 1,
                'position_end' => 1,
                'type' => 'del',
                'range' => false,
            )
        ),
        'NM_123456.1(NC_123456.1):c.100del' => array(
            'warnings' => array(
                'WREFERENCEFORMAT' => 'The genomic and transcript reference sequence IDs have been swapped. Please rewrite "NM_123456.1(NC_123456.1)" to "NC_123456.1(NM_123456.1)".',
            ),
            'data' => array(
                'position_start' => 100,
                'position_end' => 100,
                'type' => 'del',
                'range' => false,
                'suggested_correction' => array(
                    'value' => 'NC_123456.1(NM_123456.1):c.100del',
                    'confidence' => 'high',
                ),
            )
        ),

        // lovd_getVariantInfo()'s tests for all other errors or problems.
        'G.123dup' => array(
            'warnings' => array(
                'WWRONGCASE' => 'This is not a valid HGVS description, due to characters being in the wrong case. Please rewrite "G." to "g.".',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => 'dup',
                'range' => false,
                'suggested_correction' => array(
                    'value' => 'g.123dup',
                    'confidence' => 'high',
                ),
            )
        ),
        'g.123DUP' => array(
            'warnings' => array(
                'WWRONGCASE' => 'This is not a valid HGVS description, due to characters being in the wrong case. Please rewrite "DUP" to "dup".',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => 'dup',
                'range' => false,
                'suggested_correction' => array(
                    'value' => 'g.123dup',
                    'confidence' => 'high',
                ),
            )
        ),
        'g.123_130delgagagatt' => array(
            'warnings' => array(
                'WWRONGCASE' => 'This is not a valid HGVS description, due to characters being in the wrong case. Please rewrite "delgagagatt" to "delGAGAGATT".',
                'WSUFFIXGIVEN' => 'Nothing should follow "del".',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 130,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.123_130del',
                    'confidence' => 'medium',
                ),
            )
        ),
        'g.123_130delgagagauu' => array(
            'warnings' => array(
                'WWRONGCASE' => 'This is not a valid HGVS description, due to characters being in the wrong case. Please rewrite "delgagagauu" to "delGAGAGAUU".',
                'WSUFFIXGIVEN' => 'Nothing should follow "del".',
            ),
            'errors' => array(
                'EINVALIDNUCLEOTIDES' => 'This variant description contains invalid nucleotides: "U".',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 130,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.123_130del',
                    'confidence' => 'low',
                ),
            )
        ),
        'g.123_130deln[8]' => array(
            'warnings' => array(
                'WWRONGCASE' => 'This is not a valid HGVS description, due to characters being in the wrong case. Please rewrite "deln[8]" to "delN[8]".',
                'WSUFFIXGIVEN' => 'Nothing should follow "del".',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 130,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.123_130del',
                    'confidence' => 'medium',
                ),
            )
        ),
        'g.123delinsgagagauu' => array(
            'warnings' => array(
                'WWRONGCASE' => 'This is not a valid HGVS description, due to characters being in the wrong case. Please rewrite "delinsgagagauu" to "delinsGAGAGAUU".',
            ),
            'errors' => array(
                'EINVALIDNUCLEOTIDES' => 'This variant description contains invalid nucleotides: "U".',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => 'delins',
                'range' => false,
                'suggested_correction' => array(
                    'value' => 'g.123delinsGAGAGATT',
                    'confidence' => 'low',
                ),
            )
        ),
        'g.123delainst' => array(
            'warnings' => array(
                'WWRONGCASE' => 'This is not a valid HGVS description, due to characters being in the wrong case. Please check the use of upper- and lowercase characters after "del".',
                'WWRONGTYPE' =>
                    'A deletion-insertion of one base to one base should be described as a substitution. Please rewrite "delainst" to "A>T".',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => 'delins',
                'range' => false,
                'suggested_correction' => array(
                    'value' => 'g.123A>T',
                    'confidence' => 'high',
                ),
            )
        ),
        'g.123delainsu' => array(
            'warnings' => array(
                'WWRONGCASE' => 'This is not a valid HGVS description, due to characters being in the wrong case. Please check the use of upper- and lowercase characters after "del".',
                'WWRONGTYPE' =>
                    'A deletion-insertion of one base to one base should be described as a substitution. Please rewrite "delainsu" to "A>U".',
            ),
            'errors' => array(
                'EINVALIDNUCLEOTIDES' => 'This variant description contains invalid nucleotides: "U".',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => 'delins',
                'range' => false,
                'suggested_correction' => array(
                    'value' => 'g.123A>T',
                    'confidence' => 'low',
                ),
            )
        ),
        'g. 123_124insA' => array(
            'warnings' => array(
                'WWHITESPACE' => 'This variant description contains one or more whitespace characters (spaces, tabs, etc). Please remove these.',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 124,
                'type' => 'ins',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'g.123_124insA',
                    'confidence' => 'high',
                ),
            )
        ),
        ' g.123del' => array(
            'warnings' => array(
                'WWHITESPACE' => 'This variant description contains one or more whitespace characters (spaces, tabs, etc). Please remove these.',
            ),
            'data' => array(
                'position_start' => 123,
                'position_end' => 123,
                'type' => 'del',
                'range' => false,
                'suggested_correction' => array(
                    'value' => 'g.123del',
                    'confidence' => 'high',
                ),
            )
        ),

        // API-specific test; do we suggest something anyway, when a EWRONGREFERENCE remains?
        'NM_000277.1:c.838_842+3del8' => array(
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "del" does not follow HGVS guidelines. Please rewrite "8" to "N[8]".',
                'WSUFFIXGIVEN' => 'Nothing should follow "del".',
            ),
            'errors' => array(
                'EWRONGREFERENCE' => 'The variant is missing a genomic reference sequence required to verify the intronic positions.',
            ),
            'data' => array(
                'position_start' => 838,
                'position_end' => 842,
                'position_start_intron' => 0,
                'position_end_intron' => 3,
                'type' => 'del',
                'range' => true,
                'suggested_correction' => array(
                    'value' => 'NM_000277.1:c.838_842+3del',
                    'confidence' => 'low',
                ),
            )
        ),
    ),
    2 => array(
        // API-specific test; just a single test to see if v2 is working.
        array(
            'input' => 'NM_000277.1:c.838_842+3del8',
            'identified_as' => 'full_variant_DNA',
            'identified_as_formatted' => 'full variant (DNA)',
            'valid' => false,
            'warnings' => array(
                'WSUFFIXFORMAT' => 'The part after "del" does not follow HGVS guidelines.',
                'WSUFFIXGIVEN' => 'The deleted sequence is redundant and should be removed.',
            ),
            'errors' => array(
                'EWRONGREFERENCE' => 'A genomic transcript reference sequence is required to verify intronic positions.',
            ),
            'data' => array(
                'position_start' => 838,
                'position_end' => 842,
                'position_start_intron' => 0,
                'position_end_intron' => 3,
                'range' => true,
                'type' => 'del',
            ),
            'corrected_values' => array(
                'NM_000277.1:c.838_842+3del' => 0.1,
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

// First, test v1. It has a few hacks that we don't need for v2.
foreach ($aTests as $nVersion => $aTestSet) {
    if ($nVersion > 1) {
        // Obviously, we could kill one loop, but then we have a huge diff.
        // For now, this works.
        continue;
    }
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
        $sVariantURL = $sURL . '/v' . $nVersion . '/checkHGVS/' . rawurlencode($sVariant);
        $aOutput = array("Failed to decode JSON when calling $sVariantURL.");
        $sOutput = file_get_contents($sVariantURL);
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

// Then, test v2.
$nVersion = 2;
foreach ($aTests[$nVersion] as $aExpectedOutput) {
    $nTests ++;
    $sVariant = $aExpectedOutput['input'];

    // Because I don't sort on key when comparing outputs, I need to maintain the order.
    $sPrevField = 'valid';
    foreach (array('messages', 'warnings', 'errors') as $sField) {
        if (!isset($aExpectedOutput[$sField])) {
            lovd_arrayInsertAfter($sPrevField, $aExpectedOutput, $sField, array());
        }
        $sPrevField = $sField;
    }
    // We also don't get suggestions always.
    if (!isset($aExpectedOutput['corrected_values'])) {
        $aExpectedOutput['corrected_values'] = array(
            $sVariant => 1,
        );
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
            $aExpectedOutput,
        ),
    );

    // If the variant has no refseq, the output will remind the user.
    if (!str_starts_with($aExpectedOutput['data'][0]['identified_as'], 'full_variant')) {
        $aExpectedOutput['data'][0]['messages']['IREFSEQMISSING'] = 'Please note that your variant description is missing a reference sequence. Although this is not necessary for our syntax check, a variant description does need a reference sequence to be fully informative and HGVS-compliant.';
    }

    // Measure the actual output.
    $sVariantURL = $sURL . '/v' . $nVersion . '/checkHGVS/' . rawurlencode($sVariant);
    $aOutput = array("Failed to decode JSON when calling $sVariantURL.");
    $sOutput = file_get_contents($sVariantURL);
    if ($sOutput) {
        $aOutput = json_decode($sOutput, true);
    }

    if (!isset($aOutput['versions'])
        || array_keys($aOutput['versions']) != ['library_version', 'HGVS_nomenclature_versions']
        || array_keys($aOutput['versions']['HGVS_nomenclature_versions']) != ['input', 'output']
        || array_keys($aOutput['versions']['HGVS_nomenclature_versions']['input']) != ['minimum', 'maximum']
        || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $aOutput['versions']['library_version'])) {
        // Error...
        $aDifferences[$nTests] = array($nVersion, $sVariant, $aExpectedOutput, $aOutput);
        echo 'E';
        $nTestsFailed ++;
        continue;
    }
    // Since our tests don't define it, unset it.
    unset($aOutput['versions']);

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
