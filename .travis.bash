#!/usr/bin/env bash

# Force root user.

if [[ "$(whoami)" != 'root' ]]; then
  sudo -E "${BASH_SOURCE[0]}"; exit; fi;

# Clone the websharks/travis-ci repo.
# This serves as the official `/bootstrap`.

mkdir --parents ~/ws/repos &>/dev/null || exit 1;
git clone https://github.com/websharks/travis-ci /bootstrap --branch=master --depth=1 &>/dev/null || exit 1;

# Run setup scripts in websharks/travis-ci repo.

. /bootstrap/src/setup.bash &>/dev/null || exit 1;

# Custom code reinserted here via [custom] marker. Add your <custom></custom> comment markers here please.

# Run build now; i.e., Phing, etc (after custom code).

. /bootstrap/src/build.bash;
