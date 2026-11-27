"""Exercise release acceptance and rejection without a network or publishing."""

import importlib.util
import json
import os
from pathlib import Path
import subprocess
import tempfile
import unittest

SCRIPT = Path(__file__).resolve().parents[1] / "check-release.py"
spec = importlib.util.spec_from_file_location("release_guard", SCRIPT)
guard = importlib.util.module_from_spec(spec)
spec.loader.exec_module(guard)


class ReleaseGuardTest(unittest.TestCase):
    def setUp(self):
        self.original = Path.cwd()
        self.temp = tempfile.TemporaryDirectory(prefix="wpdfv-release-test-")
        self.addCleanup(self.temp.cleanup)
        os.chdir(self.temp.name)
        self.addCleanup(os.chdir, self.original)
        self.git("init", "--quiet", "--initial-branch=main")
        self.git("config", "user.name", "Synthetic release test")
        self.git("config", "user.email", "release-test@example.invalid")
        self.git("config", "commit.gpgsign", "false")
        self.git("config", "tag.gpgsign", "false")
        Path("config").mkdir()
        Path("wp-distraction-free-view.php").write_text("<?php\n/**\n * Version: 1.9.0\n */\n")
        Path("config/constants.php").write_text("<?php\ndefine( 'WPDFV_VERSION', '1.9.0' );\n")
        Path("readme.txt").write_text("Stable tag: 1.9.0\n")
        Path("package.json").write_text(json.dumps({"version": "1.9.0"}))
        Path("package-lock.json").write_text(json.dumps({"version": "1.9.0", "packages": {"": {"version": "1.9.0"}}}))
        self.sha = self.commit()
        self.git("update-ref", "refs/remotes/origin/main", self.sha)
        self.git("tag", "1.9.0")
        self.git("tag", "-a", "v1.9.0", "-m", "Synthetic annotated tag")
        self.release = {"tag_name": "1.9.0", "draft": False, "prerelease": False,
                        "published_at": "2026-10-28T00:00:00Z"}
        self.run = {"head_sha": self.sha, "head_branch": "main", "event": "push",
                    "path": ".github/workflows/ci.yml", "run_number": 10,
                    "run_attempt": 1, "status": "completed", "conclusion": "success"}

    def git(self, *args):
        return subprocess.check_output(["git", *args], text=True, stderr=subprocess.PIPE).strip()

    def commit(self):
        self.git("add", ".")
        self.git("commit", "--quiet", "-m", "Synthetic fixture")
        return self.git("rev-parse", "HEAD")

    def validate(self, tag="1.9.0", release=None, runs=None):
        return guard.validate(tag, self.release if release is None else release,
                              {"workflow_runs": [self.run]} if runs is None else runs)

    def test_stable_tag_and_annotated_v_tag(self):
        self.assertEqual(self.validate(), {"sha": self.sha, "tag": "1.9.0", "version": "1.9.0"})
        self.release["tag_name"] = "v1.9.0"
        self.assertEqual(self.validate("v1.9.0")["sha"], self.sha)

    def test_tag_can_be_ancestor_of_main(self):
        Path("later.txt").write_text("A later main commit")
        self.git("update-ref", "refs/remotes/origin/main", self.commit())
        self.assertEqual(self.validate()["sha"], self.sha)

    def test_invalid_tag_names_rejected_before_git(self):
        for tag in ("main", "--help", "1.9.0-rc.1", "01.9.0", "1.9.0\nsha=evil", "$(echo unsafe)"):
            with self.subTest(tag=tag), self.assertRaises(ValueError):
                self.validate(tag)

    def test_missing_tag_rejected(self):
        with self.assertRaises(subprocess.CalledProcessError):
            self.validate("1.9.1")

    def test_tag_outside_main_rejected(self):
        Path("feature.txt").write_text("Not integrated")
        self.commit()
        self.git("tag", "1.9.1")
        with self.assertRaises(subprocess.CalledProcessError):
            self.validate("1.9.1")

    def test_missing_main_rejected(self):
        self.git("update-ref", "-d", "refs/remotes/origin/main")
        with self.assertRaises(subprocess.CalledProcessError):
            self.validate()

    def test_nonstable_or_unpublished_release_rejected(self):
        for key, value in (("draft", True), ("prerelease", True), ("published_at", None),
                           ("tag_name", "1.8.0"), ("draft", None)):
            with self.subTest(key=key), self.assertRaises(ValueError):
                self.validate(release={**self.release, key: value})
        with self.assertRaises(ValueError):
            self.validate(release={})

    def test_each_version_field_must_match_tag(self):
        paths = ("wp-distraction-free-view.php", "config/constants.php", "readme.txt",
                 "package.json", "package-lock.json")
        for path in paths:
            with self.subTest(path=path):
                before = Path(path).read_text()
                Path(path).write_text(before.replace("1.9.0", "9.9.9"))
                changed = self.commit()
                self.git("update-ref", "refs/remotes/origin/main", changed)
                # Move only this synthetic fixture's tag to the mismatched object.
                self.git("tag", "-f", "1.9.0", changed)
                with self.assertRaisesRegex(ValueError, "Version mismatch"):
                    self.validate()
                Path(path).write_text(before)
                self.commit()

    def test_nested_lock_version_must_match(self):
        lock = json.loads(Path("package-lock.json").read_text())
        lock["packages"][""]["version"] = "1.8.0"
        Path("package-lock.json").write_text(json.dumps(lock))
        changed = self.commit()
        self.git("tag", "-f", "1.9.0", changed)
        self.git("update-ref", "refs/remotes/origin/main", changed)
        with self.assertRaisesRegex(ValueError, "root package"):
            self.validate()

    def test_no_ci_rejected(self):
        with self.assertRaises(ValueError):
            self.validate(runs={"workflow_runs": []})

    def test_unrelated_ci_rejected(self):
        for key, value in (("head_sha", "0" * 40), ("head_branch", "develop"),
                           ("event", "pull_request"), ("path", ".github/workflows/other.yml")):
            with self.subTest(key=key), self.assertRaises(ValueError):
                self.validate(runs={"workflow_runs": [{**self.run, key: value}]})

    def test_unsuccessful_or_pending_ci_rejected(self):
        for status, conclusion in (("in_progress", None), ("queued", None),
                                   ("completed", "failure"), ("completed", "cancelled"),
                                   ("completed", "skipped"), ("completed", "neutral")):
            with self.subTest(conclusion=conclusion, status=status), self.assertRaises(ValueError):
                self.validate(runs={"workflow_runs": [{**self.run, "status": status, "conclusion": conclusion}]})

    def test_later_failed_run_or_attempt_overrides_earlier_success(self):
        for changed in ({"run_number": 11}, {"run_attempt": 2}):
            later = {**self.run, **changed, "conclusion": "failure"}
            with self.subTest(changed=changed), self.assertRaises(ValueError):
                self.validate(runs={"workflow_runs": [later, self.run]})

    def test_cli_outputs_only_after_success(self):
        Path("release.json").write_text(json.dumps(self.release))
        Path("runs.json").write_text(json.dumps({"workflow_runs": [self.run]}))
        output = Path.cwd() / "output.txt"
        env = {**os.environ, "GITHUB_OUTPUT": str(output)}
        command = ["python3", str(SCRIPT), "--tag", "1.9.0", "--release-json", "release.json", "--runs-json", "runs.json"]
        passed = subprocess.run(command, env=env, capture_output=True, text=True)
        self.assertEqual(passed.returncode, 0, passed.stderr)
        before = output.read_text()
        self.assertIn(f"sha={self.sha}\n", before)
        Path("runs.json").write_text("{invalid json")
        failed = subprocess.run(command, env=env, capture_output=True, text=True)
        self.assertNotEqual(failed.returncode, 0)
        self.assertEqual(output.read_text(), before)


if __name__ == "__main__":
    unittest.main()
