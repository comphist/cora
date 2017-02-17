#!/usr/bin/python3

import argparse
import difflib
import logging
from lxml import etree
import sys

log = logging
log.basicConfig(format='%(levelname)-8s %(message)s', level=logging.INFO)

def make_anno_string(elem):
    annotations = [etree.tostring(anno).decode("utf-8").strip() for anno in elem]
    return ''.join(sorted(annotations))

def stringify(item):
    string = etree.tostring(item, method="c14n").decode("utf-8")
    string = ''.join(s.strip() for s in string.split("\n"))
    return string

def extract_items(fileobj, xpath, strict=False):
    tree = etree.parse(fileobj)
    items = []
    for item in tree.getroot().xpath(xpath):
        if not isinstance(item, str):
            if item.get('id') is not None and not args.strict:
                del item.attrib['id']
            item = stringify(item)
        if not item.endswith("\n"):
            item += "\n"
        items.append(item)
    if not items:
        log.warning("No matches for XPath expression in '{}'".format(fileobj.name))
    return items

if __name__ == '__main__':
    description = ""
    epilog = ("By default, if -s/--strict is not used, certain internal attributes "
              "such as IDs are not included in the comparison.")
    parser = argparse.ArgumentParser(description=description, epilog=epilog)
    parser.add_argument('fileA',
                        metavar='CORA_XML',
                        type=argparse.FileType('r'),
                        help='CorA-XML file to compare')
    parser.add_argument('fileB',
                        metavar='CORA_XML',
                        type=argparse.FileType('r'),
                        help='CorA-XML file to compare')
    parser.add_argument('-c', '--compare',
                        type=str,
                        default='//mod/@ascii',
                        help=('XPath expression to select the elements '
                              'to compare (default: %(default)s)'))
    parser.add_argument('-f', '--format',
                        choices=('context','unified','ndiff','html'),
                        default='unified',
                        help=('Output format for the diff (default: %(default)s)'))
    parser.add_argument('-n',
                        type=int,
                        default=2,
                        help=('Number of lines for context (default: %(default)i)'))
    parser.add_argument('-s', '--strict',
                        action='store_true',
                        default=False,
                        help=('Perform a strict comparison of attributes'))

    args = parser.parse_args()

    a = extract_items(args.fileA, args.compare, args.strict)
    b = extract_items(args.fileB, args.compare, args.strict)

    if args.format == "context":
        diff = difflib.context_diff(a, b, args.fileA.name, args.fileB.name, n=args.n)
    elif args.format == "unified":
        diff = difflib.unified_diff(a, b, args.fileA.name, args.fileB.name, n=args.n)
    elif args.format == "ndiff":
        diff = difflib.ndiff(a, b)
    elif args.format == "html":
        diff = difflib.HtmlDiff().make_file(a, b, args.fileA.name, args.fileB.name, numlines=args.n)

    try:
        sys.stdout.writelines(diff)
    except BrokenPipeError:
        pass

