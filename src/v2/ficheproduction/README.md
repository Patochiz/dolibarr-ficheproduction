# Module Fiche de Production v2.0 pour Dolibarr

Module de gestion des fiches de production avec interface moderne de drag & drop pour le colisage.

## 🚀 Nouveautés V2.0

### Interface Moderne
- **Interface drag & drop** intuitive et fluide
- **Design moderne** avec animations et transitions
- **Responsive design** adaptatif mobile/desktop
- **Feedback visuel** en temps réel

### Fonctionnalités Avancées
- **Gestion des colis multiples** (duplication automatique)
- **Contraintes de poids** avec alertes visuelles
- **Réorganisation** des produits par drag & drop
- **Filtrage et tri** avancé des produits
- **Recherche instantanée** dans l'inventaire

### Architecture Robuste
- **Base de données normalisée** (3 tables au lieu de JSON)
- **API REST** pour les interactions AJAX
- **Classes métier complètes** avec validation
- **Gestion d'erreurs** et logging intégré

## 📋 Prérequis

- **Dolibarr** version 20.0.0 ou supérieure
- **Module Commandes** activé
- **Navigateur moderne** supportant les API HTML5

## 🛠️ Installation

### 1. Déploiement des fichiers
```bash
# Copier le dossier dans custom/
cp -r src/v2/ficheproduction/ /var/www/dolibarr/custom/

# Ajuster les permissions
chown -R www-data:www-data /var/www/dolibarr/custom/ficheproduction/
chmod -R 755 /var/www/dolibarr/custom/ficheproduction/
```

### 2. Activation du module
1. Aller dans **Accueil > Configuration > Modules**
2. Rechercher "**Fiche de Production v2.0**"
3. Cliquer sur **Activer**
4. Les tables seront créées automatiquement

### 3. Configuration
1. Aller dans **Configuration > Modules > Fiche de Production**
2. Ajuster les paramètres :
   - **Poids maximum par défaut** : 25 kg
   - **Création automatique session** : Oui

## 🎯 Utilisation

### 1. Accès à l'interface
1. Ouvrir une **commande client**
2. Cliquer sur l'onglet "**Fiche de Production**"
3. L'interface de colisage se charge automatiquement

### 2. Gestion des produits
- **Zone Inventaire** (gauche) : Liste des produits disponibles
- **Filtres** : Tous, Disponibles, Partiellement utilisés, Épuisés
- **Tri** : Par référence, nom, longueur, largeur, couleur
- **Recherche** : Saisie instantanée dans la barre de recherche

### 3. Création de colis
1. Cliquer sur "**+ Nouveau Colis**"
2. **Glisser-déposer** des produits depuis l'inventaire
3. **Ajuster les quantités** directement dans le détail
4. **Dupliquer** un colis si nécessaire (×2, ×3, etc.)

### 4. Gestion avancée
- **Contraintes de poids** : Alertes automatiques si dépassement
- **Réorganisation** : Drag & drop des produits dans un colis
- **Modification** : Quantités, suppression, duplication
- **Statuts visuels** : ✅ OK, ⚠️ Attention, ❌ Dépassement

## 🏗️ Architecture Technique

### Structure des fichiers
```
ficheproduction/
├── admin/
│   ├── setup.php              # Configuration
│   └── about.php              # À propos
├── class/
│   ├── ficheproductionsession.class.php    # Session de colisage
│   ├── ficheproductioncolis.class.php      # Colis
│   ├── ficheproductioncolisline.class.php  # Lignes de colis
│   └── actions_ficheproduction.class.php   # Hooks et actions
├── core/modules/
│   └── modficheproduction.class.php        # Descripteur module
├── css/
│   └── ficheproduction.css                 # Styles interface
├── js/
│   └── ficheproduction.js                  # Logique drag & drop
├── langs/fr_FR/
│   └── ficheproduction.lang                # Traductions
├── lib/
│   └── ficheproduction.lib.php             # Fonctions utilitaires
├── sql/
│   ├── llx_ficheproduction_session.sql     # Table sessions
│   ├── llx_ficheproduction_colis.sql       # Table colis
│   ├── llx_ficheproduction_colis_line.sql  # Table lignes
│   └── *.key.sql                           # Index et contraintes
├── ficheproduction.php                     # Interface principale
└── README.md                               # Documentation
```

### Base de données

#### Table `llx_ficheproduction_session`
- Une session par commande
- Informations générales (référence chantier, commentaires)
- Gestion des utilisateurs et timestamps

#### Table `llx_ficheproduction_colis`
- Un enregistrement par colis créé
- Gestion du poids, multiples, statuts
- Liaison avec la session

#### Table `llx_ficheproduction_colis_line`
- Une ligne par produit dans un colis
- Quantités, poids, ordre d'affichage
- Liaison avec les produits Dolibarr

### API AJAX
Actions disponibles via POST :
- `ficheproduction_get_data` : Récupération des données
- `ficheproduction_add_colis` : Création d'un colis
- `ficheproduction_delete_colis` : Suppression d'un colis
- `ficheproduction_add_product` : Ajout d'un produit
- `ficheproduction_remove_product` : Suppression d'un produit
- `ficheproduction_update_quantity` : Modification de quantité
- `ficheproduction_update_multiple` : Mise à jour des multiples

## 🎨 Interface Utilisateur

### Zones principales
1. **En-tête** : Informations commande et titre
2. **Inventaire** (40%) : Produits disponibles avec filtres
3. **Constructeur** (60%) : Vue d'ensemble + détail colis

### Interactions drag & drop
- **Produits → Colis** : Ajout automatique
- **Produits → Lignes colis** : Insertion à la position
- **Réorganisation** : Tri des produits dans un colis
- **Feedback visuel** : Zones de drop actives, animations

### Responsive design
- **Desktop** : Interface 2 colonnes
- **Tablet** : Colonnes empilées
- **Mobile** : Interface simplifiée

## 🔧 Configuration Avancée

### Paramètres disponibles
```php
// Poids maximum par défaut (kg)
$conf->global->FICHEPRODUCTION_POIDS_MAX_COLIS = 25;

// Création automatique des sessions
$conf->global->FICHEPRODUCTION_AUTO_CREATE_SESSION = 1;
```

### Personnalisations possibles
1. **CSS** : Modifier `css/ficheproduction.css`
2. **Traductions** : Ajouter langues dans `langs/`
3. **Logique métier** : Étendre les classes dans `class/`
4. **Interface** : Adapter `ficheproduction.php`

## 🐛 Debug et Dépannage

### Console de debug
- **Double-clic** sur le titre pour afficher/masquer
- **Logs en temps réel** des actions
- **Inspection** des données chargées

### Problèmes courants

#### Interface ne se charge pas
```bash
# Vérifier les permissions
ls -la /var/www/dolibarr/custom/ficheproduction/

# Vérifier les logs Dolibarr
tail -f /var/www/dolibarr/documents/dolibarr.log
```

#### Erreurs JavaScript
```javascript
// Ouvrir la console navigateur (F12)
// Rechercher les erreurs dans l'onglet Console
// Vérifier que ColisageManager est défini
console.log(typeof ColisageManager);
```

#### Base de données
```sql
-- Vérifier les tables
SHOW TABLES LIKE 'llx_ficheproduction_%';

-- Vérifier une session
SELECT * FROM llx_ficheproduction_session LIMIT 5;
```

## 🔄 Migration depuis V1

### Différences principales
- **V1** : Stockage JSON dans une table
- **V2** : Architecture normalisée (3 tables)
- **V1** : Interface jspreadsheet
- **V2** : Interface drag & drop native

### Procédure de migration
1. **Sauvegarder** les données V1 si nécessaire
2. **Désactiver** le module V1
3. **Installer** le module V2
4. **Recréer** les fiches de colisage (pas de migration automatique)

## 🚧 Évolutions Prévues

### Version 2.1
- [ ] Export PDF des fiches de colisage
- [ ] Historique des modifications
- [ ] Templates de colis prédéfinis
- [ ] Calculs de volumes

### Version 2.2
- [ ] Interface d'administration étendue
- [ ] Règles de colisage automatiques
- [ ] Intégration avec les expéditions
- [ ] API REST publique

## 💡 Contributions

### Développement
1. **Fork** le repository
2. **Créer** une branche feature
3. **Développer** et tester
4. **Soumettre** une Pull Request

### Signalement de bugs
- Utiliser les **Issues GitHub**
- Fournir les **logs d'erreur**
- Décrire les **étapes de reproduction**

## 📄 Licence

Ce module est distribué sous **licence GPL v3** ou ultérieure.

## 👥 Support

Pour toute question ou demande de support :
- **Issues GitHub** : Pour les bugs et demandes d'évolution
- **Discussions** : Pour l'aide à l'utilisation
- **Documentation** : README et commentaires dans le code

---

**Module Fiche de Production v2.0** - Une solution moderne pour la gestion du colisage dans Dolibarr.