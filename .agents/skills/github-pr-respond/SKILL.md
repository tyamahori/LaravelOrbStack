---
name: github-pr-respond
description: 「PR のコメントに対応して」「PR をウォッチして」と言われたとき、自分の PR のレビュー指摘に対応するときに読む。未解決スレッドを仕訳して提示し、承認後に修正 → commit → push → 返信 → resolve、または理由を返信して resolve する手順の正本。
---

# github-pr-respond

レビュアー側の手順は github-pr-review。こちらは作者側 — 受けた指摘を仕訳し、
全スレッドに応答して閉じるまでが対応。黙って resolve しない、黙って放置しない。

## 全体フロー

ウォッチ → 未解決スレッド収集 → 仕訳を提示 → **ユーザー承認** → 実行
（修正か理由返信 → resolve）→ ウォッチに戻る。PR が merged / closed になるか、
ユーザーが止めたら終了する。

仕訳の提示と承認は毎回必須のゲート。修正・push・resolve は外向きの操作であり、
承認前に実行しない。

## 1. ウォッチと収集

一定間隔（既定5分、ユーザー指定があればそれ）でポーリングする。Claude Code では
/loop やスケジュール機構にこのスキルを載せる形でもよい。初回はまず既存の
未解決スレッドを全部処理してからウォッチに入る。

未解決スレッドと PR の状態は GraphQL でまとめて取る:

```sh
gh api graphql -F owner='<owner>' -F name='<repo>' -F number=<N> -f query='
query($owner: String!, $name: String!, $number: Int!) {
  repository(owner: $owner, name: $name) {
    pullRequest(number: $number) {
      state
      reviewThreads(first: 100) {
        nodes {
          id
          isResolved
          isOutdated
          path
          comments(first: 50) {
            nodes { databaseId author { login } body createdAt }
          }
        }
      }
    }
  }
}'
```

`state` が MERGED / CLOSED ならウォッチを終了。`isResolved: false` のスレッド
だけが対応対象。並行セッションが処理済みのことがあるので、前回見たスレッドも
resolved になっていないか毎回確認する。

## 2. 仕訳

未解決スレッドごとに次のいずれかに仕訳し、**一覧で提示して承認を待つ**:

- **対応する** — 指摘が妥当。修正方針を1行添える
- **対応しない** — 見送る理由（設計意図、別タスク送り、誤読の指摘など）を1行添える
- **回答のみ** — 質問への返答で完結し、コード変更なし
- **判断保留** — 設計判断が割れる・影響が大きい。ユーザーの判断材料を添える

指摘の採否はレビューを受ける側の判断がすべてではない。反論する場合も理由を
返信で残し、感謝や定型句で水増ししない。

## 3. 実行

承認された仕訳に従って実行する。

**対応する場合**: 修正 → テスト → commit（論理単位・リポジトリの規約に従う）→
push → スレッドに何をどう直したかをコミット SHA 付きで日本語返信 → resolve。

**対応しない / 回答のみの場合**: 理由・回答を日本語で返信 → resolve。

返信はスレッド先頭コメントの `databaseId` に対して REST で行う。日本語本文は
シェル引用が壊れやすいので `--input -` で渡す:

```sh
gh api repos/<owner>/<repo>/pulls/<N>/comments/<databaseId>/replies --input - <<'JSON'
{ "body": "<返信（日本語）>" }
JSON
```

resolve はスレッドの node ID（GraphQL の `id`）に対して mutation で行う:

```sh
gh api graphql -F threadId='<id>' -f query='
mutation($threadId: ID!) {
  resolveReviewThread(input: { threadId: $threadId }) {
    thread { isResolved }
  }
}'
```

返信 → resolve の順を守る。resolve だけしてコメントを返さないのは、指摘を
黙殺したのと同じに見える。

## 注意

- スレッド外の PR 会話コメント（issue comment）には resolve の概念がない。
  返信だけして仕訳一覧に「会話コメント」として載せる。
- `isOutdated` のスレッドも中身は生きていることがある。指摘が後続コミットで
  解消済みなら、その旨を返信して resolve する。
- push が `communication with agent failed` で失敗したら 1Password のロック
  （global-instructions の Git & SSH 節）。リモート設定をいじらない。
