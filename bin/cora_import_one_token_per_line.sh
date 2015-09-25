#!/bin/bash
#
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
#

# This script can be used as an import script for CorA, and will
# accept text files containing one word per line.

# It basically just wraps the make_coraxml.py script.

PYTHON=/usr/bin/python
SCRIPT_CONV=./make_coraxml.py

# just a very basic sanity check, don't totally rely on this
echo "~BEGIN CHECK"
file -b "$1" | grep "UTF-8" -q
if [ $? -ne 0 ]; then
    file -b "$1" | grep "ASCII" -q
    if [ $? -ne 0 ]; then
        echo "~ERROR CHECK"
        echo "Datei scheint keine Textdatei zu sein oder hat ungültiges Encoding."
        echo "Erkannter Dateityp:"
        file -b "$1"
        exit 1
    fi
fi
echo "~SUCCESS CHECK"

echo "~BEGIN XMLCALL"
echo "~BEGIN XML"
OUTPUT_CONV=$(mktemp)
$PYTHON -u $SCRIPT_CONV "$1" > "$2" 2> "$OUTPUT_CONV"
if [ $? -gt 0 ]; then
    echo "~ERROR XML"
    echo "Die Umwandlung der Transkription war nicht erfolgreich:"
    cat "$OUTPUT_CONV" | head -n 200
    NUM_LINES=$(cat "$OUTPUT_CONV" | wc -l)
    if [ $NUM_LINES -gt 200 ]; then
        echo "Es gab noch weitere Ausgaben, die hier ausgelassen wurden."
    fi
    exit 1
fi
echo "~PROGRESS 0.5"
echo "~SUCCESS XML"
echo "~SUCCESS XMLCALL"
