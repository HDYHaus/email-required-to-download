#!/usr/bin/env python3
"""Build the installable ZIP using an explicit runtime-file allowlist."""
from pathlib import Path
import hashlib
import json
import re
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED
root = Path(__file__).resolve().parents[1]
main = (root / 'email-required-to-download.php').read_text()
version = re.search(r'\* Version: ([\d.]+)', main).group(1)
assert f"define( 'ERD_VERSION', '{version}' );" in main
assert f'Stable tag: {version}' in (root / 'readme.txt').read_text()
plugin_uri = re.search(r'\* Plugin URI: (.+)', main).group(1).strip()
author_uri = re.search(r'\* Author URI: (.+)', main).group(1).strip()
assert plugin_uri.rstrip('/') != author_uri.rstrip('/'), 'Plugin URI and Author URI must be different'
assert re.search(r'^Contributors: hdyhaus, mariaojob$', (root / 'readme.txt').read_text(), re.M)
files = [root / n for n in ['email-required-to-download.php', 'uninstall.php', 'readme.txt', 'README.md']]
for directory in ['includes', 'assets', 'blocks']:
    files.extend(p for p in (root / directory).rglob('*') if p.is_file() and not p.name.startswith('.'))
files.sort()
zip_path = root / f'email-required-to-download-{version}.zip'
with ZipFile(zip_path, 'w', ZIP_DEFLATED) as z:
    for p in files:
        info = ZipInfo('email-required-to-download/' + p.relative_to(root).as_posix(), (2026, 11, 15, 0, 0, 0))
        info.external_attr = 0o100644 << 16
        info.compress_type = ZIP_DEFLATED
        z.writestr(info, p.read_bytes())
with ZipFile(zip_path) as z:
    assert z.testzip() is None
    assert len(z.namelist()) == len(files)
    for p in files:
        assert z.read('email-required-to-download/' + p.relative_to(root).as_posix()) == p.read_bytes()
manifest = {
    'version': version,
    'archive': zip_path.name,
    'sha256': hashlib.sha256(zip_path.read_bytes()).hexdigest(),
    'files': {p.relative_to(root).as_posix(): hashlib.sha256(p.read_bytes()).hexdigest() for p in files},
}
(root / 'release' / f'manifest-{version}.json').write_text(json.dumps(manifest, indent=2) + '\n')
print(f'Built and verified {zip_path.name}: {len(files)} files')
print(f'SHA256: {manifest["sha256"]}')
