#!/bin/bash

# This script can be used as an import script for CorA, and will
# accept text files containing one word per line.

# It basically just wraps the make_coraxml.py script.

PYTHON=/usr/bin/python
SCRIPT_CONV=/usr/local/bin/make_coraxml.py

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
echo "~SUCCESS XMLCALL"
