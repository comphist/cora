#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright (C) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
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


import sys
import json
import argparse

def splitAt(token, symbol):
    result = token.split(symbol)
    if len(result) < 2:
        return result
    return [x+symbol for x in result[:-1]] + [result[-1]]

class MainApplication(object):
    def __init__(self, args):
        if args.split:
            self.split_mod = "|"
            self.split_dipl = "#"
        else:
            self.split_mod = args.split_mod
            self.split_dipl = args.split_dipl
        self.lines = [x.strip() for x in args.infile.readlines()]
        self.token = ' '.join(self.lines)
        args.infile.close()

    def throw_error(self, error):
        print(error)
        exit(1)

    def performConversions(self):
        result = {}
        if self.split_mod:
            modern = self.token.split(self.split_mod)
            result['mod_ascii'] = result['mod_utf'] = \
                [m.replace(self.split_dipl, '') for m in modern]
            result['mod_trans'] = [m+self.split_mod for m in modern[:-1]] + [modern[-1]]
        else:
            result['mod_trans'] = result['mod_ascii'] = \
                result['mod_utf'] = [self.token]

        if self.split_dipl:
            dipl = self.token.split(self.split_dipl)
            result['dipl_utf'] = [d.replace(self.split_mod, '') for d in dipl]
            result['dipl_trans'] = [d+self.split_dipl for d in dipl[:-1]] + [dipl[-1]]
            result['dipl_breaks'] = [0] * len(dipl)
        else:
            result['dipl_trans'] = result['dipl_utf'] = [self.token]
            result['dipl_breaks'] = [0]

        return result

    def run(self):
        result = self.performConversions()
        print(json.dumps(result))

if __name__ == '__main__':
    description = "Reads a file containing a single token and returns it unchanged in JSON format.  Intended to be called from within CorA."
    epilog = ""
    parser = argparse.ArgumentParser(description=description, epilog=epilog)
    parser.add_argument('infile',
                        metavar='INPUT',
                        nargs='?',
                        default=sys.stdin,
                        type=argparse.FileType('r'),
                        help='Input file')
    # exists for legacy reasons:
    parser.add_argument('-s', '--split',
                        action='store_true',
                        default=False,
                        help=('Parse pipe (|) and hash (#) as tokenization symbols; '
                              'equivalent to --split-mod="|" --split-dipl="#"'))
    parser.add_argument('--split-mod',
                        default='',
                        type=str,
                        help='Symbol to split into two moderns (default: None)')
    parser.add_argument('--split-dipl',
                        default='',
                        type=str,
                        help='Symbol to split into two dipls (default: None)')
#    parser.add_argument('-e', '--encoding',
#                        default='utf-8',
#                        help='Encoding of the input file (default: utf-8)')

    arguments = parser.parse_args()

    # launching application ...
    MainApplication(arguments).run()
