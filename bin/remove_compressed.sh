#!/bin/bash
# Removes the files created by compress.sh.  This is mainly useful for
# development because otherwise each change to a CSS or JS file requires calling
# compress.sh again to take effect.

set -o errexit

rm gui/css/master.min.css
rm gui/js/mbox.min.js
rm gui/js/master.min.js
rm gui/js/admin.min.js
