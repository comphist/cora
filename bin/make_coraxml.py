#!/usr/bin/python
# -*- coding: utf-8 -*-

import os, sys
import argparse
from collections import defaultdict
from lxml import etree
from lxml.builder import ElementMaker

EM = ElementMaker()
ROOT   = EM.text
HEADER = EM.header
TOKEN  = EM.token
DIPL   = EM.dipl
MODERN = EM.mod
NORM   = EM.norm
COMMENT = EM.comment
LEMMA  = EM.lemma
POS    = EM.pos

LAYOUT = EM.layoutinfo
PAGE   = EM.page
COLUMN = EM.column
LINE   = EM.line

class CoraConverter(object):
    def __init__(self, args):
        self.input     = args.infile
        self.inputtype = args.input
        self.inputenc  = args.encoding
        self.outputenc = 'utf-8'
        self.norm      = args.normfile  or []

    def parse_textual_input(self):
        """Parse input data."""
        inputenc     = self.inputenc
        taggeroutput = (self.inputtype == 'tabbed')
        tokcount, sentcount = 0, 1

        for inputdata in map(None, self.input, self.norm):
            data = defaultdict(str)
            data['is_comment'] = False
            (line, norm) = inputdata

            line = line.decode(inputenc).strip()
            if not line:
                sentcount += 1
                continue

            if line.startswith("%%"): # comment
                data['is_comment'] = True
                data['comment'] = line[2:].strip()
                yield data
                continue

            tokcount += 1
            data['token_id']    = 't%i' % tokcount
            data['token_count'] = str(tokcount)
            if norm:
                # normalized word form
                data['norm'] = norm.decode(inputenc).strip()
            if taggeroutput:
                # tagger input -- parse POS/INFL
                data['sent_id'] = 's%i' % sentcount
                if '\t' in line:
                    (data['dipl'], pos) = line.split('\t')
                else:
                    (data['dipl'], pos) = (line, '')
            else:
                # plain one-word-per-line input
                data['dipl'] = line

            yield data

    def convert(self):
        """Build the XML tree."""
        tokens = []
        previous_comment = False
        for line in self.parse_textual_input():
            if line['is_comment']:
                tokens.append(COMMENT(line['comment'], type="K"))
                previous_comment = line['comment']
                continue

            tok_attribs = {'id':    line['token_id'],
                           'trans': line['dipl']}
            dipl_attribs = {'id':    line['token_id'] + "_d1",
                            'trans': line['dipl']}
            mod_attribs = {'id':    line['token_id'] + "_m1",
                           'trans': line['dipl'],
                           'ascii': line['dipl'],
                           'utf':   line['dipl']}
            annotations = []
            if 'lemma' in line:
                annotations.append(LEMMA(tag=line['lemma']))
            if 'pos' in line:
                annotations.append(POS(tag=line['pos']))
            if 'norm' in line:
                annotations.append(NORM(tag=line['norm']))
            if previous_comment:
                annotations.append(EM('cora-comment', previous_comment))
                previous_comment = False

            tokens.append(TOKEN(DIPL(**dipl_attribs),
                                MODERN(*annotations, **mod_attribs), **tok_attribs))
            last_dipl_id = dipl_attribs['id']

        layout = LAYOUT(PAGE(id="p1", range="c1", no="1"),
                        COLUMN(id="c1", range="l1"),
                        LINE(id="l1", name="1", range="t1_d1.."+last_dipl_id))
        tree = ROOT(HEADER(), layout, *tokens)
        return tree

    def run(self):
        xml = self.convert()
        if xml is not None:
            print etree.tostring(xml, pretty_print=True, xml_declaration=True, encoding=self.outputenc)

if __name__ == '__main__':
    description = "Generate CorA XML format from a given input file."
    epilog = "Input files are supposed to contain the historical tokens as they should appear in CorA, one token per line."
    parser = argparse.ArgumentParser(description=description, epilog=epilog)
    parser.add_argument('infile',
                        metavar='INPUT',
                        nargs='?',
                        type=argparse.FileType('r'),
                        default=sys.stdin,
                        help='Input file (default: STDIN)')
    parser.add_argument('-i', '--input',
                        choices=('plain','tabbed'),
                        default='plain',
                        help='Type of the input file (default: plain)')
    parser.add_argument('-e', '--encoding',
                        default='utf-8',
                        help='Encoding of the input file (default: utf-8)')
    parser.add_argument('-n', '--norm',
                        dest='normfile',
                        metavar='FILE',
                        type=argparse.FileType('r'),
                        help='Text file containing the normalized word forms (optional)')

    args = parser.parse_args()

    # launching application ...
    try:
        CoraConverter(args).run()
    except SystemExit:
        sys.stderr.write("\nThere were errors.\n")
        sys.stderr.flush()
