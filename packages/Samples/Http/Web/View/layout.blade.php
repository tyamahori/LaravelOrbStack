<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@hasSection('title')@yield('title') | @endif LaravelOrbStack</title>
  <style>
    :root {
      --paper: #e8eae6;
      --sheet: #ffffff;
      --ink: #15181b;
      --muted: #5b636b;
      --rule: #b8bec3;
      --live: #0f3f8c;
      --danger: #96271c;
      --serif: "Hiragino Mincho ProN", "Noto Serif JP", "Yu Mincho", "Source Han Serif JP", serif;
      --sans: "Hiragino Sans", "Noto Sans JP", "Yu Gothic", system-ui, sans-serif;
      --mono: ui-monospace, "SFMono-Regular", Menlo, Consolas, "Hiragino Sans", "Noto Sans JP", monospace;
    }
    * { box-sizing: border-box; }
    h1, h2, p, dl, dt, dd, ol, ul, pre { margin: 0; }
    html { background: var(--paper); color: var(--ink); }
    body {
      margin: 0;
      font-family: var(--sans);
      font-size: 1rem;
      line-height: 1.9;
      line-break: strict;
      text-autospace: normal;
      text-spacing-trim: normal;
      font-feature-settings: "palt";
    }
    p, li, dd, td { text-wrap: pretty; }
    h1, h2, th, dt { word-break: auto-phrase; }
    h1, h2 { text-wrap: balance; font-family: var(--serif); font-weight: 600; }
    .nowrap { white-space: nowrap; }

    main { max-width: 40rem; margin: 0 auto; padding: 6rem 1.5rem 6rem; }
    main > * + * { margin-top: 5rem; }
    main section > * + * { margin-top: 1.75rem; }
    main section > h1 + *, main section > h2 + * { margin-top: 1rem; }
    main > .status + * { margin-top: 3rem; }
    h1 { font-size: clamp(2.5rem, 8vw, 4rem); line-height: 1.15; letter-spacing: 0.01em; }
    h2 { font-size: 1.7rem; line-height: 1.35; }
    .lead { font-size: 1.125rem; color: var(--muted); }
    .muted { color: var(--muted); }
    a { color: var(--ink); text-decoration-thickness: 1px; text-underline-offset: 0.25em; text-decoration-color: var(--rule); }
    a:hover { text-decoration-color: currentColor; }
    :focus-visible { outline: 2px solid var(--ink); outline-offset: 3px; }
    code { font-family: var(--mono); font-size: 0.875em; overflow-wrap: anywhere; }
    time { font-family: var(--mono); font-size: 0.875rem; font-variant-numeric: tabular-nums; white-space: nowrap; }

    .status { padding: 0.4rem 0 0.4rem 1.25rem; border-left: 3px solid var(--live); color: var(--live); }
    .errors { padding-left: 1.25rem; color: var(--danger); }

    form > * + * { margin-top: 0.4rem; }
    form > label + input, form > label + textarea { margin-top: 0.25rem; }
    form > input + label, form > textarea + label { margin-top: 1.5rem; }
    label { display: block; font-weight: 600; }
    input, textarea {
      width: 100%;
      padding: 0.7rem 0.85rem;
      font: inherit;
      line-height: 1.6;
      color: var(--ink);
      background: var(--sheet);
      border: 1px solid var(--ink);
      border-radius: 0;
    }
    textarea { min-height: 12rem; resize: vertical; }
    button {
      margin-top: 2rem;
      padding: 0.75rem 1.75rem;
      font: inherit;
      font-weight: 600;
      color: var(--paper);
      background: var(--ink);
      border: 1px solid var(--ink);
      border-radius: 0;
      cursor: pointer;
    }
    button.danger { color: var(--danger); background: transparent; border-color: currentColor; }

    ol.memos { padding: 0; list-style: none; border-top: 1px solid var(--rule); }
    ol.memos li {
      display: grid;
      grid-template-columns: max-content 1fr;
      gap: 0 1.5rem;
      align-items: baseline;
      padding: 0.85rem 0;
      border-bottom: 1px solid var(--rule);
    }
    ol.memos time { color: var(--muted); }
    ol.memos a { font-weight: 600; text-decoration: none; }
    ol.memos a:hover { text-decoration: underline; }
    @media (max-width: 30rem) { ol.memos li { grid-template-columns: 1fr; gap: 0.1rem; } }

    .meta { display: flex; flex-wrap: wrap; gap: 0.25rem 2rem; color: var(--muted); font-size: 0.875rem; }
    .meta div { display: flex; gap: 0.5rem; }
    .meta code { overflow-wrap: normal; }
    pre.body {
      padding: 1.5rem 1.75rem;
      font: inherit;
      font-family: var(--serif);
      font-size: 1.0625rem;
      line-height: 2;
      white-space: pre-wrap;
      overflow-wrap: anywhere;
      background: var(--sheet);
      border-left: 1px solid var(--ink);
    }

    .actions { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem 2rem; }
    .actions form { display: contents; }
    .actions > *, .actions button { margin: 0; }
    .actions button { padding: 0.5rem 1.1rem; }

    .runtimes { display: grid; grid-template-columns: 1fr 1fr; padding: 0; list-style: none; border: 1px solid var(--ink); }
    .runtimes li { padding: 1.1rem 1.25rem; color: var(--muted); line-height: 1.5; }
    .runtimes li + li { border-left: 1px solid var(--ink); }
    .runtimes strong { display: block; font-family: var(--serif); font-size: 1.25rem; font-weight: 600; }
    .runtimes span { font-family: var(--mono); font-size: 0.8125rem; overflow-wrap: anywhere; }
    .runtimes .is-active { color: var(--paper); background: var(--ink); }
    @media (max-width: 30rem) {
      .runtimes { grid-template-columns: 1fr; }
      .runtimes li + li { border-left: 0; border-top: 1px solid var(--ink); }
    }

    .table-wrap { overflow-x: auto; }
    table { border-collapse: collapse; min-width: 30rem; width: 100%; }
    th, td { padding: 0.6rem 0; text-align: left; vertical-align: top; border-bottom: 1px solid var(--rule); }
    th { font-weight: 600; border-bottom-color: var(--ink); }
    td:first-child, td code { white-space: nowrap; }
    td code { overflow-wrap: normal; }
    dl.notes dt { font-family: var(--serif); font-size: 1.2rem; font-weight: 600; }
    dl.notes dd { margin-top: 0.35rem; color: var(--muted); }
    dl.notes dd + dt { margin-top: 2rem; }
    ul.plain { padding-left: 1.2rem; }
    ul.plain li + li { margin-top: 0.35rem; }

    footer {
      max-width: 40rem;
      margin: 0 auto;
      padding: 1.5rem 1.5rem 4rem;
      border-top: 1px solid var(--ink);
      font-family: var(--mono);
      font-size: 0.8125rem;
      color: var(--muted);
    }
  </style>
</head>
<body>
<main>
  @if (session('status'))
    <p class="status" role="status">{{ session('status') }}</p>
  @endif
  @yield('content')
</main>
@yield('footer')
</body>
</html>
