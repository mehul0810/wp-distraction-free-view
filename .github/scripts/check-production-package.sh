#!/usr/bin/env bash
set -euo pipefail

package_dir="${1:-}"

if [[ -z "$package_dir" || ! -d "$package_dir" ]]; then
	echo "::error::Usage: $0 <production-package-directory>"
	exit 1
fi

status=0

require_file() {
	local path="$1"

	if [[ ! -f "$package_dir/$path" ]]; then
		echo "::error::Production package is missing required file: $path"
		status=1
	fi
}

reject_path() {
	local path="$1"

	if [[ -e "$package_dir/$path" ]]; then
		echo "::error::Production package includes development-only path: $path"
		status=1
	fi
}

require_file "wp-distraction-free-view.php"
require_file "readme.txt"
require_file "composer.json"
require_file "vendor/autoload.php"
require_file "blocks/reader-button/block.json"
require_file "assets/dist/js/wpdfv.js"
require_file "assets/dist/js/wpdfv.asset.php"
require_file "assets/dist/js/wpdfv-admin.js"
require_file "assets/dist/js/wpdfv-block.js"
require_file "assets/dist/wpdfv.css"
require_file "assets/dist/wpdfv-admin.css"

reject_path ".distignore"
reject_path ".github"
reject_path ".git"
reject_path ".gitignore"
reject_path ".nvmrc"
reject_path "assets/src"
reject_path "babel.config.js"
reject_path "composer.lock"
reject_path "DESIGN.md"
reject_path "eslint.config.js"
reject_path "node_modules"
reject_path "package-lock.json"
reject_path "package.json"
reject_path "phpcs.ruleset.xml"
reject_path "phpunit.xml.dist"
reject_path "tests"
reject_path "vendor/bin"
reject_path "webpack.config.js"
reject_path "wp-textdomain.js"

if [[ -d "$package_dir/vendor" ]]; then
	if find "$package_dir/vendor" -maxdepth 4 \( -path '*/phpunit/*' -o -path '*/squizlabs/php_codesniffer/*' -o -path '*/wp-coding-standards/*' -o -path '*/dealerdirect/*' \) | grep -q .; then
		echo "::error::Production package includes Composer development dependencies."
		status=1
	fi
fi

if [[ $status -eq 0 ]]; then
	echo "Production package shape looks correct."
fi

exit "$status"
