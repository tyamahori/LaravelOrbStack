# LaravelOrbStack

Laravel を Docker Compose 環境で動かすためのサンプルプロジェクトです。OrbStack を主に想定し、Docker Desktop と Linux の Docker Engine でも同じ `.local` ドメインで動きます。Apache mod_php と FrankenPHP の 2 系統の PHP 実行環境を同時に立ち上げ、PostgreSQL、Redis、Mailpit、S3 互換ストレージを含むローカル開発環境を構築します。

## 構成

- PHP 8.5
- Laravel 13
- Apache mod_php
- FrankenPHP
- PostgreSQL
- Redis
- Mailpit
- RustFS
- Docker Compose + OrbStack
- Task
- Devbox

PHP のマイナーと Composer のバージョンは `composer.json` が正本です。PHP は `require.php`、Composer は `config.composerVersion` から Taskfile・GitHub Actions が読み取り、Dockerfile には `--build-arg` で渡します。Devbox の PHP は `devbox.json` で別途固定しています。

PHP 系 Dockerfile のベースイメージ(`composer`、`php`、`dunglas/frankenphp`)と、PIE で入れる拡張(redis、xdebug)は、Dockerfile の中でパッチバージョンまで固定しています。イメージは digest も固定します。パッチ・digest・拡張の更新は Renovate が行い、Composer は `composer.json` と Dockerfile を同じ PR で上げます。Dockerfile の `basecomposer` は、イメージの PHP のマイナーと Composer の版が `require.php`・`composerVersion` と一致しないとビルドを止めます。PHP のマイナーを上げるときは、`require.php` と両 Dockerfile の `FROM` を同じ変更で書き換えます。

ベースイメージやツールを取り込む補助ステージを除くと、Dockerfile のステージは六つです。`baseruntime` は実行に要る PHP と拡張だけを持ち、`basecomposer` はそこに Composer を足し、`basebuild` はさらに git・unzip・postgresql-client を足し、`local` は開発用の道具と xdebug を足します。本番用の `flyio` は、`flyiobuild`(`basecomposer` の上で `composer install` と `dump-autoload` を行う中間ステージ)で組み立てたアプリを `baseruntime` に載せ替えます。そのため本番イメージにはビルド用の道具も Composer のキャッシュも入らず、`fly ssh console` から Composer は使えません。

`flyiobuild` は、SDK 付属の `removeUnusedServices` で AWS SDK のクライアントを削ります。残るのは S3 と、認証などで使うため SDK が消さないサービス(Kms、SSO、SSOOIDC、Sts、Signin)だけです。ローカルの `vendor/` は削りません。本番で SQS や SES のような別の AWS サービスを使い始めるときは、そのサービス名を両 Dockerfile の `extra.aws/aws-sdk-php` へ足してください。足さないと、ローカルとテストは通っても本番でクラスが見つからずに落ちます。

## 前提条件

- macOS または Linux
- [OrbStack](https://orbstack.dev/)、Docker Desktop、Docker Engine のいずれか
- [Task](https://taskfile.dev/)
- [Devbox](https://www.jetify.com/devbox) 任意、推奨

## セットアップ

```bash
git clone git@github.com:tyamahori/LaravelOrbStack.git
cd LaravelOrbStack

# Devbox を使う場合
devbox shell

# 初回構築
task init
```

`task init` は既存コンテナ、ボリューム、ローカルイメージ、`vendor` を削除してから再構築します。ボリュームだけ初期化して起動し直す場合は `task start` を使います。

## アクセス先

OrbStack では、ドメイン連携により起動後は次の URL でアクセスできます。ホストへの port 公開は行っておらず、ホストからのアクセスはすべて OrbStack のドメイン解決経由です。

| 実行環境 | URL |
| :-- | :-- |
| Apache | <https://apachephp.local/> |
| FrankenPHP | <https://frankenphp.local/> |

OrbStack では、補助サービスへ `<サービス名>.laravelorbstack.orb.local` でコンテナの port に直接アクセスできます。

| サービス | アクセス先 |
| :-- | :-- |
| PostgreSQL | `postgresql.laravelorbstack.orb.local:5432` |
| Redis(キャッシュ) | `cache.laravelorbstack.orb.local:6379` |
| Redis(セッション) | `session.laravelorbstack.orb.local:6379` |
| Mailpit UI | <http://mail.laravelorbstack.orb.local:8025/> |
| RustFS(S3 API) | `storage.laravelorbstack.orb.local:9000` |

OrbStack 側のローカルドメイン一覧は <https://orb.local/> で確認できます。ドメインが解決しなくなった場合は該当コンテナを `docker restart` すると再登録されます。

Docker Desktop と Linux の Docker Engine には OrbStack のドメイン連携がありません。Taskfile は `docker info` でエンジンを見分け、OrbStack では `.docker/compose.orb.yaml` を、それ以外では `.docker/compose.desktop.yaml` を重ねます。`compose.desktop.yaml` は Traefik を `127.0.0.1` の 80、443、5432 番で起動し、上と同じ URL を HTTP と HTTPS の両方で受けます。初回に一度だけ次を実行してください。

```bash
.docker/proxy/setup.sh
```

このスクリプトは、ローカル CA とサーバー証明書を `~/.local/state/laravelorbstack-proxy/` に作り、`/etc/hosts` にドメインを追記し、CA を OS の信頼ストアに登録します。登録先は macOS ではシステムキーチェーン、Linux では `update-ca-certificates` で、どちらも sudo のパスワードを聞かれます。CA には名前制約を付けてあり、`apachephp.local`、`frankenphp.local`、`*.laravelorbstack.local` 以外の証明書には署名できません。何度実行しても、足りない設定だけを補います。`/etc/hosts` には `::1` の行も入れます。macOS が `.local` の AAAA レコードを mDNS で引きにいき、数秒待たされるのを避けるためです。証明書を作り直すときは、このディレクトリと登録した CA を消してから再実行します。

Docker Desktop と Linux では、補助サービスへ Traefik 経由でアクセスします。Redis は Traefik に載せていないので、ホストからは直接つなげません。

| サービス | アクセス先 |
| :-- | :-- |
| PostgreSQL | `postgresql.laravelorbstack.local:5432`(Traefik が SNI で振り分けるので `sslmode=require` 以上が必要) |
| Mailpit UI | <https://mail.laravelorbstack.local/> |
| RustFS(S3 API) | `https://storage.laravelorbstack.local` |

どの環境でも、`task e2e`(`tests/e2e.sh`)が両ドメインに HTTPS で API とページを通し、ドメインの解決と証明書の検証から、ロードバランサ、PHP、PostgreSQL、RustFS までがつながっていることを確かめます。CI の `e2e` ジョブは同じスクリプトを Linux の Docker Engine で走らせ、`compose.desktop.yaml` と `.docker/proxy/setup.sh` を使います。

## worktree ごとの環境

`git worktree` で作ったチェックアウトごとに、別の環境を同時に立ち上げられます。worktree で `task` を実行するだけで使えます。

```bash
git worktree add ../LaravelOrbStack-feature-x -b feature-x
cd ../LaravelOrbStack-feature-x
task init
```

Taskfile は初回の実行でチェックアウトの直下へ `.worktree-env`(Git の管理外)を作り、Compose のプロジェクト名を書き込みます。main のチェックアウト(`git clone` した元のディレクトリ)は `laravelorbstack` です。`git worktree add` で足した worktree は `laravelorbstack-<ブランチ名>-<ハッシュ>` になります。ブランチ名は英小文字・数字・ハイフンに直して 40 文字で切り(`feature/Foo` なら `feature-foo`)、ブランチのない detached HEAD のときは代わりに worktree の識別名を使います。識別名は Git が `.git/worktrees/` の下に振る名前で、上の例なら `LaravelOrbStack-feature-x` です。ハッシュはこの識別名から作る 6 桁で、Git が識別名を `review`、`review1` のように重ならないよう振るので、直すと同じになるブランチ(`feature/foo` と `feature-foo`)や同じ名前のディレクトリでも、名前は別々になります。名前はドメインの一部にもなるので、全体で 63 文字に収めています。ファイルがあればそのまま使うので、後からブランチを切り替えても名前は変わりません。名前を変えたいときは、環境を `task down` で止めてから `PROJECT_NAME=` の行を書き換えてください。

Taskfile はこの名前から、Compose のプロジェクト名(`-p`)と、ローカルでビルドする PHP イメージのタグ(`laravelorbstack-feature-x-33c686/php:apache-local` など)を決めます。コンテナ、ネットワーク、ボリュームはプロジェクトごとに分かれるので、データベースやストレージの中身も別々です。`task init` や `task cleanUpComposeProject` が消すのも、その worktree のプロジェクトだけです。OrbStack でのアクセス先は次のとおりで、補助サービスは `<サービス名>.laravelorbstack-feature-x-33c686.orb.local` になります。main のチェックアウトは、これまでどおり上の URL で動きます。

| 実行環境 | URL |
| :-- | :-- |
| Apache | <https://apachephp.laravelorbstack-feature-x-33c686.local/> |
| FrankenPHP | <https://frankenphp.laravelorbstack-feature-x-33c686.local/> |

`task e2e` も、その worktree のドメインに対して走ります。同時に起動できるのは OrbStack だけです。Docker Desktop と Linux では、Traefik のルーティング、証明書、`/etc/hosts` が既定のドメインにしかないので、worktree でも `apachephp.local` と `frankenphp.local` を使います。Traefik が `127.0.0.1` の 80、443、5432 番を占有するため、動かせるのは一度に一つの環境だけです。別の worktree を起動する前に、動いている環境を `task down` で止めてください。

## 主なコマンド

### 起動と停止

| コマンド | 内容 |
| :-- | :-- |
| `task init` | 全削除後にビルド、起動、S3 バケット作成、Composer install、スキーマ適用（psqldef）、IDE Helper 生成を実行 |
| `task start` | ボリューム削除後に再構築、起動、スキーマ適用（psqldef）を実行 |
| `task up` | アプリ用プロファイルのコンテナを起動 |
| `task down` | コンテナを停止 |
| `task ps` | コンテナ状態を表示 |
| `task logs` | Compose ログを追跡表示 |

### Docker イメージ

| コマンド | 内容 |
| :-- | :-- |
| `task buildBaseImages` | Apache / FrankenPHP のベースイメージをビルド |
| `task buildLocalPhps` | ローカル開発用 PHP イメージをビルド |
| `task buildImages` | ローカル PHP イメージをビルドし、補助サービスのイメージを pull |
| `task images` | このプロジェクトのコンテナが使う Docker イメージ一覧を表示 |

### コンテナ操作

| コマンド | 内容 |
| :-- | :-- |
| `task exec:apache` | Apache コンテナに入る |
| `task exec:franken` | FrankenPHP コンテナに入る |
| `task run:cmd -- <command>` | PHP 実行環境内で任意コマンドを実行 |
| `task php -- <args>` | PHP コマンドを実行 |
| `task artisan -- <args>` | Artisan コマンドを実行 |
| `task composer -- <args>` | Composer コマンドを実行 |
| `task schema:apply` | psqldef でスキーマを適用 |
| `task schema:dryRun` | psqldef の変更予定を表示 |
| `task schema:export` | psqldef で現在のスキーマを出力 |

### 品質チェックとテスト

| コマンド | 内容 |
| :-- | :-- |
| `task lintCode` | Rector と ECS を適用(自動修正)してから PHPStan、Deptrac、Mago を順に実行 |
| `task stan` | PHPStan を実行 |
| `task deptrac` | Deptrac でレイヤー依存を検査 |
| `task rectorDryRun` | Rector を dry-run で実行 |
| `task runRector` | Rector を適用 |
| `task ecs` | ECS をチェックモードで実行 |
| `task runEcs` | ECS を fix モードで実行 |
| `task mago` | Mago lint をチェックモードで実行 |
| `task onSavePHP` | 保存時向けに Rector / ECS / Mago の fix と PHPStan をまとめて実行 |
| `task phpunit` | PHPUnit を実行(`composer phpunit` 経由) |
| `task ide-helper` | Laravel IDE Helper を生成 |
| `task e2e` | 起動中の環境に対し、`.local` ドメイン経由の E2E テスト(`tests/e2e.sh`)を実行 |

Devbox シェル内では `devbox run composer` で Composer install を実行できます。Docker を起動せずにホストの PHP で全チェック(PSR-4 の厳格検査、パッケージ構成の検査、Rector dry-run、PHPStan、ECS、Deptrac、Mago)をまとめて走らせるには `composer lintCheck` を使います。`composer psrCheck` は `composer validate --strict` のあとに `dump-autoload --strict-psr` を走らせ、ファイル名とクラス名の大文字小文字のずれを macOS(case-insensitive)でも検出します。クラス参照側のずれは PHPStan の `class.nameCase` が拾います。`composer structureCheck` は AGENTS.md の配置規則のうちファイル構成だけで決まるもの(パッケージの固定語彙、`bootstrap/app.php` への登録漏れ、`.claude/skills/` のリンク)を検査します。

どの検査も、そのツールで選べるいちばん厳しい設定にしてあり、警告でも失敗します。外した規則とその理由は `libConfig/` の各設定ファイルにコメントで書いてあります。

PHP 以外は CI の `static-analysis` ジョブで検査します。ワークフローは actionlint と zizmor、Dockerfile は hadolint と Trivy(`trivy config`)、秘密情報の混入は gitleaks が全履歴を、`database/` の SQL は SQLFluff が、README・AGENTS.md・`docs/` は markdownlint-cli2 と textlint が見ます。設定ファイルは PHP のツールと同じく `libConfig/` に置き、CI と `npm run mdCheck` が `--config` などで明示的に渡します。例外は三つで、どれも設定を決まった場所でしか探さない呼ばれ方をするためです。actionlint の `.github/actionlint.yaml`(編集時のフックやエディタが引数なしで呼ぶ)と Renovate の `.github/renovate.json` は `.github/` に残します。hadolint は設定ファイルを持たず、外す規則を Dockerfile の `# hadolint ignore=` に書きます。グローバルの pre-commit フックがリポジトリ直下で引数なしに hadolint を呼ぶからです。依存パッケージの既知の脆弱性は `lint` ジョブの `composer audit --locked` で検出します。ツールの版はワークフローと `package.json` に固定してあり、Renovate が更新します。Markdown の検査は、Node.js があれば `npm ci` のあと `npm run mdCheck` で手元でも実行できます。textlint では文長(`sentence-length`)と漢数字(`arabic-kanji-numbers`)の規則を外しています。前者はインラインコードも文字数に数えてしまい、後者の自動修正は「2 つ」のように数字と単位の間を空ける書き方と合わないためです。

## Xdebug でのステップ実行

Apache 系の PHP コンテナ(`php-app`)は Xdebug を `xdebug.mode=debug`、`xdebug.start_with_request=trigger` で読み込みます(`.docker/local/php/docker-php-ext-xdebug.ini`)。トリガーを付けた実行だけが、ホストの 9003 番(`host.docker.internal`)で待ち受ける IDE へ接続します。

IDE 側では最初に一度だけ、Settings → PHP → Servers に `laravelorbstack` という名前のサーバーを追加し、Use path mappings でプロジェクトルートを `/var/www/html` に対応付けます。対応付けがないと、接続は届いても IDE がブレークポイントを Xdebug に送らないので、どこにも止まりません。

IDE の Listen for PHP Debug Connections をオンにしてから、トリガーを付けて実行します。Artisan コマンドでは、トリガーとサーバー名を環境変数で渡します。

```bash
docker compose -f .docker/compose.yaml exec -e XDEBUG_TRIGGER=1 -e PHP_IDE_CONFIG=serverName=laravelorbstack php-app php artisan memo:show <id>
```

Web 経由では、`https://apachephp.local/?XDEBUG_TRIGGER=1` のようにクエリにトリガーを付けます。フォームの送信やリダイレクト先まで止めたいときは、同じ名前の Cookie を付けてください(ブラウザ拡張の Xdebug Helper でも付けられます)。サーバー名は Apache が `SetEnv PHP_IDE_CONFIG`(`.docker/local/php/000-default.conf`)で渡すので、CLI と同じ `laravelorbstack` の定義がそのまま使われます。この設定がないと、IDE は nginx が転送先に使うホスト名 `laravelorbstack.apache.local` でサーバー定義を探し、見つからずに接続を切ります。

接続のやり取りは `storage/logs/xdebug.log` に残ります。止まらないときは、このログに `breakpoint_set` が出ているかを見てください。出ていなければ、原因は IDE 側のパス対応付けです。`composer phpunit` は `xdebug.mode=develop,coverage` で起動するので、テストの実行中はステップ実行できません。FrankenPHP コンテナ(`php-franken`)ではこの手順を確かめていません。

## コーディング規約

アプリケーションコードでは Laravel のファサードとグローバルヘルパ(`app()`、`config()`、`route()`、`view()`、`now()`、`fake()` など)を使わず、コンストラクタやメソッド引数で契約(`Illuminate\Contracts\*`)を受け取ります。Blade は必要な値をコントローラから渡し、テンプレート内でヘルパを呼びません。

この規約は 2 段階で強制しています。

| 層 | 仕組み | 対象 |
| :-- | :-- | :-- |
| 静的解析 | `libConfig/PhpStan/NoFacadeRule.php`、`libConfig/PhpStan/NoGlobalHelperRule.php` | PHPStan の解析対象パス(`packages/`、`database/`、`tests/` など)。`vendor/laravel/framework` の `helpers.php` に定義された関数はすべて対象 |
| テスト実行時 | `tests/ForbiddenCallMonitor.php`(PHPUnit 拡張、`libConfig/phpunit.xml` で登録)。Xdebug の関数モニタ(`xdebug.mode=develop`)で `laravel/framework` が宣言する全関数と `Illuminate\Support\Facades\Facade` の public static メソッドを記録し、各テストの終了時に `vendor/`・`config/`・コンパイル済み Blade 以外から呼ばれたものがあれば PHPUnit の警告にして実行を失敗させる | テストが実行したコードだけ。`__callStatic` 経由の呼び出しに加え、`swap()` や `shouldReceive()` のように基底クラスに実在する静的メソッドも対象 |

`config/*.php` はコンテナ生成前に評価されるため両方の層で例外です。`env()` や `storage_path()` はそのまま使えます。フレームワーク内部とコンパイル済み Blade からの呼び出しは、テスト実行時の検出では制限しません。本番とローカルの Web・Artisan には検出を入れず、フレームワークも改変せずに動かします。本番で見つけて止めるより、止めることで起きる障害の害のほうが大きいためです。

PHPUnit は `composer phpunit`(または `task phpunit`)で実行してください。`xdebug.mode=develop,coverage` を付けて起動し、カバレッジの要約も出します。`vendor/bin/phpunit` を直接叩くと Xdebug の関数モニタもカバレッジも動かないため、拡張の起動とカバレッジ宣言の検査が成り立たず、実行全体が失敗します。CI も `shivammathur/setup-php` の `coverage: xdebug` で Xdebug を入れています。

テストクラスには `#[CoversClass]` か `#[CoversNamespace]` で検証対象を宣言し、ついでに実行するだけのコードは `#[UsesClass]` か `#[UsesNamespace]` に書きます。宣言した対象を 1 行も実行しないテスト、アサーションのないテスト、出力やグローバル状態を変えるテストは失敗します。実行順は毎回ランダムです。

nullable な型は `?Memo` ではなく `Memo|null` と書きます。ネイティブ型は ECS の `NullableTypeDeclarationFixer`(`syntax: union`)が `--fix` で書き換え、PHPDoc は `libConfig/PhpStan/NoShorthandNullablePhpdocRule.php` が `composer stanCheck` で検出します(php-cs-fixer に PHPDoc の `?T` を直す fixer がないため)。

PHP 構文は `composer.json` の `require.php` の版に合わせます。Rector は `withPhpSets()` がこの版を読み、Mago は `composer magoCheck`(`task mago` も経由)が実行中の PHP の版を `--php-version` で渡すので、`libConfig/mago.toml` には版を書きません。Rector・PHPStan・ECS・Mago の対象には `bootstrap/app.php` と `libConfig/` も入っています。PHPUnit は非推奨・警告・notice が 1 件でもあると失敗します(`failOnAllIssues`)。Laravel はテスト中の非推奨を既定で握りつぶすので、`tests/TestCase.php` で例外に戻しています。

## サンプル実装

`packages/Samples/` は AGENTS.md の配置規則に沿った参照実装です。メモを公開すると本文を S3(RustFS)の `memos/<id>.json` に保存し、一覧用の見出しと公開日時を PostgreSQL の `memos` テーブル(`database/schema.sql`)に記録します。読み出しは Redis のキャッシュを経由し、ブラウザで最後に公開したメモの ID はセッションに残します。編集は ID と公開日時を保ったまま 3 つの保存先を書き換え、削除は 3 つすべてから消します。同じ UseCase を Web、JSON API、Artisan の 3 つから呼びます。

| 操作 | Web | JSON API | Artisan |
| :-- | :-- | :-- | :-- |
| 一覧 | <https://frankenphp.local/memos> | `GET /api/memos` | |
| 公開 | 一覧ページのフォーム | `POST /api/memos` → 201 + `Location` | `task artisan -- memo:publish <file> [--title=] [--json]` |
| 表示 | `/memos/<id>` | `GET /api/memos/<id>` | `task artisan -- memo:show <id> [--json]` |
| 編集 | `/memos/<id>/edit`(`PUT /memos/<id>`) | `PUT /api/memos/<id>` | `task artisan -- memo:edit <id> <file> [--title=] [--json]` |
| 削除 | 表示ページの「削除する」(`DELETE /memos/<id>`) | `DELETE /api/memos/<id>` → 204 | `task artisan -- memo:delete <id>` |

API は `packages/Samples/Http/Api/routes.php` に定義し、`bootstrap/app.php` の `withRouting(api: ...)` で `/api` 配下に載せています(セッションも CSRF もない `api` ミドルウェアグループ)。Blade を返す側は `Http/Web/` で、両方が使う `MemoFormRequest` は `Http/` 直下です。入力は `{"title": "...", "body": "..."}` の JSON かフォーム、出力は `MemoJson` の形(`id`、`title`、`body`、`published_at`。一覧は `body` なし)です。`/api/*` では `Accept` ヘッダがなくても入力不備は 422、見つからないときは 404 の JSON で返します(`withExceptions` の `shouldRenderJsonWhen`)。

`memo:publish` と `memo:edit` はメモの ID だけを標準出力に書くので、`memo:show` や `memo:delete` にそのまま渡せます。`--json` を付けると 1 行 1 レコードの JSON Lines になり、`file` に `-` を渡すと標準入力から本文を読みます。`memo:delete` は成功時に何も出力しません。診断は標準エラーに出し、入力不備は終了コード 2、メモが見つからないときは 1 です。

`Domain/` と `UseCase/` のテストはフレームワークなしで動き、`Http/` と `Console/` のテストは Compose の RustFS、Redis、PostgreSQL に接続します(ローカルでは `task up` と `task schema:apply` のあとに `task phpunit`)。

## ディレクトリ

```text
.
├── .docker/                  # Docker Compose と PHP イメージ定義
│   ├── compose.yaml
│   ├── compose.ci.yaml
│   ├── php.apache.Dockerfile
│   ├── php.franken.Dockerfile
│   ├── common/
│   ├── local/
│   └── flyio/
├── .github/workflows/        # CI(テスト、イメージビルド、Renovate)
├── bootstrap/                # app.php(Application::configure、プロバイダ列挙、例外変換)
├── config/                   # Laravel 設定
├── database/                 # schema.sql (psqldef) / seeder
├── libConfig/                # PHPStan / ECS / Rector / PHPUnit / Deptrac / Mago 設定
│   └── PhpStan/              # 自作 PHPStan ルール
├── packages/                 # アプリケーションコード。<Feature>/{Domain,UseCase,Http,Console,Persistence,Provider,Test}/。ルートとビューは Http/ 配下、全体設定は Common/。配置規則は AGENTS.md
├── tests/                    # PHPUnit の TestCase と拡張(テスト本体は packages/ 配下)
├── Taskfile.yml              # Task コマンド定義
├── composer.json
└── devbox.json               # Devbox 設定
```

## ローカルサービス

Compose 内では次の補助サービスを利用します。

| サービス | 用途 |
| :-- | :-- |
| `php-app` | Apache mod_php 実行環境(`apachephp.local` の実体) |
| `php-franken` | FrankenPHP 実行環境(`frankenphp.local` の実体) |
| `php-cli` | ワンショットコマンド実行用 PHP コンテナ |
| `balancer` | リバースプロキシ。`.local` ドメインを各実行環境へ振り分け |
| `traefik` | Docker Desktop と Linux だけで起動する TLS の入口(`compose.desktop.yaml`)。ホストの `127.0.0.1` で受けて `balancer`、`mail`、`storage`、`postgresql` へ渡す |
| `docker-socket-proxy` | `traefik` に Docker API の読み取りだけを許す中継(Docker Desktop と Linux のみ) |
| `postgresql` | PostgreSQL データベース |
| `cache` | Redis キャッシュ |
| `session` | Redis セッション |
| `mail` | Mailpit |
| `storage` | RustFS による S3 互換ストレージ |
| `setUpStorage` | `sample` バケット作成用の一時コンテナ |

アプリケーションの環境変数は `.docker/local/php/.env.app`(Apache)と `.env.franken`(FrankenPHP)で管理しています。CI では `.env.ci` を使います。変更後はコンテナを再起動してください。

## デプロイ

Fly.io 用の設定ファイルとして `fly-apache.toml` と `fly-franken.toml` を用意しています。

```bash
task deployApache
task deployFranken
task sshFlyIo
```

## トラブルシューティング

コンテナを作り直すには、次を実行します。

```bash
task cleanUpComposeProject
task init
```

Composer 依存関係を作り直すには、次を実行します。

```bash
task composerRefresh
```

コンテナ状態とログは、次で確認します。

```bash
task ps
task logs
```

## 参考資料

- [OrbStack](https://orbstack.dev/)
- [Task](https://taskfile.dev/)
- [Devbox](https://www.jetify.com/devbox)
- [FrankenPHP](https://frankenphp.dev/)
