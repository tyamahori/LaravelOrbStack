# 環境構築サンプルアプリケーション

LaravelをOrbStackで動かすサンプルです。環境を立ち上げたあとトップページが表示されます

# 前提
- macOS
- OrbStack v0.16.0以降がインストールされていること

# 用途
- OrbStackでLaravelを動かすとどうなるかを確認します

# メモ
- Docker Composeにおけるプロジェクト名は、`laravelorbstack`
- Makefileにて記載しています。

# 行ったこと

- `.docker`ディレクトリにて自作のdocker環境の設定ファイルを格納しました。
- `compose.yaml` にて必要なコンテナ周りの設定を定義しました。
- プロジェクト直下に`makefile`を作成し、ラッパーコマンドを使うようにしました。

### PHP

`.docker/local/php`にて以下の対応を行いました。

- PHPのDockerfileを作成しました。
- `.env.app` というファイルを作り、Laravelの設定を集約しました。コンテナ内に環境変数として渡す用にしています
- その他 PHPの設定ファイルを格納もしています。

# 起動方法

## 初回、もしくはすべてをやり直す場合

- `$ make init`
- http://php-app.laravelorbstack.orb.local/ へアクセス

## 普段の対応

| コマンド                        | 用途         |
|:----------------------------|:-----------|
| `make up`                   | コンテナを立ち上げる |
| `make down`                 | コンテナを落とす   |
| `make exec-php-app-as-user` | PHPコンテナに入る |

その他コマンドは`Makefile`を確認してください


# できること
- http://orb.local へアクセスできる
- プロジェクトのDocker環境が立ち上がっている場合、http://orb.local にてコンテナにアクセスするためのリンクが表示されている

# 行っていないこと、想定していないこと

Laravelの環境構築のサンプルのため細かなチューニングや設定などはスコープ外としています。

- `.env.app` の不要な項目削除
- configファイルの不要な項目削除
- 不要なライブラリ等の削除
- composer.jsonの設定最適化
- 静的解析ツールなどの導入
- Linterツールなどの導入

上記のような対応は行っていません。

# Makefile警察の方へ
申し訳ありません。Task( https://taskfile.dev/ )を利用するなりしてください。。。後で対応します。。
