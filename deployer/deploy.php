<?php

namespace Deployer;

require __DIR__ . '/../src/vendor/deployer/deployer/recipe/laravel.php';

// デプロイ対象ホスト
// 本番環境
host('ホスト')
    ->stage('production')
    ->user('ユーザー');

// 検証環境
host('ホスト')
    ->stage('staging')
    ->user('ユーザー');

// 設定
// デプロイ先ディレクトリ
set('deploy_path', '/var/www/src');
// デプロイ元ディレクトリ（アプリケーションコードルート）
set('source_path', __DIR__ . '/../src/');
// 何世代リリースを保持するか
set('keep_releases', 5);
// 匿名で stats を送るか（ false で無効化）
set('allow_anonymous_stats', false);
// .env は、リリース時にコピーする
set('shared_files', []);
// php-fpm 実行ユーザ
set('http_user', 'apache');
// writable タスクで sudo を使うかどうか
set('writable_use_sudo', true);

// タスク
task('upload', function () {
    upload('{{source_path}}', '{{release_path}}', [
        'options' => [
            '--exclude node_modules/',
            '--exclude tests/',
        ]
    ]);
});

task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'upload',
    'deploy:shared',
    'deploy:writable',
    'artisan:view:clear',
    'artisan:cache:clear',
    'artisan:config:cache',
    'artisan:optimize',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success',
]);

after('deploy:failed', 'deploy:unlock');

// シンボリックリンク更新後に php-fpm 再起動
desc('Restart PHP-FPM service');
task('php-fpm:restart', function () {
    run('sudo /sbin/service php-fpm restart');
});
after('deploy:symlink', 'php-fpm:restart');

// optimize
task('deploy:optimize', function () {
    run('php {{release_path}}/' . 'artisan route:cache');
})->desc('Optimize Application');
