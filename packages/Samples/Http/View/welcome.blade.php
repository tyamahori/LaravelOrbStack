<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LaravelOrbStack</title>
  <style>
    :root {
      --paper: #f4f6f4;
      --ink: #172029;
      --ink-soft: #4d5a66;
      --rule: #cfd6d3;
      --active: #1f5f4a;
      --active-tint: #dcebe3;
      --idle: #93a09a;
      --mono: ui-monospace, "SFMono-Regular", Menlo, Consolas, "Hiragino Sans", "Noto Sans JP", monospace;
      --sans: "Hiragino Sans", "Noto Sans JP", "Yu Gothic", system-ui, sans-serif;
    }
    * { box-sizing: border-box; }
    html { background: var(--paper); color: var(--ink); }
    body {
      margin: 0;
      font-family: var(--sans), serif;
      font-size: 1rem;
      line-height: 1.9;
      line-break: strict;
      text-autospace: normal;
      text-spacing-trim: normal;
      font-feature-settings: "palt";
    }
    p, li, dd, td { text-wrap: pretty; }
    h1, h2, h3, th, dt { word-break: auto-phrase; }
    h1, h2, h3 { text-wrap: balance; }
    .nowrap { white-space: nowrap; }
    main { max-width: 42rem; margin: 0 auto; padding: 4rem 1.5rem 5rem; }
    section + section { margin-top: 4.5rem; }
    h1 {
      margin: 0 0 1.5rem;
      font-size: clamp(2.4rem, 7vw, 3.6rem);
      line-height: 1.1;
      letter-spacing: -0.02em;
      font-weight: 700;
    }
    h2 {
      margin: 0 0 1rem;
      font-size: 1.35rem;
      line-height: 1.4;
      font-weight: 700;
    }
    p { margin: 0 0 1rem; }
    a { color: var(--active); text-decoration-thickness: 1px; text-underline-offset: 0.2em; }
    a:focus-visible, button:focus-visible { outline: 3px solid var(--active); outline-offset: 3px; }
    code {
      font-family: var(--mono);
      font-size: 0.9em;
      overflow-wrap: anywhere;
    }
    .runtimes {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.75rem;
      margin: 0 0 1.5rem;
      padding: 0;
      list-style: none;
    }
    .runtimes li {
      padding: 1rem 1.25rem;
      border: 1px solid var(--rule);
      color: var(--idle);
      line-height: 1.5;
    }
    .runtimes li strong { display: block; font-size: 1.1rem; font-weight: 700; }
    .runtimes li span { font-size: 0.85rem; }
    .runtimes li.is-active {
      border-color: var(--active);
      background: var(--active-tint);
      color: var(--active);
    }
    .lead { font-size: 1.1rem; color: var(--ink-soft); }
    dl { margin: 0; }
    dt { margin: 1.75rem 0 0.35rem; font-weight: 700; }
    dt:first-child { margin-top: 0; }
    dd { margin: 0; color: var(--ink-soft); }
    .table-wrap { overflow-x: auto; }
    table { border-collapse: collapse; min-width: 30rem; width: 100%; }
    th, td { padding: 0.55rem 0; text-align: left; vertical-align: top; border-bottom: 1px solid var(--rule); }
    th { font-weight: 700; }
    td:first-child, td code { white-space: nowrap; }
    td code { overflow-wrap: normal; }
    ul.plain { padding-left: 1.2rem; margin: 0; }
    footer {
      max-width: 42rem;
      margin: 0 auto;
      color: var(--ink-soft);
      font-size: 0.85rem;
      border-top: 1px solid var(--rule);
      padding: 1.25rem 1.5rem 3rem;
    }
  </style>
</head>
<body>
<main>
  <section>
    <h1>LaravelOrbStack</h1>
    <ul class="runtimes" aria-label="実行環境">
      <li @class(['is-active' => $sapi === 'apache2handler'])>
        <strong>Apache mod_php</strong>
        <span>https://apachephp.local/</span>
      </li>
      <li @class(['is-active' => $sapi === 'frankenphp'])>
        <strong>FrankenPHP</strong>
        <span>https://frankenphp.local/</span>
      </li>
    </ul>
    <p class="lead">このページは{{ $runtime }}が返しています。同じコードを2系統のPHP実行環境で同時に動かし、どちらでも同じ振る舞いになることを確かめるためのLaravelボイラープレートです。</p>
  </section>

  <section>
    <h2>何を用意しているか</h2>
    <p>OrbStack上のDocker Composeで、Apache mod_phpとFrankenPHPの2つのPHP実行環境を同時に立ち上げます。PostgreSQL、Redis(キャッシュとセッションを分離)、Mailpit、S3互換のRustFSを同じネットワークに置き、ホストへのport公開なしにOrbStackのドメイン解決だけでアクセスします。CIは同じcompose定義にCI用の差分を重ねて実行するので、ローカルとCIで環境の組み立て方が分かれません。</p>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>サービス</th><th>アクセス先</th></tr>
        </thead>
        <tbody>
          <tr><td>PostgreSQL</td><td><code>postgresql.laravelorbstack.orb.local:5432</code></td></tr>
          <tr><td>Redis(キャッシュ)</td><td><code>cache.laravelorbstack.orb.local:6379</code></td></tr>
          <tr><td>Redis(セッション)</td><td><code>session.laravelorbstack.orb.local:6379</code></td></tr>
          <tr><td>Mailpit</td><td><code>mail.laravelorbstack.orb.local:8025</code></td></tr>
          <tr><td>RustFS(S3 API)</td><td><code>storage.laravelorbstack.orb.local:9000</code></td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <section>
    <h2>どう書くか</h2>
    <dl>
      <dt>ファサードとグローバルヘルパを使わない</dt>
      <dd>アプリケーションコードは<code>app()</code>や<code>config()</code>、<code>Route::</code>のような静的な入口を持たず、必要な契約(<code>Illuminate\Contracts\*</code>)をコンストラクタやメソッド引数で受け取ります。このページを返すコントローラも<code>Application</code>と<code>View\Factory</code>を引数で受け取り、Bladeには値だけを渡しています。規約は自作PHPStanルールで静的に、<code>bootstrap/autoload.php</code>の実行時ガードで動的に、二重に強制します。</dd>
      <dt>アプリケーションコードとテストを隣に置く</dt>
      <dd>機能は<code>packages/&lt;Package&gt;/</code>に、そのテストは<code>packages/&lt;Package&gt;/Test/</code>に置きます。<code>app/</code>は持たず、Deptracで層の依存方向を固定します。</dd>
      <dt>ツールの設定を一か所に集める</dt>
      <dd>PHPStan、ECS、Rector、Mago、Deptrac、PHPUnitの設定はすべて<code>libConfig/</code>にあります。実行はTaskがコンテナ内で行うので、ホストにPHPを入れなくても同じ結果になります。</dd>
      <dt>ボイラープレートを増やさない</dt>
      <dd>Laravelの既定と同じ設定ファイル、参照されないクラス、使われない依存は持ちません。<code>Application::configure()</code>で起動し、必要になったものだけを足します。</dd>
    </dl>
  </section>

  <section>
    <h2>次にすること</h2>
    <ul class="plain">
      <li>もう一方の実行環境でこのページを開き、同じ内容が返ることを確かめる</li>
      <li><code>task lintCode</code>で静的解析とフォーマッタを、<code>task phpunit</code>でテストを実行する</li>
      <li><a href="{{ route('memos.index') }}">メモのサンプル</a>で S3 への保存、Redis キャッシュ、セッションの動作を確かめる</li>
      <li><code>packages/Samples/</code>を手本に次のパッケージを作り、<code>routes/web.php</code>から繋ぐ</li>
    </ul>
    <p>手順と各コマンドの詳細はリポジトリの<a href="https://github.com/tyamahori/LaravelOrbStack">README</a>にあります。</p>
  </section>
</main>
<footer>
  <span class="nowrap">Laravel {{ $laravelVersion }}</span> / <span class="nowrap">PHP {{ $phpVersion }}</span> / <span class="nowrap">{{ $sapi }}</span>
</footer>
</body>
</html>
