#!/usr/bin/env python3
"""Validate a stable release using local Git and read-only GitHub API evidence."""

import argparse
import json
import os
from pathlib import Path
import re
import subprocess


def git(*args):
    return subprocess.check_output(["git", *args], text=True).strip()


def require(condition, message):
    if not condition:
        raise ValueError(message)


def validate(tag, release, runs):
    require(re.fullmatch(r"v?(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)", tag),
            "Use a stable X.Y.Z or vX.Y.Z tag.")
    version = tag.removeprefix("v")
    sha = git("rev-parse", "--verify", f"refs/tags/{tag}^{{commit}}")
    subprocess.run(["git", "merge-base", "--is-ancestor", sha, "refs/remotes/origin/main"],
                   check=True)
    require(release.get("tag_name") == tag and release.get("draft") is False
            and release.get("prerelease") is False and release.get("published_at"),
            "The tag must have a published, non-draft stable GitHub release.")

    def source(path):
        return git("show", f"{sha}:{path}")

    metadata = {
        "plugin header": re.search(r"^ \* Version:\s*(\S+)", source("wp-distraction-free-view.php"), re.M),
        "runtime constant": re.search(r"define\(\s*'WPDFV_VERSION',\s*'([^']+)'", source("config/constants.php")),
        "readme stable tag": re.search(r"^Stable tag:\s*(\S+)", source("readme.txt"), re.M),
    }
    for label, match in metadata.items():
        require(match is not None and match[1] == version, f"Version mismatch: {label}.")
    for path in ("package.json", "package-lock.json"):
        package = json.loads(source(path))
        require(package.get("version") == version, f"Version mismatch: {path}.")
        if path == "package-lock.json":
            require(package.get("packages", {}).get("", {}).get("version") == version,
                    "Version mismatch: package-lock.json root package.")

    candidates = [run for run in runs.get("workflow_runs", [])
                  if run.get("head_sha") == sha and run.get("head_branch") == "main"
                  and run.get("event") == "push" and run.get("path") == ".github/workflows/ci.yml"]
    require(candidates, "No CI push run for this exact main commit.")
    latest = max(candidates, key=lambda run: (run["run_number"], run.get("run_attempt", 1)))
    require(latest.get("status") == "completed" and latest.get("conclusion") == "success",
            "The latest CI run for the release commit must have completed successfully.")
    return {"sha": sha, "tag": tag, "version": version}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--tag", required=True)
    parser.add_argument("--release-json", type=Path, required=True)
    parser.add_argument("--runs-json", type=Path, required=True)
    args = parser.parse_args()
    try:
        result = validate(args.tag, json.loads(args.release_json.read_text()),
                          json.loads(args.runs_json.read_text()))
    except (ValueError, KeyError, OSError, subprocess.CalledProcessError) as error:
        parser.exit(1, f"Release validation failed: {error}\n")
    if os.environ.get("GITHUB_OUTPUT"):
        with open(os.environ["GITHUB_OUTPUT"], "a", encoding="utf-8") as output:
            for key, value in result.items():
                output.write(f"{key}={value}\n")
    print(json.dumps(result))


if __name__ == "__main__":
    main()
