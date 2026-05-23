# LaravelOrbStack

Laravel を OrbStack 上の Docker Compose 環境で動かすためのサンプルプロジェクトです。Apache mod_php と FrankenPHP の 2 系統の PHP 実行環境を同時に立ち上げ、PostgreSQL、MySQL、Redis、Mailpit、S3 互換ストレージを含むローカル開発環境を構築します。

## 構成

- PHP 8.5
- Laravel 13
- Apache mod_php
- FrankenPHP
- PostgreSQL
- MySQL
- Redis
- Mailpit
- RustFS
- Docker Compose + OrbStack
- Task
- Devbox

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

OrbStack のドメイン連携により、起動後は次の URL でアクセスできます。

| 実行環境 | URL |
|:--|:--|
| Apache | <https://apachephp.local/> |
| FrankenPHP | <https://frankenphp.local/> |

OrbStack 側のローカルドメイン一覧は <https://orb.local/> で確認できます。

## 主なコマンド

### 起動と停止

| コマンド | 内容 |
|:--|:--|
| `task init` | 全削除後にビルド、起動、S3 バケット作成、Composer install、migration、IDE Helper 生成を実行 |
| `task start` | ボリューム削除後に再構築して起動 |
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
| `task images` | Docker イメージ一覧を表示 |

### コンテナ操作

| コマンド | 内容 |
|:--|:--|
| `task exec:apache` | Apache コンテナに入る |
| `task exec:franken` | FrankenPHP コンテナに入る |
| `task run:cmd -- <command>` | PHP 実行環境内で任意コマンドを実行 |
| `task php -- <args>` | PHP コマンドを実行 |
| `task artisan -- <args>` | Artisan コマンドを実行 |
| `task composer -- <args>` | Composer コマンドを実行 |

### 品質チェックとテスト

| コマンド | 内容 |
|:--|:--|
| `task lintCode` | PHPStan、Rector、ECS を実行 |
| `task stan` | PHPStan を実行 |
| `task rectorDryRun` | Rector を dry-run で実行 |
| `task runRector` | Rector を適用 |
| `task ecs` | ECS をチェックモードで実行 |
| `task runEcs` | ECS を fix モードで実行 |
| `task phpunit` | PHPUnit を実行 |
| `task ide-helper` | Laravel IDE Helper を生成 |

Devbox シェル内では `devbox run lint`、`devbox run stan`、`devbox run fixer`、`devbox run rector` も利用できます。

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
│   ├── prod/
│   └── flyio/
├── app/                      # Laravel アプリケーション
├── config/                   # Laravel 設定
├── database/                 # migration / seeder / factory
├── libConfig/                # PHPStan / ECS / Rector / PHPUnit / Deptrac 設定
├── packages/                 # サンプルパッケージ
├── routes/                   # Laravel ルート定義
├── tests/                    # PHPUnit テスト
├── Taskfile.yml              # Task コマンド定義
├── composer.json
└── devbox.json               # Devbox 設定
```

## ローカルサービス

Compose 内では次の補助サービスを利用します。

| サービス | 用途 |
|:--|:--|
| `postgresql` | PostgreSQL データベース |
| `mysql` | MySQL データベース |
| `cache` | Redis キャッシュ |
| `session` | Redis セッション |
| `mail` | Mailpit |
| `storage` | RustFS による S3 互換ストレージ |
| `setUpStorage` | `sample` バケット作成用の一時コンテナ |

アプリケーションの環境変数は `.docker/local/php/.env.app` と `.docker/local/php/.env.franken` で管理しています。変更後はコンテナを再起動してください。

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
