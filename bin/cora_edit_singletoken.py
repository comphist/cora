#!/usr/bin/python
# -*- coding: utf-8 -*-

import os
import json
import argparse

def splitAt(token, symbol):
    result = token.split(symbol)
    if len(result) < 2:
        return result
    return [x+symbol for x in result[:-1]] + [result[-1]]

class MainApplication(object):
    def __init__(self, args):
        self.split = args.split
        self.lines = [x.strip() for x in args.infile.readlines()]
        self.token = ' '.join(self.lines)
        args.infile.close()

    def throw_error(self, error):
        print(error)
        exit(1)

    def performConversions(self):
        result = {}
        if self.split:
            modern = self.token.split('|')
            result['mod_ascii'] = result['mod_utf'] = \
                [m.replace('#', '') for m in modern]
            result['mod_trans'] = [m+'|' for m in modern[:-1]] + [modern[-1]]
            dipl = self.token.split('#')
            result['dipl_utf'] = [d.replace('|', '') for d in dipl]
            result['dipl_trans'] = [d+'#' for d in dipl[:-1]] + [dipl[-1]]
            result['dipl_breaks'] = [0] * len(dipl)
        else:
            result['mod_trans'] = result['mod_ascii'] = result['mod_utf'] = \
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
                        type=argparse.FileType('r'),
                        help='Input file')
    parser.add_argument('-s', '--split',
                        action='store_true',
                        default=False,
                        help='Parse pipe (|) and hash (#) as tokenization symbols')
#    parser.add_argument('-e', '--encoding',
#                        default='utf-8',
#                        help='Encoding of the input file (default: utf-8)')

    args = parser.parse_args()

    # launching application ...
    MainApplication(args).run()
