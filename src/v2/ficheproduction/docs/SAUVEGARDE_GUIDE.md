# 📦 Gestionnaire de Colisage v2.0 - Guide de la Sauvegarde

## 🎉 Nouvelles Fonctionnalités v2.0

### ✨ Sauvegarde et Chargement Complets
- **💾 Sauvegarde persistante** : Tous vos colis sont maintenant sauvegardés en base de données
- **📂 Chargement automatique** : Les colisages existants se chargent automatiquement à l'ouverture
- **🔄 Synchronisation temps réel** : Vos modifications sont conservées entre les sessions

### 🆓 Support des Produits Libres
- **📦 Colis libres** : Créez des colis avec des éléments non référencés (échantillons, catalogues, etc.)
- **⚖️ Gestion du poids** : Poids personnalisable pour chaque élément libre
- **📝 Description complète** : Nom et description pour chaque produit libre

### 🏗️ Architecture Robuste
- **🗄️ Base de données** : Nouvelle structure pour supporter les produits libres
- **🔧 Classes dédiées** : Gestion complète via `FicheProductionManager`
- **🛡️ Validation** : Contrôles de cohérence et gestion d'erreurs

## 🚀 Guide d'Utilisation

### 1. Création de Colis Standards
1. **Glissez-déposez** les produits de l'inventaire vers un colis
2. **Ajustez les quantités** directement dans les vignettes
3. **Dupliquez** un colis pour créer plusieurs exemplaires identiques
4. **Surveillez le poids** avec les indicateurs visuels

### 2. Création de Colis Libres
1. Cliquez sur **"📦 Colis Libre"**
2. Ajoutez les éléments avec :
   - **Nom** (ex: "Échantillon Bleu Marine")
   - **Poids unitaire** en kg
   - **Quantité** souhaitée
3. Validez pour créer le colis

### 3. Sauvegarde du Colisage
1. Cliquez sur **"💾 Sauvegarder le colisage"**
2. Le système crée automatiquement :
   - Une **session de colisage** liée à la commande
   - Des **colis** avec leurs multiples
   - Des **lignes de produits** (standards et libres)
3. Confirmation avec détails de la sauvegarde

### 4. Chargement d'un Colisage Existant
1. Cliquez sur **"📂 Charger colisage existant"**
2. Le système restaure :
   - Tous les colis créés précédemment
   - Les quantités utilisées dans l'inventaire
   - Les produits libres avec leurs caractéristiques
3. L'interface se met à jour automatiquement

## 🗄️ Structure de Base de Données

### Tables Principales

#### `llx_ficheproduction_session`
```sql
- id : Identifiant unique
- fk_commande : Lien vers la commande
- fk_soc : Lien vers la société
- ref_chantier : Référence du chantier
- commentaires : Commentaires généraux
- status : Statut de la session
```

#### `llx_ficheproduction_colis`
```sql
- id : Identifiant unique
- fk_session : Lien vers la session
- numero_colis : Numéro du colis
- poids_max : Poids maximum (25kg par défaut)
- poids_total : Poids total calculé
- multiple_colis : Nombre de colis identiques
- status : Statut du colis
```

#### `llx_ficheproduction_colis_line`
```sql
- id : Identifiant unique
- fk_colis : Lien vers le colis
- fk_product : Lien vers le produit (NULL pour produits libres)
- is_libre_product : Indicateur produit libre (0/1)
- libre_product_name : Nom du produit libre
- libre_product_description : Description du produit libre
- quantite : Quantité
- poids_unitaire : Poids unitaire
- poids_total : Poids total de la ligne
```

## ⚙️ Configuration et Installation

### Prérequis
- Dolibarr 13.0+ 
- Module Commandes activé
- Droits de lecture/écriture sur les commandes

### Migration Base de Données
Pour supporter les produits libres, exécutez le script de migration :
```sql
-- Fichier: sql/llx_ficheproduction_colis_line_v2.sql
ALTER TABLE llx_ficheproduction_colis_line 
ADD COLUMN is_libre_product TINYINT(1) DEFAULT 0 NOT NULL,
ADD COLUMN libre_product_name VARCHAR(255) NULL,
ADD COLUMN libre_product_description TEXT NULL;
```

### Fichiers Principaux
- **ficheproduction.php** : Interface principale avec AJAX
- **class/ficheproductionmanager.class.php** : Gestionnaire de sauvegarde/chargement
- **class/ficheproductioncolis.class.php** : Gestion des colis (mise à jour)
- **class/ficheproductioncolisline.class.php** : Gestion des lignes (mise à jour)
- **css/ficheproduction.css** : Styles visuels améliorés

## 🔧 API et Méthodes

### Actions AJAX Disponibles
- **`ficheproduction_get_data`** : Charge les produits, groupes et colis existants
- **`ficheproduction_save_colis`** : Sauvegarde complète du colisage
- **`ficheproduction_load_colis`** : Chargement d'un colisage existant
- **`ficheproduction_get_statistics`** : Statistiques de session

### Méthodes JavaScript Principales
```javascript
// Sauvegarde
async function saveColisage()

// Chargement
async function loadColisage()

// Création colis libre
async function createColisLibre()

// Gestion des données
function convertJSColisData(jsColisData, products)
```

### Méthodes PHP Classes
```php
// FicheProductionManager
public function saveColisageData($fk_commande, $fk_soc, $colisData, User $user)
public function loadColisageData($fk_commande)
public function getSessionStatistics($fk_commande)

// FicheProductionColis
public function createFromJSData($colisData, $fk_session, User $user)
public function addFreeLine($name, $description, $quantite, $poids_unitaire, User $user)

// FicheProductionColisLine
public function createFreeLine($fk_colis, $name, $description, $quantite, $poids_unitaire, User $user)
```

## 🎯 Cas d'Usage Avancés

### Scénario 1 : Commande Mixte avec Échantillons
1. Créez vos colis standards avec les produits de la commande
2. Ajoutez un **colis libre** pour les échantillons
3. Sauvegardez l'ensemble du colisage
4. Imprimez la fiche de production complète

### Scénario 2 : Modification et Recharge
1. Travaillez sur votre colisage
2. Sauvegardez à mi-parcours
3. Fermez Dolibarr et revenez plus tard
4. **Chargez le colisage existant** pour continuer

### Scénario 3 : Duplication et Multiples
1. Créez un colis type avec plusieurs produits
2. Utilisez la **duplication** pour créer 5 colis identiques
3. Modifiez les quantités si nécessaire
4. Sauvegardez le tout d'un clic

## 🐛 Résolution de Problèmes

### Erreur de Sauvegarde
- Vérifiez les droits sur les tables de base
- Consultez la console de debug (double-clic sur le titre)
- Vérifiez la structure de base avec la migration v2

### Colisage non Chargé
- Assurez-vous qu'une sauvegarde a été effectuée
- Vérifiez l'ID de la commande
- Consultez les logs Dolibarr

### Produits Libres non Affichés
- Vérifiez la migration de la table `colis_line`
- Assurez-vous que `is_libre_product` est bien défini

## 📈 Performances et Optimisation

### Recommendations
- **Sauvegardez régulièrement** pendant le travail
- **Limitez à 50 colis maximum** par session pour de meilleures performances
- **Utilisez les groupes de produits** pour naviguer plus facilement

### Monitoring
- Console de debug disponible (double-clic sur titre)
- Logs détaillés des opérations AJAX
- Statistiques de session disponibles

## 🔮 Évolutions Futures

### Prochaines Fonctionnalités
- **Export PDF** personnalisé des colisages
- **Historique** des modifications
- **Templates** de colis récurrents
- **Optimisation automatique** du colisage

### Architecture Extensible
Le système est conçu pour évoluer facilement :
- Classes modulaires et extensibles
- API AJAX claire et documentée
- Base de données normalisée

---

## 💬 Support et Contribution

Pour toute question ou suggestion d'amélioration, n'hésitez pas à :
- Consulter la documentation Dolibarr
- Ouvrir une issue GitHub
- Proposer des pull requests

**Bon colisage ! 📦✨**
