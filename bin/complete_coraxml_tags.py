#!/usr/bin/python
# coding=utf-8

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
