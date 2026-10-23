### Laravel Sanctumのセットアップ (SPA認証)

[教材：10-3-1 Laravel Sanctumのセットアップ](https://coachtech-material.github.io/pro-contents/roadmap/10-3-1.html)

- Composerを使ってSanctumパッケージをインストール
- Sanctumの設定ファイル（`config/sanctum.php`）とマイグレーションファイルをプロジェクトにコピー
- APIトークンを保存するための`personal_access_tokens`テーブルのマイグレーション
- APIカーネルの設定（Laravelの構成により、`Kernel.php`または`bootstrap/app.php`）
- 環境変数の設定
- CORSでCookieの送受信を許可
- 設定の反映と確認

### 前提と今回の構成

SanctumのSPA認証は、Cookieとセッションで認証状態を維持する仕組み。Sanctumをインストールするだけでは、ログイン処理やAPIのアクセス制限は追加されない。

今回確認したプロジェクトは以下の構成。

| 項目 | 設定 |
| --- | --- |
| Laravel | 13系（`composer.json`では`^13.17`） |
| Sanctum | 4系（`composer.json`では`^4.3`） |
| LaravelのURL | `http://localhost:8000` |
| Next.jsのURL | `http://localhost:3000` |
| SailのPHP | `compose.yaml`で8.5を指定 |
| セッション保存方式 | `cookie` |

以降のコマンドはLaravelプロジェクトのルートで実行する。`sail`のエイリアスがない場合は、`sail`を`./vendor/bin/sail`に置き換える。

```bash
sail php -v
sail composer show laravel/framework
sail composer show laravel/sanctum
```

`composer.json`のバージョン指定は許容範囲。インストール済みのバージョンは`composer show`で確認する。Sanctum未導入の場合、最後のコマンドではパッケージが見つからない。

### 1. Sanctumのインストール・設定ファイルの公開

#### Laravel 11以降の新しい構成（今回のLaravel 13はこちら）

新規にAPI機能を導入する場合は、次のコマンドを使う。

```bash
sail artisan install:api
```

Sanctumの導入とAPIルートの準備を行う。すでにSanctum、`routes/api.php`、マイグレーションが存在する場合は、同じ導入作業を繰り返す必要はない。

`config/sanctum.php`など、必要なファイルが未公開なら次を実行する。既存ファイルがある場合は、内容とマイグレーションの重複を確認する。

```bash
sail artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

`bootstrap/app.php`の`withRouting()`にAPIルートが登録されていることも確認する。既存の`web`・`commands`・`health`などの設定は残す。

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

#### Laravel 10以前の構成（教材の手順）

Sanctumが未導入ならインストールして、設定ファイルとマイグレーションを公開する。

```bash
sail composer require laravel/sanctum
sail artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

Laravel 10では標準で導入済みの場合もあるため、事前に`composer show`で確認する。古いLaravelでは、そのLaravelに対応するSanctumを使用する。

### 2. マイグレーションの実行

```bash
sail artisan migrate
sail artisan migrate:status
```

`personal_access_tokens`テーブルを作成するマイグレーションが適用済みか確認する。`install:api`の実行中に適用済みなら、追加の実行では対象なしとなる。

既存の`products`などのデータを残すため、ここでは`migrate:fresh`を使わない。

`personal_access_tokens`はAPIトークン用のテーブル。今回のCookie・セッションによるSPA認証では、このテーブルにトークンを発行してログインするわけではない。

### 3. SPA認証ミドルウェアの設定（バージョン・構成別）

| アプリケーションの構成 | 設定場所 | 設定内容 |
| --- | --- | --- |
| Laravel 11以降の標準構成 | `bootstrap/app.php` | `$middleware->statefulApi();` |
| Laravel 10以前の標準構成 | `app/Http/Kernel.php` | 既存の`api`グループにミドルウェアを追加 |

旧バージョンからアップグレードしたアプリは、Laravel 11以降でもKernelを使う構成を維持できる。バージョン番号だけで決めず、実際の構成に合わせる。

#### Laravel 11以降の標準構成

既存の`bootstrap/app.php`の`withMiddleware()`に追加する。

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->statefulApi();
})
```

ファイル上部で以下がインポートされていることも確認する。

```php
use Illuminate\Foundation\Configuration\Middleware;
```

`statefulApi()`によって、APIミドルウェアグループに`EnsureFrontendRequestsAreStateful`が追加される。

この構成では、Sanctumのために`app/Http/Kernel.php`を新規作成しない。今回、設定のためだけに作成したKernelは削除し、`bootstrap/app.php`に設定した。

#### Laravel 10以前など、既存のKernelを使う構成

既存の`app/Http/Kernel.php`のKernelクラス内にある`$middlewareGroups`を編集する。`api`グループの先頭へ次の1行を追加し、その他の既存設定は残す。

```php
\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
```

追加後の`api`グループの例（`$middlewareGroups`内の抜粋）：

```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

`throttle`などの内容はプロジェクトの既存設定を優先する。この配列だけでファイル全体を置き換えない。

教材の`protected $middlewareGroups = [...]`はクラス内に書くプロパティの抜粋。`<?php`直下へそのまま貼り付けると構文エラーになる。

### 4. 環境変数の設定

Laravelの`.env`を設定する。

```dotenv
APP_URL=http://localhost:8000

SANCTUM_STATEFUL_DOMAINS=localhost:3000

SESSION_DRIVER=cookie
SESSION_LIFETIME=120
SESSION_DOMAIN=localhost
```

| 項目 | 意味・注意点 |
| --- | --- |
| `APP_URL` | Laravel側のURL。実際に公開しているポートに合わせる |
| `SANCTUM_STATEFUL_DOMAINS` | SPA認証の対象にするフロントのホスト。スキームは付けず、ポートは含める |
| `SESSION_DOMAIN` | Cookieを送信するドメイン。スキーム・ポートは付けない |
| `SESSION_DRIVER` | 今回は`cookie`。`file`や`database`でもSPA認証は可能 |

Laravelの新しい雛形では、セッションの初期値が`database`の場合がある。教材の「デフォルトは`file`」という説明を前提にせず、実際の`.env`と`config/session.php`を確認する。

ブラウザで使うホスト名は`localhost`に統一する。`localhost`と`127.0.0.1`を混在させると、Cookieや許可元の条件が一致しなくなる。

この例はローカル開発用。実運用でサブドメイン間のSPA認証を行う場合は、同じルートドメイン配下に置き、CookieのドメインやHTTPSの設定も合わせる。無関係なドメイン同士で、そのまま同じ構成が使えるわけではない。

### 5. CORS設定

#### `config/cors.php`がない場合（Laravel 11以降など）

```bash
sail artisan config:publish cors
```

#### `config/cors.php`がある場合（Laravel 10以前や公開済みのプロジェクト）

公開し直す必要はない。既存の設定を編集する。

#### 方法A：設定ファイルに直接書く（今回のプロジェクトで採用）

```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

この方法なら、`.env`に`CORS_ALLOWED_ORIGINS`や`CORS_SUPPORTS_CREDENTIALS`を書く必要はない。

#### 方法B：環境変数から読み込む（教材に合わせる場合）

`.env`：

```dotenv
CORS_ALLOWED_ORIGINS=http://localhost:3000
CORS_SUPPORTS_CREDENTIALS=true
```

`config/cors.php`の該当項目：

```php
'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000')),
'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', false),
```

方法A・Bのどちらでもよい。**環境変数を書くだけでは反映されず、設定ファイル側で`env()`を使って読み込む必要がある。** この2つの方法の違いはLaravelのバージョンによるものではない。

`allowed_origins`にはスキーム・ポートを含むURLを書く。Cookie付き通信では、許可元に`*`を指定せず、`http://localhost:3000`を明示する。

`paths`にはCORSを適用するURLパスを書く。後ほどAPI配下以外に`/login`・`/logout`を作り、Next.jsから直接呼ぶ場合は、`login`・`logout`も追加する。

### 6. 設定の反映と確認

設定キャッシュがある場合、`.env`や設定ファイルの変更を読み直すためにキャッシュを削除する。

```bash
sail artisan config:clear
sail artisan route:list --path=sanctum
sail artisan route:list --path=api -vv
```

教材では以下のSail再起動も案内されている。通常のPHPリクエストでは設定変更のたびに再起動する必要はないが、コンテナの環境設定などを変更した場合は再起動する。

```bash
sail down
sail up -d
```

再起動だけでは、Laravelの設定キャッシュは削除されない。

Next.jsを`http://localhost:3000`で利用する前提で、CSRF Cookie取得時のCORSヘッダーを確認する例：

```bash
curl -i \
  -H 'Origin: http://localhost:3000' \
  -H 'Accept: application/json' \
  http://localhost:8000/sanctum/csrf-cookie
```

通常は`204`が返り、以下のヘッダーとCookieの発行が確認できる。

```text
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Set-Cookie: ...
```

`curl`はブラウザのCORS制約を適用しないため、これだけでブラウザ上の認証成功を確認したことにはならない。

### 今回の確認結果と、後ほど実施する作業

2026年10月22日時点で、Laravel側のファイルを確認した結果：

- `bootstrap/app.php`に`statefulApi()`を設定済み。
- 設定用に新規作成した`app/Http/Kernel.php`は削除済み。
- `config/cors.php`の許可元は`http://localhost:3000`、`supports_credentials`は`true`。
- `.env`は上記の`APP_URL`・`SANCTUM_STATEFUL_DOMAINS`・セッション設定と整合している。
- `bootstrap/app.php`・`config/cors.php`・`config/sanctum.php`のPHP構文チェックは成功。

ログイン処理とAPIの認証制限は、このセットアップの後で実施する。

- Laravelにセッション認証によるログイン・ログアウト処理を実装する。
- ログイン必須にしたいAPIに`auth:sanctum`を付ける。
- Next.js側でCookieとCSRFトークンの送信を設定し、ブラウザで通信を確認する。

`statefulApi()`はCookie認証を利用できるようにする設定であり、それだけでAPIへの未認証アクセスを拒否するものではない。

Next.js側のコードは今回の確認対象フォルダにないため未確認。フロントでは、まず`/sanctum/csrf-cookie`を取得し、Cookieと`X-XSRF-TOKEN`ヘッダーを送ってログインする。Axiosを使う場合は、`withCredentials`と`withXSRFToken`の設定を確認する。

### 今回つまずいた点：ホストとSailのPHPバージョン

確認に使ったホスト側のPHPは8.3.6だったが、インストール済み依存関係はPHP 8.4.1以上を要求していた。このため、ホストで`php artisan ...`を実行すると、起動前にComposerのプラットフォームチェックで停止した。

`compose.yaml`ではSailのPHP 8.5を指定しているため、教材どおりSail内で実行する。

```bash
sail php -v
sail composer check-platform-reqs
sail artisan route:list
```

確認時のエージェント環境ではDockerへのアクセス権限もなく、Sailでの起動確認・ブラウザでの通信確認までは行えていない。設定ファイルの確認とPHP構文チェックの結果を、実際の認証動作確認と区別する。

### 参考

- [COACHTECH教材：Laravel Sanctumのセットアップ](https://coachtech-material.github.io/pro-contents/roadmap/10-3-1.html)
- [Laravel 13公式：Sanctum](https://laravel.com/docs/13.x/sanctum)
- [Laravel 10公式：Sanctum](https://laravel.com/docs/10.x/sanctum)
- [Laravel 11アップグレードガイド：アプリケーション構成](https://laravel.com/docs/11.x/upgrade#application-structure)
- [Laravel公式：設定キャッシュ](https://laravel.com/docs/13.x/configuration#configuration-caching)
