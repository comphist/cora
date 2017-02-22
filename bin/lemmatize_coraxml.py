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
from lxml import etree
import argparse
import glob

class MainApplication:
    def __init__(self, args):
        self.args = args
        self.xmlfile = args.infile
        self.lemma = {}

    def output(self, text):
        try:
            print text.encode("utf-8")
        except UnicodeError:
            print text

    def lemmatize(self, word):
        if word in self.lemma:
            return self.lemma[word]
        elif word.lower() in self.lemma:
            return self.lemma[word.lower()]
        elif '-' in word:
            final = word.split("-")[-1]
            if final in self.lemma:
                return self.lemma[final]
        else:
            for i in range(1,len(word)-4):
                part = word[i:]
                if part in self.lemma:
                    return self.lemma[part]
                if part.capitalize() in self.lemma:
                    return self.lemma[part.capitalize()].lower()

        return ""

    def read_lemmalist(self):
        for line in self.args.lemma:
            (word, lemma) = line.strip().decode("utf-8").split("\t")
            self.lemma[word] = lemma

    def parse_xml(self):
        parser = etree.XMLParser(resolve_entities=True)
        self.xml = etree.parse(self.xmlfile, parser=parser)

    def process_xml(self):
        root = self.xml.getroot()
        for mod in root.findall("*/mod"):
            lemma = self.lemmatize(mod.attrib["ascii"])
            if lemma != "":
                mod.append(etree.Element("lemma", {"tag": lemma}))

    def run(self):
        self.read_lemmalist()
        self.parse_xml()
        self.process_xml()
        print etree.tostring(self.xml)

if __name__ == '__main__':
    description = "Adds lemma information to a CorA XML file."
    epilog = ""
    parser = argparse.ArgumentParser(description=description, epilog=epilog)
    parser.add_argument("infile",
                        help="CorA XML file to lemmatize")
    parser.add_argument("--lemma",
                        "-l",
                        required=True,
                        type=argparse.FileType('r'),
                        help="Tab-separated lemma list")

    args = parser.parse_args()

    MainApplication(args).run()
