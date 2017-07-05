#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright (C) 2013-2017 Marcel Bollmann <bollmann@linguistics.rub.de>
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

import os, sys
import argparse
from string import punctuation
from lxml import etree

def utf8_encode(text):
    try:
        return text.encode("utf-8")
    except UnicodeError:
        return text

class MainApplication(object):
    empty = ["", "--"]

    def __init__(self, args):
        self.args = args

    def getShifttagRanges(self, root, tags):
        ranges = {}  # {range_begin: (range_end, tag), ...}
        shifttags = root.find('shifttags')
        if shifttags is None or len(tags) == 0:
            return {}
        for tagname in tags:
            for shifttag in shifttags.iter(tagname):
                # deals with both range="t1..t2" and range="t1" attributes:
                r = shifttag.get('range').split('..')
                rstart, rend = r[0], r[-1]
                ranges[rstart] = (rend, tagname)
        return ranges

    def extractData(self, root, ranges):
        current_range = None
        annotypes = self.args.types or []
        formtypes = self.args.forms or []
        for tok in root.iter('token'):
            tok_id = tok.get('id')
            if current_range is None and tok_id in ranges:
                current_range = ranges[tok_id]
            for mod in tok.iter('mod'):
                mod_id = mod.get('id')
                columns = []
                for formtype in formtypes:
                    form = mod.get(formtype) or ""
                    columns.append(form)
                for annotype in annotypes:
                    node = mod.find(annotype)
                    if node is not None and node.get('tag') is not None:
                        columns.append(node.get('tag'))
                    else:
                        columns.append(self.empty[0])
                yield {'columns': columns,
                       'tokid': tok_id,
                       'modid': mod_id,
                       'in_range': current_range[1] if current_range else None}
            if current_range and tok_id == current_range[0]:
                current_range = None

    def run(self):
        try:
            tree = etree.parse(self.args.infile)
            root = tree.getroot()
        except etree.XMLSyntaxError, e:
            sys.stderr.write("Error parsing XML file:\n")
            sys.stderr.write(e.message)
            sys.stderr.write("\n")
            exit(1)

        sep = "\t"
        remove_punct = self.args.remove_punct
        remove_empty = self.args.remove_empty
        remove_shift = bool(self.args.remove_shift)
        downcase = self.args.downcase

        if remove_shift:
            exclude_ranges = self.getShifttagRanges(root, self.args.remove_shift)
        else:
            exclude_ranges = {}

        for row in self.extractData(root, exclude_ranges):
            if remove_shift and row['in_range']:
                continue
            if remove_empty and any((x in self.empty for x in row['columns'])):
                continue
            if remove_punct and all((c in punctuation for c in row['columns'][0])):
                continue
            output = row['columns']
            if downcase:
                output = [x.lower() for x in output]

            sys.stdout.write(utf8_encode(sep.join(output)))
            sys.stdout.write("\n")

if __name__ == '__main__':
    description = "Extracts annotation from CorA XML files."
    epilog = """Without any arguments, the output consists of the modern token
                in its 'ascii' representation.  Use the -f parameter to change
                or add representations, and the -t parameter to add annotations
                to the output."""
    parser = argparse.ArgumentParser(description=description, epilog=epilog)
    required = parser.add_argument_group('Content options')
    required.add_argument('infile',
                          metavar='FILE',
                          nargs='?',
                          type=argparse.FileType('r'),
                          default=sys.stdin,
                          help='CorA XML file (default: STDIN)')
    required.add_argument('-f', '--forms',
                          metavar='FORM',
                          type=str,
                          nargs='+',
                          default=['ascii'],
                          help='''Wordforms to export
                                  (default: ascii)''')
    required.add_argument('-t', '--types',
                          metavar='TYPE',
                          type=str,
                          nargs='+',
                          help='''Annotation types to export
                                  (as names of the respective XML tags)''')
    #parser.add_argument('-s', '--separator',
    #                    type=str,
    #                    default="\t",
    #                    help='Column separator (default: tab)')
    #parser.add_argument('-e', '--empty',
    #                    type=str,
    #                    default="",
    #                    help='Filler to use if annotation is not found (default: empty string)')
    preprocess = parser.add_argument_group('Preprocessing options',
                        '''These options provide commonly needed filtering
                            and preprocessing mechanisms.''')
    preprocess.add_argument('-d', '--downcase',
                            action='store_true',
                            help='Downcase all characters')
    preprocess.add_argument('-p', '--remove-punct',
                            action='store_true',
                            help='''Exclude tokens that consist only
                                    of punctuation symbols''')
    preprocess.add_argument('-e', '--remove-empty',
                            action='store_true',
                            help='''Exclude tokens where one of the
                                    selected annotations is missing''')
    preprocess.add_argument('-s', '--remove-shift',
                            metavar='SHIFTTAG',
                            type=str,
                            nargs='+',
                            help='''Exclude tokens in the range of
                                    the given shifttags (e.g., "fm")''')

    args = parser.parse_args()

    # launching application ...
    MainApplication(args).run()
