#!/usr/bin/python
# -*- coding: utf-8 -*-

import os
import json
import argparse

class MainApplication(object):
    def __init__(self, args):
        self.token    = [x.strip() for x in args.infile.readlines()]
        args.infile.close()

    def throw_error(self, error):
        print(error)
        exit(1)

    def performConversions(self):
        result = {"mod_trans": [],
                  "mod_ascii": [],
                  "mod_utf":   [],
                  "dipl_trans": [],
                  "dipl_utf":   []
                  }

        for elem in self.token:            
            for conv_type in result:
                result[conv_type].append(elem)

        result["dipl_breaks"] = [1 for i in range(len(self.token)-1)]
        result["dipl_breaks"].append(0)
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
#    parser.add_argument('-e', '--encoding',
#                        default='utf-8',
#                        help='Encoding of the input file (default: utf-8)')

    args = parser.parse_args()

    # launching application ...
    MainApplication(args).run()
