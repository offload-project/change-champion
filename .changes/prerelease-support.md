---
type: minor
---

Add pre-release version support with `--prerelease` flag for alpha, beta, and rc versions.

- `cc version --prerelease alpha` creates versions like `1.0.0-alpha.1`
- Supports bumping through pre-release stages: alpha → beta → rc → stable
- Graduate to stable release by running `cc version` without the flag
