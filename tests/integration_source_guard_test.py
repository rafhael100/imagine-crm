"""Regression examples for replacing the unified tree with the Sites-only tree."""
import hashlib
import importlib.util
from pathlib import Path
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('guard', ROOT / 'scripts/ci/check-integrated-candidate.py')
guard = importlib.util.module_from_spec(spec)
spec.loader.exec_module(guard)


class IntegrationSourceGuardTest(unittest.TestCase):
    def test_rejects_dropped_security_file_and_older_restore(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / 'restore.sh').write_text('final postmaster readiness')
            (root / 'sites.php').write_text('new integration')
            manifest = {'preserved_sha256': {
                'iam.php': hashlib.sha256(b'original IAM').hexdigest(),
                'restore.sh': hashlib.sha256(b'final postmaster readiness').hexdigest(),
            }, 'required_additions': ['sites.php']}
            self.assertEqual(guard.inspect(root, manifest), ['iam.php'])
            (root / 'iam.php').write_bytes(b'original IAM')
            self.assertEqual(guard.inspect(root, manifest), [])
            (root / 'restore.sh').write_text('temporary postmaster readiness')
            self.assertEqual(guard.inspect(root, manifest), ['restore.sh'])
            (root / 'sites.php').unlink()
            self.assertEqual(guard.inspect(root, manifest), ['restore.sh', 'sites.php'])


if __name__ == '__main__':
    unittest.main()
