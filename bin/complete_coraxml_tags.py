#!/usr/bin/python
# coding=utf-8

import os
import argparse
from lxml import etree

description = "Replaces incomplete POS tags in a CorA XML file according to a given tag list."
epilog = "All POS tags that are missing required morphology information are replaced with the _first_ matching POS tag (including morphology) in the supplied tagset.  The output is a CorA XML file identical to the input file except for those completed POS tags."
parser = argparse.ArgumentParser(description=description, epilog=epilog)
parser.add_argument('infile',
                    metavar='CORA_XML',
                    type=argparse.FileType('r'),
                    help='CorA XML file')
parser.add_argument('tagset',
                    metavar='TAGSET_TXT',
                    type=argparse.FileType('r'),
                    help='Text file containing one valid POS tag per line')

args = parser.parse_args()

# XML file
xmltree = etree.parse(args.infile)
# Tagset file
tagset = [line.rstrip() for line in args.tagset]

# Iterate ...
xmlroot = xmltree.getroot()
for mod in xmlroot.iter('pos'):
    tag = mod.get('tag')
    if tag not in tagset:
        tag = tag + '.'
        for ctag in tagset:
            if ctag.startswith(tag):
                mod.set('tag', ctag)
                break

print (etree.tostring(xmlroot, encoding="utf-8", xml_declaration=True))
