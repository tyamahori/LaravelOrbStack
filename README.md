# 環境構築サンプルアプリケーション

LaravelをOrbStackで動かすサンプルです。環境を立ち上げたあとトップページが表示されます

## 概要

このプロジェクトは、Laravel 12とPHP 8.4を使用したWebアプリケーションの開発環境をOrbStackで構築するサンプルです。ApacheとFrankenPHPの両方に対応し、PostgreSQLとMySQLのデータベース環境も含まれています。

## 前提条件

- macOS
- [OrbStack](https://orbstack.dev/) がインストールされていること
- [Task](https://taskfile.dev/) がインストールされていること
- [Devbox](https://www.jetify.com/devbox) がインストールされていること（推奨）

## 技術スタック

- **PHP**
- **Laravel**
- **Webサーバー**: ApachePHP / FrankenPHP
- **データベース**: PostgreSQL / MySQL 
- **コンテナ**: Docker + OrbStack
- **タスクランナー**: Task
- **開発環境**: Devbox

## 用途

- OrbStackでLaravelを動かすとどうなるかを確認します
- ApacheとFrankenPHPの両方の環境でのパフォーマンス比較
- コンテナベースの開発環境の構築方法の学習

# 行ったこと

- `.docker`ディレクトリにて自作のdocker環境の設定ファイルを格納しました。
- `compose.yaml` にて必要なコンテナ周りの設定を定義しました。
- プロジェクト直下に`Taskfile.yml`を作成し、ラッパーコマンドを使うようにしました。

### PHP

`.docker/local/php`にて以下の対応を行いました。

- PHPのDockerfileを作成しました。
- `.env.app` というファイルを作り、Laravelの設定を集約しました。コンテナ内に環境変数として渡す用にしています
- その他 PHPの設定ファイルを格納もしています。

# セットアップ手順

## 1. 初回セットアップ

```bash
# プロジェクトをクローン
git clone <repository-url>
cd laravelorbstack

# Devboxを使用する場合（推奨）
devbox shell

# 初回環境構築
task init
```

## 2. アクセス確認

セットアップ完了後、以下のURLにアクセスしてLaravelのデフォルトページが表示されることを確認してください：

- **Apache版**: https://php-app.laravelorbstack.orb.local/
- **FrankenPHP版**: https://php-franken.laravelorbstack.orb.local/

## 利用可能なコマンド

### 基本操作

| コマンド | 用途 |
|:--------|:-----|
| `task init` | 初回セットアップ（全てクリーンアップしてから構築） |
| `task start` | 通常の起動（ボリュームは保持） |
| `task up` | コンテナを立ち上げる |
| `task down` | コンテナを停止する |
| `task ps` | コンテナの状態確認 |
| `task logs` | ログを表示 |

### イメージ管理

| コマンド | 用途 |
|:--------|:-----|
| `task buildLocalPhps` | 両方のPHPイメージをビルド |
| `task buildLocalApacheModPhp` | Apache版PHPイメージをビルド |
| `task buildLocalFrankenPhp` | FrankenPHP版イメージをビルド |
| `task images` | Dockerイメージ一覧表示 |

### コンテナ操作

| コマンド | 用途 |
|:--------|:-----|
| `task exec:apache` | Apache PHPコンテナに入る |
| `task exec:franken` | FrankenPHPコンテナに入る |

### 開発ツール

| コマンド | 用途 |
|:--------|:-----|
| `task composer` | Composerコマンド実行 |
| `task artisan` | Artisanコマンド実行 |
| `task php` | PHPコマンド実行 |
| `task lintCode` | コード品質チェック（PHPStan + ECS + Rector） |
| `task phpunit` | PHPUnitテスト実行 |
| `task ide-helper` | IDE Helper生成 |


# プロジェクト構成

## ディレクトリ構造

```
.
├── .docker/                 # Docker設定ファイル
│   ├── compose.yaml        # Docker Compose設定
│   ├── php.apache.Dockerfile # Apache版PHP Dockerfile
│   ├── php.franken.Dockerfile # FrankenPHP版 Dockerfile
│   ├── common/             # 共通設定
│   ├── local/              # ローカル開発用設定
│   ├── prod/               # 本番用設定
│   └── flyio/              # Fly.io デプロイ用設定
├── libConfig/              # 開発ツール設定
│   ├── ecs.php            # Easy Coding Standard設定
│   ├── phpstan.neon       # PHPStan設定
│   ├── rector.php         # Rector設定
│   ├── phpunit.xml        # PHPUnit設定
│   └── deptrac.yaml       # Deptrac設定
├── packages/               # カスタムパッケージ
├── Taskfile.yml           # Taskコマンド定義
└── devbox.json            # Devbox設定
```

## 開発ツール

このプロジェクトには以下の開発ツールが組み込まれています：

### コード品質管理

- **PHPStan**: 静的解析ツール（レベル9設定）
- **Easy Coding Standard (ECS)**: コーディング規約チェック
- **Rector**: PHPコードの自動リファクタリング
- **Deptrac**: アーキテクチャ依存関係の管理

### テスト

- **PHPUnit**: ユニットテスト・統合テスト
- **Laravel IDE Helper**: IDE補完サポート

### 実行例

```bash
# コード品質チェック（全て実行）
task lintCode

# 個別実行
task stan      # PHPStan実行
task ecs       # ECS実行
task rector    # Rector実行（ドライラン）
task phpunit   # テスト実行
```

## デプロイメント

### Fly.io デプロイ

```bash
# Apache版をデプロイ
task deployApache

# FrankenPHP版をデプロイ
task deployFranken

# デプロイ後のSSH接続
task sshFlyIo
```

## トラブルシューティング

### よくある問題

1. **コンテナが起動しない場合**
   ```bash
   task cleanUpComposeProject  # 全てクリーンアップ
   task init                   # 再構築
   ```

2. **Composerの依存関係エラー**
   ```bash
   task composerRefresh        # Composer完全リフレッシュ
   ```

3. **データベース接続エラー**
   ```bash
   task ps                     # コンテナ状態確認
   task logs                   # ログ確認
   ```

### パフォーマンス比較

ApacheとFrankenPHPの両方の環境が利用可能なため、パフォーマンス比較が可能です：

- **Apache**: 従来のmod_php環境
- **FrankenPHP**: Go製の高速PHPサーバー

## 利用可能な機能

- **OrbStack統合**: https://orb.local でローカル環境一覧表示
- **HTTPS対応**: 自動SSL証明書生成
- **マルチデータベース**: PostgreSQL・MySQL両対応
- **開発ツール統合**: 保存時の自動コード整形・解析

## 制限事項・スコープ外

このプロジェクトは環境構築のサンプルのため、以下は対応していません：

- `.env.app` の不要な項目削除
- configファイルの不要な項目削除
- composer.jsonの設定最適化
- 本番環境向けのセキュリティ設定
- パフォーマンスチューニング

## 参考資料

- [OrbStack公式ドキュメント](https://orbstack.dev/)
- [Task公式ドキュメント](https://taskfile.dev/)
- [Devbox公式ドキュメント](https://www.jetify.com/devbox)
- [FrankenPHP公式ドキュメント](https://frankenphp.dev/)
- [Caddy HTTPS設定](https://caddy.community/t/caddy-trust-in-docker-for-local-certificates/18122)
