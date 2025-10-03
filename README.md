# Système de Gestion Shopping - Contrôle et Statistiques

## 📋 Description

Ce système permet de suivre les performances de votre boutique en ligne avec un focus spécial sur le suivi des assistantes/vendeurs. Il exploite la base de données existante de votre boutique pour fournir des analyses détaillées.

## 🎯 Fonctionnalités Principales

### 1. Tableau de Bord Principal (`index.php`)
- **Statistiques globales** : CA, commandes, produits vendus, panier moyen
- **Évolution des ventes** : Graphiques sur 30 jours
- **Produits les plus vendus** : Top 10 avec quantités et CA
- **Performance des assistantes** : Classement et statistiques
- **Analyse des bénéfices** : Estimation des coûts et profits
- **Répartition par statut** : Visualisation des commandes par état

### 2. Performance des Assistantes (`assistant/index.php`)
- **Vue d'ensemble** : Classement de toutes les assistantes
- **Dashboard individuel** : Statistiques détaillées par assistante
- **Comparaison des performances** : Graphiques comparatifs
- **Suivi quotidien** : Évolution jour par jour
- **Commandes récentes** : Historique détaillé

### 3. Gestion des Utilisateurs
- **Authentification sécurisée** avec sessions PHP
- **Rôles utilisateur** :
  - **Admin/SuperAdmin** (role = 1) : Accès complet
  - **Assistante/Helper** (role = 0) : Dashboard personnel uniquement

## 🛠️ Installation

### Prérequis
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache/Nginx)
- Base de données `shopping2` existante

### Étapes d'installation

1. **Cloner/Copier le projet** dans votre répertoire web :
   ```bash
   cp -r gestion-shopping /var/www/html/
   ```

2. **Configurer la base de données** :
   - Éditer le fichier `.env` avec vos paramètres de connexion
   ```ini
   [database]
   host = localhost
   dbname = shopping2
   username = votre_utilisateur
   password = votre_mot_de_passe
   charset = utf8mb4
   ```

3. **Installer les mots de passe de test** :
   ```bash
   php install.php
   ```

4. **Accéder au système** :
   - URL : `http://votre-domaine/gestion-shopping/`
   - Comptes de test :
     - **Admin** : `admin@gmail.com` / `password`
     - **Assistante** : `assistante@gmail.com` / `password`

## 📊 Structure de la Base de Données

Le système utilise les tables existantes de votre boutique :

- **`users`** : Utilisateurs (admin et assistantes)
- **`products`** : Catalogue des produits
- **`orders`** : Commandes avec `manager_id` pour tracer l'assistante
- **`product_packs`** : Packs de produits
- **`product_caracteristics`** : Caractéristiques des produits

## 🔧 Configuration

### Rôles Utilisateur
Dans la table `users`, le champ `role` détermine les permissions :
- `role = 1` : Admin (accès complet)
- `role = 0` : Assistante (dashboard personnel uniquement)

### Statuts de Commande
Le système suit les statuts dans `orders.newstat` :
- `new` : Nouvelle commande
- `processing` : En cours de traitement
- `deliver` : Livrée
- `canceled` : Annulée
- `unreachable` : Client injoignable
- `remind` : Rappel nécessaire

## 📈 Métriques et KPI

### Indicateurs Globaux
- **Chiffre d'affaires** : Somme des `total_price` des commandes
- **Nombre de commandes** : Total des commandes
- **Produits vendus** : Somme des quantités
- **Panier moyen** : CA total / nombre de commandes

### Indicateurs par Assistante
- **Taux de conversion** : (Commandes livrées / Total commandes) × 100
- **Performance quotidienne** : Évolution jour par jour
- **Produits préférés** : Top des produits vendus par assistante

### Analyse des Bénéfices
- **Estimation des coûts** : 60% du prix de vente (configurable)
- **Marge bénéficiaire** : (CA - Coûts) / CA × 100
- **Bénéfice net** : CA - Coûts estimés

## 🎨 Interface Utilisateur

### Design
- **Responsive** : Compatible mobile et desktop
- **Bootstrap 5** : Framework CSS moderne
- **Chart.js** : Graphiques interactifs
- **Font Awesome** : Icônes

### Navigation
- **Sidebar** avec menu contextuel
- **Filtres de période** : Jour, semaine, mois
- **Tableaux triables** et responsives
- **Graphiques interactifs**

## 🔒 Sécurité

- **Hachage des mots de passe** avec `password_hash()`
- **Protection des sessions** PHP
- **Requêtes préparées** pour éviter l'injection SQL
- **Contrôle d'accès** basé sur les rôles

## 📱 Utilisation

### Pour un Administrateur
1. Se connecter avec un compte admin
2. Accéder au tableau de bord principal pour une vue globale
3. Naviguer vers "Performance Assistantes" pour les détails
4. Sélectionner une assistante pour voir ses performances individuelles

### Pour une Assistante
1. Se connecter avec son compte
2. Voir automatiquement son dashboard personnel
3. Consulter ses statistiques et commandes récentes

## 🛠️ Personnalisation

### Ajouter de nouveaux KPI
Modifier les classes dans `/src/` :
- `Stat.php` : Métriques globales
- `Assistant.php` : Métriques par assistante
- `Product.php` : Métriques produits

### Modifier l'apparence
- Éditer `/assets/css/dashboard.css`
- Modifier les variables CSS dans `:root`
- Ajuster les couleurs et animations

### Ajouter de nouvelles vues
- Créer de nouveaux fichiers PHP
- Utiliser les classes existantes pour les données
- Suivre la structure MVC simple

## 📞 Support

Pour toute question ou amélioration :
- Vérifier les logs PHP pour les erreurs
- S'assurer que la base de données est accessible
- Contrôler les permissions des fichiers

## 🚀 Évolutions Possibles

1. **Export Excel/PDF** des rapports
2. **Alertes automatiques** par email
3. **API REST** pour applications mobiles
4. **Tableau de bord temps réel** avec WebSockets
5. **Intégration** avec d'autres systèmes (CRM, comptabilité)
6. **Machine Learning** pour prédictions de vente
7. **Géolocalisation** des commandes
8. **Système de notifications** push

## 📝 Notes Techniques

- **PHP 8+** : Compatible avec les versions récentes
- **Base de données** : Optimisée pour de gros volumes
- **Cache** : Possibilité d'ajouter Redis/Memcached
- **Logs** : Système de logging intégrable