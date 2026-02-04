# Change Champion

A tool to manage versioning and changelogs for Composer packages, inspired
by [changesets](https://github.com/changesets/changesets).

## Installation

```bash
composer require offload-project/change-champion --dev
```

Or install globally:

```bash
composer global require offload-project/change-champion
```

## Quick Start

```bash
# Initialize in your project
cc init

# Create a changeset when you make changes
cc add

# View pending changesets
cc status

# Apply version bump and generate changelog
cc version

# Create a git tag
cc publish
```

## Commands

### `cc init`

Initialize change-champion in your project. Creates a `.changes` directory with configuration.

```bash
cc init

# With GitHub Actions automation
cc init --with-github-actions
```

**Options:**

- `--with-github-actions` - Also install GitHub Actions workflows for automation

### `cc add`

Create a new changeset. Run this after making changes that should be released.

```bash
# Interactive mode
cc add

# Non-interactive mode
cc add --type=minor --message="Add user authentication feature"

# Create empty changeset (useful for CI)
cc add --empty
```

**Options:**

- `--type`, `-t` - Bump type: `major`, `minor`, or `patch`
- `--message`, `-m` - Summary of the change
- `--empty` - Create an empty changeset

### `cc status`

Show pending changesets and the calculated next version.

```bash
cc status

# Verbose output
cc status -v
```

### `cc version`

Apply all pending changesets: bump the version in `composer.json`, update `CHANGELOG.md`, and delete the changeset
files.

```bash
# Interactive confirmation
cc version

# Preview changes without applying
cc version --dry-run

# Skip changelog generation
cc version --no-changelog
```

**Options:**

- `--dry-run` - Show what would be done without making changes
- `--no-changelog` - Skip changelog generation

### `cc publish`

Create a git tag for the current version.

```bash
# Create and push tag
cc publish

# Create tag without pushing
cc publish --no-push

# Preview without creating tag
cc publish --dry-run
```

**Options:**

- `--dry-run` - Show what would be done without making changes
- `--no-push` - Create tag but don't push to remote

## Changeset Format

Changesets are stored in `.changes/` as markdown files:

```markdown
---
type: minor
---

Add user authentication with OAuth2 support.
```

## Configuration

Configuration is stored in `.changes/config.json`:

```json
{
  "baseBranch": "main",
  "changelog": true
}
```

**Options:**

- `baseBranch` - The base branch for comparisons (default: `main`)
- `changelog` - Whether to generate changelog entries (default: `true`)

## Semantic Versioning

Change-composer follows [semver](https://semver.org/):

- **patch** (0.0.x) - Bug fixes, minor changes
- **minor** (0.x.0) - New features, backwards compatible
- **major** (x.0.0) - Breaking changes

When multiple changesets exist, the highest bump type wins. For example, if you have one `minor` and one `patch`
changeset, the version will be bumped as `minor`.

## Workflow

1. Make changes to your code
2. Run `cc add` to create a changeset describing your changes
3. Commit the changeset file along with your code changes
4. When ready to release, run `cc version` to bump versions and update changelog
5. Commit the version bump
6. Run `cc publish` to create a git tag
7. Push the tag to trigger your release pipeline

## GitHub Actions Automation

Install GitHub Actions workflows to automate your release process:

```bash
cc init --with-github-actions
```

This installs three workflows:

### `changeset-check.yml`

Comments on PRs that don't include a changeset, reminding contributors to add one.

### `changeset-release.yml`

When changesets are merged to `main`, automatically:
- Runs `cc version` to bump the version and update changelog
- Creates a "Release vX.X.X" pull request

### `changeset-publish.yml`

When a release PR is merged, automatically:
- Creates a git tag
- Creates a GitHub Release with changelog content

### Setup

After installing the workflows, enable the required permissions:

1. Go to **Settings → Actions → General**
2. Under **Workflow permissions**, enable:
   - "Read and write permissions"
   - "Allow GitHub Actions to create and approve pull requests"

## License

MIT
