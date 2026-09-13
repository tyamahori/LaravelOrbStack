@extends('memos.layout')

@section('title', $memo->title)

@section('content')
  <section>
    <h1>{{ $memo->title }}</h1>
    <dl>
      <dt>ID</dt>
      <dd><code>{{ $memo->id->value }}</code></dd>
      <dt>公開日時</dt>
      <dd><time datetime="{{ $memo->publishedAt->format(DATE_ATOM) }}">{{ $memo->publishedAt->format('Y-m-d H:i:s') }}</time></dd>
    </dl>
    <pre class="body">{{ $memo->body }}</pre>
  </section>

  <section>
    <p><a href="{{ route('memos.index') }}">メモ一覧へ戻る</a></p>
  </section>
@endsection
