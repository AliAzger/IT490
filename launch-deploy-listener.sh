#!/bin/bash
set -e

echo "launch script run";

cd /home/dylan/dev/IT490;
source ./.venv/bin/activate;
echo "python venv activated";

cd deploy-system;
python listener.py;