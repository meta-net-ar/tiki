#!/bin/bash
set -euo pipefail

if [ ! -f ".git" ]; then
  # Run git status --porcelain gives machine-readable output
  # The safe directory is for if this is run under gitlab-ci-local with a mounted volume for the base repo of a git worktree
  porcelainOutput=$(git  -c safe.directory='*' status --porcelain)
  grepStatus=$?

  #pipe to grep and print matches
  # Look for lines
  # starting with '?? ' (untracked, not ignored files)
  # Containing '/node_modules/'
  set +e
  echo "$porcelainOutput" | grep '^?? .*\/node_modules\/'
  grepStatus=$?
  set -e

  if (( grepStatus == 1 )); then
    echo "All good, not unexpected node_modules directories from package.json version conflicts."
    exit 0
  elif (( grepStatus == 0 )); then
    echo "Error, unexpected node_modules directories above.  They are likely caused from package.json version conflicts or unregenerated package-lock.json.  See instructions at the top of the main package.json for advice on how to resolve the situation"
    exit 1
  else
    echo "Error executing grep"
    exit 1
  fi
else
  echo "WARNING: .git is a file, we are likely in a workspace, skip this check"
fi
