# LaravelOrbStack

Laravel を OrbStack 上の Docker Compose 環境で動かすためのサンプルプロジェクトです。Apache mod_php と FrankenPHP の 2 系統の PHP 実行環境を同時に立ち上げ、PostgreSQL、Redis、Mailpit、S3 互換ストレージを含むローカル開発環境を構築します。

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

PHP と Composer のバージョンは `composer.json` が正本です。PHP は `require.php`、Composer は `config.composerVersion` から Taskfile・Dockerfile(`--build-arg`)・GitHub Actions が読み取るので、上げるときはそこだけ変更します。Devbox の PHP は `devbox.json` で別途固定しています。

## 前提条件

- macOS
- [OrbStack](https://orbstack.dev/)
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

OrbStack のドメイン連携により、起動後は次の URL でアクセスできます。ホストへの port 公開は行っておらず、ホストからのアクセスはすべて OrbStack のドメイン解決経由です。

| 実行環境 | URL |
|:--|:--|
| Apache | <https://apachephp.local/> |
| FrankenPHP | <https://frankenphp.local/> |

補助サービスへは `<サービス名>.laravelorbstack.orb.local` でコンテナの port に直接アクセスできます。

| サービス | アクセス先 |
|:--|:--|
| PostgreSQL | `postgresql.laravelorbstack.orb.local:5432` |
| Redis(キャッシュ) | `cache.laravelorbstack.orb.local:6379` |
| Redis(セッション) | `session.laravelorbstack.orb.local:6379` |
| Mailpit UI | <http://mail.laravelorbstack.orb.local:8025/> |
| RustFS(S3 API) | `storage.laravelorbstack.orb.local:9000` |

OrbStack 側のローカルドメイン一覧は <https://orb.local/> で確認できます。ドメインが解決しなくなった場合は該当コンテナを `docker restart` すると再登録されます。

## 主なコマンド

### 起動と停止

| コマンド | 内容 |
|:--|:--|
| `task init` | 全削除後にビルド、起動、S3 バケット作成、Composer install、スキーマ適用（psqldef）、IDE Helper 生成を実行 |
| `task start` | ボリューム削除後に再構築、起動、スキーマ適用（psqldef）を実行 |
| `task up` | アプリ用プロファイルのコンテナを起動 |
| `task down` | コンテナを停止 |
| `task ps` | コンテナ状態を表示 |
| `task logs` | Compose ログを追跡表示 |

### Docker イメージ

| コマンド | 内容 |
|:--|:--|
| `task buildBaseImages` | Apache / FrankenPHP のベースイメージをビルド |
| `task buildLocalPhps` | ローカル開発用 PHP イメージをビルド |
| `task buildImages` | ローカル PHP イメージをビルドし、補助サービスのイメージを pull |
| `task images` | このプロジェクトのコンテナが使う Docker イメージ一覧を表示 |

### コンテナ操作

| コマンド | 内容 |
|:--|:--|
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
|:--|:--|
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

Devbox シェル内では `devbox run composer` で Composer install を実行できます。Docker を起動せずにホストの PHP で全チェック(PSR-4 の厳格検査、Rector dry-run、PHPStan、ECS、Deptrac、Mago)をまとめて走らせるには `composer lintCheck` を使います。`composer psrCheck` は `dump-autoload --strict-psr` で、ファイル名とクラス名の大文字小文字のずれを macOS(case-insensitive)でも検出します。クラス参照側のずれは PHPStan の `class.nameCase` が拾います。

## コーディング規約

アプリケーションコードでは Laravel のファサードとグローバルヘルパ(`app()`、`config()`、`route()`、`view()`、`now()`、`fake()` など)を使わず、コンストラクタやメソッド引数で契約(`Illuminate\Contracts\*`)を受け取ります。Blade は必要な値をコントローラから渡し、テンプレート内でヘルパを呼びません。

この規約は 2 段階で強制しています。

| 層 | 仕組み | 対象 |
|:--|:--|:--|
| 静的解析 | `libConfig/PhpStan/NoFacadeRule.php`、`libConfig/PhpStan/NoGlobalHelperRule.php` | PHPStan の解析対象パス(`packages/`、`database/`、`tests/` など)。`vendor/laravel/framework` の `helpers.php` に定義された関数はすべて対象 |
| 実行時 | `bootstrap/autoload.php` が `vendor/laravel/framework` の各 `helpers.php` をガード呼び出し入りで先に評価し、`Illuminate\Support\Facades\Facade` も同様にフレームワークより先に定義する。`vendor/` と `config/` 以外からの呼び出しで `LogicException` を投げる | `public/index.php`、`artisan`、`composer phpunit` の 3 エントリポイント。`helpers.php` の全関数(`collect()`、`now()`、`e()` などコンテナを触らないものも含む)。コンパイル済み Blade も含む |

`config/*.php` はコンテナ生成前に評価されるため両方の層で例外です。`env()` や `storage_path()` はそのまま使えます。フレームワーク内部からのヘルパ・ファサード呼び出しは制限しません。実行時ガードのファサード判定は `__callStatic` で行うため、`swap()` や `shouldReceive()` のように基底クラスに実在する静的メソッドは静的解析のみが対象です。

PHPUnit は `composer phpunit`(または `task phpunit`)で実行してください。`vendor/bin/phpunit` を直接叩くとガードが読み込まれず、`packages/Samples/Test/GlobalHelperGuardTest.php` と `FacadeGuardTest.php` が失敗します。

nullable な型は `?Memo` ではなく `Memo|null` と書きます。ネイティブ型は ECS の `NullableTypeDeclarationFixer`(`syntax: union`)が `--fix` で書き換え、PHPDoc は `libConfig/PhpStan/NoShorthandNullablePhpdocRule.php` が `composer stanCheck` で検出します(php-cs-fixer に PHPDoc の `?T` を直す fixer がないため)。

## サンプル実装

`packages/Samples/` は AGENTS.md の配置規則に沿った参照実装です。メモを公開すると本文を S3(RustFS)の `memos/<id>.json` に保存し、一覧用の見出しと公開日時を PostgreSQL の `memos` テーブル(`database/schema.sql`)に記録します。読み出しは Redis のキャッシュを経由し、ブラウザで最後に公開したメモの ID はセッションに残します。編集は ID と公開日時を保ったまま 3 つの保存先を書き換え、削除は 3 つすべてから消します。同じ UseCase を Web と Artisan の両方から呼びます。

| 操作 | Web | Artisan |
|:--|:--|:--|
| 一覧・公開 | <https://frankenphp.local/memos> | `task artisan -- memo:publish <file> [--title=] [--json]` |
| 表示 | `/memos/<id>` | `task artisan -- memo:show <id> [--json]` |
| 編集 | `/memos/<id>/edit`(`PUT /memos/<id>`) | `task artisan -- memo:edit <id> <file> [--title=] [--json]` |
| 削除 | 表示ページの「削除する」(`DELETE /memos/<id>`) | `task artisan -- memo:delete <id>` |

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
├── bootstrap/                # app.php(Application::configure、プロバイダ列挙、例外変換)/ autoload.php(グローバルヘルパとファサードの実行時ガード)
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
|:--|:--|
| `php-app` | Apache mod_php 実行環境(`apachephp.local` の実体) |
| `php-franken` | FrankenPHP 実行環境(`frankenphp.local` の実体) |
| `php-cli` | ワンショットコマンド実行用 PHP コンテナ |
| `balancer` | リバースプロキシ。OrbStack ドメインを各実行環境へ振り分け |
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

コンテナを作り直す場合:

```bash
task cleanUpComposeProject
task init
```

Composer 依存関係を作り直す場合:

```bash
task composerRefresh
```

コンテナ状態とログを確認する場合:

```bash
task ps
task logs
```

## 参考資料

- [OrbStack](https://orbstack.dev/)
- [Task](https://taskfile.dev/)
- [Devbox](https://www.jetify.com/devbox)
- [FrankenPHP](https://frankenphp.dev/)
