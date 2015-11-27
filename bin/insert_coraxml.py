#!/usr/bin/python
# -*- coding: utf-8 -*-
# Marcel Bollmann, 2013

import sys
from lxml import etree
import argparse

class MainApplication:
    def __init__(self, args):
        self.args = args
        self.xmlfile = args.infile

    def output(self, text):
        try:
            print text.encode("utf-8")
        except UnicodeError:
            print text

    def nextnorm(self):
        try:
            line = self.args.norm.next().decode("utf-8").strip()
        except (StopIteration, AttributeError) as e:
            return [None]*4
        return (line.split('\t') + [None]*4)[:4]

    def nextpos(self):
        try:
            line = self.args.pos.next().decode("utf-8").strip()
        except (StopIteration, AttributeError) as e:
            return [None]*3
        if '\t' not in line:
            return (line, '', '')
        (asc, tag) = line.split('\t')
        (pos, morph) = (tag, None)      # XML currently expects no separation between POS and Morph
        #if '.' in tag and tag != "$.":
        #    (pos, morph) = tag.split('.', 1)
        #else:
        #    (pos, morph) = (tag, None)
        return (asc, pos, morph)

    def nextalign(self):
        try:
            line = self.args.align.next().decode("utf-8").strip()
        except (StopIteration, AttributeError) as e:
            return [None]*2
        if '\t' not in line:
            return (line, '', '')
        return line.split('\t')
        
    def parse_xml(self):
        parser = etree.XMLParser(resolve_entities=True)
        self.xml = etree.parse(self.xmlfile, parser=parser)

    def broad_match(self, one, two):
        if(not one and two):
            return False
        t1 = one.strip().lower().replace(u"ß", u"ss")
        t2 = two.strip().lower().replace(u"ß", u"ss")
        return (t1 == t2)

    def process_xml(self):
        root = self.xml.getroot()
        for mod in root.findall("*/mod"):
            this_asc = mod.get('ascii')
            (asc, norm, norm_broad, norm_type) = self.nextnorm()
            if asc:
                if this_asc != asc and not self.broad_match(this_asc, asc):
                    sys.stderr.write("*** Simple form mismatch in norm: %s / %s\n" % (this_asc, asc))
                    if not self.args.ignore:
                        exit(1)
                if norm:
                    mod.append(etree.Element("norm", {"tag": norm}))
                if norm_broad:
                    mod.append(etree.Element("norm_broad", {"tag": norm_broad}))
                if norm_type:
                    if norm_type not in ('s','f','x'):
                        sys.stderr.write("*** Illegal norm type: %s\n" % norm_type)
                        exit(1)
                    mod.append(etree.Element("norm_type", {"tag": norm_type}))
            (asc, pos, morph) = self.nextpos()
            if asc:
                if this_asc != asc and not self.broad_match(this_asc, asc):
                    sys.stderr.write("*** Simple form mismatch in POS: %s / %s\n" % (this_asc, asc))
                    if not self.args.ignore:
                        exit(1)
                if pos:
                    mod.append(etree.Element("pos", {"tag": pos}))
                if morph:
                    mod.append(etree.Element("morph", {"tag": morph}))
            (asc, align) = self.nextalign()
            if asc:
                if this_asc != asc and not self.broad_match(this_asc, asc):
                    sys.stderr.write("*** Simple form mismatch in align: %s / %s\n" % (this_asc, asc))
                    if not self.args.ignore:
                        exit(1)
                if align:
                    mod.append(etree.Element("norm_align", {"tag": align}))
                    
                    
    def run(self):
        self.parse_xml()
        self.process_xml()
        print etree.tostring(self.xml)

if __name__ == '__main__':
    description = "Adds annotations to an existing CorA XML file."
    epilog = "The first column is always expected to be the simplified tokens.  If they don't match those in the XML file, the script will throw an error unless the -i option is specified."
    parser = argparse.ArgumentParser(description=description, epilog=epilog)
    parser.add_argument("infile",
                        help="CorA XML file to process")
    parser.add_argument("--norm",
                        "-n",
                        type=argparse.FileType('r'),
                        help="Normalization data in three-column format")
    parser.add_argument("--pos",
                        "-p",
                        type=argparse.FileType('r'),
                        help="POS tag data in two-column format")
    parser.add_argument("--align",
                        "-a",
                        type=argparse.FileType('r'),
                        help="Character alignment data in two-column format")
    parser.add_argument("--ignore-mismatch",
                        "-i",
                        action="store_true",
                        dest="ignore",
                        default=False,
                        help="Ignore mismatching simplifications")

    args = parser.parse_args()

    MainApplication(args).run()
