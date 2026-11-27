#!/usr/bin/env bash
set -euo pipefail

# Each PHP job has its own MySQL service. Give each WordPress install separate tables.
work_dir=$(mktemp -d "${RUNNER_TEMP:?}/wp-compat.XXXXXXXX")
curl --fail --silent --show-error --location --retry 3 https://github.com/wp-cli/wp-cli/releases/download/v2.12.0/wp-cli-2.12.0.phar -o "$work_dir/wp-cli.phar"
echo "ce34ddd838f7351d6759068d09793f26755463b4a4610a5a5c0a97b68220d85c  $work_dir/wp-cli.phar" | sha256sum --check
curl --fail --silent --show-error --retry 3 https://api.wordpress.org/core/stable-check/1.0/ -o "$work_dir/releases.json"

test_wordpress() {
    local version=$1
    local core="$work_dir/$version"
    local wp=(php "$work_dir/wp-cli.phar" --skip-packages --path="$core")
    echo "::group::WordPress $version on PHP $(php -r 'echo PHP_VERSION;')"
    mkdir "$core"
    curl --fail --silent --show-error --location --retry 3 "https://wordpress.org/wordpress-$version.tar.gz" -o "$work_dir/wordpress.tar.gz"
    tar -xzf "$work_dir/wordpress.tar.gz" --strip-components=1 -C "$core"
    php -r 'require $argv[1]; if ($wp_version !== $argv[2]) { throw new RuntimeException("Downloaded WordPress $wp_version, expected $argv[2]"); }' "$core/wp-includes/version.php" "$version"
    "${wp[@]}" config create --dbname=wordpress_test --dbuser=root --dbpass=root --dbhost=127.0.0.1 --dbprefix="wp_${version//[.-]/_}_"
    "${wp[@]}" core install --url=http://reader.test --title=Reader --admin_user=reader --admin_email=reader@example.test --skip-email > /dev/null
    ln -s "$GITHUB_WORKSPACE" "$core/wp-content/plugins/wp-distraction-free-view"
    "${wp[@]}" plugin activate wp-distraction-free-view
    vendor/bin/phpunit -c phpunit.integration.xml.dist --bootstrap "$core/wp-load.php"
    echo "::endgroup::"
}

read -ra series_list <<< "${WP_SERIES:?}"

for series in "${series_list[@]}"; do
    version=$(jq -r --arg series "$series" '
        keys | map(select(test("^[0-9]+([.][0-9]+){1,2}$")))
        | map(select(. == $series or startswith($series + ".")))
        | max_by(split(".") | map(tonumber)) // empty
    ' "$work_dir/releases.json")
    if [[ -z "$version" && "$series" == 7.2 ]]; then
        version=7.2-RC3
    fi
    if [[ -z "$version" ]]; then
        echo "::error::No WordPress release found for $series"
        exit 1
    fi

    if [[ "$series" == 6.0 && "$version" != 6.0 ]]; then
        test_wordpress 6.0
    fi
    test_wordpress "$version"
done
