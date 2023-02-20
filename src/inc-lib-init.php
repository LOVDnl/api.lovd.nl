<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 * Adapted from /src/inc-lib-init.php in the LOVD3 project.
 *
 * Created     : 2022-08-08
 * Modified    : 2023-02-20   // When modified, also change the library_version.
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

// A place to store values used by multiple functions. It doesn't really make
//  sense to define a function simply to store information. But we can't put
//  this in $_SETT because tests need it (and don't include inc-init.php).
$_LIBRARIES = array(
    'regex_patterns' => array(
        'refseq' => array(
            'basic' => '/^[A-Z_.t0-9()-]+$/',
            'strict'  =>
                '/^([NX][CGMRTW]_[0-9]{6}\.[0-9]+' .
                '|[NX][MR]_[0-9]{9}\.[0-9]+' .
                '|N[CGTW]_[0-9]{6}\.[0-9]+\([NX][MR]_[0-9]{6,9}\.[0-9]+\)' .
                '|ENS[TG][0-9]{11}\.[0-9]+' .
                '|LRG_[0-9]+(t[0-9]+)?' .
                ')$/',
        ),
        'refseq_to_DNA_type' => array(
            '/[NX]M_/'                    => array('c'),
            '/[NX]R_/'                    => array('n'),
            '/^(ENST|LRG_[0-9]+t[0-9]+)/' => array('c', 'n'),
            '/^ENSG/'                     => array('g', 'm'),
            '/^NC_(001807\.|012920\.).$/' => array('m'),
            '/^(N[CGTW]_[0-9]+\.[0-9]+$|LRG_[0-9]+$)/' => array('g'),
        ),
    ),
);





function lovd_arrayInsertAfter ($sKey, &$a, $sKeyToInsert, $ValueToInsert)
{
    // Insert $sKeyToInsert having $ValueToInsert,
    //  after entry $sKey in array $aOri.
    // Based on code by Brad Erickson (http://eosrei.net/comment/287).
    // MIT licensed code, compatible with GPL.
    if (array_key_exists($sKey, $a)) {
        $aNew = array();
        foreach ($a as $k => $value) {
            $aNew[$k] = $value;
            if ($k === $sKey) {
                $aNew[$sKeyToInsert] = $ValueToInsert;
            }
        }
        $a = $aNew;
        return true;
    }
    return false;
}





function lovd_cleanDirName ($s)
{
    // Cleans a given path by resolving a relative path.
    if (!is_string($s)) {
        // No input.
        return false;
    }

    // Clean up the pwd; remove '\' (some PHP versions under Windows seem to escape the slashes with backslashes???)
    $s = stripslashes($s);
    // Clean up the pwd; remove '//'
    $s = preg_replace('/\/+/', '/', $s);
    // Clean up the pwd; remove '/./'
    $s = preg_replace('/\/\.\//', '/', $s);
    // Clean up the pwd; remove '/dir/../'
    $s = preg_replace('/\/[^\/]+\/\.\.\//', '/', $s);

    if (preg_match('/\/(\.)?\.\//', $s)) {
        // Still not clean... Pff...
        $s = lovd_cleanDirName($s);
    }

    return $s;
}





function lovd_convertBytesToHRSize ($nValue)
{
    // This function takes integers and converts it to sizes like "128M".

    if (!is_int($nValue) && !ctype_digit($nValue)) {
        return false;
    }

    $aSizes = array(
        ' bytes', 'K', 'M', 'G', 'T', 'P',
    );
    $nKey = 0; // bytes.

    while ($nValue >= 1024 && $nKey < count($aSizes)) {
        $nValue /= 1024;
        $nKey ++;
    }

    // Precision makes no sense with three digits.
    if ($nValue >= 100 || !$nKey) {
        // Return an integer.
        return round($nValue) . $aSizes[$nKey];
    } else {
        return number_format($nValue, 1) . $aSizes[$nKey];
    }
}





function lovd_convertIniValueToBytes ($sValue)
{
    // This function takes output from PHP's ini_get() function like "128M" or
    // "256k" and converts it to an integer, measured in bytes.
    // Implementation taken from the example on php.net.
    // FIXME; Implement proper checks here? Regexp?

    $nValue = (int) $sValue;
    $sLast = strtolower(substr($sValue, -1));
    switch ($sLast) {
        case 'g':
            $nValue *= 1024;
        case 'm':
            $nValue *= 1024;
        case 'k':
            $nValue *= 1024;
    }

    return $nValue;
}





function lovd_convertSecondsToTime ($sValue, $nDecimals = 0, $bVerbose = false)
{
    // This function takes a number of seconds and converts it into whole
    // minutes, hours, days, months or years.
    // $nDecimals indicates the number of decimals to use in the returned value.
    // $bVerbose defines whether to use short notation (s, m, h, d, y) or long notation
    //   (seconds, minutes, hours, days, years).
    // FIXME; Implement proper checks here? Regexp?

    $nValue = (int) $sValue;
    if (ctype_digit((string) $sValue)) {
        $sValue .= 's';
    }
    $sLast = strtolower(substr($sValue, -1));
    $nDecimals = (int) $nDecimals;

    $aConversion =
        array(
            's' => array(60, 'm', 'second'),
            'm' => array(60, 'h', 'minute'),
            'h' => array(24, 'd', 'hour'),
            'd' => array(265, 'y', 'day'),
            'y' => array(100, 'c', 'year'),
            'c' => array(100, '', 'century'), // Above is not supported.
        );

    foreach ($aConversion as $sUnit => $aConvert) {
        list($nFactor, $sNextUnit) = $aConvert;
        if ($sLast == $sUnit && $nValue > $nFactor) {
            $nValue /= $nFactor;
            $sLast = $sNextUnit;
        }
    }

    $nValue = round($nValue, $nDecimals);
    if ($bVerbose) {
        // Make it "3 years" instead of "3y".
        return $nValue . ' ' . $aConversion[$sLast][2] . ($nValue == 1? '' : 's');
    } else {
        return $nValue . $sLast;
    }
}





function lovd_getInstallURL ($bFull = true)
{
    // Returns URL that can be used in URLs or redirects.
    // ROOT_PATH can be relative or absolute.
    return (!$bFull? '' : PROTOCOL . $_SERVER['HTTP_HOST']) .
        lovd_cleanDirName(substr(ROOT_PATH, 0, 1) == '/'? ROOT_PATH : dirname($_SERVER['SCRIPT_NAME']) . '/' . ROOT_PATH);
}





function lovd_getVariantInfo ($sVariant, $sTranscriptID = '', $bCheckHGVS = false)
{
    // Parses the variant, and returns the position fields (2 for genomic
    //  variants, 4 for cDNA variants) in an associative array. If no
    //  positions can be found, the function returns false.
    // $sVariant stores the HGVS description of the variant.
    // $sTranscriptID stores the internal ID or NCBI ID of the transcript,
    //  as needed to process 3' UTR variants (such as c.*10del).
    // $bCheckHGVS holds the boolean which, if set to true, will change
    //  the functionality to return either true or false, depending on
    //  whether the variant matches the syntax of HGVS nomenclature.
    // If $bCheckHGVS is set to false, and any HGVS syntax issues are found,
    //  this information will be added to the response array in the form
    //  of warnings (if not fatal) or errors (when the syntax issues are
    //  such that they make the variant ambiguous or implausible).
    global $_DB, $_LIBRARIES;

    static $aTranscriptOffsets = array();
    $aResponse = array(
        // This array will store all the information which we will
        //  return to the user later on in this function.
        'position_start' => 0,
        'position_end'   => 0,
        'type'           => '',
        'range'          => false,
        'warnings'       => array(),
        'errors'         => array(),
    );

    // Trim the variant and remove whitespaces.
    $nLength = strlen($sVariant);
    $sVariant = preg_replace('/\s+/', '', $sVariant);
    if (strlen($sVariant) != $nLength) {
        // Whitespace was removed. Warn.
        if ($bCheckHGVS) {
            return false;
        }
        $aResponse['warnings']['WWHITESPACE'] =
            'This variant description contains one or more whitespace characters (spaces, tabs, etc). Please remove these.';
    }


    // Match the reference sequence if one was given.
    $sReferenceSequence = '';
    if (lovd_variantHasRefSeq($sVariant)) {
        // The user seems to have written down a reference sequence.
        // Let's see if it matches the expected format.
        list($sReferenceSequence, $sVariant) = explode(':', $sVariant, 2);

        if (lovd_isValidRefSeq($sReferenceSequence)) {
            // Check if the reference sequence matches one of
            //  the possible formats.
            if ($sTranscriptID) {
                // A transcript ID has been passed to this function.
                // We should check if it matches the transcript in the DNA field.
                $sField = (substr($sReferenceSequence, 0, 3) == 'ENS'? 'id_ensembl' : 'id_ncbi');
                if (is_numeric($sTranscriptID)) {
                    $sRefSeqID = $_DB->q('SELECT `' . $sField . '` FROM ' . TABLE_TRANSCRIPTS . ' WHERE id = ?',
                        array($sTranscriptID))->fetchColumn();
                } elseif (is_array($sTranscriptID)) {
                    // LOVD+ sends us an array object instead of an ID, during conversion.
                    $sRefSeqID =
                        (isset($sTranscriptID['id_ncbi'])? $sTranscriptID['id_ncbi'] :
                            (isset($sTranscriptID['id'])? $sTranscriptID['id'] : ''));
                } else {
                    $sRefSeqID = $sTranscriptID;
                }

                if (preg_match('/\b' . preg_quote($sRefSeqID) . '\b/', $sReferenceSequence)) {
                    // The transcript given in the DNA description is also the
                    //  transcript that we're using in LOVD for this variant.
                    $aResponse['warnings']['WTRANSCRIPTFOUND'] =
                        'A transcript reference sequence has been found in the DNA description. Please remove it.';
                } elseif (strpos($sRefSeqID, '.') && preg_match('/\b' . preg_quote(strstr($sRefSeqID, '.', true)) . '\.[0-9]+\b/', $sReferenceSequence)) {
                    // The transcript given in the DNA description is also the
                    //  transcript that we're using in LOVD for this variant,
                    //  but the version number is different.
                    $aResponse['warnings']['WTRANSCRIPTVERSION'] =
                        'The transcript reference sequence found in the DNA description is a different version from the configured transcript.' .
                        ' Please adapt the DNA description to the configured transcript and then remove the reference sequence from the DNA field.';
                } else {
                    // This is an actual problem; the submitter used a different
                    //  refseq than the transcript configured in LOVD.
                    $aResponse['warnings']['WDIFFERENTREFSEQ'] =
                        'The reference sequence found in the DNA description does not match the configured transcript.' .
                        ' Please adapt the DNA description to the configured transcript and then remove the reference sequence from the DNA field.';
                }
            }

            $aPrefixesByRefSeq = lovd_getVariantPrefixesByRefSeq($sReferenceSequence);
            if (!in_array($sVariant[0], $aPrefixesByRefSeq)) {
                // Check whether the DNA type of the variant matches the DNA type of the reference sequence.
                if ($bCheckHGVS) {
                    return false;
                }
                $aResponse['errors']['EWRONGREFERENCE'] =
                    'The given reference sequence (' . $sReferenceSequence . ') does not match the DNA type (' . $sVariant[0] . ').' .
                    ' For variants on ' . $sReferenceSequence . ', please use the ' . implode('. or ', $aPrefixesByRefSeq) . '. prefix.';
                switch ($sVariant[0]) {
                    case 'c':
                    case 'n':
                        $aResponse['errors']['EWRONGREFERENCE'] .=
                            ' For ' . $sVariant[0] . '. variants, please use a ' . ($sVariant[0] == 'c'? '' : 'non-') . 'coding transcript reference sequence.';
                        break;
                    case 'g':
                    case 'm':
                        $aResponse['errors']['EWRONGREFERENCE'] .=
                            ' For ' . $sVariant[0] . '. variants, please use a ' . ($sVariant[0] == 'g'? 'genomic' : 'mitochondrial') . ' reference sequence.';
                        break;
                }

            } elseif (!preg_match('/^(N[CGTW]|LRG)/', $sReferenceSequence)
                && (preg_match('/[0-9]+[-+]([0-9]+|\?)/', $sVariant))) {
                // If a variant has intronic positions, it must have a
                //  reference that contains those positions.
                if ($bCheckHGVS) {
                    return false;
                }
                $aResponse['errors']['EWRONGREFERENCE'] =
                    'The variant is missing a genomic reference sequence required to verify the intronic positions.';
            }

        } else {
            // The user seems to have tried to add a reference sequence, but it
            //  was not formatted correctly. We will return errors or warnings accordingly.
            if ($bCheckHGVS) {
                return false;
            }
            // Check for missing version. We don't want to yet define another pattern.
            // Just check if it helps to add a version number.
            if (lovd_isValidRefSeq(preg_replace('/([0-9]{6})([()]|$)/', '$1.1$2', $sReferenceSequence))) {
                // OK, adding a .1 helped. So, version is missing.
                $aResponse['errors']['EREFERENCEFORMAT'] =
                    'The reference sequence ID is missing the required version number.' .
                    ' NCBI RefSeq and Ensembl IDs require version numbers when used in variant descriptions.';

            } elseif (preg_match('/^([NX][MR]_[0-9]{6,9}\.[0-9]+)\((N[CGTW]_[0-9]{6}\.[0-9]+)\)$/', $sReferenceSequence, $aRegs)) {
                $aResponse['warnings']['WREFERENCEFORMAT'] =
                    'The genomic and transcript reference sequence IDs have been swapped.' .
                    ' Please rewrite "' . $aRegs[0] . '" to "' . $aRegs[2] . '(' . $aRegs[1] . ')".';

            } elseif (preg_match('/([NX][CGMRTW])-?([0-9]+)/', $sReferenceSequence, $aRegs)) {
                // The user forgot the underscore or used a hyphen.
                $aResponse['warnings']['WREFERENCEFORMAT'] =
                    'NCBI reference sequence IDs require an underscore between the prefix and the numeric ID.' .
                    ' Please rewrite "' . $aRegs[0] . '" to "' . $aRegs[1] . '_' . $aRegs[2] . '".';

            } elseif (preg_match('/([NX][CGMRTW])_([0-9]{1,5})\.([0-9]+)/', $sReferenceSequence, $aRegs)) {
                // The user is using too few digits.
                $aResponse['warnings']['WREFERENCEFORMAT'] =
                    'NCBI reference sequence IDs require at least six digits.' .
                    ' Please rewrite "' . $aRegs[0] . '" to "' . $aRegs[1] . '_' . str_pad($aRegs[2], 6, '0', STR_PAD_LEFT) . '.' . $aRegs[3] . '".';

            } elseif (preg_match('/([NX][CGMRTW])_(0+)([0-9]{6})\.([0-9]+)/', $sReferenceSequence, $aRegs)) {
                // The user is using too many digits.
                // (in principle, this would also match NM_[0-9]{9}, but that is correct and wouldn't get here)
                $aResponse['warnings']['WREFERENCEFORMAT'] =
                    'NCBI reference sequence IDs allow no more than six or nine digits.' .
                    ' Please rewrite "' . $aRegs[0] . '" to "' . $aRegs[1] . '_' . $aRegs[3] . '.' . $aRegs[4] . '".';

            } elseif (preg_match('/([NX][MR])_(0+)([0-9]{9})\.([0-9]+)/', $sReferenceSequence, $aRegs)) {
                // The user is using too many digits.
                $aResponse['warnings']['WREFERENCEFORMAT'] =
                    'NCBI transcript reference sequence IDs allow no more than nine digits.' .
                    ' Please rewrite "' . $aRegs[0] . '" to "' . $aRegs[1] . '_' . $aRegs[3] . '.' . $aRegs[4] . '".';

            } elseif (preg_match('/(LRG)([0-9]+)/', $sReferenceSequence, $aRegs)) {
                // LRGs require underscores.
                $aResponse['warnings']['WREFERENCEFORMAT'] =
                    'LRG reference sequence IDs require an underscore between the prefix and the numeric ID.' .
                    ' Please rewrite "' . $aRegs[0] . '" to "' . $aRegs[1] . '_' . $aRegs[2] . '".';

            } elseif (preg_match('/(ENS[GT])[_-]([0-9]+)/', $sReferenceSequence, $aRegs)) {
                // Ensembl IDs disallow underscores.
                $aResponse['warnings']['WREFERENCEFORMAT'] =
                    'Ensembl reference sequence IDs don\'t allow a divider between the prefix and the numeric ID.' .
                    ' Please rewrite "' . $aRegs[0] . '" to "' . $aRegs[1] . $aRegs[2] . '".';

            } elseif (preg_match('/(ENS[GT])([0-9]{1,10})\.([0-9]+)/', $sReferenceSequence, $aRegs)) {
                // The user is using too few digits.
                $aResponse['warnings']['WREFERENCEFORMAT'] =
                    'Ensembl reference sequence IDs require 11 digits.' .
                    ' Please rewrite "' . $aRegs[0] . '" to "' . $aRegs[1] . str_pad($aRegs[2], 11, '0', STR_PAD_LEFT) . '.' . $aRegs[3] . '".';

            } else {
                $aResponse['errors']['EREFERENCEFORMAT'] =
                    'The reference sequence could not be recognised.' .
                    ' Supported reference sequence IDs are from NCBI Refseq, Ensembl, and LRG.';
            }
        }
    }
    // Preliminary determination of a range. This can have false positives, like g.1delins100_200,
    //  but once we have split off the suffix, we'll try again.
    $aResponse['range'] = (strpos($sVariant, '_') !== false);


    // All information of interest will be placed into an associative array.
    // Note: For now, the regular expression only works for c., g., n., and m. variants.
    preg_match(
        '/^([cgmn])\.' .                         // 1.  Prefix.

        '([?=]$|(' .                             // 2. '?' or '=' (e.g. c.=).
        '(\({1,2})?' .              // 4=(       // 4.  Opening parentheses.
        '([-*]?[0-9]+|\?)' .                     // 5.  (Earliest) start position.
        '([-+]([0-9]+|\?))?' .                   // 6.  (Earliest) intronic start position.
        '(?(4)(_' .
            '([-*]?[0-9]+|\?)' .                 // 9. Latest start position.
            '([-+]([0-9]+|\?))?' .               // 10. Latest intronic start position.
        '\))?)' .

        '(_' .
            '(\()?' .               // 13=(
            '([-*]?[0-9]+|\?)' .                 // 14. (Earliest) end position.
            '([-+]([0-9]+|\?))?' .               // 15. (Earliest) intronic end position.
            '(?(13)_' .
                '([-*]?[0-9]+|\?)' .             // 17. Latest end position.
                '([-+]([0-9]+|\?))?' .           // 18. Latest intronic end position.
        '\)))?' .

        '((?:[ACGTU]+|\.)>(?:[ACGTRYSWKMBDHUVN]+|\.)' .      //  | (substitution)
        '|([ACGTU]+\[[0-9]+])+' .                            //  | (repeat sequence)
        '|[ACGTU]*=(\/{1,2}[ACGTU]*>[ACGTRYSWKMBDHUVN]+)?' . //  | (wild types, mosaics, or chimerics)
        '|ins|dup|con|delins|del|inv|sup|\?' .               //  V
        '|\|(gom|lom|met=|.+))' .                            // 20. Type of variant.

        '(.*)))/i',                                          // 24. Suffix.

        $sVariant, $aMatches);

    $aVariant = (!isset($aMatches[0])? array() : array(
        // All information of the variant is stored into this associative array.
        // Notes: -If the information was not found, the positions are cast to 0
        //         and the variant type, parentheses, and suffix, are cast to an
        //         empty string. (e.g. c.?)
        //        -If an intronic position is given a question mark, its position
        //         is cast to 1 in case of +? and -1 for -?. (e.g. c.10-?del)
        'complete'                => $aMatches[0],
        'prefix'                  => (!isset($aMatches[1])?  '' : strtolower($aMatches[1])),
        'positions'               => (!isset($aMatches[3])?  '' : $aMatches[3]),
        'starting_parentheses'    => (!isset($aMatches[4])?  '' : $aMatches[4]), // The parentheses are given to make additional checks later on in the function easier.
        'earliest_start'          => (!isset($aMatches[5])?   0 : $aMatches[5]), // These are not cast to integers, since they can still hold an informative '*'.
        'earliest_intronic_start' => (!isset($aMatches[6])?   0 : (int) str_replace('?', '1', $aMatches[6])),
        'latest_start'            => (!isset($aMatches[9])?   0 : $aMatches[9]),
        'latest_intronic_start'   => (!isset($aMatches[10])?  0 : (int) str_replace('?', '1', $aMatches[10])),
        'earliest_end'            => (!isset($aMatches[14])?  0 : $aMatches[14]),
        'earliest_intronic_end'   => (!isset($aMatches[15])?  0 : (int) str_replace('?', '1', $aMatches[15])),
        'latest_end'              => (!isset($aMatches[17])?  0 : $aMatches[17]),
        'latest_intronic_end'     => (!isset($aMatches[18])?  0 : (int) str_replace('?', '1', $aMatches[18])),
        'type'                    => (!isset($aMatches[20])? '' :
            (preg_match('/(^[ACTG]*=|[>\[])/i', $aMatches[20])? strtoupper($aMatches[20]) : strtolower($aMatches[20]))),
        'suffix'                  => (!isset($aMatches[24])? '' : $aMatches[24]),
    ));

    // Doing this here, to show we use $aMatches and that this code should be updated if the regexp is updated.
    // Check for "0" in positions. We need to do this on $aMatches, because no type casting has taken place there.
    $aZeroValues = array('0', '-0', '+0');
    foreach (array(5, 6, 9, 10, 14, 15, 17, 18) as $i) {
        if (isset($aMatches[$i])) {
            if (in_array($aMatches[$i], $aZeroValues)) {
                $aResponse['errors']['EPOSITIONFORMAT'] =
                    'This variant description contains an invalid position: "0". Please verify your description and try again.';
                break;
            } else {
                foreach ($aZeroValues as $sZeroValue) {
                    if (substr($aMatches[$i], 0, strlen($sZeroValue)) === $sZeroValue) {
                        // Stack warnings, so all problems are highlighted.
                        $aResponse['warnings']['WPOSITIONFORMAT'] =
                            (isset($aResponse['warnings']['WPOSITIONFORMAT'])?
                                $aResponse['warnings']['WPOSITIONFORMAT'] : 'Variant positions should not be prefixed by a 0.') .
                            ' Please rewrite "' . $aMatches[$i] . '" to "' .
                            ($sZeroValue[0] == '+'? '+' : '') . (int) $aMatches[$i] . '".';
                    }
                }
            }
        }
    }
    if ($bCheckHGVS
        && (isset($aResponse['errors']['EPOSITIONFORMAT']) || isset($aResponse['warnings']['WPOSITIONFORMAT']))) {
        return false;
    }

    if (!isset($aVariant['complete']) || $aVariant['complete'] != $sVariant) {
        // If the complete match is not set or does not equal the given variant,
        //  then the variant is not HGVS-compliant, and we cannot extract any
        //  information.
        if ($bCheckHGVS) {
            return false;
        }

        // Before we just return false when people request more information;
        //  check for some currently unsupported syntax that we do recognize.

        // 1) "Or" syntax using a ^.
        if (strpos($sVariant, '^') !== false) {
            // This is a stub, but it's better than nothing.
            // We replace the ^ and everything that follows with a =, and
            //  process the variant like this. Then we overwrite the type, and
            //  we return what we have.
            // Note that variants like g.123A>C^124G>C don't reach us; they are
            //  matched and caught elsewhere.
            $aVariant = lovd_getVariantInfo(strstr($sVariant, '^', true) . '=');
            if ($aVariant !== false) {
                $aVariant['type'] = '^';
                // We have to throw an ENOTSUPPORTED, although we're returning
                //  positions. We currently cannot claim these are HGVS or not,
                //  so an WNOTSUPPORTED isn't appropriate.
                $aVariant['errors']['ENOTSUPPORTED'] =
                    'Currently, variant descriptions using "^" are not yet supported.' .
                    ' This does not necessarily mean the description is not valid HGVS.';
                return $aVariant;
            }
        }

        // 2) Combined variants that should be split.
        if (preg_match('/\[.+;.+\]/', $sVariant)) {
            // Although insertions can have this pattern as well, they don't end
            //  up here; so we're left with combined variants.
            // Try to send in the first one.
            $aVariant = lovd_getVariantInfo(
                str_replace(array('[', ']'), '', strstr($sVariant, ';', true)));
            if ($aVariant !== false) {
                $aVariant['type'] = ';';
                // We have to throw an ENOTSUPPORTED, although we're returning
                //  positions. We currently cannot claim these are HGVS or not,
                //  so an WNOTSUPPORTED isn't appropriate.
                $aVariant['errors']['ENOTSUPPORTED'] =
                    'Currently, variant descriptions of combined variants are not yet supported.' .
                    ' This does not necessarily mean the description is not valid HGVS.' .
                    ' Please submit your variants separately.';
                // Some descriptions throw some warnings.
                $aVariant['warnings'] = array();
                return $aVariant;
            }
        }

        // 3) qter/pter/cen-based positions, translocations, fusions.
        foreach (array('qter', 'pter', 'cen', '::') as $sUnsupported) {
            if (strpos($sVariant, $sUnsupported)) {
                $aResponse['errors']['ENOTSUPPORTED'] =
                    'Currently, variant descriptions using "' . $sUnsupported . '" are not yet supported.' .
                    ' This does not necessarily mean the description is not valid HGVS.';

                // We do have one requirement; chromosomal reference sequence.
                if ($sReferenceSequence && substr($sReferenceSequence, 0, 2) != 'NC') {
                    $aResponse['errors']['EWRONGREFERENCE'] =
                        'The variant is missing a chromosomal reference sequence required for pter, cen, or qter positions.';
                }
                return $aResponse;
            }
        }

        // 4) Methylation-related variants without a pipe.
        // We'll check for methylation-related variants here, that sometimes
        //  lack a pipe character. Since we currently can't parse positions
        //  anymore, we'll have to throw an error. If we can identify the user's
        //  mistake, we can ask the user or lovd_fixHGVS() to correct it.
        if (preg_match('/[0-9](gom|lom|met=|bsrC?)$/', $sVariant, $aRegs)) {
            // Variant ends in a methylation-related suffix, but without a pipe.
            // We can guess here that this can be fixed.
            $aResponse['errors']['EPIPEMISSING'] =
                'Please place a "|" between the positions and the variant type (' . $aRegs[1] . ').';
            return $aResponse;
        }
        return false;
    }

    // Clean position string. We'll use it for reporting later on.
    if ($aVariant['positions']) {
        $aVariant['positions'] = strstr($aVariant['positions'], $aVariant['type'], true);
        // And now with more precision.
        $aResponse['range'] = (strpos($aVariant['positions'], '_') !== false);
    }

    // Check the variant's case.
    // First, handle an annoying exception.
    if (substr($aVariant['type'], -4) == 'bsrc') {
        $aVariant['type'] = str_replace('bsrc', 'bsrC', $aVariant['type']);
    }
    // Now check.
    if ((isset($aMatches[1]) && $aVariant['prefix'] != $aMatches[1])
        || (isset($aMatches[20]) && $aVariant['type'] != $aMatches[20])) {
        // There's a case problem.
        if ($bCheckHGVS) {
            return false;
        }
        $aResponse['warnings']['WWRONGCASE'] =
            'This is not a valid HGVS description, due to characters being in the wrong case.' .
            ' Please check the use of upper- and lowercase characters.';
    }

    // Storing the variant type.
    if (!$aVariant['type']) {
        // If no type was matched, we can be sure that the variant is either
        //  a full wild type or a full unknown variant; so either g.= or g.? .
        // In this case, we do not need to go over all tests, since there is
        //  simply a lot less information to test. We will do a few tests
        //  and add all necessary information, and then return our response
        //  right away.
        if (in_array($aVariant['prefix'], array('c', 'n'))) {
            // Initialize intronic positions, set to zero for .? or .= variants.
            $aResponse['position_end_intron'] = 0;
            $aResponse['position_start_intron'] = 0;
        }
        // For unknown variants (c.?), the type is set to NULL.
        $aResponse['type'] = (substr($aVariant['complete'], -1) == '='? '=' : NULL);

        if ($aResponse['type'] == '=') {
            // HGVS requires unchanged sequence ("=") to always give positions.
            $aResponse['errors']['EMISSINGPOSITIONS'] =
                'When using "=", please provide the position(s) that are unchanged.';
            return ($bCheckHGVS? false : $aResponse);
        }
        return ($bCheckHGVS? true : $aResponse);

    } elseif ($aVariant['type'][0] == '|') {
        // There might be variant types which some users would like to see
        //  being added to HGVS, but are not yet, e.g. the "bsr" and "per" types.
        // We want lovd_getVariantInfo() to still make an effort to read these
        //  variants, so we can extract as much information from them as
        //  possible (such as the positions and other warnings that might
        //  have occurred). This is an error, not a warning, since it means
        //  that the variant is theoretically incorrect and not fixable.
        if (in_array($aVariant['type'], array('|gom', '|lom', '|met='))) {
            $aResponse['warnings']['WNOTSUPPORTED'] =
                'Although this variant is a valid HGVS description, this syntax is currently not supported for mapping and validation.';
        } else {
            if ($bCheckHGVS) {
                return false;
            }
            $aResponse['errors']['ENOTSUPPORTED'] = 'This is not a valid HGVS description, please verify your input after "|".';
        }
        $aResponse['type'] = 'met';

    } elseif (strpos($aVariant['type'], '=') !== false) {
        if (substr_count($sVariant, '/') == 1) {
            $aResponse['type'] = 'mosaic';
        } elseif (substr_count($sVariant, '/') == 2) {
            $aResponse['type'] = 'chimeric';
        } else {
            $aResponse['type'] = '=';
        }

    } elseif (strpos($aVariant['type'], '>')) {
        $aResponse['type'] = 'subst';

    } elseif ($aVariant['type'] == 'con') {
        if ($bCheckHGVS) {
            return false;
        }
        $aResponse['type'] = 'delins';
        $aResponse['warnings']['WWRONGTYPE'] =
            'A conversion should be described as a deletion-insertion. Please rewrite "con" to "delins".';

    } elseif (substr($aVariant['type'], -1) == ']') {
        $aResponse['type'] = 'repeat';
        $aResponse['warnings']['WNOTSUPPORTED'] =
            'Although this variant is a valid HGVS description, this syntax is currently not supported for mapping and validation.';

    } elseif ($aVariant['type'] == '?') {
        $aResponse['type'] = NULL;

    } else {
        $aResponse['type'] = $aVariant['type'];
    }



    // If given, check if we already know this transcript.
    if (is_array($sTranscriptID)) {
        // LOVD+ sends us an array object instead of an ID, during conversion.
        $nID =
            (isset($sTranscriptID['id_ncbi'])? $sTranscriptID['id_ncbi'] :
                (isset($sTranscriptID['id'])? $sTranscriptID['id'] :
                    @implode($sTranscriptID)));
        if (!isset($aTranscriptOffsets[$nID])) {
            $aTranscriptOffsets[$nID] =
                (isset($sTranscriptID['position_c_cds_end'])? $sTranscriptID['position_c_cds_end'] : 0);
        }
        $sTranscriptID = $nID;

    } elseif ($sTranscriptID === false || !$_DB) {
        // If the transcript ID is passed as false, we are asked to ignore not
        //  having the transcript. Pick some random number, high enough to not
        //  be smaller than position_start if that's not in the UTR.
        // Also, we take this default when we're unit testing and thus don't
        //  have a database connection.
        $aTranscriptOffsets[$sTranscriptID] = 1000000;

    } elseif ($sTranscriptID && !isset($aTranscriptOffsets[$sTranscriptID])) {
        $aTranscriptOffsets[$sTranscriptID] = $_DB->q('SELECT position_c_cds_end FROM ' . TABLE_TRANSCRIPTS . ' WHERE (id = ? OR id_ncbi = ?)',
            array($sTranscriptID, $sTranscriptID))->fetchColumn();
        if (!$aTranscriptOffsets[$sTranscriptID]) {
            // The transcript is not configured correctly. We will treat this transcript as unknown.
            $sTranscriptID = '';
        }
    }



    // Converting 3' UTR notations ('*' in the position fields) to normal notations,
    //  checking for '?', and disallowing negative positions for prefixes other than c.
    foreach (array('earliest_start', 'latest_start', 'earliest_end', 'latest_end') as $sPosition) {
        if (substr($aVariant[$sPosition], 0, 1) == '*') {
            if ($aVariant['prefix'] != 'c') {
                //  If the '*' is given, the DNA must be of type coding (prefix = c).
                if ($bCheckHGVS) {
                    return false;
                }
                $aResponse['errors']['EFALSEUTR'] =
                    'Only coding transcripts (c. prefix) have a UTR region. Therefore, position "' . $aVariant[$sPosition] .
                    '" which describes a position in the 3\' UTR, is invalid when using the "' . $aVariant['prefix'] . '" prefix.';
                return $aResponse;
            }
            if ($sTranscriptID === '') {
                // If the '*' symbol is given, we must also have a transcript.
                // This mistake does not lie with the user, but with LOVD as the function should not have
                //  been called with a transcriptID or transcriptID=false.
                return false;
            }
            // We add the length of the transcript to the position if a '*' has been found.
            $aVariant[$sPosition] = substr($aVariant[$sPosition], 1) + $aTranscriptOffsets[$sTranscriptID];

        } elseif ($aVariant[$sPosition] == '?') {
            $aResponse['messages']['IUNCERTAINPOSITIONS'] = 'This variant description contains uncertain positions.';

        } else {
            // When no '*' or '?' is found, we can safely cast the position to integer.
            $aVariant[$sPosition] = (int) $aVariant[$sPosition];

            if ($aVariant[$sPosition] < 0 && $aVariant['prefix'] != 'c') {
                if ($bCheckHGVS) {
                    return false;
                }
                $aResponse['errors']['EFALSEUTR'] =
                    'Only coding transcripts (c. prefix) have a UTR region. Therefore, position "' . $aVariant[$sPosition] .
                    '" which describes a position in the 5\' UTR, is invalid when using the "' . $aVariant['prefix'] . '" prefix.';
                return $aResponse;
            }
        }
    }



    // Making sure that all early positions are bigger than the later positions
    //  and that all start positions are bigger than the end positions.
    foreach (
        array(
             array('earliest_start',   'latest_start'),
             array('earliest_end',     'latest_end'),
             array('earliest_start',   'earliest_end'),
             array('latest_start',     'earliest_end'),
             array('latest_start',     'latest_end')
        ) as $aFirstAndLast) {

        if ($aVariant[$aFirstAndLast[0]] && $aVariant[$aFirstAndLast[1]]
            && $aVariant[$aFirstAndLast[0]] != '?'
            && $aVariant[$aFirstAndLast[1]] != '?') {
            // We only check the positions if neither are unknown.
            list($sFirst, $sLast) = $aFirstAndLast;
            $sIntronicFirst = str_replace('_', '_intronic_', $sFirst);
            $sIntronicLast  = str_replace('_', '_intronic_', $sLast);

            if ($aVariant[$sFirst] > $aVariant[$sLast]) {
                // Switch positions.
                list($aVariant[$sFirst], $aVariant[$sIntronicFirst], $aVariant[$sLast], $aVariant[$sIntronicLast]) =
                    array($aVariant[$sLast], $aVariant[$sIntronicLast], $aVariant[$sFirst], $aVariant[$sIntronicFirst]);
                $sPositionWarning = 'The positions are not given in the correct order.';

            } elseif ($aVariant[$sFirst] == $aVariant[$sLast]) {
                // Positions are the same. Now compare intronic positions.
                // Intronic position fields are always defined, so we can safely
                //  compare them.
                if ($aVariant[$sIntronicFirst] > $aVariant[$sIntronicLast]) {
                    list($aVariant[$sIntronicFirst], $aVariant[$sIntronicLast]) = array($aVariant[$sIntronicLast], $aVariant[$sIntronicFirst]);
                    $sPositionWarning = 'The intronic positions are not given in the correct order.';

                } elseif ($aVariant[$sIntronicFirst] == $aVariant[$sIntronicLast]
                    && !(
                        $aVariant['earliest_start'] && $aVariant['earliest_start']
                        && $aVariant['earliest_start'] && $aVariant['earliest_start']
                        && $sFirst == 'latest_start' && $sLast == 'earliest_end'
                    )) {
                    // The intronic offset is also the same (or both 0).
                    // There is an exception; variants with four positions can
                    //  have the same middle position. This should be allowed.
                    if ($bCheckHGVS) {
                        return false;
                    }
                    $sPositionWarning = 'This variant description contains two positions that are the same.';
                    if ($aVariant['type'] == 'ins') {
                        // Insertions must receive the two neighboring positions
                        //  between which they have taken place.
                        // If both positions are the same, this makes the variant
                        //  unclear to the extent that it cannot be interpreted.
                        $aResponse['errors']['EPOSITIONFORMAT'] = $sPositionWarning .
                            ' Please verify your description and try again.';
                        break;
                    }
                }
            }

            if (isset($sPositionWarning)) {
                if ($bCheckHGVS) {
                    return false;
                }
                // NOTE: This overwrites any previous warnings. Both warnings generated in the beginning (positions that
                //  are, or are prefixed with, 0) and warnings generated in the code directly above.
                $aResponse['warnings']['WPOSITIONFORMAT'] = $sPositionWarning .
                    ' Please verify your description and try again.';
            }
        }
    }



    // Storing the positions.
    // After discussing the issue, it is decided to use to inner positions in cases where the positions are
    //  unknown. This means that e.g. c.(1_2)_(5_6)del will be returned as having a position_start of 2, and
    //  a position_end of 5. However, if we find a variant such as c.(1_?)_(?_6)del, we will save the outer
    //  positions (so a position_start of 1 and a position_end of 6).
    // Remember: When there are no parentheses, only earliest_start and earliest_end are set.
    //           Not having an earliest_end, means there was only one position set or one range with parentheses.
    //           Having one range with parentheses, sets the earliest and latest start positions.
    $aResponse['position_start'] =
        (!$aVariant['latest_start'] || $aVariant['latest_start'] == '?' || !$aVariant['earliest_end']?
            $aVariant['earliest_start'] : $aVariant['latest_start']);

    if (!$aVariant['earliest_end']) {
        if ($aVariant['latest_start']) {
            // Not having an end, but having a latest start happens for variants like c.(100_200)del(10).
            $aResponse['position_end'] = $aVariant['latest_start'];
        } else {
            // Single-position variants.
            $aResponse['position_end'] = $aResponse['position_start'];
        }
    } elseif ($aVariant['earliest_end'] != '?' || !$aVariant['latest_end']) {
        // Earliest end is not unknown, or simply the only choice we have.
        $aResponse['position_end'] = $aVariant['earliest_end'];
    } else {
        $aResponse['position_end'] = $aVariant['latest_end'];
    }

    if (in_array($aVariant['prefix'], array('n', 'c'))) {
        $aResponse['position_start_intron'] = ($aVariant['latest_start']? $aVariant['latest_intronic_start'] : $aVariant['earliest_intronic_start']);
        $aResponse['position_end_intron']   = ($aVariant['earliest_end']? $aVariant['earliest_intronic_end'] : $aResponse['position_start_intron']);
    }

    if (!$aVariant['earliest_end'] && $aVariant['latest_start']) {
        // We now know we are dealing with a case such as g.(1_5)ins. This means
        //  that the positions are uncertain, but somewhere within the range as
        //  given within the parentheses. We add a message to make sure users
        //  know our interpretation and can make sure they meant it as such.
        // Note that IPOSITIONRANGE, IUNCERTAINPOSITIONS, and IUNCERTAINRANGE all send
        //  the same message to the user about uncertain positions. However, internally,
        //  this notice is used to determine whether the variant needs a suffix
        //  because the variant's position is a single, uncertain range.
        $aResponse['messages']['IPOSITIONRANGE'] = 'This variant description contains uncertain positions.';

        if (in_array($aVariant['prefix'], array('n', 'c'))) {
            $aResponse['position_start_intron'] = $aVariant['earliest_intronic_start'];
        }

    } elseif ($aVariant['earliest_end']
        && ($aVariant['latest_start'] || $aVariant['latest_end'])) {
        // Another class of unknown positions;
        // g.(1_5)_(10_15) OR g.5_(10_15) OR g.(1_5)_10.
        // We'll store the inner positions, but it's good to know that there is
        //  uncertainty.
        $aResponse['messages']['IUNCERTAINRANGE'] = 'This variant description contains uncertain positions.';
    }





    // Now check the syntax of the variant in detail.

    // Making sure intronic positions are only given for variants which can hold them.
    if (($aVariant['earliest_intronic_start'] || $aVariant['latest_intronic_start']
        || $aVariant['earliest_intronic_end'] || $aVariant['latest_intronic_end'])
        && !in_array($aVariant['prefix'], array('c', 'n'))) {
        if ($bCheckHGVS) {
            return false;
        }
        $aResponse['errors']['EFALSEINTRONIC'] =
            'Only transcripts (c. or n. prefixes) have introns.' .
            ' Therefore, this variant description with a position in an intron' .
            ' is invalid when using the "' . $aVariant['prefix'] . '" prefix.';
        if (strpos($sVariant, '-') && !strpos($sVariant, '_')) {
            $aResponse['errors']['EFALSEINTRONIC'] .=
                ' Did you perhaps try to indicate a range?' .
                ' If so, please use an underscore (_) to indicate a range.';
        }
        // Before we return this, also add the intronic positions. This'll
        //  allow us to make some guesstimate on whether or not this may
        //  have been a typo.
        $aResponse['position_start_intron'] = ($aVariant['latest_start']? $aVariant['latest_intronic_start'] : $aVariant['earliest_intronic_start']);
        $aResponse['position_end_intron']   = ($aVariant['earliest_end']? $aVariant['earliest_intronic_end'] : $aResponse['position_start_intron']);
        if (!$aVariant['earliest_end'] && $aVariant['latest_start']) {
            $aResponse['position_start_intron'] = $aVariant['earliest_intronic_start'];
        }
        return $aResponse;
    }

    // Making sure wild type descriptions don't provide nucleotides
    // (e.g. c.123A=, which should be c.123=).
    if ($aResponse['type'] == '=' && preg_match('/[ACGT]/', $sVariant)) {
        if ($bCheckHGVS) {
            return false;
        }
        $aResponse['warnings']['WBASESGIVEN'] = 'When using "=", please remove the original sequence before the "=".';
    }

    // Making sure no redundant '?'s are given as positions.
    if (strpos($aVariant['positions'], '?') !== false) {
        // Let's try to keep this simple. There's so many combinations,
        //  why not just work on strings?
        $sFixedPosition = str_replace(
            array(
                '(?_?)',
                '_?)_(?_',
                '?_?',
                '?)_?',
                '?_(?',
            ),
            array(
                '?',
                '_',
                '?',
                '?)',
                '(?',
            ),
            $aVariant['positions']
        );
        // Exception; ?_? should be allowed for ins variants (g.?_?ins[...]).
        if ($sFixedPosition == '?' && $aVariant['type'] == 'ins') {
            $sFixedPosition = '?_?';
        }
        if ($aVariant['positions'] != $sFixedPosition) {
            $sQuestionMarkWarning =
                'Please rewrite the positions ' . $aVariant['positions'] .
                ' to ' . $sFixedPosition . '.';

            if ($bCheckHGVS) {
                return false;
            }
            $aResponse['warnings']['WTOOMUCHUNKNOWN'] =
                'This variant description contains redundant question marks. ' .
                $sQuestionMarkWarning;
        }
    }



    // Checking all type-specific format requirements.
    if ($aVariant['type'] == 'delins' && strlen($aVariant['suffix']) == 1
        && !$aVariant['earliest_end'] && lovd_getVariantLength($aResponse) == 1) {
        // If an insertion/deletion deletes one base and replaces it by one, it
        //  should be called and formatted as a substitution.
        if ($bCheckHGVS) {
            return false;
        }
        $aResponse['warnings']['WWRONGTYPE'] =
            'A deletion-insertion of one base to one base should be described as a substitution.';

    } elseif ($aVariant['type'] == 'ins') {
        if (!($aVariant['earliest_start'] == '?' || $aVariant['latest_start'] || $aVariant['earliest_end'])) {
            // An insertion must always hold two positions: so it must have an earliest end
            // (c.1_2insA) or a latest start (c.(1_5)insA). That is: except if the variant
            // was given as c.?insA.
            if ($bCheckHGVS) {
                return false;
            }
            $aResponse['errors']['EPOSITIONMISSING'] =
                'An insertion must be provided with the two positions between which the insertion has taken place.';

        } elseif ($aVariant['latest_end'] || ($aVariant['latest_start'] && $aVariant['earliest_end'])) {
            // An insertion should not get more than two positions: so it should not
            //  have a latest end (c.1_(2_5)insA) or a latest start and earliest end
            //  (c.(1_5)_6insA.
            if ($bCheckHGVS) {
                return false;
            }
            $aResponse['errors']['EPOSITIONFORMAT'] = 'Insertions should not be given more than two positions.';

        } elseif ($aVariant['earliest_start'] && $aVariant['earliest_end']
            && $aVariant['earliest_start'] != '?' && $aVariant['earliest_end'] != '?') {
            // An insertion must always get two positions which are next to each other,
            //  since the inserted nucleotides will be placed in the middle of those.
            // Calculate the length of the variant properly, including intronic positions.
            $nLength = lovd_getVariantLength($aResponse);
            if (!$nLength || $nLength > 2) {
                if ($bCheckHGVS) {
                    return false;
                }
                $aResponse['errors']['EPOSITIONFORMAT'] =
                    'An insertion must have taken place between two neighboring positions. ' .
                    'If the exact location is unknown, please indicate this by placing parentheses around the positions.';
            }

        } elseif (isset($aResponse['messages']['IPOSITIONRANGE'])
            && $aVariant['earliest_start'] != '?' && $aVariant['latest_start'] != '?') {
            // If the exact location of an insertion is unknown, this can be indicated
            //  by placing the positions in the range-format (e.g. c.(1_10)insA). In this
            //  case, the two positions should not be neighbours, since that would imply that
            //  the position is certain.
            // Calculate the length of the variant properly, including intronic positions.
            $nLength = lovd_getVariantLength($aResponse);
            if ($nLength == 2) {
                if ($bCheckHGVS) {
                    return false;
                }
                $aResponse['errors']['EPOSITIONFORMAT'] =
                    'The two positions do not indicate a range longer than two bases.' .
                    ' Please remove the parentheses if the positions are certain.';
            }
        }

    } elseif ($aVariant['type'] == 'inv') {
        if (!isset($aResponse['messages']['IUNCERTAINPOSITIONS'])
            && !($aVariant['latest_start'] && $aVariant['earliest_end'])
            && lovd_getVariantLength($aResponse) == 1) {
            // An inversion must always have a length of more than one, unless
            //  an uncertain range has been provided; then the calculated length
            //  could be one while in reality, it's unknown. The exact
            //  combination of a latest start and an earliest end is therefore
            //  excluded; these are g.(A_B)_(C_D)inv variants.
            if ($bCheckHGVS) {
                return false;
            }
            $aResponse['errors']['EPOSITIONFORMAT'] =
                'Inversions require a length of at least two bases.';

        } elseif (isset($aResponse['messages']['IPOSITIONRANGE'])
            && !isset($aResponse['messages']['IUNCERTAINPOSITIONS'])
            && lovd_getVariantLength($aResponse) == 2) {
            // If the exact location of an inversion is unknown, this can be
            //  indicated by placing the positions in the range-format (e.g.
            //  c.(1_10)inv). In this case, the two positions should not be
            //  neighbours, since that would imply that the position is certain.
            if ($bCheckHGVS) {
                return false;
            }
            $aResponse['errors']['EPOSITIONFORMAT'] =
                'The two positions do not indicate a range longer than two bases.' .
                ' Please remove the parentheses if the positions are certain.';
        }

    } elseif ($aResponse['type'] == 'subst') {
        $aSubstitution = explode('>', $aVariant['type']);
        if ($aSubstitution[0] == '.' && $aSubstitution[1] == '.') {
            if ($bCheckHGVS) {
                return false;
            }
            $aResponse['errors']['EWRONGTYPE'] =
                'This substitution does not seem to contain any data. Please provide bases that were replaced.';

        } elseif ($aSubstitution[0] == '.') {
            if ($bCheckHGVS) {
                return false;
            }
            $aResponse['errors']['EWRONGTYPE'] =
                'A substitution should be a change of one base to one base. Did you mean to describe an insertion?';

        } elseif ($aSubstitution[0] == $aSubstitution[1]) {
            if ($bCheckHGVS) {
                return false;
            }
            $aResponse['warnings']['WWRONGTYPE'] =
                'A substitution should be a change of one base to one base. Did you mean to describe an unchanged ' .
                ($aResponse['range']? 'range' : 'position') . '?';

        } elseif ($aSubstitution[1] == '.') {
            if ($bCheckHGVS) {
                return false;
            }
            $aResponse['warnings']['WWRONGTYPE'] =
                'A substitution should be a change of one base to one base. Did you mean to describe a deletion?';

        } elseif (strlen($aSubstitution[0]) > 1 || strlen($aSubstitution[1]) > 1) {
            // A substitution should be a change of one base to one base. If this
            //  is not the case, we will let the user know that it should have been
            //  a delins.
            if ($bCheckHGVS) {
                return false;
            }
            $aResponse['warnings']['WWRONGTYPE'] =
                'A substitution should be a change of one base to one base. Did you mean to describe a deletion-insertion?';
        }
        if ($aVariant['earliest_end']) {
            // As substitutions are always a one-base change, they should
            //  only receive one positions (so the end position should be empty).
            if ($bCheckHGVS) {
                return false;
            }
            if ($aVariant['earliest_start'] != $aVariant['earliest_end']) {
                // If the two positions are not the same, the variant is not fixable.
                $aResponse['errors']['ETOOMANYPOSITIONS'] =
                    'Too many positions are given; a substitution is used to only indicate single-base changes and therefore should have only one position.';
            }
        }
        if (isset($aResponse['messages']['IPOSITIONRANGE'])) {
            // VV won't support this... although we'll allow c.(100_101)A>G.
            $aResponse['warnings']['WNOTSUPPORTED'] =
                'Although this variant is a valid HGVS description, this syntax is currently not supported for mapping and validation.';
        }

    } elseif ($aResponse['type'] == 'repeat' && $aVariant['prefix'] == 'c') {
        foreach (explode('[', $aVariant['type']) as $sRepeat) {
            if (ctype_alpha($sRepeat) && strlen($sRepeat) % 3) {
                // Repeat variants on coding DNA should always have
                //  a length of a multiple of three bases.
                $aResponse['warnings']['WINVALIDREPEATLENGTH'] =
                    'A repeat sequence of coding DNA should always have a length of (a multiple of) 3.';
                if ($bCheckHGVS) {
                    return false;
                }
                break;
            }
        }
    }



    // Making sure the parentheses are placed correctly, and are removed from the suffix when they do not belong to it.
    if (substr_count($aVariant['complete'], '(') != substr_count($aVariant['complete'], ')')) {
        // If there are more opening parentheses than there are parentheses closed (or vice versa),
        //  the variant is not HGVS.
        if ($bCheckHGVS) {
            return false;
        }
        $aResponse['warnings']['WUNBALANCEDPARENTHESES'] = 'The variant description contains unbalanced parentheses.';
    }

    if (substr_count($aVariant['suffix'], '(') < substr_count($aVariant['suffix'], ')')) {
        // The suffix of variant c.(1_2ins(50)) is saved as (50)). We want to remove all parentheses
        //  which are not part of the actual suffix, and that is what we do here.
        $aVariant['suffix'] = substr($aVariant['suffix'], 0, -1);
    }



    // Finding out if the suffix is appropriately placed and
    //  is formatted as it should.
    if (!$aVariant['suffix']
        && (in_array($aVariant['type'], array('ins', 'delins'))
            || isset($aResponse['messages']['IPOSITIONRANGE']))
        && $aResponse['type'] != 'subst') {
        // Variants of type ins and delins need a suffix showing what has been
        //  inserted and variants which took place within a range need a suffix
        //  showing the length of the variant.
        // This is not required for substitutions with an IPOSITIONRANGE,
        //  as their length is always 1.
        if ($bCheckHGVS) {
            return false;
        }
        if (in_array($aVariant['type'], array('ins', 'delins'))) {
            $aResponse['errors']['ESUFFIXMISSING'] =
                'The inserted sequence must be provided for insertions or deletion-insertions.';
        } else {
            $aResponse['errors']['ESUFFIXMISSING'] =
                'The length must be provided for variants which took place within an uncertain range.';
        }

    } elseif ($aVariant['suffix']) {
        // Check the suffix for each type of variant.
        // First, exclude something that we don't support.
        if (strpos($sVariant, '^') !== false) {
            // "Or" syntax using a ^.
            if ($bCheckHGVS) {
                return false;
            }
            $aResponse['type'] = '^';
            // We have to throw an ENOTSUPPORTED, although we're returning
            //  positions. We currently cannot claim these are HGVS or not,
            //  so an WNOTSUPPORTED isn't appropriate.
            $aResponse['errors']['ENOTSUPPORTED'] =
                'Currently, variant descriptions using "^" are not yet supported.' .
                ' This does not necessarily mean the description is not valid HGVS.';
            return $aResponse;

        } elseif ($aResponse['type'] == 'repeat') {
            // Repeats should never be given a suffix.
            if ($bCheckHGVS) {
                return false;
            }
            $aResponse['warnings']['WSUFFIXGIVEN'] = 'Nothing should follow "' . $aVariant['type'] . '".';

        } elseif (in_array($aResponse['type'], array('ins', 'delins'))) {
            // Note: Using $aResponse's type here, because 'con' is changed to 'delins' there.
            // For insertions and deletion-insertions, the suffix can be quite complex. Also, it doesn't depend on the
            //  variant's length, so all checks are different. Check all possibilities.
            // Case problems are not checked here. Although it would perhaps help to provide a better warning,
            //  lovd_fixHGVS() already takes care of all issues, so we don't really need to check here.
            if (substr_count($aVariant['suffix'], '[') != substr_count($aVariant['suffix'], ']')) {
                if ($bCheckHGVS) {
                    return false;
                }
                $aResponse['warnings']['WSUFFIXFORMAT'] =
                    'The part after "' . $aVariant['type'] . '" contains unbalanced square brackets.';

            } else {
                $bSuffixIsSurroundedByBrackets = ($aVariant['suffix'][0] == '[' && substr($aVariant['suffix'], -1) == ']');
                $bMultipleInsertionsInSuffix = strpos($aVariant['suffix'], ';');

                foreach (explode(';', (!$bSuffixIsSurroundedByBrackets? $aVariant['suffix'] :
                        substr($aVariant['suffix'], 1, -1))) as $sInsertion) {
                    // Looping through all possible variants.
                    // Some have specific errors, so we handle these first.
                    if (preg_match('/^([ACGTN]+)\[([0-9]+|\?)_([0-9]+|\?)\]$/', $sInsertion, $aRegs)) {
                        // c.1_2insN[10_20].
                        if ($bCheckHGVS) {
                            return false;
                        }
                        list(, $sSequence, $nSuffixMinLength, $nSuffixMaxLength) = $aRegs;
                        $aResponse['warnings']['WSUFFIXFORMAT'] =
                            'The part after "' . $aVariant['type'] . '" does not follow HGVS guidelines.' .
                            ' Please rewrite "' . $sInsertion . '" to "' . $sSequence . '[' .
                            ($nSuffixMinLength == $nSuffixMaxLength?
                                $nSuffixMinLength :
                                '(' . (strpos($sInsertion, '?') !== false || $nSuffixMinLength < $nSuffixMaxLength?
                                    $nSuffixMinLength . '_' . $nSuffixMaxLength :
                                    min($nSuffixMinLength, $nSuffixMaxLength) . '_' . max($nSuffixMinLength, $nSuffixMaxLength)) . ')') . ']".';

                    } elseif (preg_match('/^([ACGTN]+)\[(([0-9]+|\?)|\(([0-9]+|\?)_([0-9]+|\?)\))\]$/', $sInsertion, $aRegs)) {
                        // c.1_2insN[40] or ..N[(1_2)].
                        if (isset($aRegs[4])) {
                            // Range was given.
                            list(, $sSequence, $nSuffixLength,, $nSuffixMinLength, $nSuffixMaxLength) = $aRegs;
                            if (strpos($nSuffixLength, '?') === false && $nSuffixMinLength >= $nSuffixMaxLength) {
                                if ($bCheckHGVS) {
                                    return false;
                                }
                                list($nSuffixMinLength, $nSuffixMaxLength) = array($nSuffixMaxLength, $nSuffixMinLength);
                                $aResponse['warnings']['WSUFFIXFORMAT'] =
                                    'The part after "' . $aVariant['type'] . '" does not follow HGVS guidelines.' .
                                    ' Please rewrite "' . $sInsertion . '" to "' . $sSequence . '[' .
                                    ($nSuffixMinLength == $nSuffixMaxLength?
                                        $nSuffixMinLength :
                                        '(' . $nSuffixMinLength . '_' . $nSuffixMaxLength . ')') . ']".';
                            }
                        }

                    } elseif (!(
                        (!(!$bMultipleInsertionsInSuffix && $bSuffixIsSurroundedByBrackets)                            // so no c.1_2ins[A]
                            && (preg_match('/^[ACGTN]+$/', $sInsertion)                                                // c.1_2insATG
                                || (preg_match(                                                                        // c.1_2ins15+1_16-1
                                    '/^([-*]?[0-9]+([-+][0-9]+)?)_([-*]?[0-9]+([-+]([0-9]+))?)(inv)?$/', $sInsertion, $aRegs)
                                    && !(ctype_digit($aRegs[1]) && ctype_digit($aRegs[3]) && $aRegs[1] > $aRegs[3])))) // if positions are simple, is A < B?
                        ||
                        ($bSuffixIsSurroundedByBrackets && strpos($sInsertion, ':')
                            && ( // If we have brackets and we find a colon, we expect a full position or inversion.
                                (substr($sInsertion, -3) == 'inv' && lovd_getVariantInfo($sInsertion, false, true))
                                || lovd_getVariantInfo($sInsertion . 'del', false, true)
                            )
                        ))) {
                        if ($bCheckHGVS) {
                            return false;
                        }
                        $aResponse['warnings']['WSUFFIXFORMAT'] =
                            'The part after "' . $aVariant['type'] . '" does not follow HGVS guidelines.';
                    }
                }
            }

        } elseif (strpos($aVariant['suffix'], ';') !== false) {
            // Combined variants that should be split.
            if ($bCheckHGVS) {
                return false;
            }
            $aResponse['type'] = ';';
            // We have to throw an ENOTSUPPORTED, although we're returning
            //  positions. We currently cannot claim these are HGVS or not,
            //  so an WNOTSUPPORTED isn't appropriate.
            $aResponse['errors']['ENOTSUPPORTED'] =
                'Currently, variant descriptions of combined variants are not yet supported.' .
                ' This does not necessarily mean the description is not valid HGVS.' .
                ' Please submit your variants separately.';
            // Some descriptions throw some warnings.
            $aResponse['warnings'] = array();
            return $aResponse;

        } else {
            // All other variants should get their suffix checked first, before
            //  we warn that it shouldn't be there. Because if it contains a
            //  different type of error, we should report that first.
            // Case problems are not checked yet. So it's important to do that here.
            $bCaseOK = true;

            // First check all length issues. Can we parse the suffix into a
            //  simple length?
            $nSuffixMinLength = $nSuffixMaxLength = 0;

            if (ctype_digit($aVariant['suffix'])) {
                // g.123_124del2.
                $nSuffixMinLength = $aVariant['suffix'];
                $aResponse['warnings']['WSUFFIXFORMAT'] =
                    'The length of the variant is not formatted following the HGVS guidelines.' .
                    ' Please rewrite "' . $aVariant['suffix'] . '" to "N[' . $nSuffixMinLength . ']".';

            } elseif (preg_match('/^[ACGTNU]+$/i', $aVariant['suffix'])) {
                // g.123_124delAA.
                $bCaseOK = ($aVariant['suffix'] == strtoupper($aVariant['suffix']));
                if (strpos(strtoupper($aVariant['suffix']), 'U') !== false) {
                    $aResponse['warnings']['WSUFFIXFORMAT'] =
                        'The part after "' . $aVariant['type'] . '" does not follow HGVS guidelines.' .
                        ' Please rewrite "' . $aVariant['type'] . $aVariant['suffix'] . '" to "' . $aVariant['type'] . str_replace('U', 'T', strtoupper($aVariant['suffix'])) . '".';
                }
                $nSuffixMinLength = strlen($aVariant['suffix']);

            } elseif (preg_match('/^\(([0-9]+)(?:_([0-9]+))?\)$/', $aVariant['suffix'], $aRegs)) {
                // g.123_124del(2), g.(100_200)del(50_60).
                list(, $nSuffixMinLength, $nSuffixMaxLength) = array_pad($aRegs, 3, '');
                if ($nSuffixMaxLength && $nSuffixMinLength > $nSuffixMaxLength) {
                    list($nSuffixMinLength, $nSuffixMaxLength) = array($nSuffixMaxLength, $nSuffixMinLength);
                }
                $aResponse['warnings']['WSUFFIXFORMAT'] =
                    'The length of the variant is not formatted following the HGVS guidelines.' .
                    ' Please rewrite "' . $aVariant['suffix'] . '" to "N[' .
                    (!$nSuffixMaxLength || $nSuffixMinLength == $nSuffixMaxLength?
                        $nSuffixMinLength :
                        '(' . $nSuffixMinLength . '_' . $nSuffixMaxLength . ')') . ']".';

            } elseif (preg_match('/^N\[([0-9]+)_([0-9]+)\]$/i', $aVariant['suffix'], $aRegs)) {
                // g.(100_200)delN[50_60].
                $bCaseOK = (substr($aVariant['suffix'], 0, 1) == 'N');
                list(, $nSuffixMinLength, $nSuffixMaxLength) = $aRegs;
                if ($nSuffixMinLength > $nSuffixMaxLength) {
                    list($nSuffixMinLength, $nSuffixMaxLength) = array($nSuffixMaxLength, $nSuffixMinLength);
                }
                $aResponse['warnings']['WSUFFIXFORMAT'] =
                    'The length of the variant is not formatted following the HGVS guidelines.' .
                    ' Please rewrite "' . $aVariant['suffix'] . '" to "N[' .
                    ($nSuffixMinLength == $nSuffixMaxLength?
                        $nSuffixMinLength :
                        '(' . $nSuffixMinLength . '_' . $nSuffixMaxLength . ')') . ']".';

            } elseif (preg_match('/^N\[([0-9]+|\(([0-9]+)_([0-9]+)\))\]$/i', $aVariant['suffix'], $aRegs)) {
                // g.123_124delN[2], g.(100_200)delN[(50_60)].
                $bCaseOK = (substr($aVariant['suffix'], 0, 1) == 'N');
                if (count($aRegs) == 2) {
                    list(, $nSuffixMinLength) = $aRegs;
                } else {
                    list(,, $nSuffixMinLength, $nSuffixMaxLength) = $aRegs;

                    if ($nSuffixMinLength > $nSuffixMaxLength || $nSuffixMinLength == $nSuffixMaxLength) {
                        $aResponse['warnings']['WSUFFIXFORMAT'] =
                            'The length of the variant is not formatted following the HGVS guidelines.' .
                            ' Please rewrite "' . $aVariant['suffix'] . '" to "N[' .
                            ($nSuffixMinLength == $nSuffixMaxLength?
                                $nSuffixMinLength :
                                '(' . $nSuffixMaxLength . '_' . $nSuffixMinLength . ')') . ']".';
                        list($nSuffixMinLength, $nSuffixMaxLength) = array($nSuffixMaxLength, $nSuffixMinLength);
                    }
                }
            }
            if (!$bCaseOK) {
                if (!isset($aResponse['warnings']['WSUFFIXFORMAT'])) {
                    // Wrong case only, no U-characters detected.
                    $aResponse['warnings']['WWRONGCASE'] =
                        'This is not a valid HGVS description, due to characters being in the wrong case.' .
                        ' Please rewrite "' . $aVariant['type'] . $aVariant['suffix'] . '" to "' . $aVariant['type'] . strtoupper($aVariant['suffix']) . '".';
                } else {
                    // There's already a detailed warning on what to replace. Throw a general warning only.
                    $aResponse['warnings']['WWRONGCASE'] =
                        'This is not a valid HGVS description, due to characters being in the wrong case.' .
                        ' Please check the use of upper- and lowercase characters after "' . $aVariant['type'] . '".';
                }
            }
            if ($bCheckHGVS
                && (isset($aResponse['warnings']['WSUFFIXFORMAT']) || isset($aResponse['warnings']['WWRONGCASE']))) {
                return false;
            }

            if ($nSuffixMinLength && !isset($aResponse['messages']['IUNCERTAINPOSITIONS'])) {
                // Length given; check sizes and if this matches the variant's length.
                // We can not check this with question marks in the positions (IUNCERTAINPOSITION); there might not be
                //  a maximum variant size and we won't know whether we have the inner or outer positions stored.
                $nVariantLength = lovd_getVariantLength($aResponse);
                if (!$nSuffixMaxLength) {
                    $nSuffixMaxLength = $nSuffixMinLength;
                }

                if (isset($aResponse['messages']['IUNCERTAINRANGE'])) {
                    // Variants with three or more positions. We have the inner positions stored; we know nothing about
                    //  the outer range currently, so we can not check this.
                    if ($nVariantLength == $nSuffixMinLength && $nSuffixMinLength == $nSuffixMaxLength) {
                        $aResponse['warnings']['WSUFFIXINVALIDLENGTH'] =
                            'The positions indicate a range equally long as the given length of the variant.' .
                            ' Please remove the variant length and position uncertainty if the positions are certain, or adjust the positions or variant length.';
                    } elseif ($nVariantLength > $nSuffixMinLength) {
                        $aResponse['warnings']['WSUFFIXINVALIDLENGTH'] =
                            'The positions indicate a range longer than the given length of the variant.' .
                            ' Please adjust the positions if the variant length is certain, or adjust the variant length.';
                    }

                } elseif (isset($aResponse['messages']['IPOSITIONRANGE'])) {
                    // Variants like c.(1_2)del(5).
                    if ($nVariantLength < $nSuffixMaxLength) {
                        $aResponse['warnings']['WSUFFIXINVALIDLENGTH'] =
                            'The positions indicate a range smaller than the given length of the variant.' .
                            ' Please adjust the positions or variant length.';
                    } elseif ($nVariantLength == $nSuffixMinLength) {
                        $aResponse['warnings']['WSUFFIXINVALIDLENGTH'] =
                            'The positions indicate a range equally long as the given length of the variant.' .
                            ' Please remove the variant length and parentheses if the positions are certain, or adjust the positions or variant length.';
                    }

                } else {
                    // Simple variants with one or two known positions, no uncertainties.
                    if ($nVariantLength < $nSuffixMaxLength) {
                        // The positions are smaller than the max length, so the length is at least partially larger.
                        $aResponse['warnings']['WSUFFIXINVALIDLENGTH'] =
                            'The positions indicate a range shorter than the given length of the variant.' .
                            ' Please adjust the positions if the variant length is certain, or remove the variant length.';
                    } elseif ($nVariantLength > $nSuffixMinLength) {
                        // The positions are bigger than the min length, so the length is at least partially smaller.
                        $aResponse['warnings']['WSUFFIXINVALIDLENGTH'] =
                            'The positions indicate a range longer than the given length of the variant.' .
                            ' Please adjust the positions if the variant length is certain, or remove the variant length.';
                    } elseif (!isset($aResponse['warnings']['WWRONGCASE'])) {
                        // Length is not (partially) larger, is not (partially) smaller, so must be equal.
                        // This is where the suffix becomes unnecessary.
                        $aResponse['warnings']['WSUFFIXGIVEN'] = 'Nothing should follow "' . $aVariant['type'] . '".';
                    }
                }

                if ($bCheckHGVS
                    && (isset($aResponse['warnings']['WSUFFIXINVALIDLENGTH']) || isset($aResponse['warnings']['WSUFFIXGIVEN']))) {
                    return false;
                }

            } elseif (!$nSuffixMinLength) {
                // We couldn't parse the suffix.
                if (isset($aResponse['messages']['IUNCERTAINRANGE'])) {
                    // Variants with three or more positions. The suffix isn't required.
                    $aResponse['warnings']['WSUFFIXFORMAT'] =
                        'The length of the variant is not formatted following the HGVS guidelines.' .
                        ' If you didn\'t mean to specify a variant length, please remove the part after "' . $aVariant['type'] . '".';
                } elseif (isset($aResponse['messages']['IPOSITIONRANGE'])) {
                    // Variants like c.(1_2)del(5). The suffix is mandatory.
                    $aResponse['warnings']['WSUFFIXFORMAT'] =
                        'The length of the variant is not formatted following the HGVS guidelines.' .
                        ' When indicating an uncertain position like this, the length or sequence of the variant must be provided.';
                } elseif ($aVariant['type'] == 'del' && strpos(strtolower($aVariant['suffix']), 'ins')) {
                    // A very special case; deletions where the suffix contains "ins". This is usually a delNinsN case.
                    // We can have this rewritten, but only when the length matches. We'll use a recursive call to find
                    //  out if that's OK. Based on that, we'll devise our answer.
                    list($sDeleted, $sInserted) = array_map('strtoupper', explode('ins', str_replace('u', 't', strtolower($aVariant['suffix'])), 2));
                    $aDeletion = lovd_getVariantInfo(str_replace($aVariant['suffix'], $sDeleted, $sVariant), $sTranscriptID);
                    // If the suffix matches the variant's length, or the suffix is unparseable, then we'll get a WSUFFIXGIVEN.
                    if (count($aDeletion['warnings']) == 1 && isset($aDeletion['warnings']['WSUFFIXGIVEN'])) {
                        $aResponse['type'] = 'delins';
                        $bCaseOK = ($aVariant['suffix'] == $sDeleted . 'ins' . $sInserted);
                        if (!$bCaseOK) {
                            $aResponse['warnings']['WWRONGCASE'] =
                                'This is not a valid HGVS description, due to characters being in the wrong case.' .
                                ' Please check the use of upper- and lowercase characters after "' . $aVariant['type'] . '".';
                        }
                        if (strlen($sDeleted) == 1 && strlen($sInserted) == 1 && preg_match('/^[ACGTN]$/', $sDeleted)) {
                            // Another special case; a delins that should have been a substitution.
                            $aResponse['warnings']['WWRONGTYPE'] =
                                'A deletion-insertion of one base to one base should be described as a substitution.' .
                                ' Please rewrite "' . $aVariant['type'] . $aVariant['suffix'] . '" to "' . $sDeleted . '>' . $sInserted . '".';
                        } else {
                            // We're not going to check here if this is a delAinsAT here that should be a shifted ins
                            //  or even check for insertions that should be dups. VV will handle that if we need it.
                            // Simply tell them to rewrite it.
                            $aResponse['warnings']['WSUFFIXFORMAT'] =
                                'The part after "' . $aVariant['type'] . '" does not follow HGVS guidelines.' .
                                ' Please rewrite "' . $aVariant['type'] . $aVariant['suffix'] . '" to "delins' . $sInserted . '".';
                        }

                    } elseif (count($aDeletion['warnings']) == 1 && isset($aDeletion['warnings']['WSUFFIXINVALIDLENGTH'])) {
                        // Length mismatched. Just pass it on.
                        $aResponse['type'] = 'delins';
                        $aResponse['warnings'] = $aDeletion['warnings'];

                    } else {
                        // We got other warnings. Maybe the format is wrong? Just throw an error.
                        $aResponse['warnings']['WSUFFIXFORMAT'] =
                            'The part after "' . $aVariant['type'] . '" does not follow HGVS guidelines.';
                    }

                } else {
                    // Simple variants with one or two known positions, no uncertainties. The suffix is forbidden.
                    // Still, make a difference between "suffix sometimes allowed but not understood"
                    //  and "suffix never allowed".
                    if ($aResponse['type'] == 'subst') {
                        $aResponse['warnings']['WSUFFIXGIVEN'] = 'Nothing should follow "' . $aVariant['type'] . '".';
                    } else {
                        $aResponse['warnings']['WSUFFIXFORMAT'] =
                            'The part after "' . $aVariant['type'] . '" does not follow HGVS guidelines.';
                    }
                }

                if ($bCheckHGVS
                    && (isset($aResponse['warnings']['WSUFFIXFORMAT'])
                        || isset($aResponse['warnings']['WSUFFIXGIVEN'])
                        || isset($aResponse['warnings']['WWRONGCASE'])
                        || isset($aResponse['warnings']['WWRONGTYPE']))) {
                    return false;
                }
            }
        }
    }

    // At this point, we can be certain that our variant fully matched the HGVS nomenclature.
    if ($bCheckHGVS) {
        return true;
    }

    // Done checking the syntax of the variant.





    // When strict SQL mode is enabled, we'll get errors when we try and
    //  insert large numbers in the position fields.
    // Check the positions we extracted; the variant could be described badly,
    //  and this could cause a query error.
    // Rather, fix the position fields to their respective maximum values.
    static $aMinMaxValues = array(
        'g' => array(
            'position_start' => array(1, 4294967295),
            'position_end' => array(1, 4294967295),
        ),
        'm' => array(
            'position_start' => array(1, 4294967295),
            'position_end' => array(1, 4294967295),
        ),
        'c' => array(
            'position_start' => array(-8388608, 8388607),
            'position_start_intron' => array(-2147483648, 2147483647),
            'position_end' => array(-8388608, 8388607),
            'position_end_intron' => array(-2147483648, 2147483647),
        ),
        'n' => array(
            'position_start' => array(1, 8388607),
            'position_start_intron' => array(-2147483648, 2147483647),
            'position_end' => array(1, 8388607),
            'position_end_intron' => array(-2147483648, 2147483647),
        ),
    );

    if (isset($aMinMaxValues[$aVariant['prefix']])) {
        // If the min and max values are defined for this prefix, check the fields.

        foreach ($aMinMaxValues[$aVariant['prefix']] as $sField => $aMinMaxValue) {
            if ($aResponse[$sField] === '?') {
                $aResponse[$sField] = (substr($sField, -5) == 'start'? $aMinMaxValue[0] : $aMinMaxValue[1]);

            } else {
                $nOriValue = $aResponse[$sField];
                $aResponse[$sField] = max($aResponse[$sField], $aMinMaxValue[0]);
                $aResponse[$sField] = min($aResponse[$sField], $aMinMaxValue[1]);

                if ($nOriValue != $aResponse[$sField]) {
                    $sFieldName = str_replace('position_', '', $sField);
                    if (strpos($sField, 'intron')) {
                        $sFieldName = str_replace('_intron', ' in intron', $sFieldName);
                    }

                    if (!isset($aResponse['warnings']['WPOSITIONLIMIT'])) {
                        $aResponse['warnings']['WPOSITIONLIMIT'] = 'Position is beyond the possible limits of its type: ' . $sFieldName . '.';
                    } else {
                        // Append.
                        $aResponse['warnings']['WPOSITIONLIMIT'] =
                            str_replace(array('Position is ', ' its '), array('Positions are ', ' their '), rtrim($aResponse['warnings']['WPOSITIONLIMIT'], '.')) . ', ' . $sFieldName . '.';
                    }
                }
            }
        }
    }

    return $aResponse;
}





function lovd_getVariantLength ($aVariant)
{
    // This function receives an array in the format as given by
    //  lovd_getVariantInfo() and calculates the length of the variant.
    // This length will only include intronic positions if the input contains
    //  these. When the length cannot be determined due to crossing the center
    //  of an intron, this function will return false.

    if (!isset($aVariant['position_start']) || !isset($aVariant['position_end'])
        || $aVariant['position_start'] == '?' || $aVariant['position_end'] == '?') {
        return false;
    }

    $nBasicLength = $aVariant['position_end'] - $aVariant['position_start'] + 1;
    if (empty($aVariant['position_start_intron'])
        && empty($aVariant['position_end_intron'])) {
        // Simple case; genomic variant or simply no introns involved.
        return ($nBasicLength);

    } elseif (empty($aVariant['position_start_intron'])) {
        // So we have an intronic end, but not an intronic start.
        // If the intronic end is negative, this means we're crossing the
        //  center of an intron, and the length cannot be determined.
        if ($aVariant['position_end_intron'] < 0) {
            return false;
        }
        return ($nBasicLength + $aVariant['position_end_intron']);

    } elseif (empty($aVariant['position_end_intron'])) {
        // So we have an intronic start, but not an intronic end.
        // If the intronic start is positive, this means we're crossing the
        //  center of an intron, and the length cannot be determined.
        if ($aVariant['position_start_intron'] > 0) {
            return false;
        }
        return ($nBasicLength + abs($aVariant['position_start_intron']));
    }

    // Else, we have intronic positions both for the start and the end.
    if ($aVariant['position_start'] == $aVariant['position_end']) {
        // Same side of the intron. Just take the max minus the min.
        // NOTE: $nBasicLength is already 1 even though no length has been
        //  calculated yet. So we don't have to add that 1 here.
        return (
            $nBasicLength +
            max(
                $aVariant['position_start_intron'],
                $aVariant['position_end_intron']
            ) -
            min(
                $aVariant['position_start_intron'],
                $aVariant['position_end_intron']
            )
        );

    } elseif ($aVariant['position_start_intron'] > 0
        || $aVariant['position_end_intron'] < 0) {
        // Still nope.
        return false;
    }

    // OK, just add the lengths.
    return (
        $nBasicLength
        + abs($aVariant['position_start_intron'])
        + $aVariant['position_end_intron']);
}





function lovd_getVariantPrefixesByRefSeq ($s)
{
    // Returns all the DNA type prefixes which fit a given reference sequence.
    // The variable $s could be a full variant description, or it might
    //  just be a reference sequence.
    global $_LIBRARIES;

    // Get matching DNA type prefixes.
    foreach ($_LIBRARIES['regex_patterns']['refseq_to_DNA_type'] as $sPattern => $aDNATypes) {
        if (preg_match($sPattern, $s)) {
            return $aDNATypes;
        }
    }

    // No matches found.
    return array();
}





function lovd_getVariantRefSeq ($sVariant)
{
    // This function isolates and returns the reference sequence from a variant description, if there is any.

    if (!lovd_variantHasRefSeq($sVariant)) {
        return false;
    }

    return strstr($sVariant, ':', true);
}





function lovd_isValidRefSeq ($sRefSeq)
{
    // This function checks if the given string is a valid reference sequence description.
    global $_LIBRARIES;

    return (bool) (
        is_string($sRefSeq)
        &&
        preg_match($_LIBRARIES['regex_patterns']['refseq']['strict'], $sRefSeq)
    );
}





function lovd_shortenString ($s, $l = 50)
{
    // Based on a function provided by Ileos.nl in the interest of Open Source.
    // Shortens string nicely to a given length.
    // FIXME; Should be able to shorten from the left as well, useful with for example transcript names.
    if (strlen($s) > $l) {
        $s = rtrim(substr($s, 0, $l - 3), '(');
        // Also make sure the parentheses are balanced. It assumes they were balanced before shorting the string.
        $nClosingParenthesis = 0;
        while (substr_count($s, '(') > (substr_count($s, ')') + $nClosingParenthesis)) {
            $s = rtrim(substr($s, 0, ($l - 3 - ++$nClosingParenthesis)), '('); // Usually eats off one, but we may have started with a shorter string because of the rtrim().
        }
        $s .= '...' . str_repeat(')', $nClosingParenthesis);
    }
    return $s;
}





function lovd_variantHasRefSeq ($sVariant)
{
    // This function returns whether the general pattern of a reference sequence was found in a variant description.
    global $_LIBRARIES;

    return (
        is_string($sVariant)
        &&
        strpos($sVariant, ':') !== false
        &&
        preg_match($_LIBRARIES['regex_patterns']['refseq']['basic'], strstr($sVariant, ':', true))
    );
}





function lovd_variantRemoveRefSeq ($sVariant)
{
    // This function removes the reference sequence from a variant description.

    if (!lovd_variantHasRefSeq($sVariant)) {
        return $sVariant;
    }

    return substr(strstr($sVariant, ':'), 1);
}
?>
