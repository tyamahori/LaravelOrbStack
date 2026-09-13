@extends('samples::memos.layout')

@section('title', '編集: ' . $memo->title)

@section('content')
  <section>
    <h1>メモを編集する</h1>
    <p class="lead">ID <code>{{ $memo->id->value }}</code> と公開日時はそのまま、見出しと本文だけを書き換えます。</p>
    @if ($errors->any())
      <ul class="errors">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    @endif
    <form method="post" action="{{ route('memos.update', ['id' => $memo->id->value]) }}">
      @csrf
      @method('PUT')
      <label for="title">見出し</label>
      <input id="title" name="title" type="text" maxlength="100" required value="{{ old('title', $memo->title) }}">
      <label for="body">本文</label>
      <textarea id="body" name="body" required>{{ old('body', $memo->body) }}</textarea>
      <button type="submit">更新する</button>
    </form>
  </section>

  <section>
    <p><a href="{{ route('memos.show', ['id' => $memo->id->value]) }}">編集をやめる</a></p>
  </section>
@endsection
