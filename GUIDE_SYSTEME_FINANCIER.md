# 📊 SYSTÈME DE GESTION FINANCIÈRE AVANCÉ

## Vue d'ensemble

Ce système permet de suivre **TOUS** les aspects financiers de votre boutique :
- ✅ Prix d'achat des produits avec historique
- ✅ Salaires des assistantes (base + commissions + primes)
- ✅ Dépenses opérationnelles (publicité, hébergement, etc.)
- ✅ Coûts de livraison par commande
- ✅ Campagnes publicitaires avec ROI
- ✅ Objectifs et budgets mensuels
- ✅ Rapports de rentabilité complets

---

## 🗄️ TABLES CRÉÉES

### 1. **product_purchase_history**
Historique complet des achats de produits
- `purchase_price` : Prix d'achat unitaire
- `quantity` : Quantité achetée
- `supplier` : Nom du fournisseur
- `purchase_date` : Date d'achat

### 2. **product_current_costs**
Prix d'achat actuel de chaque produit (vue rapide)

### 3. **assistant_salaries**
Salaires mensuels des assistantes
- `base_salary` : Salaire fixe
- `commission_rate` : Taux de commission (%)
- `commission_amount` : Montant calculé automatiquement
- `bonus` : Primes
- `deductions` : Déductions
- `payment_status` : pending/paid/cancelled

### 4. **operational_expenses**
Toutes les dépenses opérationnelles
Types: `publicite`, `hebergement`, `domaine`, `marketing`, `logistique`, `emballage`, `livraison`, `fournitures`, `telecommunications`, `autres`

### 5. **order_delivery_costs**
Coûts de livraison par commande

### 6. **advertising_campaigns**
Suivi des campagnes publicitaires
Plateformes: `facebook`, `instagram`, `google`, `tiktok`, `whatsapp`, `autres`

### 7. **monthly_targets**
Objectifs mensuels (global ou par assistante)

### 8. **monthly_budgets**
Budgets mensuels par catégorie

---

## 💻 UTILISATION EN PHP

### Initialiser la classe
```php
use src\Finance;

$finance = new Finance();
```

### 1. ENREGISTRER UN ACHAT DE PRODUITS

```php
// Exemple: Acheter 100 unités du produit ID 5 à 1000 FCFA l'unité
$finance->recordProductPurchase(
    productId: 5,
    purchasePrice: 1000,
    quantity: 100,
    supplier: 'Fournisseur ABC',
    purchaseDate: '2025-10-01 10:00:00',
    notes: 'Achat en gros avec réduction'
);
```

**Effet**: 
- Enregistre l'achat dans l'historique
- Met à jour automatiquement le prix d'achat actuel
- Permet de calculer la marge réelle

### 2. ENREGISTRER UN SALAIRE

```php
// Salaire de l'assistante ID 2 pour septembre 2025
$finance->recordAssistantSalary(
    userId: 2,
    month: '2025-09',
    baseSalary: 50000,      // 50,000 FCFA fixe
    commissionRate: 5,       // 5% de commission sur les ventes
    bonus: 10000,            // Prime de 10,000 FCFA
    deductions: 0,
    notes: 'Excellente performance'
);
```

**Effet**:
- Calcule automatiquement les commissions basées sur les ventes du mois
- Salaire total = base + commissions + bonus - déductions

### 3. ENREGISTRER UNE DÉPENSE

```php
// Dépense publicitaire Facebook
$finance->recordExpense(
    expenseType: 'publicite',
    amount: 25000,
    description: 'Campagne Facebook Ads - Octobre',
    expenseDate: '2025-10-01',
    category: 'Facebook Ads',
    vendor: 'Meta',
    paymentMethod: 'bank_transfer',
    notes: 'Campagne produits automne'
);

// Hébergement site web
$finance->recordExpense(
    expenseType: 'hebergement',
    amount: 15000,
    description: 'Hébergement annuel',
    expenseDate: '2025-10-01',
    vendor: 'OVH',
    paymentMethod: 'credit_card'
);
```

### 4. ENREGISTRER UN COÛT DE LIVRAISON

```php
// Coût de livraison pour la commande ID 100
$finance->recordDeliveryCost(
    orderId: 100,
    deliveryCost: 500,
    deliveryPartner: 'DHL Tchad',
    deliveryDate: '2025-10-02',
    notes: 'Livraison express'
);
```

### 5. CRÉER UNE CAMPAGNE PUBLICITAIRE

```php
// Nouvelle campagne Instagram
$campaignId = $finance->createCampaign(
    campaignName: 'Campagne Halloween 2025',
    platform: 'instagram',
    startDate: '2025-10-15',
    budget: 50000,
    endDate: '2025-10-31',
    notes: 'Ciblage 18-35 ans, Ndjamena'
);

// Mettre à jour les stats de la campagne
$finance->updateCampaignStats(
    campaignId: $campaignId,
    spent: 35000,
    impressions: 125000,
    clicks: 2500,
    conversions: 45
);

// Lier une commande à la campagne (tracking)
$finance->linkOrderToCampaign(orderId: 105, campaignId: $campaignId);
```

### 6. DÉFINIR DES OBJECTIFS

```php
// Objectif global pour octobre
$finance->setMonthlyTarget(
    month: '2025-10',
    targetRevenue: 1000000,  // 1M FCFA
    userId: null,             // null = objectif global
    targetOrders: 100,
    targetProfit: 400000
);

// Objectif pour l'assistante ID 2
$finance->setMonthlyTarget(
    month: '2025-10',
    targetRevenue: 300000,
    userId: 2,
    targetOrders: 30
);
```

### 7. DÉFINIR DES BUDGETS

```php
// Budget publicité octobre
$finance->setMonthlyBudget(
    month: '2025-10',
    category: 'publicite',
    allocatedAmount: 75000
);

// Budget hébergement
$finance->setMonthlyBudget(
    month: '2025-10',
    category: 'hebergement',
    allocatedAmount: 20000
);
```

---

## 📈 OBTENIR DES RAPPORTS

### Rapport financier mensuel complet

```php
$report = $finance->getMonthlyFinancialSummary('2025-09');

echo "=== RAPPORT SEPTEMBRE 2025 ===\n";
echo "Chiffre d'affaires: " . $report['total_revenue'] . " FCFA\n";
echo "Coût des produits: " . $report['product_costs'] . " FCFA\n";
echo "Coûts de livraison: " . $report['delivery_costs'] . " FCFA\n";
echo "Profit brut: " . $report['gross_profit'] . " FCFA\n";
echo "Salaires: " . $report['salaries'] . " FCFA\n";
echo "Dépenses opérationnelles: " . $report['operational_expenses'] . " FCFA\n";
echo "PROFIT NET: " . $report['net_profit'] . " FCFA\n";
```

### Rentabilité des produits

```php
$products = $finance->getProductProfitability(limit: 10);

foreach ($products as $p) {
    echo "{$p['name']}: ";
    echo "Marge {$p['profit_margin_percent']}% ";
    echo "({$p['unit_profit']} FCFA/unité)\n";
}
```

### Rentabilité par assistante

```php
$assistants = $finance->getAssistantProfitability('2025-09');

foreach ($assistants as $a) {
    echo "{$a['assistant_name']} ({$a['month']}):\n";
    echo "  Ventes: {$a['total_revenue']} FCFA\n";
    echo "  Profit brut: {$a['gross_profit']} FCFA\n";
    echo "  Salaire: {$a['salary_cost']} FCFA\n";
    echo "  PROFIT NET: {$a['net_profit']} FCFA\n\n";
}
```

### Objectifs vs Réalisations

```php
$targets = $finance->getTargetVsActual('2025-09');

foreach ($targets as $t) {
    $name = $t['assistant_name'] ?? 'Global';
    echo "{$name}:\n";
    echo "  Objectif: {$t['target_revenue']} FCFA\n";
    echo "  Réalisé: {$t['actual_revenue']} FCFA\n";
    echo "  Taux: {$t['achievement_rate']}%\n\n";
}
```

### Budgets vs Dépenses

```php
$budgets = $finance->getBudgetVsSpent('2025-10');

foreach ($budgets as $b) {
    echo "{$b['budget_category']}:\n";
    echo "  Budget: {$b['allocated_amount']} FCFA\n";
    echo "  Dépensé: {$b['actual_spent']} FCFA\n";
    echo "  Restant: {$b['remaining']} FCFA\n";
    echo "  Utilisation: {$b['usage_rate']}%\n\n";
}
```

### Profit d'une commande spécifique

```php
$orderProfit = $finance->calculateOrderProfit(100);

echo "Commande #100:\n";
echo "  Revenus: {$orderProfit['revenue']} FCFA\n";
echo "  Coût produit: {$orderProfit['product_cost']} FCFA\n";
echo "  Coût livraison: {$orderProfit['delivery_cost']} FCFA\n";
echo "  PROFIT: {$orderProfit['net_profit']} FCFA\n";
```

---

## 🎯 WORKFLOW RECOMMANDÉ

### Chaque jour:
1. Enregistrer les coûts de livraison des commandes livrées
2. Enregistrer les nouvelles dépenses

### Chaque semaine:
3. Mettre à jour les stats des campagnes publicitaires
4. Vérifier les budgets vs dépenses

### Chaque mois:
5. Enregistrer les nouveaux achats de produits
6. Calculer et enregistrer les salaires
7. Définir les objectifs du mois suivant
8. Définir les budgets du mois suivant
9. Générer le rapport financier mensuel complet

---

## 🔍 VUES SQL DISPONIBLES

### `product_profitability`
Marge bénéficiaire de chaque produit

### `monthly_financial_report`
Rapport financier mensuel global

### `assistant_profitability`
Rentabilité par assistante par mois

---

## ⚠️ IMPORTANT

1. **Prix d'achat**: Enregistrez TOUS les achats de produits pour avoir des marges précises
2. **Coûts de livraison**: Enregistrez-les pour chaque commande livrée
3. **Salaires**: Enregistrez-les avant de générer les rapports mensuels
4. **Dépenses**: Enregistrez toutes les dépenses le jour même
5. **Campagnes**: Liez les commandes aux campagnes pour calculer le ROI

---

## 📊 INDICATEURS CLÉS (KPI)

Le système calcule automatiquement:
- ✅ Chiffre d'affaires (CA)
- ✅ Coût des marchandises vendues (CMV)
- ✅ Marge brute
- ✅ Dépenses opérationnelles
- ✅ Profit net
- ✅ Rentabilité par produit
- ✅ Rentabilité par assistante
- ✅ ROI des campagnes publicitaires
- ✅ Taux d'atteinte des objectifs
- ✅ Taux d'utilisation des budgets

---

## 🚀 PROCHAINES ÉTAPES

1. Remplir les prix d'achat actuels de tous vos produits
2. Enregistrer l'historique des achats (si disponible)
3. Définir les salaires de base des assistantes
4. Enregistrer les dépenses des derniers mois
5. Créer un dashboard pour visualiser tout ça!

**Le système est maintenant prêt et compatible avec votre boutique existante ! 🎉**
