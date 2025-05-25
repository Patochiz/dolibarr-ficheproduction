# 🔄 Guide de Migration - Fonction de Sauvegarde

## 📋 Plan de Migration

Voici comment migrer de l'ancienne version vers la nouvelle version avec sauvegarde intégrée.

## 📂 Structure des Fichiers - AVANT/APRÈS

### ❌ AVANT (fichiers redondants)
```
src/v2/ficheproduction/
├── ficheproduction.php          (JavaScript incomplet intégré)
├── js/
│   ├── ficheproduction-save.js  (fonctions de sauvegarde seulement)
│   └── ficheproduction-complete.js (tout en vrac)
└── css/
    └── ficheproduction.css      (styles de base seulement)
```

### ✅ APRÈS (structure optimisée)
```
src/v2/ficheproduction/
├── ficheproduction.php          (→ remplacé par ficheproduction-final.php)
├── js/
│   └── ficheproduction.js       (fichier JavaScript unifié)
├── css/
│   ├── ficheproduction.css      (styles de base existants)
│   └── ficheproduction-save.css (styles pour la sauvegarde)
└── docs/
    ├── SAUVEGARDE.md            (documentation technique)
    └── README-SAUVEGARDE.md     (guide utilisateur)
```

## 🚀 Étapes de Migration

### Étape 1 : Sauvegarde des Fichiers Existants
```bash
# Créer un backup de l'existant
cp ficheproduction.php ficheproduction.php.backup
cp js/ js_backup/ -r
cp css/ css_backup/ -r
```

### Étape 2 : Remplacement des Fichiers

#### 📄 Fichier PHP Principal
```bash
# Remplacer le fichier principal
mv ficheproduction-final.php ficheproduction.php
```

#### 📂 Fichiers JavaScript
```bash
# Nettoyer et remplacer
rm -f js/ficheproduction-save.js
rm -f js/ficheproduction-complete.js
# Le fichier js/ficheproduction.js est déjà le bon
```

#### 🎨 Fichiers CSS
```bash
# Ajouter le CSS de sauvegarde (ficheproduction-save.css déjà créé)
# Conserver ficheproduction.css existant
```

### Étape 3 : Vérification de l'Inclusion CSS

Dans le fichier `ficheproduction.php`, vérifier que les deux CSS sont inclus :
```php
// Load external CSS files
print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('/ficheproduction/css/ficheproduction.css', 1).'">';
print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('/ficheproduction/css/ficheproduction-save.css', 1).'">';
```

### Étape 4 : Test de l'Installation

#### Test Basique
1. **Ouvrir une fiche de production** existante
2. **Vérifier l'affichage** → Interface normale visible
3. **Créer un colis** → Drag & drop fonctionne
4. **Cliquer "💾 Sauvegarder"** → Modale de progression s'affiche
5. **Recharger la page** → Données automatiquement restaurées

#### Test Console Debug
1. **Double-clic sur le titre** de la page
2. **Console de debug** apparaît en bas à droite
3. **Messages de log** visibles pendant les actions

#### Test Colis Libres
1. **Créer un colis libre** via le bouton "📦 Colis Libre"
2. **Ajouter des éléments** libres (échantillons, etc.)
3. **Sauvegarder et recharger** → Colis libres conservés

## 🐛 Résolution de Problèmes

### Problème : Bouton "Sauvegarder" invisible
**Cause** : Permissions utilisateur insuffisantes
**Solution** :
```php
// Vérifier les droits dans Dolibarr
$user->rights->commande->creer = true; // Doit être activé
```

### Problème : Erreur JavaScript "initializeFicheProduction is not defined"
**Cause** : Fichier JavaScript non chargé ou corrompu
**Solution** :
1. Vérifier l'inclusion du JS dans le PHP
2. Contrôler la syntaxe du fichier `js/ficheproduction.js`
3. Vérifier les permissions de fichier

### Problème : Sauvegarde échoue avec erreur 500
**Cause** : Classes PHP manquantes ou erreur SQL
**Solution** :
1. Vérifier que les classes FicheProductionManager sont disponibles
2. Contrôler les tables de base de données :
   - `llx_ficheproduction_session`
   - `llx_ficheproduction_colis`  
   - `llx_ficheproduction_colis_line`

### Problème : Données sauvegardées non rechargées
**Cause** : Conversion JavaScript/PHP incorrecte
**Solution** :
1. Activer la console de debug (double-clic titre)
2. Vérifier les logs de chargement des données
3. Contrôler la correspondance des IDs de produits

## 📊 Validation Post-Migration

### Checklist de Validation
- [ ] **Interface** : Drag & drop fonctionne normalement
- [ ] **Bouton sauvegarde** : Visible pour utilisateurs avec droits
- [ ] **Sauvegarde basique** : Colis normaux sauvegardés/rechargés
- [ ] **Colis libres** : Création et sauvegarde OK
- [ ] **Multiples** : Colis avec multiple > 1 conservés
- [ ] **Totaux** : Calculs de poids et nombre corrects
- [ ] **Console debug** : Accessible et fonctionnelle
- [ ] **Impression** : Fonction d'impression conservée
- [ ] **Permissions** : Respect des droits utilisateur

### Test de Charge
```php
// Test avec une commande contenant :
- 10+ lignes de produits différents
- Colis avec 5+ produits chacun
- Multiples variables (1, 2, 5 colis identiques)
- Mix colis normaux + libres
```

### Test de Performance
- **Sauvegarde** : < 3 secondes pour 10 colis
- **Chargement** : < 2 secondes à l'ouverture
- **Interface** : Drag & drop fluide sans lag

## 🔧 Maintenance Post-Migration

### Surveillance Recommandée
1. **Logs d'erreur** Dolibarr pour erreurs PHP/SQL
2. **Console navigateur** pour erreurs JavaScript  
3. **Performance** des requêtes de sauvegarde/chargement
4. **Espace disque** pour les tables de session

### Optimisations Futures
1. **Nettoyage automatique** des sessions anciennes
2. **Compression** des données JSON stockées
3. **Cache côté client** pour améliorer les performances
4. **API REST** pour intégration externe

## 📈 Métriques de Succès

### Indicateurs Techniques
- **Taux d'erreur** : < 1% sur les sauvegardes
- **Temps de réponse** : < 3s pour sauvegarde
- **Compatibilité** : 100% rétrocompatible

### Indicateurs Utilisateur  
- **Facilité d'utilisation** : Aucun changement d'interface
- **Fiabilité** : Données jamais perdues
- **Performance** : Pas de ralentissement perceptible

## 🎯 Résultat Attendu

Après migration complète :

✅ **Fonctionnalité de sauvegarde** pleinement opérationnelle  
✅ **Interface utilisateur** inchangée et familière  
✅ **Performance** maintenue ou améliorée  
✅ **Code** propre, maintenable et documenté  
✅ **Compatibilité** totale avec l'existant  

La migration doit être **transparente pour l'utilisateur final** tout en ajoutant la fonctionnalité de sauvegarde tant attendue.

---

**Migration Guide v2.0**  
**Date** : Mai 2025  
**Status** : ✅ Prêt pour déploiement
