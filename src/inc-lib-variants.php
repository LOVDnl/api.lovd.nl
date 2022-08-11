<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 * Adapted from /src/inc-lib-variants.php in the LOVD3 project.
 *
 * Created     : 2022-08-11
 * Modified    : 2022-08-11
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





function lovd_fixHGVS ($sVariant, $sType = '')
{
    // This function tries to recognize common errors in the HGVS nomenclature,
    //  and fix the variants in such a way, that they will be recognizable and
    //  usable.
    // $sType stores the DNA type (c, g, m, or n) to allow for this function to
    //  fully validate the variant and, optionally, its reference sequence.

    // Check for a reference sequence. We won't check it here, so we won't be
    //  very strict.
    // FIXME: Loes replaces this with lovd_holdsRefSeq but that returns a boolean. We need the $aRegs.
    //  We could modify that function to return the refseq, but its pattern is currently way too simple.
    //  Maybe remove the simple pattern and use the complex pattern?
    if (preg_match('/^(ENS[GT]|LRG_([0-9]+t)?|[NX][CGMRTW]_)[0-9]+(\.[0-9]+)?/i', $sVariant, $aRegs)) {
        // Something that looks like a reference sequence is prefixing the
        //  variant. Cut it off and store it separately. We'll return it, but
        //  this way we can actually check the variant itself.
        if (strpos($sVariant, ':') === false) {
            // We don't always have a : in there, though.
            // Try to insert one.
            $sVariant = str_replace($aRegs[0], $aRegs[0] . ':', $sVariant);
        }
        list($sReference, $sVariant) = explode(':', $sVariant, 2);
        // Fix possible case issues. All uppercase except for the t in LRG_123t1.
        $sReference = preg_replace('/(?<=[0-9])T(?=[0-9])/', 't', strtoupper($sReference));
        $sReference .= ':'; // To simplify the concatenation later on.
    } else {
        // No reference was found.
        $sReference = '';
    }

    // In case users forgot to remove the starting ':'.
    $sVariant = ltrim($sVariant, ':');

    if (!$sVariant) {
        return $sReference . $sVariant;
    }

    if (!in_array($sType, array('g', 'm', 'c', 'n'))) {
        // If type is not given, default to something.
        // We usually just default to 'g'. But when it's obviously something
        //  else, pick that other thing.
        if (in_array(strtolower($sVariant[0]), array('c', 'g', 'm', 'n'))) {
            $sType = strtolower($sVariant[0]);
        } else {
            if (preg_match('/[0-9][+-][0-9]/', $sVariant)) {
                // Variant doesn't have a prefix either, *and* there seems to be an
                //  intronic position mentioned.
                $sType = 'c';
            } elseif ($sReference) {
                // If can't get it from the variant, but we do have a refseq,
                //  let that one do the talking!
                if (preg_match('/^[NX]R_[0-9]/', $sReference)) {
                    $sType = 'n';
                } elseif (preg_match('/^(ENST|LRG_[0-9]+t|[NX]M_)[0-9]/', $sReference)) {
                    $sType = 'c';
                } elseif (preg_match('/^NC_(001807|012920)/', $sReference)) {
                    $sType = 'm';
                } else {
                        $sType = 'g';
                }
            } else {
                // Fine, we default to 'g'.
                $sType = 'g';
            }
        }
    }

    // Trim the variant and remove whitespace.
    $sVariant = preg_replace('/\s+/', '', $sVariant);

    // Replace special – (hyphen, minus, en dash, em dash) with a simple - (hyphen-minus).
    $sVariant = str_replace(array('‐', '−', '–', '—'), '-', $sVariant);

    // Rare, but seen; "c," as prefix instead of "c.".
    if (substr($sVariant, 0, 2) == $sType . ',') {
        $sVariant[1] = '.';
    }

    // More special characters arising from copying variants from PDFs. Some journals decide to use specialized fonts to
    //  create markup for normal characters, such as the > in a substitution. This is a terrible idea, as
    //  text-recognition then completely fails and copying the variant from the PDF results in a misformatted variant.
    // " " seen in AIPL1_20702822_Jacobson-2011.pdf ("c.216G A")
    // "®" seen in CACNA1F_9662399_Strom-1998.pdf ("1106G®A")
    // "?" seen in CACNA1F_12111638_Wutz-2002.pdf ("220T?C")
    // "!" seen in CRB1_32351147_Liu-2020.pdf ("C!T")
    // "." seen in MERTK_19403518_Charbel%20Issa-2009.pdf ("c.2189+1G.T")
    // "4" seen in MERTK_30851773_Bhatia-2019.pdf ("c.1647T4G")
    // "→" seen in NYX_11062472_Pusch-2000.pdf ("1040T→C")
    // Because " " has already been trimmed to "", make pattern optional.
    // Note the "u" modifier to allow for UTF-8 characters.
    if (preg_match('/^([cgmn]\.[0-9_+-]+[ACGTU])[®?!.4→]?([ACGTRYSWKMBDHUVN])$/u', $sVariant, $aRegs)) {
        // One of these characters has been found specifically in a substitution pattern. Replace it.
        return $sReference . $aRegs[1] . '>' . $aRegs[2];
    }

    // Do a quick HGVS check.
    if (lovd_getVariantInfo($sReference . $sVariant, false, true)) {
        // All good!
        return $sReference . $sVariant;
    }

    // Move or remove wrongly placed parentheses.
    $nOpening = substr_count($sVariant, '(');
    $nClosing = substr_count($sVariant, ')');
    if ($nOpening != $nClosing) {
        // There are more parentheses opening than closing.
        // We won't be looking all the way into the variant, since that is
        //  simply too much effort for the reward. However, we will take a look
        //  at the simplest and most common mistakes.
        if (strpos($sVariant, '((') !== false && ($nOpening - $nClosing) == 1) {
            // e.g., g.((123_234)_(345_456)del.
            return lovd_fixHGVS($sReference . str_replace('((', '(', $sVariant), $sType);

        } elseif (($nClosing - $nOpening) == 1 && strpos($sVariant, '))')) {
            // e.g. g.(123_234)_(345_456))del.
            return lovd_fixHGVS($sReference . str_replace('))', ')', $sVariant), $sType);

        } else {
            // The parentheses are formatted in a more difficult way than
            //  is worth handling. We will return the variant, which is sadly
            //  still not HGVS.
            return $sReference . $sVariant; // Not HGVS.
        }

    } elseif ($sVariant[0] == '(') {
        // All opening parentheses are closed, but the description starts with
        //  one, which isn't an option. Don't just assume a prefix is there or
        //  not, check.
        if (!preg_match('/\b[cgmn]\./', $sVariant)) {
            // No prefix found. Add one.
            return lovd_fixHGVS(
                $sReference . $sType . '.(' . substr($sVariant, 1), $sType);
        } elseif (preg_match('/^\(([cgmn]\.)/', $sVariant, $aRegs)) {
            // The variant is written as (c.1_2insA). We will rewrite this as c.(1_2insA).
            return lovd_fixHGVS(
                $sReference . $aRegs[1] . '(' . substr($sVariant, 3), $sType);
        }

    } elseif (strpos($sVariant, '((') !== false || strpos($sVariant, '))') !== false) {
        if (preg_match('/\(\([0-9_]+\)\)/', $sVariant)) {
            // c.((1_5))insA or c.100_500del((10))
            return lovd_fixHGVS($sReference . str_replace(array('((', '))'), array('(', ')'), $sVariant), $sType);
        }
    }

    // Add prefix in case it is missing.
    if (!in_array(strtolower($sVariant[0]), array('c', 'g', 'm', 'n'))) {
        return lovd_fixHGVS($sReference . $sType . ($sVariant[0] == '.'? '' : '.') . $sVariant, $sType);
    }

    // Protein formatting for DNA variants.
    if (preg_match('/^([cgmn]\.)([ACGTUN]+)([0-9]+)([ACGTUN]+)$/', $sVariant, $aRegs)) {
        // Rebuild the variant into a substitution.
        list(, $sPrefix, $sRef, $nPosition, $sAlt) = $aRegs;
        return lovd_fixHGVS($sReference . $sPrefix . $nPosition . $sRef . '>' . $sAlt, $sType);
    }

    // Remove redundant prefixes due to copy/paste errors (g.12_g.23del to g.12_23del).
    // But only remove them if there isn't another refseq in front of it
    //  (like for complex insertions).
    if (preg_match_all('/(?<!:)' . preg_quote($sType, '/') . '\./', $sVariant) > 1) {
        return lovd_fixHGVS($sReference . $sType . '.' .
            preg_replace('/(?<!:)' . preg_quote($sType, '/') . '\./', '', $sVariant), $sType);
    }

    // Make sure no unnecessary bases are given for wild types (c.123A= -> c.123=).
    if (preg_match('/[0-9]([ACGTN]+=)/i', $sVariant, $aRegs)) {
        return lovd_fixHGVS($sReference . str_replace($aRegs[1], '=', $sVariant), $sType);
    }



    // The basic steps have all been taken. From this point forward, we
    //  can use the warning and error messages of lovd_getVariantInfo() to check
    //  and fix the variant.
    $aVariant = lovd_getVariantInfo($sReference . $sVariant, false);
    if ($aVariant === false) {
        return $sReference . $sVariant; // Not HGVS.

    } elseif (isset($aVariant['errors']['EPIPEMISSING'])) {
        // This looked like a methylation-related variant that was missing a
        //  pipe, failing lovd_getVariantInfo()'s entire regexp.
        return lovd_fixHGVS($sReference . preg_replace('/(gom|lom|met=|bsrC?)$/', '|$1', $sVariant), $sType);

    } elseif (isset($aVariant['errors']['ENOTSUPPORTED'])
        && $aVariant['type'] == 'met' && strpos($sVariant, '||')) {
        // Whatever is after the pipe wasn't recognized, but we also found more
        //  pipes. Try removing them.
        return lovd_fixHGVS($sReference . preg_replace('/\|{2,}/', '|', $sVariant), $sType);

    } elseif (isset($aVariant['errors']['EFALSEUTR']) || isset($aVariant['errors']['EFALSEINTRONIC'])) {
        // The wrong prefix was given. In other words: intronic positions or UTR
        //  notations were found for genomic DNA.
        if (strtolower($sVariant[0]) == $sType) {
            if (isset($aVariant['errors']['EFALSEINTRONIC'])
                && ($aVariant['position_start'] >= 250000 || $aVariant['position_start_intron'] >= 250000)) {
                // If variants hold false intronic positions, it might be that
                //  the user accidentally wrote down '-' while meaning '_'.
                // We will fix this only if we can be really sure this is the case,
                //  which is if the variant contains a position too big to
                //  be of a transcript.
                return lovd_fixHGVS($sReference . str_replace('-', '_', $sVariant), $sType);

            } else {
                // The user likely put the input in the wrong field.
                // We cannot fix this variant with certainty.
                return $sReference . $sVariant; // Not HGVS.
            }

        } else {
            // If the prefix does not equal the expected type, we can be sure
            //  to try and add in the type instead. Perhaps the user accidentally
            //  wrote down a 'g.' in the transcript field.
            return lovd_fixHGVS($sReference . $sType . substr($sVariant, 1), $sType);
        }

    } elseif (!empty($aVariant['errors'])
        && !isset($aVariant['errors']['ESUFFIXMISSING'])
        && !isset($aVariant['errors']['EPOSITIONFORMAT'])
        && isset($aVariant['warnings']['WTOOMUCHUNKNOWN'])) {
        return $sReference . $sVariant; // Not HGVS.
    }

    // Swap the reference sequences if they are used in the wrong order.
    if (isset($aVariant['warnings']['WREFERENCEFORMAT'])
        && preg_match('/Please rewrite "([^"]+)" to "([^"]+)"\.$/', $aVariant['warnings']['WREFERENCEFORMAT'], $aRegs)) {
        return lovd_fixHGVS($aRegs[2] . ':' . $sVariant, $sType);
    }

    // Fix case problems.
    if (isset($aVariant['warnings']['WWRONGCASE'])) {
        // Check prefix. I'd rather do it here.
        if (ctype_upper($sVariant[0])) {
            return lovd_fixHGVS($sReference . strtolower($sVariant[0]) . substr($sVariant, 1), $sType);

        } elseif ($aVariant['type'] == 'subst') {
            // If not the prefix, try the bases. First up, substitutions.
            return lovd_fixHGVS($sReference . $sVariant[0] .
                str_replace('U', 'T', strtoupper(substr($sVariant, 1))), $sType);

        } elseif (ctype_lower($aVariant['type'])
            && strpos($sVariant, $aVariant['type']) === false
            && stripos($sVariant, $aVariant['type']) !== false) {
            // Case problem in the variant type itself; i.e., g.123DEL.
            return lovd_fixHGVS($sReference . str_ireplace($aVariant['type'], $aVariant['type'], $sVariant), $sType);

        } elseif (($aVariant['type'] == 'del' || $aVariant['type'] == 'delins')
            && preg_match('/^(.+)del([ACGTUN\[0-9\]]+)?(?:ins([ACGTUN\[0-9\]]+))?$/i', $sVariant, $aRegs)
            && (
                (isset($aRegs[2]) && $aRegs[2] != strtoupper($aRegs[2]))
                ||
                (isset($aRegs[3]) && $aRegs[3] != strtoupper($aRegs[3])))) {
            // Deletions and deletion-insertion events.
            // Note: A "delins" can also look like "delAinsG".
            return lovd_fixHGVS($sReference . $aRegs[1] . 'del' .
                str_replace('U', 'T', strtoupper($aRegs[2]) .
                    (!isset($aRegs[3])? '' : 'ins' . strtoupper($aRegs[3]))), $sType);
        }
    }

    // Change the variant type (if possible) if the wrong type was chosen.
    if (isset($aVariant['warnings']['WWRONGTYPE'])) {
        // lovd_getVariantInfo() already often provides the fix.
        if (preg_match('/Please rewrite "([^"]+)" to "([^"]+)"\.$/', $aVariant['warnings']['WWRONGTYPE'], $aRegs)) {
            list(, $sOldType, $sNewType) = $aRegs;
            return lovd_fixHGVS($sReference . str_replace($sOldType, $sNewType, $sVariant), $sType);
        }

        if ($aVariant['type'] == 'subst') {
            // Handle all notations of substitutions regardless of the length of
            //  the REF and ALT; recognize deletions, insertions, duplications,
            //  and more.
            // NOTE: We can't handle c.100.>A as we don't know whether this
            //  means c.99_100insA or c.100_101insA. This depends on the
            //  implementation of the program that created the VCF.
            // (ANNOVAR does something else than most other VCF generators)
            // Either way, VCF doesn't actually allow empty REFs or ALTs.
            preg_match('/([0-9_+-]+)([A-Z]+)>([A-Z.]+)$/', $sVariant, $aRegs);
            list(, $sPosition, $sRef, $sAlt) = $aRegs;
            $sAlt = rtrim($sAlt, '.'); // Change . into an empty string.
            $nPositionLength = lovd_getVariantLength($aVariant);

            // We'll always accept having only one position (g.100AAA>.)
            //  but we'll never accept having a range that doesn't agree with
            //  the length of the REF.
            if ($nPositionLength > 1 && $nPositionLength != strlen($sRef)) {
                // e.g., c.100_102AAAA>A.
                // This is an error we cannot fix. We don't know if the
                //  error is in the positions or the given sequence.
                return $sReference . $sVariant;
            }

            // The following code is similar to lovd_getVariantDescription(),
            //  but it's not the same. It needs to handle complex (intronic)
            //  positions. Not sure, but decided to duplicate and adapt.
            // FIXME: Perhaps we can write a new function that's more generic?

            // Shift variant if REF and ALT are similar.
            // Save original value before we edit it.
            $sAltOriginal = $sAlt;
            $nOffset = 0;
            // 'Eat' letters from either end - first left, then right - to isolate the difference.
            while (strlen($sRef) > 0 && strlen($sAlt) > 0 && $sRef[0] == $sAlt[0]) {
                $sRef = substr($sRef, 1);
                $sAlt = substr($sAlt, 1);
                $nOffset ++;
            }
            while (strlen($sRef) > 0 && strlen($sAlt) > 0 && $sRef[strlen($sRef) - 1] == $sAlt[strlen($sAlt) - 1]) {
                $sRef = substr($sRef, 0, -1);
                $sAlt = substr($sAlt, 0, -1);
            }
            $nREFLength = strlen($sRef); // We are sure this is not a period.
            $nALTLength = strlen($sAlt);

            // Now determine the actual variant type.
            if ($nREFLength == 0 && $nALTLength == 0) {
                // Nothing left. Take the original position and add '='.
                return lovd_fixHGVS($sReference . str_replace($aRegs[0], $sPosition . '=', $sVariant), $sType);

            } elseif ($nREFLength == 1 && $nALTLength == 1) {
                // Substitution.
                // Recalculate the position always; we might have started with a
                //  range, but ended with just a single position.
                $sPosition = lovd_formatPositions(lovd_modifyVariantPosition($aVariant, $nOffset, 1));
                return lovd_fixHGVS($sReference . str_replace($aRegs[0], $sPosition . $sRef . '>' . $sAlt, $sVariant), $sType);

            } elseif ($nALTLength == 0) {
                // Deletion.
                $sPosition = lovd_formatPositions(lovd_modifyVariantPosition($aVariant, $nOffset, $nREFLength));
                return lovd_fixHGVS($sReference . str_replace($aRegs[0], $sPosition . 'del', $sVariant), $sType);

            } elseif ($nREFLength == 0) {
                // Something has been added... could be an insertion or a duplication.
                if (substr($sAltOriginal, strrpos($sAltOriginal, $sAlt) - $nALTLength, $nALTLength) == $sAlt) {
                    // Duplication. Note that the start position might be quite
                    //  far from the actual insert.
                    $sPosition = lovd_formatPositions(lovd_modifyVariantPosition($aVariant, $nOffset - $nALTLength, $nALTLength));
                    return lovd_fixHGVS($sReference . str_replace($aRegs[0], $sPosition . 'dup', $sVariant), $sType);

                } else {
                    // Insertion. We don't need to worry about an offset of 0,
                    //  as we don't accept empty REFs - they can only have been
                    //  emptied by shifting.
                    $sPosition = lovd_formatPositions(lovd_modifyVariantPosition($aVariant, $nOffset - 1, 2));
                    return lovd_fixHGVS($sReference . str_replace($aRegs[0], $sPosition . 'ins' . $sAlt, $sVariant), $sType);
                }

            } else {
                // Inversion or deletion-insertion. Both REF and ALT are >1.
                $sPosition = lovd_formatPositions(lovd_modifyVariantPosition($aVariant, $nOffset, $nREFLength));

                if ($sRef == strrev(str_replace(array('A', 'C', 'G', 'T'), array('T', 'G', 'C', 'A'), strtoupper($sAlt)))) {
                    // Inversion.
                    return lovd_fixHGVS($sReference . str_replace($aRegs[0], $sPosition . 'inv', $sVariant), $sType);
                } else {
                    // Deletion-insertion. Both REF and ALT are >1.
                    return lovd_fixHGVS($sReference . str_replace($aRegs[0], $sPosition . 'delins' . $sAlt, $sVariant), $sType);
                }
            }

        } elseif ($aVariant['type'] == 'delins') {
            return $sReference . $sVariant; // Not HGVS, and not fixable by us (unless we use VV).
        }
    }

    // Remove the suffix if it is given to a variant type which should not hold one.
    if (isset($aVariant['warnings']['WSUFFIXGIVEN']) && !isset($aVariant['warnings']['WTOOMUCHUNKNOWN'])) {
        // For anything not "del" or "dup", better not touch it. This can also be thrown for types that never should
        //  have a suffix, like substitutions or repeats. So don't mess with what we don't understand.
        if (!in_array($aVariant['type'], array('del', 'dup'))) {
            return $sReference . $sVariant;
        }
        // Redundant variant suffixes that indicate a length, e.g. g.10_20del11 or g.1dupA, are already checked in
        //  lovd_getVariantInfo() to makes sure that the length they indicate matches the given positions. If this does
        //  not match, a WSUFFIXINVALIDLENGTH is thrown rather than a WSUFFIXGIVEN. And if a suffix isn't understood,
        //  we'll see a WSUFFIXFORMAT. So for dels and dups, we can be sure we can drop the suffix now.
        // The warning message indicates where the unwanted suffix starts.
        // We take this string by isolating the part between the double quotes.
        list(,$sVariantType) = explode('"', $aVariant['warnings']['WSUFFIXGIVEN']);
        list($sBeforeType, $sSuffix) = explode($sVariantType, $sVariant, 2);
        return lovd_fixHGVS(
            $sReference . $sBeforeType . $sVariantType .
            str_repeat(')', (substr_count($sSuffix, ')') - substr_count($sSuffix, '('))), $sType);
    }



    // Reformat wrongly described suffixes.
    if (isset($aVariant['warnings']['WSUFFIXFORMAT'])) {
        if (isset($aVariant['warnings']['WSUFFIXINVALIDLENGTH'])
            || !$aVariant['type']) {
            // In this case, the variant suffix is interpreted and understood to be broken,
            //  or the type couldn't even properly be interpreted. Let's not touch it.
            return $sReference . $sVariant;
        }

        // Often, the solution to the problem will be given in the warning itself.
        // Do this first, because delAinsG variants we can't handle otherwise, as "delins" can't be found in those
        //  variants, and the suffix can't be isolated properly.
        if (preg_match('/Please rewrite "([^"]+)" to "([^"]+)"\.$/', $aVariant['warnings']['WSUFFIXFORMAT'], $aRegs)) {
            list(, $sOldSuffix, $sNewSuffix) = $aRegs;
            // To prevent a disaster when g.100del1 gets replaced to g.N[1]00delN[1], replace only the last occurrence.
            // I'm not 100% percent sure if $sVariant always ends with $sOldSuffix, so just be a bit more intelligent.
            $nPosition = strrpos($sVariant, $sOldSuffix);
            return lovd_fixHGVS($sReference . substr_replace($sVariant, $sNewSuffix, $nPosition, strlen($sOldSuffix)), $sType);
        }

        list($sBeforeSuffix, $sSuffix) = explode($aVariant['type'], $sVariant, 2);
        if (in_array($aVariant['type'], array('ins', 'delins'))) {
            // Extra format checks which only apply to ins or delins types.

            // Suffix could contain a closing parenthesis that opens in the beginning of the variant.
            // Don't let it mess up our parsing. Parentheses must be balanced in the entire variant (we checked).
            $bClosingParenthesis = false;
            $sNewSuffix = $sSuffix;
            if (substr_count($sNewSuffix, '(') < substr_count($sNewSuffix, ')') && substr($sNewSuffix, -1) == ')') {
                // Unbalanced parentheses in the suffix, and the suffix
                //  ends with an closing parenthesis.
                $bClosingParenthesis = true;
                $sNewSuffix = substr($sNewSuffix, 0, -1);
            }

            // Remove [ and ], when present.
            if ($sSuffix[0] == '[' && substr($sSuffix, -1) == ']') {
                $sNewSuffix = substr($sNewSuffix, 1, -1);
            }

            // It became too difficult to handle complex suffixes, so let's
            //  split them up.
            $aParts = explode(';', $sNewSuffix);
            $nParts = count($aParts);

            foreach ($aParts as $i => $sPart) {
                if (preg_match('/^[ACGTNU]+$/i', $sPart) || preg_match('/^N\[/i', $sPart)) {
                    // Looks good, but make sure the case is good, too.
                    $aParts[$i] = $sPart = str_replace('U', 'T', strtoupper($sPart));
                }

                if (preg_match('/^\([ACTG]+\)$/', $sPart) || preg_match('/^N\[\([0-9]+\)\]/', $sPart)) {
                    // Remove redundant parentheses, e.g. ins(A) or insN[(20)].
                    $aParts[$i] = str_replace(array('(', ')'), '', $sPart);

                } elseif (preg_match('/^\([0-9]+(?:_[0-9]+)?\)$/', $sPart, $aRegs)) {
                    // The length of a variant was formatted as 'ins(length)'
                    //  instead of 'insN[length]' or 'ins(min_max)' instead
                    //  of 'insN[(min_max)]'.
                    $aParts[$i] = preg_replace(
                        array('/\(([0-9]+)\)/', '/\(([0-9]+_[0-9]+)\)/'),
                        array('N[${1}]', 'N[(${1})]'), $sPart);

                } elseif (preg_match('/^[NX][CGMRTW]_[0-9]+/i', $sPart)) {
                    // This is a full position with refseq. Often, mistakes are
                    //  made in this suffix. So check it.
                    // Append '=' to convert the position into something we can
                    //  check.
                    if (!lovd_getVariantInfo($sPart . '=', false, true)) {
                        $sPart = lovd_fixHGVS($sPart . '=');
                        if (lovd_getVariantInfo($sPart, false, true)) {
                            // FixHGVS() has fixed this part.
                            $aParts[$i] = rtrim($sPart, '=');
                        }
                    }
                }
            }

            $sNewSuffix = implode(';', $aParts);
            // Add [ and ] again, when needed.
            if ($nParts > 1 || preg_match('/^[NX][CGMRTW]_[0-9]+/', $sNewSuffix)
                || strpos($sNewSuffix, ':') !== false) {
                $sNewSuffix = '[' . $sNewSuffix . ']';
            }
            if ($bClosingParenthesis) {
                $sNewSuffix .= ')';
            }
            if ($sSuffix != $sNewSuffix) {
                // Something has changed.
                return lovd_fixHGVS($sReference .
                    $sBeforeSuffix . $aVariant['type'] . $sNewSuffix, $sType);
            }
        }
    }

    // Fix problems with too many questionmarks.
    if (isset($aVariant['warnings']['WTOOMUCHUNKNOWN'])) {
        // This means that question marks where given to the variant in
        //  places where they do not bring any additional value. We'll let
        //  lovd_getVariantInfo() do all the work!
        if (preg_match('/Please rewrite the positions ([()0-9_?+-]+) to ([()0-9_?+-]+)\.$/',
            $aVariant['warnings']['WTOOMUCHUNKNOWN'], $aRegs)) {
            list(, $sOldPosition, $sNewPosition) = $aRegs;
            return lovd_fixHGVS($sReference . str_replace($sOldPosition, $sNewPosition, $sVariant), $sType);
        }
    }

    // Rare situation; Uncertain positions are given that should just be certain.
    if (isset($aVariant['errors']['EPOSITIONFORMAT'])
        && isset($aVariant['messages']['IPOSITIONRANGE'])
        && lovd_getVariantLength($aVariant) == 2
        && in_array($aVariant['type'], array('ins', 'inv'))) {
        // E.g., c.(1_2)insA.
        $sPositions = lovd_formatPositions($aVariant);
        return $sReference . str_replace(
            '(' . $sPositions . ')',
            $sPositions,
            $sVariant
        );
    }

    // Swap positions if necessary.
    if (isset($aVariant['warnings']['WPOSITIONFORMAT'])) {
        // Before we start manually trying to figure out what's going on, try the suggested instructions.
        // Currently, these instructions are only given for positions that start with a 0, which should be removed.
        if (preg_match('/Please rewrite "([^"]+)" to "([^"]+)"\.$/', $aVariant['warnings']['WPOSITIONFORMAT'], $aRegs)) {
            list(, $sOldPosition, $sNewPosition) = $aRegs;
            // These warnings may be stacked. Using this pattern, we'll just grab the last one.
            // So it may take a few iterations before everything is fixed.
            // FIXME: This is a place for a possible infinite recursion. When the preg_replace() below fails because
            //  the pattern doesn't match, we'll recurse infinitely. Fixed two instances already, but in case I
            //  overlooked yet another situation, better re-code using a preg_match and a str_replace() using $aRegs.
            return lovd_fixHGVS($sReference . preg_replace(
                '/(' . (ctype_digit($sOldPosition[0])? '[^0-9]' : '.') . ')' . preg_quote($sOldPosition) . '([^0-9])/',
                '${1}' . $sNewPosition . '$2', $sVariant), $sType);
        }

        $aPositions = array();
        preg_match(
            '/^([cgmn])\.' .                         // 1.  Prefix.
            '((' .
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
            '(.*)' .                                 // 20. Type of variant and suffix.
            '))/',
            $sVariant, $aMatches);
        // c.(1_2)_(3_4)del == c.(A_B)_(C_D)del
        // c.1_2del         == c.A_Cdel
        // c.(1_2)del       == c.(A_B)del
        $sBefore  = $aMatches[1];
        $sAfter   = $aMatches[20];

        $aPositions['A']       = $aMatches[5];
        $aPositions['AIntron'] = $aMatches[6];
        $aPositions['B']       = $aMatches[9];
        $aPositions['BIntron'] = $aMatches[10];
        $aPositions['C']       = $aMatches[14];
        $aPositions['CIntron'] = $aMatches[15];
        $aPositions['D']       = $aMatches[17];
        $aPositions['DIntron'] = $aMatches[18];

        if ($aPositions['C']
            // Deliberately don't case these to int;
            //  max(<number>, "") is always <number>.
            && max(
                str_replace(array('?', '*'), array('', '1000000'), $aPositions['A']),
                str_replace(array('?', '*'), array('', '1000000'), $aPositions['B'])
            ) > max(
                str_replace(array('?', '*'), array('', '1000000'), $aPositions['C']),
                str_replace(array('?', '*'), array('', '1000000'), $aPositions['D']))) {
            // If this is the case, the positions are swapped in groups,
            //  i.e., c.(6_10)_(1_5)del.
            list($aPositions['A'], $aPositions['AIntron'], $aPositions['B'], $aPositions['BIntron'],
                $aPositions['C'], $aPositions['CIntron'], $aPositions['D'], $aPositions['DIntron']) =
                array($aPositions['C'], $aPositions['CIntron'], $aPositions['D'], $aPositions['DIntron'],
                    $aPositions['A'], $aPositions['AIntron'], $aPositions['B'], $aPositions['BIntron']);

            // Now that we swapped the whole groups, swap the inner positions as
            //  well, but only if they're both defined.
            if ($aPositions['A'] && $aPositions['B']) {
                list($aPositions['A'], $aPositions['AIntron'], $aPositions['B'], $aPositions['BIntron']) =
                    array($aPositions['B'], $aPositions['BIntron'], $aPositions['A'], $aPositions['AIntron']);
            }
            if ($aPositions['C'] && $aPositions['D']) {
                list($aPositions['C'], $aPositions['CIntron'], $aPositions['D'], $aPositions['DIntron']) =
                    array($aPositions['D'], $aPositions['DIntron'], $aPositions['C'], $aPositions['CIntron']);
            }

        } else {
            // If the above is not the case, the positions are swapped more
            //  intricately. This will be checked and fixed one by one.
            foreach (array(array('A', 'B'), array('C', 'D'), array('A', 'C'), array('B', 'D')) as $aFirstAndLast) {
                list($sFirst, $sLast) = $aFirstAndLast;

                if ($aPositions[$sFirst] && $aPositions[$sLast]
                    && $aPositions[$sFirst] != '?' && $aPositions[$sLast] != '?') {
                    // We only check the positions if the first and last value are
                    //  not empty strings or question marks.
                    $sIntronicFirst = $sFirst . 'Intron';
                    $sIntronicLast = $sLast . 'Intron';

                    if (str_replace('*', '1000000', $aPositions[$sFirst])
                        > str_replace('*', '1000000', $aPositions[$sLast])) {
                        list($aPositions[$sFirst], $aPositions[$sIntronicFirst], $aPositions[$sLast], $aPositions[$sIntronicLast]) =
                            array($aPositions[$sLast], $aPositions[$sIntronicLast], $aPositions[$sFirst], $aPositions[$sIntronicFirst]);

                    } elseif ($aPositions[$sFirst] == $aPositions[$sLast]) {
                        if ((int) str_replace('?', '1', $aPositions[$sIntronicFirst])
                            > (int) str_replace('?', '1', $aPositions[$sIntronicLast])) {
                            list($aPositions[$sIntronicFirst], $aPositions[$sIntronicLast]) =
                                array($aPositions[$sIntronicLast], $aPositions[$sIntronicFirst]);

                        } elseif ($sFirst . $sLast == 'AB' || $sFirst . $sLast == 'CD'
                            || ($sFirst . $sLast == 'AC' && !$aPositions['B'] && !$aPositions['D'])) {
                            // If the first and last positions are the same, we can
                            //  only remove the last one if the positions are
                            //  grouped together (e.g. c.1_1del, or c.(1_1)_10del).
                            $aPositions[$sLast] = '';
                            $aPositions[$sIntronicLast] = '';
                        }
                    }
                }
            }
        }

        $sNewVariant = $sBefore . '.' .
            ($aPositions['A'] && $aPositions['B'] ? '(' : '') . $aPositions['A'] . $aPositions['AIntron'] .
            ($aPositions['B'] ? '_' . $aPositions['B'] . $aPositions['BIntron'] . ')' : '') .
            ($aPositions['C'] ? '_' . ($aPositions['D'] ? '(' : '') . $aPositions['C'] . $aPositions['CIntron'] : '') .
            ($aPositions['D'] ? '_' . $aPositions['D'] . $aPositions['DIntron'] . ')' : '') .
            $sAfter;

        if ($sNewVariant != $sVariant) {
            return lovd_fixHGVS($sReference . $sNewVariant, $sType);
        }
    }



    // We're out of things that we can do.
    return $sReference . $sVariant; // Not HGVS.
}





function lovd_formatPosition ($nPosition, $nPositionIntron)
{
    // This function simply formats the given
    //  numbers into a proper position string.
    // Not to be confused with lovd_formatPositions().

    if (!$nPosition || !is_numeric($nPosition)
        || ($nPositionIntron && !is_numeric($nPositionIntron))) {
        return false;
    }

    return $nPosition .
        (!$nPositionIntron? '' :
            ($nPositionIntron < 1? $nPositionIntron : '+' . $nPositionIntron));
}





function lovd_formatPositions ($aVariant)
{
    // This function simply formats the
    //  full position string for the given variant.
    // Not to be confused with lovd_formatPosition().

    $sPositionStart = lovd_formatPosition(
        $aVariant['position_start'],
        (!isset($aVariant['position_start_intron'])? NULL : $aVariant['position_start_intron'])
    );
    $sPositionEnd = lovd_formatPosition(
        $aVariant['position_end'],
        (!isset($aVariant['position_end_intron'])? NULL : $aVariant['position_end_intron'])
    );

    if ($sPositionStart == $sPositionEnd) {
        return $sPositionStart;
    } else {
        return $sPositionStart . '_' . $sPositionEnd;
    }
}





function lovd_modifyVariantPosition ($aVariant, $nOffset = 0, $nLength = 0)
{
    // Takes the start position from the $aVariant array (g. based or c. based),
    //  shifts it using the given offset, adds the given length,
    //  and calculates the new positions (overwriting them).

    if (!isset($aVariant['position_start'])
        || (!is_int($nOffset) && !ctype_digit($nOffset))
        || (!is_int($nLength) && !ctype_digit($nLength))) {
        return false;
    }

    if (!$nLength) {
        $nLength = lovd_getVariantLength($aVariant);
    }

    $aVariant['position_end'] = $aVariant['position_start'];
    $bTranscript = isset($aVariant['position_start_intron']);
    $bIntronic = ($bTranscript && $aVariant['position_start_intron']);
    if ($bTranscript) {
        $aVariant['position_end_intron'] = $aVariant['position_start_intron'];
    }

    $aOffsets = array();
    if ($nOffset) {
        $aOffsets[] = array('position_start', $nOffset);
    }
    if ($nOffset || $nLength > 1) {
        $aOffsets[] = array('position_end', ($nOffset + $nLength - 1));
    }

    foreach ($aOffsets as list($sPosition, $nOffset)) {
        if ($bIntronic) {
            $nPositionIntron = $aVariant[$sPosition . '_intron'] + $nOffset;
            // Compensate for the possibility that we just left the intron.
            if (($aVariant[$sPosition . '_intron'] > 0 && $nPositionIntron < 0)
                || ($aVariant[$sPosition . '_intron'] < 0 && $nPositionIntron > 0)) {
                $nPosition = $aVariant[$sPosition] + $nPositionIntron;
                $nPositionIntron = 0;
            } else {
                $nPosition = $aVariant[$sPosition];
            }
            $aVariant[$sPosition . '_intron'] = $nPositionIntron;

        } else {
            $nPosition = $aVariant[$sPosition] + $nOffset;
        }
        // Compensate for the possibility that we just entered or left the UTR.
        if ($aVariant[$sPosition] > 0 && $nPosition <= 0) {
            $nPosition --;
        } elseif ($aVariant[$sPosition] < 0 && $nPosition >= 0) {
            $nPosition ++;
        }
        $aVariant[$sPosition] = $nPosition;
    }

    return $aVariant;
}
?>