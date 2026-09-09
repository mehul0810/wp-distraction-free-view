#!/usr/bin/env bash

set -euo pipefail

DB_NAME=${1-wordpress_test}
DB_USER=${2-root}
DB_PASS=${3-root}
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
SKIP_DB_CREATE=${6-false}

TMPDIR=${TMPDIR-/tmp}
WP_TESTS_DIR=${WP_TESTS_DIR-${TMPDIR}/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-${TMPDIR}/wordpress}

download() {
	local url=$1
	local target=$2

	if command -v curl >/dev/null 2>&1; then
		curl -sL "$url" -o "$target"
	elif command -v wget >/dev/null 2>&1; then
		wget -nv -O "$target" "$url"
	else
		echo "curl or wget is required to download WordPress test files." >&2
		exit 1
	fi
}

download_wordpress() {
	if [ -d "$WP_CORE_DIR" ]; then
		return
	fi

	mkdir -p "$WP_CORE_DIR"

	local archive="${TMPDIR}/wordpress.tar.gz"
	if [ "$WP_VERSION" = "latest" ]; then
		download https://wordpress.org/latest.tar.gz "$archive"
	else
		download "https://wordpress.org/wordpress-${WP_VERSION}.tar.gz" "$archive"
	fi

	tar --strip-components=1 -xzf "$archive" -C "$WP_CORE_DIR"
}

install_test_suite() {
	if [ -d "$WP_TESTS_DIR" ]; then
		return
	fi

	local branch="trunk"
	if [ "$WP_VERSION" != "latest" ]; then
		branch="tags/${WP_VERSION}"
	fi

	if command -v svn >/dev/null 2>&1; then
		mkdir -p "$WP_TESTS_DIR"
		svn export --quiet "https://develop.svn.wordpress.org/${branch}/tests/phpunit/includes" "${WP_TESTS_DIR}/includes"
		svn export --quiet "https://develop.svn.wordpress.org/${branch}/tests/phpunit/data" "${WP_TESTS_DIR}/data"
		return
	fi

	if [ "$WP_VERSION" != "latest" ]; then
		echo "svn is required to install versioned WordPress PHPUnit test suites." >&2
		exit 1
	fi

	local develop_dir="${TMPDIR}/wordpress-develop"
	local archive="${TMPDIR}/wordpress-develop.tar.gz"

	download https://github.com/WordPress/wordpress-develop/archive/refs/heads/trunk.tar.gz "$archive"
	rm -rf "$develop_dir"
	mkdir -p "$WP_TESTS_DIR"
	mkdir -p "$develop_dir"
	tar --strip-components=1 -xzf "$archive" -C "$develop_dir"
	cp -R "${develop_dir}/tests/phpunit/includes" "${WP_TESTS_DIR}/includes"
	cp -R "${develop_dir}/tests/phpunit/data" "${WP_TESTS_DIR}/data"
}

install_config() {
	local config="${WP_TESTS_DIR}/wp-tests-config.php"

	if [ -f "$config" ]; then
		return
	fi

	local branch="trunk"
	if [ "$WP_VERSION" != "latest" ]; then
		branch="tags/${WP_VERSION}"
	fi

	download "https://develop.svn.wordpress.org/${branch}/wp-tests-config-sample.php" "$config"
	sed -i.bak "s:dirname( __FILE__ ) . '/src/':'${WP_CORE_DIR}/':" "$config"
	sed -i.bak "s/youremptytestdbnamehere/${DB_NAME}/" "$config"
	sed -i.bak "s/yourusernamehere/${DB_USER}/" "$config"
	sed -i.bak "s/yourpasswordhere/${DB_PASS}/" "$config"
	sed -i.bak "s|localhost|${DB_HOST}|" "$config"
	rm -f "${config}.bak"
}

create_database() {
	if [ "$SKIP_DB_CREATE" = "true" ]; then
		return
	fi

	mysqladmin create "$DB_NAME" --user="$DB_USER" --password="$DB_PASS" --host="$DB_HOST" 2>/dev/null || true
}

download_wordpress
install_test_suite
install_config
create_database

echo "WordPress tests installed in ${WP_TESTS_DIR}"
