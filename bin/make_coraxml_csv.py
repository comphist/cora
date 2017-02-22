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
from collections import defaultdict
from lxml import etree
from lxml.builder import ElementMaker

EM = ElementMaker()

class CoraCSVConverter(object):
    tokens = []
    fields = []

    def __init__(self, args):
        self.data      = list(args.infile)
        self.inputenc  = args.encoding
        self.outputenc = 'utf-8'

    def read_header(self):
        self.fields = self.data.pop(0).decode(self.inputenc).strip("\r\n").split("\t")

    def parse_data(self):
        def make_elems(mytoken):
            elems = []
            for (i, dipl) in enumerate(mytoken['dipls']):
                if (not dipl.get('trans')) and (not dipl.get('utf')):
                    continue
                dipl.set('id', token['id'] + "_d" + str(i+1))
                elems.append(dipl)
            for (i, mod) in enumerate(mytoken['mods']):
                if (not mod.get('trans')) and (not mod.get('utf')):
                    continue
                mod.set('id', token['id'] + "_m" + str(i+1))
                elems.append(mod)
            return elems

        self.read_header()
        current_token = {'dipls': [], 'mods': []}
        token_count = 1
        self.data.append("")
        for line in self.data:
            line = line.decode(self.inputenc).strip("\r\n") # only strip newlines
            if line.strip(): # but here, check if it's sth other than whitespace
                dipl, mod, annos = {}, {}, []
                for (field, value) in zip(self.fields, line.split("\t")):
                    if field.startswith('dipl_'):
                        dipl[field[5:]] = value
                    elif field.startswith('mod_'):
                        mod[field[4:]] = value
                    else:
                        annos.append(EM(field, tag=value))
                if dipl:
                    current_token['dipls'].append(EM.dipl(**dipl))
                if mod:
                    current_token['mods'].append(EM.mod(*annos, **mod))
            elif dipl or mod:
                token = {'id': "t" + str(token_count),
                         'trans': ''.join([m.get('trans') for m in current_token['mods']])}
                elems = make_elems(current_token)
                self.tokens.append(EM.token(*elems, **token))
                current_token = {'dipls': [], 'mods': []}
                token_count += 1

    def convert(self):
        for elem in self.tokens[0]:
            if elem.tag == 'dipl':
                first_dipl_id = elem.get('id')
                break
        for elem in self.tokens[-1]:
            if elem.tag == 'dipl':
                last_dipl_id = elem.get('id')
                # no break! so we actually get the last one
        layout = EM.layoutinfo(EM.page(id="p1", range="c1", no="1"),
                               EM.column(id="c1", range="l1"),
                               EM.line(id="l1", name="1",
                                       range='..'.join([first_dipl_id, last_dipl_id])
                                       ))
        tree = EM.text(EM.header(), layout, *(self.tokens))
        return tree

    def run(self):
        self.parse_data()
        xml = self.convert()
        if xml is not None:
            print etree.tostring(xml, pretty_print=True, xml_declaration=True, encoding=self.outputenc)

if __name__ == '__main__':
    description = "Generate CorA XML format from a CSV file."
    epilog = ("The first line of the CSV file must be the header; recognized "
              "fields are 'dipl_trans', 'dipl_utf', 'mod_trans' etc., while "
              "all other fields are treated as annotation layers.  Successive "
              "lines are considered to belong to the same token; empty lines "
              "mark token boundaries.")
    parser = argparse.ArgumentParser(description=description, epilog=epilog)
    parser.add_argument('infile',
                        metavar='INPUT',
                        nargs='?',
                        type=argparse.FileType('r'),
                        default=sys.stdin,
                        help='Input file (default: STDIN)')
    parser.add_argument('-e', '--encoding',
                        default='utf-8',
                        help='Encoding of the input file (default: utf-8)')

    args = parser.parse_args()

    # launching application ...
    try:
        CoraCSVConverter(args).run()
    except SystemExit:
        sys.stderr.write("\nThere were errors.\n")
        sys.stderr.flush()
