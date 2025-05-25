# 💾 Fonctionnalité de Sauvegarde - FicheProduction v2.0

## Vue d'ensemble

La fonction de sauvegarde permet de sauvegarder et restaurer l'état complet du colisage dans la base de données Dolibarr. Les données sont persistées et automatiquement rechargées lors de la prochaine ouverture de la fiche de production.

## 🚀 Nouvelles Fonctionnalités

### ✅ Sauvegarde Complète
- **Sauvegarde en base de données** : Toutes les données de colisage sont sauvegardées dans les tables dédiées
- **Support des colis normaux et libres** : Gestion complète des deux types de colis
- **Gestion des multiples** : Sauvegarde des quantités multipliées
- **Traçabilité** : Chaque sauvegarde est horodatée avec l'utilisateur

### ✅ Chargement Automatique
- **Restauration à l'ouverture** : Les données sauvegardées sont automatiquement rechargées
- **État de l'inventaire** : Les quantités utilisées sont recalculées correctement
- **Cohérence des données** : Vérification de l'intégrité lors du chargement

### ✅ Interface Utilisateur
- **Bouton de sauvegarde** : Interface claire et intuitive
- **Barre de progression** : Feedback visuel pendant la sauvegarde
- **Messages d'état** : Confirmation de succès ou affichage des erreurs
- **Modales informatives** : Interface moderne et responsive

## 📋 Structure des Données

### Tables de Base de Données

#### `llx_ficheproduction_session`
- **Rôle** : Session de colisage par commande
- **Champs clés** :
  - `fk_commande` : ID de la commande
  - `fk_soc` : ID de la société
  - `ref_chantier` : Référence du chantier
  - `commentaires` : Commentaires associés

#### `llx_ficheproduction_colis`
- **Rôle** : Données des colis créés
- **Champs clés** :
  - `fk_session` : Lien vers la session
  - `numero_colis` : Numéro du colis
  - `poids_max` / `poids_total` : Gestion des poids
  - `multiple_colis` : Nombre de colis identiques
  - `status` : Statut du colis

#### `llx_ficheproduction_colis_line`
- **Rôle** : Lignes de produits dans chaque colis
- **Champs clés** :
  - `fk_colis` : Lien vers le colis
  - `fk_product` : ID du produit (NULL pour produits libres)
  - `is_libre_product` : Indicateur produit libre
  - `libre_product_name` : Nom du produit libre
  - `quantite` : Quantité du produit
  - `poids_unitaire` / `poids_total` : Gestion des poids

## 🔧 Implémentation Technique

### Classes PHP

#### `FicheProductionManager`
- **Méthode** : `saveColisageData()`
  - Sauvegarde complète des données JavaScript vers la base
  - Gestion transactionnelle (rollback en cas d'erreur)
  - Support des produits libres et standards

- **Méthode** : `loadColisageData()`
  - Chargement des données sauvegardées
  - Conversion au format JavaScript
  - Restauration de l'état complet

#### `FicheProductionSession`
- Gestion des sessions de colisage
- Création automatique par commande
- Liaison avec les extrafields de la commande

#### `FicheProductionColis` & `FicheProductionColisLine`
- Gestion CRUD des colis et lignes
- Support des produits libres
- Calculs automatiques des poids

### Actions AJAX

#### `ficheproduction_save_colis`
```php
// Données envoyées
{
    "colis_data": "[JSON des données de colisage]"
}

// Réponse
{
    "success": true,
    "message": "Colisage sauvegardé avec succès: 3 colis créés",
    "session_id": 42,
    "colis_saved": 3
}
```

#### `ficheproduction_load_saved_data`
```php
// Réponse
{
    "success": true,
    "session_id": 42,
    "colis": [
        {
            "id": 1,
            "number": 1,
            "maxWeight": 25,
            "totalWeight": 15.5,
            "multiple": 2,
            "status": "ok",
            "products": [...]
        }
    ]
}
```

### JavaScript

#### Fonctions Principales
- `saveColisage()` : Fonction principale de sauvegarde
- `loadSavedData()` : Chargement automatique
- `prepareColisageDataForSave()` : Préparation des données
- `convertSavedDataToJS()` : Conversion données BDD → JS

#### Gestion d'État
- Variables globales maintenues en cohérence
- Recalcul automatique des quantités utilisées
- Mise à jour temps réel de l'interface

## 🎯 Utilisation

### Pour l'Utilisateur Final

1. **Créer des colis** via l'interface drag & drop
2. **Cliquer sur "💾 Sauvegarder"** pour persister les données
3. **Suivre la progression** via la barre de statut
4. **Recevoir la confirmation** de succès
5. **Recharger la page** → Les données sont automatiquement restaurées

### Pour le Développeur

#### Ajouter un Nouveau Type de Données
1. Modifier les tables SQL si nécessaire
2. Étendre `prepareColisageDataForSave()` pour le nouveau format
3. Adapter `convertSavedDataToJS()` pour la conversion inverse
4. Tester la sauvegarde/chargement complet

#### Debugging
- Double-cliquer sur le titre → Console de debug visible
- Logs détaillés de toutes les opérations
- Suivi des API calls et réponses

## 📦 Fichiers Modifiés/Ajoutés

### Fichiers Principaux
- `ficheproduction.php` : Ajout des actions AJAX et bouton sauvegarde
- `js/ficheproduction-save.js` : Fonctions JavaScript de sauvegarde
- `css/ficheproduction-save.css` : Styles pour l'interface de sauvegarde

### Classes Existantes Utilisées
- `class/ficheproductionmanager.class.php` : Gestionnaire principal
- `class/ficheproductionsession.class.php` : Gestion des sessions
- `class/ficheproductioncolis.class.php` : Gestion des colis
- `class/ficheproductioncolisline.class.php` : Gestion des lignes

## 🛠️ Installation

### Prérequis
- Module FicheProduction v2.0 installé
- Tables de base de données créées
- Permissions utilisateur appropriées

### Activation
1. Les fichiers sont automatiquement inclus
2. Le bouton "Sauvegarder" apparaît si l'utilisateur a les droits d'édition
3. Le chargement automatique se fait à l'ouverture de chaque fiche

## 🔍 Tests et Validation

### Scénarios de Test

#### Test Sauvegarde Basique
1. Créer 2-3 colis avec différents produits
2. Sauvegarder via le bouton
3. Vérifier le message de succès
4. Recharger la page
5. ✅ Les colis doivent être restaurés identiques

#### Test Colis Libres
1. Créer un colis libre avec plusieurs éléments
2. Sauvegarder
3. Recharger
4. ✅ Les éléments libres doivent être conservés

#### Test Multiples
1. Créer un colis et définir multiple = 3
2. Sauvegarder et recharger
3. ✅ Le multiple doit être conservé
4. ✅ Les totaux doivent être corrects

#### Test Gestion d'Erreurs
1. Simuler une erreur réseau
2. ✅ Message d'erreur approprié
3. ✅ Pas de corruption des données

## 🚨 Points d'Attention

### Sécurité
- ✅ Validation des données côté serveur
- ✅ Protection CSRF avec tokens
- ✅ Vérification des permissions utilisateur
- ✅ Échappement des données SQL

### Performance
- ✅ Transactions optimisées
- ✅ Chargement différé des données sauvegardées
- ✅ Minimisation des appels AJAX

### Compatibilité
- ✅ Compatible avec l'interface existante
- ✅ Rétrocompatible (pas de session = mode normal)
- ✅ Support mobile via CSS responsive

## 📈 Évolutions Futures

### Améliorations Prévues
- **Historique des sauvegardes** : Garder un historique des versions
- **Sauvegarde automatique** : Auto-save toutes les X minutes
- **Export des données** : Export Excel/PDF du colisage sauvegardé
- **Commentaires par colis** : Ajout de notes spécifiques

### API Extensions
- **API REST** : Exposition des données via API REST
- **Synchronisation** : Sync entre différents postes
- **Validation workflow** : Circuit de validation du colisage

## 💡 Notes de Développement

### Bonnes Pratiques Respectées
- Code modulaire et réutilisable
- Gestion d'erreurs complète
- Interface utilisateur intuitive
- Documentation technique détaillée
- Tests de validation complets

### Architecture
- Séparation claire PHP/JavaScript
- Utilisation des classes Dolibarr existantes
- Respect des conventions de codage du projet
- Gestion des erreurs centralisée

---

**Version** : 2.0  
**Date** : Mai 2025  
**Auteur** : Équipe FicheProduction  
**Status** : ✅ Implémenté et testé
