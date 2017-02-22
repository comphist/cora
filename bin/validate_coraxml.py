#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright (C) 2017 Marcel Bollmann <bollmann@linguistics.rub.de>
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

from __future__ import absolute_import, division, print_function
import os, sys
import argparse
from collections import namedtuple
import logging as log
from lxml import etree

def _map_etree_errorlevel(lvl):
    if lvl == etree.ErrorLevels.FATAL:
        return log.CRITICAL
    elif lvl == etree.ErrorLevels.ERROR:
        return log.ERROR
    elif lvl == etree.ErrorLevels.WARNING:
        return log.WARNING
    else:
        return log.INFO

RangedElement = namedtuple('RangedElement', ['id', 'range'])
ParseError = namedtuple('ParseError',
                        ['filename', 'line', 'column',
                         'domain_name', 'type_name', 'message'])

def index(list_, elem):
    try:
        return list_.index(elem)
    except ValueError:
        return None

class CoraXMLValidator(object):
    def __init__(self, schema):
        self.error_log = []
        self.schema = schema
        self.tree = None

    def _log(self, err, level=log.ERROR):
        def _str(e):
            fmt = "{1}:{2}:{0.domain_name}:{0.type_name}: {0.message}"
            line = e.line if e.column == 0 else "{0.line}:{0.column}".format(e)
            return fmt.format(e, os.path.basename(e.filename), line)
        if isinstance(err, etree._LogEntry):
            level = _map_etree_errorlevel(err.level)
            msg = _str(err)
        elif isinstance(err, ParseError):
            msg = _str(err)
        else:
            msg = str(err)
        self.error_log.append((level, msg))

    def __call__(self, fstream, *args, **kwargs):
        self.error_log = []
        self.set_input(fstream)
        return self.validate(*args, **kwargs)

    def set_input(self, fstream):
        try:
            self.filename = os.path.basename(fstream.name)
            self.tree = etree.parse(fstream)
            self.root = self.tree.getroot()
        except etree.ParseError as err:
            self._error(str(err))

    def _parse_range(self, range):
        if '..' in range:
            return tuple(range.split('..'))
        else:
            return (range, range)

    def _get_ranged_elements(self, xpath):
        elements = []
        for elem in self.root.iter(xpath):
            id_ = elem.get('id')
            range_ = self._parse_range(elem.get('range'))
            elements.append(RangedElement(id_, range_))
        return elements

    def validate(self, skip_ranges=False, skip_trans=False):
        if not self.tree:
            self._log(("{}:0:CORAXML_VALIDATOR: There is no document tree -- "
                       "stopping validation").format(self.filename))
            return False
        if self.schema is not None and not self.schema(self.tree):
            for entry in self.schema.error_log:
                self._log(entry)
            self._log(("{}:0:CORAXML_VALIDATOR: RelaxNG validation failed -- "
                       "stopping validation").format(self.filename))
            return False
        logical_valid = []
        if not skip_ranges:
            logical_valid.append(self.validate_ranges())
            logical_valid.append(self.validate_shiftranges())
        if not skip_trans:
            logical_valid.append(self.validate_trans())
        if not all(logical_valid):
            self._log("{}:0:CORAXML_VALIDATOR: Logical validation failed"\
                      .format(self.filename))
            return False
        return True

    def _validate_id_ranges(self, pointer, pointee):
        i = 0
        valid = True
        pointed_ids, pointed_lines = [], []
        for e in self.root.iter(pointee):
            pointed_ids.append(e.get('id'))
            pointed_lines.append(e.sourceline)
        missing_idx = []
        for elem in self.root.iter(pointer):
            range_from, range_to = self._parse_range(elem.get('range'))
            pos_from = index(pointed_ids, range_from)
            pos_to = index(pointed_ids, range_to)
            checks = ((pos_from, range_from),) if range_from == range_to else \
                ((pos_from, range_from), (pos_to, range_to))
            for (pos, id_) in checks:
                if pos is None:
                    err = ParseError(
                        filename = self.filename, line = elem.sourceline, column = 0,
                        domain_name = "LOGIC_IDRANGES",
                        type_name = "IDRANGE_ERR_NOTFOUND",
                        message = "Pointer to non-existent element <{} id='{}'>"\
                                  .format(pointee, id_)
                        )
                    self._log(err)
                    valid = False
                elif pos < i:
                    err = ParseError(
                        filename = self.filename, line = elem.sourceline, column = 0,
                        domain_name = "LOGIC_IDRANGES",
                        type_name = "IDRANGE_ERR_ORDERING",
                        message = "Pointer to element <{} id='{}'> violates ordering constraint"\
                                  .format(pointee, id_)
                        )
                    self._log(err)
                    valid = False
            if pos_to is not None and pos_to >= i:
                if pos_from is None:
                    missing_idx += list(range(i, pos_to))
                elif pos_from > i:
                    missing_idx += list(range(i, pos_from))
                i = pos_to + 1
        if len(pointed_ids) > i:
            missing_idx += list(range(i, len(pointed_ids)))
        for missing in missing_idx:
            err = ParseError(
                filename = self.filename, line = pointed_lines[missing], column = 0,
                domain_name = "LOGIC_IDRANGES",
                type_name = "IDRANGE_ERR_COVERAGE",
                message = "<{} id='{}'> is not covered by a valid <{}> range"\
                            .format(pointee, pointed_ids[missing], pointer)
                )
            self._log(err)
            valid = False
        return valid

    def validate_ranges(self):
        """Validate that range attributes point to consecutive elements and
        cover the full range of target elements."""
        ranges = [
            ('page', 'column'),
            ('column', 'line'),
            ('line', 'dipl')
            ]
        valid = []
        for (x, y) in ranges:
            valid.append(self._validate_id_ranges(x, y))
        if not all(valid):
            return False
        else:
            return True

    def validate_shiftranges(self):
        """Validate that range attributes point to existing elements, but ignore
        consecutiveness or full coverage."""
        shifttags = self.root.find("shifttags")
        valid = True
        pointee = "token"
        if shifttags is None:
            return True
        for elem in shifttags.iterchildren():
            range_from, range_to = self._parse_range(elem.get('range'))
            elem_from = self.root.find("{}[@id='{}']".format(pointee, range_from))
            elem_to = self.root.find("{}[@id='{}']".format(pointee, range_to))
            missing = range_from if elem_from is None else range_to if elem_to is None else None
            if missing is not None:
                err = ParseError(
                    filename = self.filename, line = elem.sourceline, column = 0,
                    domain_name = "LOGIC_IDRANGES",
                    type_name = "IDRANGE_ERR_NOTFOUND",
                    message = "Pointer to non-existent element <{} id='{}'>"\
                              .format(pointee, missing)
                    )
                self._log(err)
                valid = False
                continue
            if elem_from.sourceline > elem_to.sourceline:
                err = ParseError(
                    filename = self.filename, line = elem.sourceline, column = 0,
                    domain_name = "LOGIC_IDRANGES",
                    type_name = "IDRANGE_ERR_ORDERING",
                    message = "Pointer to element <{} id='{}'> violates ordering constraint"\
                              .format(pointee, elem_to.get('id'))
                    )
                self._log(err)
                valid = False
        return valid

    def validate_trans(self):
        """Validate that trans attribute of combined dipls/mods equals that of
        parent token."""
        valid = True
        for elem in self.root.iter("token"):
            token_trans = elem.get("trans")
            for layer in ("dipl", "mod"):
                layer_trans = ''.join(x.get('trans', default='') for x in \
                                      elem.iterchildren(layer))
                if layer_trans != token_trans:
                    if layer_trans == '' and layer == 'mod' and \
                        elem.find('mod') is None:
                        # special case -- it is allowed for a token to have no <mod>s
                        continue
                    err = ParseError(
                        filename = self.filename, line = elem.sourceline, column = 0,
                        domain_name = "LOGIC_TRANS",
                        type_name = "TRANS_ERR_MISMATCH",
                        message = ("Transcription '{}' doesn't match "
                                   "concatenated transcription of <{}> children "
                                   "'{}'").format(token_trans, layer, layer_trans)
                        )
                    self._log(err)
                    valid = False
        return valid


if __name__ == '__main__':
    description = "Validates CorA XML files."
    epilog = ""
    parser = argparse.ArgumentParser(description=description, epilog=epilog)
    parser.add_argument('infiles',
                        metavar='FILE',
                        type=argparse.FileType('r'),
                        nargs='+',
                        help='CorA XML file to validate')
    parser.add_argument('-s', '--schema',
                        metavar='RELAXNG_FILE',
                        type=str,
                        default=None,
                        help=('Path to Relax NG schema file for CorA XML'
                              ' (by default, looks for a file called '
                              ' cora-xml.rng in <scriptdir> and '
                              ' <scriptdir>/../doc/)'))
    parser.add_argument('-q', '--quiet',
                        action="store_true",
                        default=False,
                        help='Only report errors')
    parser.add_argument('--skip-schema',
                        action="store_true",
                        default=False,
                        help=('Skip RelaxNG validation'
                              ' (Only use this if you\'re certain'
                              ' that your input is in CorA XML format, and'
                              ' you want to validate internal logic only;'
                              ' otherwise, completely random XML files could'
                              ' be reported as valid!)'))
    parser.add_argument('--skip-ranges',
                        action="store_true",
                        default=False,
                        help='Skip validation of ID ranges')
    parser.add_argument('--skip-trans',
                        action="store_true",
                        default=False,
                        help='Skip validation of "trans" attributes')

    args = parser.parse_args()
    log_level = log.ERROR if args.quiet else log.INFO
    log.basicConfig(format='%(levelname)-8s %(message)s', level=log_level)

    # Instantiate RelaxNG schema
    if args.skip_schema:
        schema = None
    else:
        schemafile = args.schema
        if not schemafile:
            scriptdir = os.path.dirname(os.path.abspath(__file__))
            for candidate in (scriptdir + '/cora-xml.rng',
                              scriptdir + '/../doc/cora-xml.rng'):
                if os.path.exists(candidate):
                    schemafile = os.path.abspath(candidate)
                    log.info("Using RelaxNG schema file: {}".format(schemafile))
                    break
            else:
                log.critical("No RelaxNG schema file given and none found in default locations")
                exit(1)
        try:
            schema = etree.RelaxNG(file=schemafile)
        except etree.RelaxNGParseError as err:
            log.critical("Error parsing RelaxNG schema: {}".format(str(err)))
            exit(1)

    # Validate
    validator = CoraXMLValidator(schema=schema)
    exit_code = 0

    for infile in args.infiles:
        log.info("Validating {}...".format(infile.name))
        if validator(infile,
                     skip_ranges=args.skip_ranges,
                     skip_trans=args.skip_trans):
            log.info("{} is valid".format(infile.name))
        else:
            for (level, message) in validator.error_log:
                log.log(level, message)
            log.error("{} is invalid".format(infile.name))
            exit_code = 4

    exit(exit_code)
