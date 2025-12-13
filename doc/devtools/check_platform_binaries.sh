#!/bin/sh
set -e

# This script performs a spot check for an unlikely platform-specific binary
# in package--lock.json. When the npm bug occurs, only the host platform's
# binaries are present, so checking for one that is never the host
# is a reliable way to detect the issue.

echo "Checking that package-lock.json contains non-host platform binaries..."

PACKAGE_TO_CHECK='"@parcel/watcher-android-arm64":'

if ! grep -q "$PACKAGE_TO_CHECK" package-lock.json; then
  echo
  echo "ERROR: The required platform-specific binary '$PACKAGE_TO_CHECK' is missing from package-lock.json." >&2
  echo "This indicates the lockfile may be incomplete." >&2
  echo "To fix this, please try regenerating the 'package-lock.json' file by deleting it and running 'npm install' again." >&2
  exit 1
else
  echo "Success: The package-lock.json file appears to be complete."
fi