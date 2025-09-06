<?php
// api/cron_earnings.php
// Open API to trigger hourly earnings (no token required)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ignore_user_abort(true);
set_time_limit(0);

// Immediately send response
header('Content-Type: application/json');
echo json_encode([
    "success" => true,
    "message" => "Cron started. Processing in background."
]);
flush();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/wallet_sync.php';

try {
    // Fetch active subscriptions
    $subs = $pdo->query("
        SELECT up.id AS subscription_id, up.user_id, up.end_date, p.daily_return, p.price
        FROM user_plans up
        JOIN plans p ON up.plan_id = p.id
        WHERE up.active = 1
    ");

    if (!$subs) throw new Exception('Failed to fetch active subscriptions.');

    $count = 0;

    while ($row = $subs->fetch(PDO::FETCH_ASSOC)) {
        $user_id = $row['user_id'];
        $subscription_id = $row['subscription_id'];

        // Calculate hourly earning = (plan price * daily_return%) / 24
        $dailyReturnPercent = (float)$row['daily_return'];
        $planPrice = (float)$row['price'];
        $amount = ($planPrice * ($dailyReturnPercent / 100)) / 24;

        // Skip if user doesn’t exist
        $checkUser = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $checkUser->execute([$user_id]);
        if (!$checkUser->fetch()) continue;

        // Insert transaction (no restriction)
        $ref = json_encode([
            'subscription_id' => $subscription_id,
            'time' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_SLASHES);

        $ins = $pdo->prepare("
            INSERT INTO transactions 
            (user_id, type, amount, status, created_at, reference) 
            VALUES (?, 'earning', ?, 'completed', NOW(), ?)
        ");
        $ins->execute([$user_id, $amount, $ref]);

        // Insert into earnings
        $earn = $pdo->prepare("
            INSERT INTO earnings (user_plan_id, amount, earned_on, commissions)
            VALUES (?, ?, NOW(), 0.00)
        ");
        $earn->execute([$subscription_id, $amount]);

        // Sync wallet
        sync_wallet_balance($pdo, $user_id);

        $count++;

        // Deactivate expired plans
        if (strtotime($row['end_date']) < strtotime(date('Y-m-d'))) {
            $pdo->prepare("UPDATE user_plans SET active = 0 WHERE id = ?")
                ->execute([$subscription_id]);
        }
    }

    file_put_contents(
        __DIR__.'/cron_log.txt', 
        date('Y-m-d H:i:s') . " - Hourly earnings processed: $count users\n", 
        FILE_APPEND
    );

} catch (Exception $e) {
    file_put_contents(
        __DIR__.'/cron_log.txt', 
        date('Y-m-d H:i:s') . " - Error: ".$e->getMessage()."\n", 
        FILE_APPEND
    );
}
