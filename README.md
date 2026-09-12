# Admin URI Refilter

This Bludit CMS plugin allows you to safely customize your administration area URL directly from the admin panel, featuring an **automatic recovery system** that handles core updates. By changing the default admin URI, it protects your site through proactive defense against automated attacks.

## Key Features

*   **Safe Delayed-Rewrite Engine**: Securely modifies the core `variables.php` file using a session queue, eliminating the risk of 500 errors during setup.
*   **404 Stealth Auto Recovery**: Automatically restores your custom URL in the background and redirects your browser before a 404 error page renders when Bludit core updates reset the URL.
*   **Manual Rewrite Auto-Sync**: Automatically detects manual modifications to `variables.php` via FTP or SSH and synchronizes the plugin database.
*   **Strict Input Validation**: Restricts input to alphanumeric characters, hyphens, and underscores to prevent errors.

## Requirements

*   **Bludit CMS v3.x or later**
*   **File Permissions**: The core file `/bl-kernel/boot/variables.php` must be writable by PHP.

## Installation & Setup

1.  **Backup**: Back up `/bl-kernel/boot/variables.php` before enabling rewrite features.
2.  **Upload**: Upload the plugin folder into `/bl-content/plugins/admin-uri-refilter/`.
3.  **Activate**: Go to Bludit Admin Area -> **Settings** -> **Plugins** and activate the plugin.
4.  **Configure**: Enter your custom admin URL and save.

## Development & License

*   **Developer**: HATTA (https://hattantoco.com / GitHub: https://github.com)
*   **License**: MIT license

---

# Admin URI Refilter

本プラグインは、Bludit CMSの管理画面URL（定数 `ADMIN_URI_FILTER`）を管理画面から安全にカスタマイズし、コアアップデートによる初期化（リセット）に対しても自動で復旧を行うための専用拡張プラグインです。
管理画面のURIをデフォルトの `admin` から推測されにくい独自の文字列へと変更（難読化）することにより、 **不正ログイン試行（ブルートフォース攻撃など）の攻撃に対して、ログイン画面そのものに到達することを困難にし、管理画面を狙った無差別な自動攻撃からサイトを保護（事前防御）** します。

## 主な機能

*   **安全な遅延書き換えエンジン**: セッションキューを活用し、ページリロードのタイミングでコアの `variables.php` ファイルを安全に書き換えます。設定時の500エラーや即時切断を防ぎます。
*   **404ステルス自動復旧**: Bluditのコアファイルがアップデートされると、管理画面URLは初期値（`admin`）にリセットされます。本プラグインは、アップデート後の最初のログイン直後にユーザーがメニューをクリックした瞬間、裏側でファイルを自動復旧。ブラウザが404エラー画面を出力する前に、JavaScriptによって元のカスタムURLのダッシュボードへとスムーズに引き戻します。
*   **手動書き換えへの自動同期**: 管理者がFTPやSSHを用いて `variables.php` 内の定数を直接手動で書き換えた場合、プラグイン設定画面にアクセスした瞬間にその変更を察知し、プラグイン側のデータベースを最新の値に自動上書き同期します。
*   **厳格な入力バリデーション**: 入力可能な文字を半角英数字、ハイフン、アンダースコア（`^[a-zA-Z0-9_-]+$`）に制限し、構文エラーやURLの破損を未然に防ぎます。

## 動作環境・要件

*   **Bludit CMS v3.x 以降**
*   **ファイルパーミッション**: `/bl-kernel/boot/variables.php` がPHPから書き込み可能であること。

## インストールと設定手順

1.  **バックアップ**: URL書き換え機能を有効にする前に、念のため `/bl-kernel/boot/variables.php` のバックアップを保存してください。
2.  **アップロード**: プラグインコードを `/bl-content/plugins/admin-uri-refilter/` フォルダにアップロードします。
3.  **有効化**: Bludit管理画面 -> **設定** -> **プラグイン** から **Admin URI Refilter** を有効化します。
4.  **設定**: 設定画面の入力フィールドに希望するカスタム管理画面URLを入力し、**保存**をクリックします。次の画面で生成される「新しいURLへ移動」ボタンをクリックすると、URLの切り替えが完了します。

## 開発・ライセンス

*   **開発者**: HATTA (<https://hattantoco.com> / GitHub: <https://github.com>)
*   **ライセンス**: MIT license