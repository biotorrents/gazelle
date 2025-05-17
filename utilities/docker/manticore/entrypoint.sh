#!/bin/bash

# exit on a non-zero status
set -e

# generate config from php
php /etc/manticoresearch/manticore.conf.sh > /etc/manticoresearch/manticore.conf

# start manticore
exec /entrypoint.sh "$@"
