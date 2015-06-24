#!/bin/bash
mkdir -p m4
aclocal -I m4
automake --add-missing
autoconf
rm -rf m4
