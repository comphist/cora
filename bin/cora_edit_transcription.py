#!/usr/bin/python
# -*- coding: utf-8 -*-

#import os, sys
import subprocess
import json
import argparse

VALID_SEPARATORS = ['=',
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

def endsWithSeparator(line):
    for elem in VALID_SEPARATORS:
        if line.endswith(elem):
            return True
    return False

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
        self.token    = [x.strip() for x in args.infile.readlines()]
        self.filename = str(args.infile.name)
        args.infile.close()

    def throw_error(self, error):
        print(error)
        exit(1)

    def checkTranscription(self):
        if len(self.token) > 1:
            # all lines except the last one have to end in a separator
            if not all([endsWithSeparator(line) for line in self.token[:-1]]):
                self.throw_error("Zeilenumbrüche sind nur erlaubt, wenn ihnen ein Trennzeichen vorangeht.")
        elif len(self.token) == 0 or self.token[0] == "":
            self.throw_error("Transkription darf nicht leer sein.")

        if endsWithSeparator(self.token[-1]):
            self.throw_error("Transkription darf nicht mit einem Trennzeichen enden.")

        if any([(" " in line) for line in self.token]):
            self.throw_error("Die Transkription darf keine Leerzeichen enthalten.")

        self.callCheckScript()

    def callCheckScript(self):
        command = ['ruby', self.script]
        command.extend(CHECK_SCRIPT_OPTIONS['check'])
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
            command = ['ruby', self.script]
            command.extend(CHECK_SCRIPT_OPTIONS[conv_type])
            command.append(self.filename)
            try:
                output = subprocess.check_output(command)
            except subprocess.CalledProcessError as inst:
                self.throw_error("Das Umwandeln der Transkription ist fehlgeschlagen (Code %i):\n%s\n" % (inst.returncode, inst.output))
            result[conv_type] = output.strip().split("\n")

        result["dipl_breaks"] = []    
        for dipl in result["dipl_trans"]:
            result["dipl_breaks"].append(1 if endsWithSeparator(dipl) else 0)
            
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
#    parser.add_argument('-e', '--encoding',
#                        default='utf-8',
#                        help='Encoding of the input file (default: utf-8)')

    args = parser.parse_args()

    # launching application ...
    MainApplication(args).run()
