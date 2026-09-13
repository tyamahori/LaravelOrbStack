# LaravelOrbStack の設計・実装ルール

このファイルはエージェント(Claude Code、Codex、OMP)向けの正本で、`CLAUDE.md` はここへのシンボリックリンクです。環境構築・コマンド一覧・ヘルパ禁止の仕組みは `README.md` に書いてあり、ここでは重複させません。機械横断の作法(コミット、日本語、ツール選択)はグローバル指示に任せ、このリポジトリ固有の「どこに何を置き、何に依存してよいか」だけを決めます。

このリポジトリは数年単位の運用を前提にします。設計の柱は四つで、**機能ごとにパッケージを切り(package by feature)、パッケージの中では依存を内側に向け(clean architecture)、部品は一つの仕事だけをして組み合わせ(Unix 哲学)、フレームワークは外側の層でだけ、しかし遠慮なく使う**。四つとも、いま書く速さではなく、書いた人がいなくなった後に変更する安さのために選んでいます。迷ったら「3 年後に別の人がこの変更だけを読んで直せるか」で判定してください。以下はその四つを、このリポジトリのディレクトリと検査ツールに落としたものです。

## 機能ごとにパッケージを切る

アプリケーションコードは `packages/<Feature>/` に置きます。PSR-4 で `LaravelOrbStack\<Feature>` に対応し(`composer.json`)、`<Feature>` は `Ordering`、`Billing` のような業務上の能力の名前です。`Controllers`、`Services`、`Repositories` のような技術的な役割名でディレクトリを切ることはしません。役割で切ると一つの仕様変更が複数ディレクトリに散り、機能で切ると一つのディレクトリに収まります。

パッケージの中は、ファイルが 1 つでも次の固定語彙のサブディレクトリに置きます(`packages/Samples/Http/HomeController.php` がこの形)。別の名前を発明しないでください。パッケージ直下に置いたクラスは Deptrac がどこにも依存できない層として扱うので、最初の `use` で検査に落ちます。

| ディレクトリ | 置くもの |
|:--|:--|
| `Domain/` | エンティティ、値オブジェクト、ドメイン例外、外側へ要求する interface(port) |
| `UseCase/` | 1 つの操作を表すクラス。`RegisterUser` のような動詞+名詞で命名し、公開メソッドは `__invoke` 1 つ |
| `Http/` | Controller、FormRequest、レスポンス整形。ルート定義は `Http/routes.php`、Blade は `Http/View/`(ビュー名は `<feature>::` 名前空間付き) |
| `Console/` | Artisan コマンド |
| `Persistence/` | Eloquent モデル、port の実装(リポジトリ)、外部 API クライアント |
| `Provider/` | ServiceProvider(`<Feature>ServiceProvider.php`)。port と実装の `bind`、ビュー名前空間(`loadViewsFrom`)、Artisan コマンド(`commands()`)の登録を担い、`bootstrap/app.php` の `withProviders([...], withBootstrapProviders: false)` に列挙する(`bootstrap/providers.php` は置かない)。`Http/routes.php` は同じファイルの `withRouting(web: [...])` に列挙する。プロバイダの `boot()` で `$router->group()` すると RouteServiceProvider が登録されず名前索引(`refreshNameLookups`)が更新されないので、`route('name')` が解決できない(テストで検出済み) |
| `Test/` | そのパッケージのテスト(`*Test.php`)と、port のテスト用実装(`Fake*.php`、例 `FakeMemoStore`)。`tests/` にはテスト基盤だけを置く |

パッケージ間の参照は、相手の `Domain/` にある interface と値オブジェクトに限ります。他パッケージの UseCase、Http、Persistence のクラスを import したり注入したりしてはいけません。A が B の能力を必要とするなら、A が自分の `Domain/` に port を定義し、B 側(または `packages/Common/`)がそれを実装して A の `Provider/` で束ねます。この「相手の `Domain/` だけ」という制約は Deptrac の層がパッケージ横断で定義されているため機械検査できず、レビューで守ります。

`packages/Common/` はプロジェクト全体に効くものを置く唯一の場所で、業務機能ではありません。中身は他のパッケージと同じ固定語彙のサブディレクトリに分けます。アプリ全体の設定(時計、日付クラス、Eloquent の strict モード)は `Common/Provider/AppServiceProvider.php` に置き、特定パッケージの port を `bind` してはいけません。それは各パッケージの `Provider/` の仕事です(`packages/Samples/Provider/SamplesServiceProvider.php` がこの形)。3 つ以上のパッケージが同じ値オブジェクトや port を使うようになったら `Common/Domain/` へ移し、2 つ目までは各パッケージに置いたままにします。パッケージを消すときは、そのディレクトリと `bootstrap/app.php` の `withProviders` の行を消せば結線が残りません。

リポジトリ直下に残すのは Laravel の入口契約と実行時の書き込み先だけです。`bootstrap/`(`public/index.php` と `artisan` が読む `app.php`、実行時ガードの `autoload.php`)、`config/`(コンテナ生成前に評価される)、`public/`、`database/`(`schema.sql` と seeder)、`storage/`(Docker・Xdebug・Apache が参照する書き込み先)、`tests/`(テスト基盤のみ)です。`app/`、`routes/`、`resources/`、`packages/Shared/` は作りません(`App\` 名前空間は `composer.json` から外してあります)。ルート定義は `packages/<Feature>/Http/routes.php`、Blade は `packages/<Feature>/Http/View/`、全体設定は `packages/Common/` に置きます。`git mv` で中身を移したあとの空ディレクトリは Git に残らないので、そのまま削除します。

## パッケージの中では依存を内側に向ける

依存の向きは `Http/`・`Console/`・`Persistence/` → `UseCase/` → `Domain/` の一方向です。`Domain/` と `UseCase/` は `Illuminate\*`、`Symfony\*`、`Carbon\*`、PDO、ファイルシステムを import しません。これらは HTTP も DB も時計もなしで PHPUnit から直接 new して動くのが完成条件です。時刻は `Psr\Clock\ClockInterface` を、乱数や外部呼び出しは `Domain/` の port を注入して受け取ります。

境界を越えるデータは `readonly` なプレーンオブジェクトか値オブジェクトです。Request、Eloquent モデル、Collection を UseCase の引数や戻り値にしないでください。

信頼境界での検証は一度だけ行います。HTTP 入力は `Http/` の FormRequest でパースして型付きの値にし、内側では検証済みとして扱います。「常に成り立つこと」(空でない、範囲内、一意)は値オブジェクトのコンストラクタか DB 制約(`database/schema.sql`)で守り、利用側で再検証しません。

失敗の扱いは一つの方針に揃えます。業務上あり得る結果(見つからない、重複、状態不正)は `Domain/` に定義した例外か戻り値で表し、Http 層がステータスへ変換します。プログラミング誤りとインフラ障害はそのまま伝播させ、途中で握りつぶしたり catch してログだけ出して続行したりしません。

Laravel のファサードとグローバルヘルパは全面禁止で、`Illuminate\Contracts\*` をコンストラクタやメソッド引数で受け取ります。禁止の範囲、`config/` の例外、静的解析と実行時ガードの二段構えは `README.md` の「コーディング規約」を参照してください。`Illuminate\Contracts\*` を受け取ってよいのは `Http/`・`Console/`・`Persistence/`・`Provider/` だけで、`UseCase/` は自分の `Domain/` の port だけを受け取ります。

interface は、実装が今この場で 2 つある(本物とテスト用の代替、または本当に 2 実装)か、パッケージ境界を越える port である場合に限って作ります。実装 1 つの interface、製品 1 つの factory、変わらない値の設定項目は作りません。

## 部品は一つの仕事だけをして組み合わせる

クラスとメソッドは一つの仕事で止めます。説明に「〜して、かつ〜する」が出るなら二つに分けます。真偽値のフラグ引数で振る舞いを切り替えるより、二つの UseCase を用意して呼び分けるほうを選びます。大きな操作は小さな UseCase を順に呼ぶ合成で作り、既存の UseCase に分岐を足して育てません。

Artisan コマンド(`Console/`)は薄く保ち、引数の解釈と出力整形だけを担って本体は UseCase に委ねます。入力は引数か標準入力、結果は標準出力、進捗と警告は標準エラー、成功時は黙って終了コード 0、失敗時は非 0 です。機械が読む出力は `--json` オプションで JSON Lines(1 行 1 レコード)にし、人向けの装飾を混ぜません。あるコマンドの出力が別のコマンドの入力として加工なしに渡せるかを設計時に確かめてください。

`Taskfile.yml` と `composer.json` の scripts も同じ流儀です。新しいタスクは既存コマンドのパイプラインで書けないか先に試し、成功時は静かに、失敗時は診断を標準エラーに出して非 0 で終わるようにします。

## フレームワークは薄く、使うべきところでは使う

Laravel を薄く使うとは、フレームワークに触れる層を `Http/`・`Console/`・`Persistence/`・`Provider/` に限ることであって、その層でフレームワークを避けることではありません。`Domain/` と `UseCase/` がフレームワークなしで動くのは前節の通りですが、外側の層で Laravel が既に解いている問題を自前で解き直すのは、薄さではなく二重実装です。

外側の層では次のものをそのまま使います。HTTP 入力の検証は FormRequest、DB は Eloquent モデルとクエリビルダ、キャッシュ・セッション・ファイルシステム・時計は `Illuminate\Contracts\*` と `Psr\Clock\ClockInterface` の注入、依存の束ね方はコンテナの `bind`、ドメイン例外から HTTP ステータスへの変換は `bootstrap/app.php` の `withExceptions`、Artisan は `Illuminate\Console\Command` の signature と終了コード定数、Blade は `route()`・`old()`・`session()`・`@csrf`・`@method`。`packages/Samples/` がこの形で、Eloquent を包む Repository 基底クラスも、FormRequest の代わりの Validator ラッパも、独自の Response ビルダも持ちません。

避けるのは、フレームワークの機能のうち呼び出し側から見えない場所で振る舞いを変えるものです。モデルイベント・オブザーバ・グローバルスコープ・アクセサやミューテータでの変換・暗黙のモデルバインディングによる取得は、UseCase の流れを読んでも何が起きるか分からなくなるので使わず、同じことを UseCase の明示的な呼び出しで書きます。Eloquent モデルは `Persistence/` の中で port を実装するための道具であり、その外に出しません(`MemoRecord` は `EloquentMemoIndex` だけが触り、`MemoHeading` に詰め替えて返す)。

判断に迷ったら次の順で考えます。それは `Domain/`・`UseCase/` の関心か(なら Laravel を使わない)。外側の層の関心で Laravel に既製の解があるか(なら包まずにそのまま使う)。その既製の解は呼び出し箇所から振る舞いが読めるか(読めないなら明示的な呼び出しに置き換える)。

## 長期運用で効いてくること

永続化した形式は互換境界です。S3 の JSON、キャッシュの値、セッションのキー、DB の列は、それを書いたコードより長く生きます。形式を変えるときは旧形式を読めるようにするか、移行手順を同じ変更に含めます。テスト用の `Fake*` は往復のずれを検出できないので(`published_at` のマイクロ秒落ちは実ストアの往復で初めて出た)、`Persistence/` の実装ごとに、実ストアへ書いて読み戻して等しいことを確かめるテストを 1 本置きます。

依存の更新は定常作業です。PHP と Laravel のメジャー追従を後回しにせず、PHP バージョンの正は `composer.json` の `require.php`、更新手段は Rector のアップグレードセットとします。依存の更新は機能変更と混ぜず、独立したコミットにします。

消すことは変更の一部です。使われなくなったコード、互換のための分岐、切り替えフラグは、それを不要にした同じ変更で消します。「後で消す」は残り続けます。

決定の理由はコードにもテストにも残らないので、コミットをまたいで残る決定(パッケージ境界、データモデル、外部サービスの選択、永続化形式)は `docs/adr/NNNN-kebab-title.md` に ADR として書きます。既存の ADR を覆すときは、新しい番号で書いて旧 ADR の冒頭に「superseded by」を追記し、旧 ADR 自体は消しません。

## 検査で強制していること

| 規則 | 仕組み | 状態 |
|:--|:--|:--|
| ファサード・グローバルヘルパ禁止 | `libConfig/PhpStan/NoFacadeRule.php`、`NoGlobalHelperRule.php`、`bootstrap/autoload.php` の実行時ガード | 強制済み |
| nullable は `T\|null`(`?T` 禁止) | ECS `NullableTypeDeclarationFixer`(ネイティブ型、自動修正)、`libConfig/PhpStan/NoShorthandNullablePhpdocRule.php`(PHPDoc) | 強制済み |
| PHPStan level max + strict rules | `libConfig/phpstan.neon` | 強制済み |
| レイヤー依存とディレクトリ配置 | `libConfig/deptrac.yaml`(`composer deptracCheck` は `--fail-on-uncovered` 付き) | 強制済み。層は namespace で判定し、`Domain/`・`UseCase/` から `Illuminate\*`・`Symfony\*`・`Carbon\*`・PDO への依存、パッケージ直下のクラスの依存、`Persistence/` から `UseCase/` への依存を落とす。`Provider/` は全層に依存できる |
| PSR-4 と大文字小文字 | `composer psrCheck`、PHPStan `class.nameCase` | 強制済み |

検査は Composer のラッパーで走らせます。`composer stanCheck -- --no-progress --error-format=raw`、`ecsCheck`、`rectorCheck`、`magoCheck`、`deptracCheck`、`phpunit`(`APP_KEY` が必要)。フォーマッタは ECS だけで、Mago は lint 専用です。`vendor/bin/phpunit` を直接叩くと実行時ガードが読み込まれず `packages/Samples/Test/` のガード系テストが落ちます。ローカルで `SampleControllerTest` が落ちるのは `libConfig/phpunit.xml` が Redis を要求する既知の状態で、コンテナ内と CI では通ります。

Larastan は `Command::argument()` と `option()` の戻り型を signature から推論します。`is_string($this->argument('file'))` のような型ガードは書かず、推論が外れるときは signature の書き方を直します。クラスやディレクトリを移したあとは `composer dump-autoload -q` を実行してから検査を回します。

## 新しいコードを書く前に答える六つの問い

1. どの `packages/<Feature>/` に属するか。既存に入らない理由は何か。
2. 呼び出し側が知る必要があるのは何か(interface)。隠せるのは何か。
3. 信頼できない入力はどこから入り、どの型に変わるか。
4. 常に成り立つべきことは何で、どの一箇所がそれを守るか。
5. 失敗は呼び出し側にどう見えるか。
6. この決定を将来やめるとき、どこを触ればよいか。永続化した形式は残るか。

一つでも空欄なら設計はまだ固まっていません。決定がコミットをまたいで残るものは、前節の通り `docs/adr/` に ADR として残します。
