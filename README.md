# 環境構築サンプルアプリケーション

LaravelをOrbStackで動かすサンプルです。環境を立ち上げたあとトップページが表示されます

# 前提
- macOS
- [OrbStack](https://orbstack.dev/) がインストールされていること
- [Task](https://taskfile.dev/) がインストールされていること

# 用途
- OrbStackでLaravelを動かすとどうなるかを確認します

# 行ったこと

- `.docker`ディレクトリにて自作のdocker環境の設定ファイルを格納しました。
- `compose.yaml` にて必要なコンテナ周りの設定を定義しました。
- プロジェクト直下に`Taskfile.yml`を作成し、ラッパーコマンドを使うようにしました。

### PHP

`.docker/local/php`にて以下の対応を行いました。

- PHPのDockerfileを作成しました。
- `.env.app` というファイルを作り、Laravelの設定を集約しました。コンテナ内に環境変数として渡す用にしています
- その他 PHPの設定ファイルを格納もしています。

# 起動方法

## 初回、もしくはすべてをやり直す場合

- `$ task init`
- https://php-app.laravelorbstack.orb.local/ へアクセスするとLaravelのデフォルトページが表示されます。

## 普段の対応

| コマンド                                 | 用途                  |
|:-------------------------------------|:--------------------|
| `$ task up`                          | コンテナを立ち上げる          |
| `$ task down`                        | コンテナを落とす            |
| `$ task buildPhpImageForFrankenPHP ` | FrankenPHPイメージを作り直す |
| `$ task buildPhpImage `              | ApachePHPイメージを作り直す  |
| `$ task exec:apache`                 | ApachePHPコンテナに入る    |


# できること
- OrbStackが起動していると、https://orb.local にアクセスできます。

# 行っていないこと、想定していないこと

Laravelの環境構築のサンプルのため細かなチューニングや設定などはスコープ外としています。

- `.env.app` の不要な項目削除
- configファイルの不要な項目削除
- composer.jsonの設定最適化

上記のような対応は行っていません。

# Memo
- https://caddy.community/t/caddy-trust-in-docker-for-local-certificates/18122
