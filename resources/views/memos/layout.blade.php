<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title') - LaravelOrbStack</title>
  <style>
    :root {
      --paper: #f4f6f4;
      --ink: #172029;
      --ink-soft: #4d5a66;
      --rule: #cfd6d3;
      --active: #1f5f4a;
      --active-tint: #dcebe3;
      --mono: ui-monospace, "SFMono-Regular", Menlo, Consolas, "Hiragino Sans", "Noto Sans JP", monospace;
      --sans: "Hiragino Sans", "Noto Sans JP", "Yu Gothic", system-ui, sans-serif;
    }
    * { box-sizing: border-box; }
    html { background: var(--paper); color: var(--ink); }
    body { margin: 0; font-family: var(--sans), serif; line-height: 1.9; line-break: strict; font-feature-settings: "palt"; }
    main { max-width: 42rem; margin: 0 auto; padding: 4rem 1.5rem 5rem; }
    section + section { margin-top: 3.5rem; }
    h1 { margin: 0 0 1.5rem; font-size: clamp(2rem, 6vw, 3rem); line-height: 1.1; letter-spacing: -0.02em; text-wrap: balance; }
    h2 { margin: 0 0 1rem; font-size: 1.35rem; line-height: 1.4; }
    p { margin: 0 0 1rem; text-wrap: pretty; }
    a { color: var(--active); text-decoration-thickness: 1px; text-underline-offset: 0.2em; }
    a:focus-visible, button:focus-visible, input:focus-visible, textarea:focus-visible { outline: 3px solid var(--active); outline-offset: 3px; }
    code { font-family: var(--mono); font-size: 0.9em; overflow-wrap: anywhere; }
    .lead { font-size: 1.1rem; color: var(--ink-soft); }
    .status { padding: 0.75rem 1.25rem; margin: 0 0 2rem; border: 1px solid var(--active); background: var(--active-tint); color: var(--active); }
    .errors { margin: 0 0 1rem; padding-left: 1.2rem; color: #8a2b1c; }
    label { display: block; font-weight: 700; margin: 1.25rem 0 0.35rem; }
    input, textarea { width: 100%; padding: 0.6rem 0.75rem; font: inherit; line-height: 1.6; border: 1px solid var(--rule); background: #fff; color: var(--ink); }
    textarea { min-height: 10rem; resize: vertical; }
    button { margin-top: 1.5rem; padding: 0.7rem 1.5rem; font: inherit; font-weight: 700; color: #fff; background: var(--active); border: 0; cursor: pointer; }
    ol.memos { margin: 0; padding: 0; list-style: none; }
    ol.memos li { padding: 0.75rem 0; border-bottom: 1px solid var(--rule); display: flex; gap: 1rem; flex-wrap: wrap; align-items: baseline; }
    ol.memos time { color: var(--ink-soft); font-size: 0.85rem; font-family: var(--mono); white-space: nowrap; }
    pre.body { white-space: pre-wrap; overflow-wrap: anywhere; margin: 0; padding: 1.25rem; font: inherit; border: 1px solid var(--rule); background: #fff; }
    dl { margin: 0 0 2rem; display: grid; grid-template-columns: max-content 1fr; gap: 0.25rem 1.5rem; }
    dt { font-weight: 700; }
    dd { margin: 0; color: var(--ink-soft); }
    .empty { color: var(--ink-soft); }
  </style>
</head>
<body>
<main>
  @if (session('status'))
    <p class="status" role="status">{{ session('status') }}</p>
  @endif
  @yield('content')
</main>
</body>
</html>
