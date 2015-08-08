#!/bin/bash

# This script can be used as an import script for CorA, and will
# accept ReF-compatible transcription files.

RUBY=/usr/bin/ruby
PYTHON=/usr/bin/python
SCRIPT_CHECK=/usr/local/bin/convert_check.rb
SCRIPT_CONV=/usr/local/bin/convert_coraxml.py

echo "~BEGIN CHECK"
OUTPUT_CHECK=$($RUBY $SCRIPT_CHECK -C "$1" 2>&1)
grep -q "^ERROR" <<< "$OUTPUT_CHECK"
if [ $? -eq 0 ]; then
    echo "~ERROR CHECK"
    echo "Die Prüfung der Transkription hat Fehler ergeben:"
    echo "$OUTPUT_CHECK" | head -n 200
    NUM_LINES=$(echo "$OUTPUT_CHECK" | wc -l)
    if [ $NUM_LINES -gt 200 ]; then
        echo "Es gab noch weitere Ausgaben, die hier ausgelassen wurden."
    fi
    echo
    echo "(HINWEIS: Transkriptionsdateien müssen immer als .txt-Datei im Bonner Transkriptionsformat hochgeladen werden.)"
    exit 1
fi
echo "~SUCCESS CHECK"

echo "~BEGIN XMLCALL"
OUTPUT_CONV=$(mktemp)
$PYTHON -u $SCRIPT_CONV "$1" "$2" 2> "$OUTPUT_CONV"
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
