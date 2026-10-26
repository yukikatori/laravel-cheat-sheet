### 認証機能の実装（Fortify）

https://lms.coachtech.site/v2/user/01jk3m1df0x0tj5hx5w651hmrp/curriculums/01kjeb9gkn2p45p12c189q7xf9/chapters/01kjeb9grc70kpnddcqxawwe7q/sections/01kjeb9grk6pqjrdv8ygs7mf94/
	- Laravel Fortifyのインストール
	- Fortifyの設定ファイルとサービスプロバイダを公開
	- config/app.php の providers 配列にFortifyServiceProviderを追加
	- config/fortify.php を開き、ログイン後のリダイレクト先を
    - RouteServiceProvider::HOME を参照するように変更
    - app/Providers/FortifyServiceProvider.php を編集し、認証ビューを設定