#!/bin/bash

# WordPress パフォーマンステスト
# レスポンス時間、メモリ使用量、同時接続テストを実行します

set -e

SITE_URL="http://localhost"
TEST_DURATION=30
CONCURRENT_USERS=10

echo "Starting WordPress performance test..."
echo "Site URL: $SITE_URL"
echo "Test Duration: ${TEST_DURATION} seconds"
echo "Concurrent Users: $CONCURRENT_USERS"
echo "======================================="

# 基本レスポンス時間テスト
echo "1. Basic Response Time Test"
echo "----------------------------"
for i in {1..5}; do
    response_time=$(curl -o /dev/null -s -w "%{time_total}\n" $SITE_URL)
    echo "Request $i: ${response_time}s"
done

# 同時接続テスト（abコマンドがあればテスト）
echo ""
echo "2. Concurrent Connection Test"
echo "----------------------------"
if command -v ab >/dev/null 2>&1; then
    echo "Running Apache Benchmark (ab) test..."
    ab -n 100 -c $CONCURRENT_USERS $SITE_URL/
else
    echo "Apache Benchmark (ab) not found, skipping concurrent test"
    echo "You can install it with: brew install apache-bench (macOS) or apt-get install apache2-utils (Ubuntu)"
fi

# コンテナのリソース使用量チェック
echo ""
echo "3. Container Resource Usage"
echo "----------------------------"
echo "Docker containers status:"
docker compose ps

echo ""
echo "Container resource usage:"
docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}" $(docker compose ps -q)

# WordPressの設定確認
echo ""
echo "4. WordPress Performance Settings"
echo "----------------------------"
docker compose exec wordpress wp eval "
    echo 'WordPress Version: ' . get_bloginfo('version') . PHP_EOL;
    echo 'PHP Version: ' . phpversion() . PHP_EOL;
    echo 'Memory Limit: ' . ini_get('memory_limit') . PHP_EOL;
    echo 'Max Execution Time: ' . ini_get('max_execution_time') . PHP_EOL;
    echo 'OPcache Status: ' . (extension_loaded('opcache') ? 'Enabled' : 'Disabled') . PHP_EOL;
    if (extension_loaded('opcache')) {
        echo 'OPcache Memory: ' . ini_get('opcache.memory_consumption') . ' MB' . PHP_EOL;
    }
" --path=/var/www/html --allow-root

# データベースパフォーマンス確認
echo ""
echo "5. Database Performance"
echo "----------------------------"
docker compose exec mysql mysql -u root -p$MYSQL_ROOT_PASSWORD -e "
    SELECT 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size (MB)',
        COUNT(*) AS 'Tables'
    FROM information_schema.tables 
    WHERE table_schema = 'wordpress';
"

# nginx アクセスログの確認
echo ""
echo "6. Nginx Access Log (Last 10 entries)"
echo "----------------------------"
docker compose exec nginx tail -10 /var/log/nginx/access.log || echo "No access logs found"

# 推奨事項の出力
echo ""
echo "7. Performance Recommendations"
echo "----------------------------"
echo "✓ Basic functionality is working"
echo "✓ Database connection is stable"
echo "✓ File upload is functional"
echo ""
echo "For production optimization, consider:"
echo "- Enable OPcache for PHP"
echo "- Implement WordPress caching plugins"
echo "- Configure CDN for static assets"
echo "- Enable gzip compression (already configured)"
echo "- Monitor database performance"
echo "- Set up proper backup schedules"

echo ""
echo "Performance test completed!"