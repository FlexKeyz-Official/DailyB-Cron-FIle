 <?php
// includes/wallet_sync.php
// Call sync_wallet_balance($pdo, $userId) after any transaction that affects balance

function sync_wallet_balance(PDO $pdo, int $userId): void {
    // Recalculate balance from transactions table
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(
            CASE
                WHEN LOWER(type) IN ('deposit','earning','commission') AND status = 'completed' THEN amount
                WHEN LOWER(type) IN ('withdraw','withdrawal','purchase') AND status = 'completed' THEN -amount
                ELSE 0
            END
        ), 0) AS balance
        FROM transactions
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $balance = (float)($stmt->fetchColumn() ?? 0.0);

    // Update or insert into wallets table
    $upd = $pdo->prepare("UPDATE wallets SET balance=? WHERE user_id=?");
    $upd->execute([$balance, $userId]);
    if ($upd->rowCount() === 0) {
        // No row updated, insert new
        $ins = $pdo->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance=VALUES(balance)");
        $ins->execute([$userId, $balance]);
    }
}