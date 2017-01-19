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


import os
import subprocess
import json
import argparse

DEFAULT_VALID_SEPARATORS = ['=',
                            '=|',
                            '(=)',
                            '<=>',
                            '[=]',
                            '<=>|',
                            '[=]|',
                            '<<=>>',
                            '[[=]]',
                            '<<=>>|',
                            '[[=]]|']

CHECK_SCRIPT_OPTIONS = {
    "check": ["-C", "-L"],
    "mod_trans": ["-L", "-T", "-c", "orig", "-t", "all", "-p", "leave", "-r", "leave", "-i", "original", "-d", "leave", "-s", "delete", "-e", "leave"],
    "mod_ascii": ["-L", "-T", "-c", "simple", "-t", "all", "-p", "leave", "-r", "delete", "-i", "leave", "-d", "delete", "-s", "delete", "-e", "delete"],
    "mod_utf": ["-L", "-T", "-c", "utf", "-t", "all", "-p", "leave", "-r", "delete", "-i", "leave", "-d", "delete", "-s", "delete", "-e", "delete"],
    "dipl_trans": ["-L", "-T", "-S", "-c", "orig", "-t", "historical", "-p", "leave", "-r", "leave", "-i", "original", "-d", "leave", "-s", "original", "-e", "leave"],
    "dipl_utf": ["-L", "-T", "-S", "-c", "utf", "-t", "historical", "-p", "delete", "-r", "delete", "-i", "leave", "-d", "leave", "-s", "leave", "-e", "delete"]
    }

class MainApplication(object):
    def __init__(self, args):
        self.script   = args.bin
        self.transenc = args.enc
        self.token    = [x.strip() for x in args.infile.readlines()]
        self.filename = str(args.infile.name)
        self.valid_separators = self.makeValidSeparators()
        args.infile.close()

    def makeValidSeparators(self):
        if self.transenc:
            transencfile = open(os.path.dirname(self.script) + '/' + self.transenc + '.json', 'r')
            encoding = json.load(transencfile)
            valid_seps = [x+encoding["SEPERATING_CHAR"] for x in encoding["LINE_CONNECTOR_ORIGINAL"]]
            valid_seps.extend(encoding["LINE_CONNECTOR_EDIT"])
            valid_seps.extend(encoding["LINE_CONNECTOR_ORIGINAL"])
            transencfile.close()
            return valid_seps
        return DEFAULT_VALID_SEPARATORS

    def endsWithSeparator(self, line):
        for elem in self.valid_separators:
            if line.endswith(elem):
                return True
        return False

    def throw_error(self, error):
        print(error)
        exit(1)

    def checkTranscription(self):
        if len(self.token) > 1:
            # all lines except the last one have to end in a separator
            if not all([self.endsWithSeparator(line) for line in self.token[:-1]]):
                self.throw_error("Zeilenumbrüche sind nur erlaubt, wenn ihnen ein Trennzeichen vorangeht.")
        elif len(self.token) == 0 or self.token[0] == "":
            self.throw_error("Transkription darf nicht leer sein.")

        if self.endsWithSeparator(self.token[-1]):
            self.throw_error("Transkription darf nicht mit einem Trennzeichen enden.")

        if any([(" " in line) for line in self.token]):
            self.throw_error("Die Transkription darf keine Leerzeichen enthalten.")

        self.callCheckScript()

    def callCheckScript(self):
        command = [self.script]
        command.extend(CHECK_SCRIPT_OPTIONS['check'])
        if self.transenc:
            command.extend(['-E', self.transenc])
        command.append(self.filename)
        try:
            output = subprocess.check_output(command)
        except subprocess.CalledProcessError as inst:
            self.throw_error("Die Prüfung der Transkription ist fehlgeschlagen (Code %i):\n%s\n" % (inst.returncode, inst.output))

    def performConversions(self):
        result = {"mod_trans": [],
                  "mod_ascii": [],
                  "mod_utf":   [],
                  "dipl_trans": [],
                  "dipl_utf":   []
                  }

        for conv_type in result:
            command = [self.script]
            command.extend(CHECK_SCRIPT_OPTIONS[conv_type])
            if self.transenc:
                command.extend(['-E', self.transenc])
            command.append(self.filename)
            try:
                output = subprocess.check_output(command)
            except subprocess.CalledProcessError as inst:
                self.throw_error("Das Umwandeln der Transkription ist fehlgeschlagen (Code %i):\n%s\n" % (inst.returncode, inst.output))
            result[conv_type] = output.strip().split("\n")

        result["dipl_breaks"] = []
        for dipl in result["dipl_trans"]:
            result["dipl_breaks"].append(1 if self.endsWithSeparator(dipl) else 0)

        return result

    def run(self):
        self.checkTranscription()
        result = self.performConversions()
        print(json.dumps(result))

if __name__ == '__main__':
    description = "Parses a file containing a single token in ReF/Anselm transcription format, checks it for validity, and performs all conversions.  Intended to be called from within CorA."
    epilog = ""
    parser = argparse.ArgumentParser(description=description, epilog=epilog)
    parser.add_argument('infile',
                        metavar='INPUT',
                        nargs='?',
                        type=argparse.FileType('r'),
                        help='Input file')
    parser.add_argument('-b', '--bin',
                        type=str,
                        required=True,
                        help='Path to the ruby check script')
    parser.add_argument('-E', '--enc',
                        type=str,
                        help='Optional transcription encoding file to use (must be in the same directory as the check script)')
#    parser.add_argument('-e', '--encoding',
#                        default='utf-8',
#                        help='Encoding of the input file (default: utf-8)')

    args = parser.parse_args()

    # launching application ...
    MainApplication(args).run()
