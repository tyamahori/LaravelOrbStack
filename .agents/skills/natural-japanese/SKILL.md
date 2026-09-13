---
name: natural-japanese
description: 日本語の文書（議事録・レポート・ガイド・企画書・メール・スライド構成・note・ブログ）を書く／直すとき、AI臭い・不自然・読みにくい・一文が長いと指摘されたとき、AI臭さの採点や文体プロファイル化を頼まれたときに使う。Markdown 整形規約は japanese-tech-writing。
license: MIT
argument-hint: "[write|score] [quick|full|exp] [対象ファイルや依頼内容]"
---

# natural-japanese

仕事の日本語を、読みやすくわかりやすく書くためのスキル。議事録・調査レポート・社内ガイド・リサーチメモ・スライドといった仕事の文書から、note・ブログ・エッセイまで。AI臭さの除去は工程の一部として組み込まれている。

## 設計思想

軸は二つ。第一に「検出は機械、判断はAI」。AIは自分の癖を認識しにくいから、疑いの検出は機械が決定的に行い、直すかどうかはAI（あなた）が文脈で判断する。第二に「事後修正より生成時制約」。書いた後にAI臭を消すより、書く前の設計と書くときの制約で発生自体を防ぐほうが効く。工程は「設計 → 執筆 → 検査 → 収束」の順に進む。

## 実行入口

コマンドの相対パスはこの skill のルートを基準にする。
[モード選択と呼び出し](references/modes.md)で quick / full / score を選ぶ。
既定は quick。ユーザーの full 指定や高リスク文書を勝手に縮小しない。

- **write quick**: [設計と執筆](references/writing.md)、[検査・収束・後片付け](references/checks.md) の quick に必要な手順を使う。該当 doctype を読み、lint 1回と自分での通読を行う。追加 references は finding の判断に必要な場合だけ読む。サブエージェントと full 評価は不要。
- **write full**: 上記に加え [full 評価](references/full-evaluation.md) を読む。構造・読みやすさ・doctype の独立レビュー、判断台帳、収束、最終評価を省略しない。
- **score quick / full / exp**: 最初に [診断](references/diagnose.md) を読む。診断だけを行い、依頼がない限り書き換えない。write の手順を一括読込しない。

## 完了条件

事実・出典とユーザーの意図を保持する。lint は疑いの提示であり、指摘を機械的に直さない。
採用した修正と残す理由を判断し、新しい指摘が出なくなったら最終通読する。
quick でも lint を省略しない。full の品質基準は full 評価に従う。
quick の最終通読でも、自然さ・情報密度・走査性・論理・誠実さ・自己証明力の6観点を点検する。
チャット返信のためだけには発火せず、文書の作成・推敲・診断に使う。
