#!/usr/bin/python3

# Copyright (C) 2017 Marcel Bollmann <bollmann@linguistics.rub.de>
#
# Permission is hereby granted, free of charge, to any person obtaining a copy of
# this software and associated documentation files (the "Software"), to deal in
# the Software without restriction, including without limitation the rights to
# use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
# the Software, and to permit persons to whom the Software is furnished to do so,
# subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
# FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
# COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
# IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
# CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

import argparse
from difflib import SequenceMatcher
import itertools as it
import logging
from lxml import etree
from mblevenshtein import LevenshteinAligner
import sys

log = logging
aligner = LevenshteinAligner()

class ApplicationException(Exception):
    pass

def posstring(a, b, c, d):
    l = str(a) if a == b or a == b-1 else '-'.join((str(a), str(b)))
    r = str(c) if c == d or c == d-1 else '-'.join((str(c), str(d)))
    return ','.join((l, r))

def levenshtein(a, b):
    d, _ = aligner.perform_levenshtein(a, b)
    return d / max(len(a), len(b))

def extract_mod_ascii(tree, fname):
    mods = list(tree.getroot().iter('mod'))
    asciis = [mod.get('ascii') for mod in mods]
    if any(m is None for m in asciis):
        log.error("There were empty 'ascii' attributes in {} file!".format(fname))
        exit(1)
    return mods, asciis

def make_diff(a, b):
    matcher = SequenceMatcher(a=a, b=b, autojunk=False)
    opcodes = matcher.get_opcodes()
    log.info("Similarity ratio: {:.4f}".format(matcher.ratio()))
    return opcodes

def merge_mod(a, b):
    for child in a:
        if b.find(child.tag) is not None:
            b.replace(b.find(child.tag), child)
        else:
            b.append(child)
    if a.get('checked') is not None:
        b.set('checked', a.get('checked'))

def flag_change(tag, a_str, b):
    if tag == "replace":
        comment = "#>MATCH: {}".format(a_str)
    elif tag == "delete":
        comment = "#>DEL: {}".format(a_str)
    elif tag == "delete_after":
        comment = "#>DEL_AFTER: {}".format(a_str)
    elif tag == "insert":
        comment = "#>INS"
    if b.find("cora-flag[@name='general error']") is None:
        b.append(etree.Element('cora-flag', name="general error"))
    if b.find("comment") is not None:
        tag = b.find("comment")
        tag.set('tag', ' '.join((tag.get('tag'), comment)))
    else:
        b.append(etree.Element('comment', tag=comment))

def disambiguate_opcodes(modsA, modsB, opcodes, maxspan=0):
    new_opcodes = []
    for tag, i1, i2, j1, j2 in opcodes:
        if tag == "replace" and (i2-i1) != (j2-j1):
            assert i1 != i2 and j1 != j2
            if (i2-i1) > maxspan or (j2-j1) > maxspan:
                raise ApplicationException("Span of replace op exceeds limit: {}"\
                                           .format(posstring(i1, i2, j1, j2)))
            distances = []
            mappings = {}
            # calculate Levenshtein distance for all pairs of tokens
            for i, j in it.product(range(i1, i2), range(j1, j2)):
                d = levenshtein(modsA[i].get('ascii'), modsB[j].get('ascii'))
                distances.append((i, j, d))
            distances.sort(key=lambda x: x[-1])
            # extract pairs with lowest distance, so that each token is only
            # ever chosen once
            while distances:
                best = distances[0]
                mappings[best[0]] = best[1]
                distances = [d for d in distances if d[0] != best[0] and d[1] != best[1]]
            # produce new opcodes
            ix, jx = i1, j1
            while ix < i2:
                if ix not in mappings:
                    new_opcodes.append(('delete', ix, ix+1, jx, jx))
                    ix += 1
                else:
                    target_j = mappings[ix]
                    if target_j > jx:
                        new_opcodes.append(('insert', ix, ix, jx, target_j))
                        jx = target_j
                    new_opcodes.append(('replace', ix, ix+1, jx, jx+1))
                    ix += 1
                    jx += 1
            if jx < j2:
                new_opcodes.append(('insert', ix, ix, jx, j2))
        else:
            new_opcodes.append((tag, i1, i2, j1, j2))
    return new_opcodes

def perform_merge(modsA, modsB, opcodes, flag_changes=False):
    for tag, i1, i2, j1, j2 in opcodes:
        if tag in ("equal", "replace"):
            assert (i2-i1) == (j2-j1)
            for i, j in zip(range(i1, i2), range(j1, j2)):
                merge_mod(modsA[i], modsB[j])
                if tag == "replace" and flag_changes:
                    flag_change("replace", modsA[i].get('ascii'), modsB[j])
        elif tag == "delete" and flag_changes:
            del_mods = ' '.join(m.get('ascii') for m in modsA[i1:i2])
            try:
                flag_change("delete", del_mods, modsB[j1])
            except IndexError:
                flag_change("delete_after", del_mods, modsB[j1-1])
        elif tag == "insert" and flag_changes:
            for j in range(j1, j2):
                flag_change("insert", None, modsB[j])
        # log changes
        if tag != "equal":
            msg = "{:19s}  {:20s} |  {:20s}"
            log.info(msg.format(
                posstring(i1, i2, j1, j2),
                ' '.join(mod.get('ascii') for mod in modsA[i1:i2]),
                ' '.join(mod.get('ascii') for mod in modsB[j1:j2])
                ))


if __name__ == '__main__':
    description = "Merges annotation from a CorA-XML file into another one."
    epilog = ("The output is TARGET_XML with additional annotations copied over "
              "from SOURCE_XML.  A log output of changes is written to STDERR.")
    parser = argparse.ArgumentParser(description=description, epilog=epilog)
    parser.add_argument('fileA',
                        metavar='SOURCE_XML',
                        type=argparse.FileType('r'),
                        help='XML file with annotations to merge')
    parser.add_argument('fileB',
                        metavar='TARGET_XML',
                        type=argparse.FileType('r'),
                        help='XML file to merge into')
    parser.add_argument('-f', '--flag-changes',
                        action='store_true',
                        default=False,
                        help=('Set the "general error" flag and add a comment to'
                              ' the XML output of all tokens where changes'
                              ' occurred'))
    parser.add_argument('-m', '--maximum-span',
                        metavar='N',
                        type=int,
                        default=5,
                        help=('Maximum span for matching consecutive replacements'
                              ' via Levenshtein distance; will produce an error'
                              ' if longer spans occur (default: %(default)i)'))
    parser.add_argument('-n', '--dry-run',
                        action='store_true',
                        default=False,
                        help=('Only calculate merge and generate log info, do not'
                              ' output merged XML document'))
    parser.add_argument('-q', '--quiet',
                        action='store_true',
                        default=False,
                        help=('Don\'t print log messages to STDERR except in case'
                              ' of errors'))

    args = parser.parse_args()
    if args.maximum_span < 0:
        args.maximum_span = 2147483647

    log_level = logging.ERROR if args.quiet else logging.INFO
    log.basicConfig(format='%(levelname)-8s %(message)s', level=log_level)

    fileA = etree.parse(args.fileA)
    fileB = etree.parse(args.fileB)
    try:
        modsA, asciiA = extract_mod_ascii(fileA, 'source')
        modsB, asciiB = extract_mod_ascii(fileB, 'target')
        opcodes = make_diff(asciiA, asciiB)
        opcodes = disambiguate_opcodes(modsA, modsB, opcodes, maxspan=args.maximum_span)
        perform_merge(modsA, modsB, opcodes, flag_changes=args.flag_changes)
    except ApplicationException as e:
        log.error(str(e))
        exit(1)

    if not args.dry_run:
        document = etree.tostring(fileB,
                                  encoding="utf-8",
                                  xml_declaration=True,
                                  pretty_print=True)
        print(document.decode("utf-8"))
