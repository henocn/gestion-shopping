<?php
require_once __DIR__ . '/../src/Connectdb.php';
use src\Connectdb;

$pdo = Connectdb::getConnection();

function logln($m){ echo date('H:i:s') . ' ' . $m . PHP_EOL; }

try {
    $pdo->beginTransaction();

    // 1) Initialiser product_stock à partir des achats - ventes
    logln('Reconstruction des stocks...');
    // Effacer et reconstituer (sécurisé si table vide)
    $pdo->exec("DELETE FROM product_stock");

    // Totaux achats
    $purchases = $pdo->query("SELECT product_id, COALESCE(SUM(quantity),0) as qty FROM product_purchase_history GROUP BY product_id")
                     ->fetchAll(PDO::FETCH_KEY_PAIR);

    // Totaux vendus (toutes livrées)
    $sales = $pdo->query("SELECT product_id, COALESCE(SUM(quantity),0) as qty FROM orders WHERE newstat='deliver' GROUP BY product_id")
                 ->fetchAll(PDO::FETCH_KEY_PAIR);

    $productIds = array_unique(array_merge(array_keys($purchases), array_keys($sales)));
    $ins = $pdo->prepare("INSERT INTO product_stock (product_id, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)");
    foreach ($productIds as $pid) {
        $p = (int) ($purchases[$pid] ?? 0);
        $s = (int) ($sales[$pid] ?? 0);
        $q = max(0, $p - $s);
        $ins->execute([(int)$pid, $q]);
    }

    // 2) Remplir les snapshots de coûts pour les commandes livrées sans snapshot
    logln('Backfill des snapshots de coûts...');
    $stmt = $pdo->query("SELECT o.id, o.product_id, o.quantity, o.updated_at
                         FROM orders o
                         LEFT JOIN order_cost_snapshots ocs ON o.id = ocs.order_id
                         WHERE o.newstat = 'deliver' AND ocs.id IS NULL");
    $insSnap = $pdo->prepare("INSERT INTO order_cost_snapshots
        (order_id, product_id, quantity, unit_purchase_price_at_sale, total_purchase_cost_at_sale, delivery_cost_at_sale)
        VALUES (?, ?, ?, ?, ?, ?)");
    $getPP = $pdo->prepare("SELECT purchase_price FROM product_purchase_history 
                            WHERE product_id = ? AND purchase_date <= ?
                            ORDER BY purchase_date DESC, id DESC LIMIT 1");
    $getDel = $pdo->prepare("SELECT COALESCE(delivery_cost,0) FROM order_delivery_costs WHERE order_id = ?");
    while ($o = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $getPP->execute([(int)$o['product_id'], $o['updated_at']]);
        $pp = (float) ($getPP->fetchColumn() ?: 0);
        $getDel->execute([(int)$o['id']]);
        $del = (float) ($getDel->fetchColumn() ?: 0);
        $qty = (int) $o['quantity'];
        $insSnap->execute([(int)$o['id'], (int)$o['product_id'], $qty, $pp, $pp * $qty, $del]);
    }

    $pdo->commit();
    logln('Terminé.');
} catch (Exception $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Erreur backfill: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
