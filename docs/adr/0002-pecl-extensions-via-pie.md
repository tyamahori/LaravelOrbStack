# 0002. PECL 由来の拡張は PIE で入れ、PHP 同梱の拡張は install-php-extensions に残す

- Status: Accepted
- Date: 2026-10-22

## Context

PHP イメージ(`.docker/php.apache.Dockerfile`、`.docker/php.franken.Dockerfile`)は、拡張をすべて mlocati/docker-php-extension-installer(以下 IPE)で入れていた。IPE は PHP 同梱の拡張を `docker-php-ext-install` でビルドし、PECL で配布される拡張(このリポジトリでは redis と xdebug)は `pecl install` で pecl.php.net から取得する。

pecl.php.net は PECL の非推奨と PIE(PHP Installer for Extensions)への移行を告知している。PIE は拡張を Packagist から解決し、ソースを GitHub から取得する。IPE を PIE に対応させる issue(mlocati/docker-php-extension-installer#1125)は 2026-10-22 時点で未解決なので、PECL の拡張を IPE で入れ続けるかぎり、イメージのビルドは pecl.php.net への依存から抜けられない。

## Decision

redis と xdebug は PIE で入れる。PIE は `ghcr.io/php/pie:<版>-bin` をダイジェストで固定し、`RUN --mount=type=bind` でビルド時にだけマウントするので、最終イメージには残らない。gd、intl、zip、bcmath、pdo_pgsql、pgsql、opcache は引き続き IPE で入れる。

redis は、IPE が有効にしていた圧縮(lzf、zstd、lz4)をそのまま有効にする。PIE が取得する GitHub のアーカイブには、phpredis が同梱している liblzf(git submodule)が含まれない。そのため、Debian の `liblzf1` と `liblzf-dev` を入れて `--with-liblzf=yes` でリンクし、ビルドが終わったら `-dev` パッケージだけを消す。

## Alternatives

PHP 同梱の拡張も PIE で入れて、IPE をやめる方法。PIE の対応一覧(`docs/supported-extensions.md`)には gd が載っていない。また IPE がやっている依存ライブラリ(ICU、libzip、libpq、画像ライブラリ)の導入と後片付けを Dockerfile で自前で書くことになる。同梱の拡張は pecl.php.net を使わないので、移す利点がない。

IPE のソース指定(`install-php-extensions phpredis/phpredis@<tag>`)で GitHub から取得する方法。新しい道具は増えないが、版を自分で固定して更新し続ける必要がある。IPE の issue では、phpredis をこの方法で入れて失敗した報告もある。

IPE が PIE に対応するのを待つ方法。時期が見えず、その間は pecl.php.net の障害でビルドが止まる。

## Consequences

PIE は PHP 8.1 以上でしか動かない。このリポジトリは `composer.json` の `require.php` で最新の PHP を追うので問題にならないが、8.1 未満のイメージには使えない。

redis と xdebug は `pie install phpredis/phpredis:<版>` の形で版を固定し、Packagist の新しい版は Renovate が上げる。PIE 本体の版も Renovate がダイジェストごと更新する。

PIE が GitHub からソースを取得するので、認証なしの GitHub API のレート制限に当たりうる。CI でビルドが失敗するようになったら、`GITHUB_TOKEN` を BuildKit の secret として PIE に渡すことを検討する。

IPE が PIE に対応したら、redis と xdebug を IPE に戻して PIE のステージを消せる。そのときは新しい ADR を書き、この ADR の冒頭に「superseded by」を追記する。
