@extends('samples::layout')

@section('content')
  <section>
    <h1>LaravelOrbStack</h1>
    <p class="lead">同じコードを2系統のPHP実行環境で同時に動かし、どちらでも同じ振る舞いになることを確かめるためのLaravelボイラープレートです。このページは{{ $runtime }}が返しています。</p>
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
    <dl class="notes">
      <dt>ファサードとグローバルヘルパを使わない</dt>
      <dd>アプリケーションコードは<code>app()</code>や<code>config()</code>、<code>Route::</code>のような静的な入口を持たず、必要な契約(<code>Illuminate\Contracts\*</code>)をコンストラクタやメソッド引数で受け取ります。このページを返すコントローラも<code>Application</code>と<code>View\Factory</code>を引数で受け取り、Bladeには値だけを渡しています。規約は自作PHPStanルールで静的に、<code>bootstrap/autoload.php</code>の実行時ガードで動的に、二重に強制します。</dd>
      <dt>機能ごとにパッケージを切る</dt>
      <dd>機能は<code>packages/&lt;Feature&gt;/</code>に、そのテストは<code>packages/&lt;Feature&gt;/Test/</code>に置きます。<code>app/</code>と<code>routes/</code>は持たず、Deptracで層の依存方向を固定します。</dd>
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
      <li><a href="{{ route('memos.index') }}">メモのサンプル</a>でS3への保存、Redisキャッシュ、セッションの動作を確かめる</li>
      <li><code>packages/Samples/</code>を手本に次のパッケージを作り、<code>bootstrap/app.php</code>の<code>withProviders</code>と<code>withRouting</code>に列挙する</li>
    </ul>
    <p>手順と各コマンドの詳細はリポジトリの<a href="https://github.com/tyamahori/LaravelOrbStack">README</a>にあります。</p>
  </section>
@endsection

@section('footer')
<footer>
  <span class="nowrap">Laravel {{ $laravelVersion }}</span> / <span class="nowrap">PHP {{ $phpVersion }}</span> / <span class="nowrap">{{ $sapi }}</span>
</footer>
@endsection
