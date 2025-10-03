# Scripts de Backfill

Ce dossier contient un script pour initialiser les stocks (`product_stock`) et les snapshots de coûts à la vente (`order_cost_snapshots`) à partir des données historiques.

## Fichiers
- scripts/backfill_inventory_and_snapshots.php: reconstruit le stock par produit et crée les snapshots de coûts pour les commandes livrées sans snapshot.

## Ce que fait le script
1. Vide `product_stock` puis insère pour chaque produit `achats totaux - ventes livrées` (quantités). La quantité est plafonnée à 0 minimum.
2. Pour chaque commande livrée sans snapshot, insère dans `order_cost_snapshots` les champs:
   - unit_purchase_price_at_sale: prix d'achat le plus récent à la date de livraison (<= updated_at)
   - total_purchase_cost_at_sale: unit_purchase_price_at_sale × quantité
   - delivery_cost_at_sale: coût livraison de `order_delivery_costs` (0 si absent)

## Prérequis
- Les tables suivantes doivent exister: `product_stock`, `stock_movements`, `order_cost_snapshots`, `product_purchase_history`, `order_delivery_costs`, `orders`.
- La connexion PDO est configurée via `src/.env`.

## Lancer le script
Exécutez le script depuis la racine du projet:

php scripts/backfill_inventory_and_snapshots.php

Le script est idempotent pour les snapshots (n'insère que celles manquantes) et reconstruit entièrement `product_stock` à chaque exécution.

## Remarques
- Aucun enregistrement n'est supprimé dans `stock_movements` par ce script.
- Si des commandes ont été livrées sans coût d'achat historique, le prix d'achat utilisé sera 0.
- Adaptez le seuil d'alerte de stock via `Finance::getLowStockAlerts($threshold)`.
