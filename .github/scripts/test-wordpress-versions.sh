#!/usr/bin/env bash
set -euo pipefail

# Each PHP job has its own MySQL service. Give each WordPress install separate tables.
work_dir=$(mktemp -d "${RUNNER_TEMP:?}/wp-compat.XXXXXXXX")
curl --fail --silent --show-error --retry 3 https://api.wordpress.org/core/stable-check/1.0/ -o "$work_dir/releases.json"
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

    core="$work_dir/$version"
    echo "::group::WordPress $version on PHP $(php -r 'echo PHP_VERSION;')"
    wp core download --path="$core" --version="$version"
    [[ "$(wp core version --path="$core")" == "$version" ]]
    wp config create --path="$core" --dbname=wordpress_test --dbuser=root --dbpass=root --dbhost=127.0.0.1 --dbprefix="wp_${series//./_}_"
    wp core install --path="$core" --url=http://reader.test --title=Reader --admin_user=reader --admin_email=reader@example.test --skip-email > /dev/null
    ln -s "$GITHUB_WORKSPACE" "$core/wp-content/plugins/wp-distraction-free-view"
    wp plugin activate wp-distraction-free-view --path="$core"
    vendor/bin/phpunit -c phpunit.integration.xml.dist --bootstrap "$core/wp-load.php"
    echo "::endgroup::"
done
