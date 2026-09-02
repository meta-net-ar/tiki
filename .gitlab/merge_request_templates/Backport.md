## This MR is a backport

_If it is not, delete everything below, including the heading above._

_Backports are costly and can add regressions, so we justify the ones we keep. "Just in case" won't be merged._

*Policy: https://dev.tiki.org/Backport-Policy*

## 1. Based on

- **Backport of**: _!MR-number_
- **Cherry-picked from**: _commit-hash(es)_
- **From branch**: _normally the one just above the target_
- **Target branch**: _ex: 27.x_
- **Final target branch**: _last branch in the chain if it is not this MR's target_

## 2. Tested since the upstream merge
_Where, how, by whom. Ex: dogfooded on dev.tiki.org, checked X. Same-day backport? Say why it is safe._

## 3. Who is harmed if not backported
Name a specific org, client, or scenario. Not "someone asked me".
_Ex: clients on 27.x LTS lose Intertiki once linked to a newer Tiki._

If not merged, keeping the current code in the target branch (tick at least one):

- [ ] Causes fatal error or major bug
- [ ] Produces a regression (e.g., in usability, UX)
- [ ] Breaks compatibility with a supported platform, dependency, API, or service
- [ ] Blocks specific client, funded project, or production site
- [ ] Blocks installation, upgrade, or normal use

## 4. Why it is safe, or worth the risk
_Ex: self-contained (new wikiplugin). No code execution change (string, return code, translation). Already broken in the target branch._

## 5. Release note entry (mandatory)
_Operators run old releases to avoid surprises, so every change must appear here._

## 6. Checklist
- [ ] Backport chain followed: each active branch between source and target has this fix
- [ ] Commits cherry-picked one by one, squash OFF
- [ ] `CherryPickDownTo` label on the highest-version MR (Source MR)
- [ ] A reviewer who can look promptly is assigned
