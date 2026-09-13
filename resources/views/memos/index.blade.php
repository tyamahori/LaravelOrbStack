@extends('memos.layout')

@section('title', 'メモ')

@section('content')
  <section>
    <h1>メモ</h1>
    <p class="lead">公開したメモは S3(RustFS)に JSON として保存され、読み出しは Redis のキャッシュを経由します。最後に公開したメモの ID はセッションに覚えておきます。</p>
    @if (is_string($lastPublishedId))
      <p>このブラウザで最後に公開したメモ: <a href="{{ route('memos.show', ['id' => $lastPublishedId]) }}"><code>{{ $lastPublishedId }}</code></a></p>
    @else
      <p class="empty">このブラウザではまだメモを公開していません。</p>
    @endif
  </section>

  <section>
    <h2>公開する</h2>
    @if ($errors->any())
      <ul class="errors">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    @endif
    <form method="post" action="{{ route('memos.store') }}">
      @csrf
      <label for="title">見出し</label>
      <input id="title" name="title" type="text" maxlength="100" required value="{{ old('title') }}">
      <label for="body">本文</label>
      <textarea id="body" name="body" required>{{ old('body') }}</textarea>
      <button type="submit">公開する</button>
    </form>
  </section>

  <section>
    <h2>公開済み</h2>
    @if ($memos === [])
      <p class="empty">まだメモはありません。</p>
    @else
      <ol class="memos">
        @foreach ($memos as $memo)
          <li>
            <time datetime="{{ $memo->publishedAt->format(DATE_ATOM) }}">{{ $memo->publishedAt->format('Y-m-d H:i:s') }}</time>
            <a href="{{ route('memos.show', ['id' => $memo->id->value]) }}">{{ $memo->title }}</a>
          </li>
        @endforeach
      </ol>
    @endif
  </section>
@endsection
