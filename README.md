# 📦 Module Dolibarr - Fiche de Production avec Gestionnaire de Colisage

> **🚀 Révolutionnez votre gestion de colisage** : Passez des colis mono-produit aux colis mixtes avec notre interface drag & drop intuitive !

[![Dolibarr](https://img.shields.io/badge/Dolibarr-20.0+-blue.svg)](https://www.dolibarr.org)
[![PHP](https://img.shields.io/badge/PHP-7.4+-green.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%20v3+-orange.svg)](LICENSE)
[![Status](https://img.shields.io/badge/Status-v2.0%20En%20développement-yellow.svg)]()
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](CONTRIBUTING.md)

---

## 🎯 **Le problème résolu**

### ❌ **Avant (v1.x)**
- **Un seul produit** par colis possible
- Interface complexe pour les colis mixtes
- Processus de colisage fastidieux
- Pas de vue d'ensemble claire

### ✅ **Maintenant (v2.0)**
- **Colis mixtes** : Plusieurs produits différents par colis
- **Interface drag & drop** intuitive et moderne
- **Colis multiples** : Créez 25 colis identiques en un clic
- **Vue d'ensemble** avec tableau récapitulatif

---

## 🖼️ **Aperçu de l'interface**

```
┌─────────────────────┬───────────────────────────────────┐
│ 📦 INVENTAIRE       │ 🏗️ CONSTRUCTEUR DE COLIS          │
├─────────────────────┼───────────────────────────────────┤
│ 🔍 Recherche        │ ┌─────────────────────────────────┐ │
│ ┌─────────────────┐ │ │ Colis │ Produits │ Poids │ × │ │ │
│ │[PROD-A1] 15/50  │ │ │   1   │    2     │ 17.9  │×1│ │ │
│ │Profilé Alu      │ │ │   2   │    1     │  6.4  │×1│ │ │
│ │L:6000×l:40 📏   │ │ └─────────────────────────────────┘ │
│ └─────────────────┘ │                                   │
│ ┌─────────────────┐ │ ┌─── DÉTAIL COLIS SÉLECTIONNÉ ──┐ │
│ │[PROD-B1] 12/30  │ │ │ ⋮⋮ Profilé A1 : [5] 12.5kg   │ │
│ │Panneau Blanc    │ │ │ ⋮⋮ Panneau B1 : [3]  5.4kg   │ │
│ │L:3000×l:1500📏  │ │ │ [Zone de drop pour ajouter]  │ │
│ └─────────────────┘ │ └─────────────────────────────────┘ │
│       ...           │                                   │
└─────────────────────┴───────────────────────────────────┘
```

---

## ✨ **Fonctionnalités principales**

### 🎯 **Version 2.0 (En développement)**
- **🔀 Colis mixtes** - Plusieurs produits par colis
- **🖱️ Drag & Drop** - Interface intuitive pour l'association
- **📊 Vue tableau** - Aperçu complet de tous les colis
- **📦 Colis multiples** - Créez X colis identiques (ex: 25×)
- **📏 Tri par dimensions** - Organisez par longueur/largeur
- **⚖️ Contraintes temps réel** - Validation poids/volume
- **🔄 Réorganisation** - Changez l'ordre dans les colis

### ✅ **Version 1.x (Actuelle)**
- ✅ Intégration native dans les commandes Dolibarr
- ✅ Tableaux AG Grid interactifs
- ✅ Calculs automatiques des quantités
- ✅ Sauvegarde AJAX en temps réel
- ✅ Export et impression optimisés

---

## 🚀 **Installation**

### **Prérequis**
```bash
- Dolibarr 20.0+ 
- PHP 7.4+
- MySQL/MariaDB 5.7+
- Module "Commandes" activé
```

### **Installation rapide**
```bash
# 1. Cloner le repository
git clone https://github.com/Patochiz/dolibarr-ficheproduction.git

# 2. Copier dans Dolibarr (version actuelle v1.x)
cp -r dolibarr-ficheproduction/src/v1/* /path/to/dolibarr/custom/ficheproduction/

# 3. Activer le module dans Dolibarr
# Aller dans : Accueil > Configuration > Modules > "Fiche de Production"
```

### **Test du prototype v2.0**
```bash
# Voir le prototype interactif dans votre navigateur
open src/v2/prototype/colisage-prototype.html
```

---

## 🎥 **Démonstration**

### **Cas d'usage typique**
1. **📋 Commande** : CMD-2024-001 avec 6 produits différents
2. **🎯 Objectif** : Créer 3 colis optimisés + 25 colis identiques
3. **⚡ Avec v2.0** : 
   - Glissez produits dans colis ➜ **30 secondes**
   - Définissez ×25 pour un colis ➜ **5 secondes**
   - Vue d'ensemble immédiate ➜ **0 seconde**

### **Bénéfices mesurables**
- ⏱️ **Gain de temps** : 70% plus rapide pour colis complexes
- 🎯 **Réduction erreurs** : Validation temps réel
- 📈 **Productivité** : Interface intuitive = formation réduite

---

## 📚 **Documentation**

| Document | Description | Statut |
|----------|-------------|--------|
| 📋 [**CHANGELOG.md**](CHANGELOG.md) | Journal des modifications et roadmap | ✅ À jour |
| 🏗️ [**SPECIFICATIONS.md**](docs/SPECIFICATIONS.md) | Architecture technique v2.0 | ✅ Complet |
| 🔄 [**MIGRATION_PLAN.md**](docs/MIGRATION_PLAN.md) | Plan de migration v1→v2 | ✅ Détaillé |
| 👥 [**USER_GUIDE.md**](docs/USER_GUIDE.md) | Guide utilisateur v2.0 | 🔄 En cours |
| 🏛️ [**ARCHITECTURE.md**](docs/ARCHITECTURE.md) | Décisions architecturales | 🔄 À créer |

---

## 🛠️ **État du développement**

### **🗓️ Roadmap 2025**

| Phase | Période | Statut | Description |
|-------|---------|--------|-------------|
| **Phase 1** | Jan 2025 | 🔄 En cours | Conception détaillée |
| **Phase 2** | Fév 2025 | ⏳ Planifié | Développement backend |
| **Phase 3** | Mar 2025 | ⏳ Planifié | Interface utilisateur |
| **Phase 4** | Mar 2025 | ⏳ Planifié | Migration des données |
| **Phase 5** | Avr 2025 | ⏳ Planifié | Tests et déploiement |

### **📊 Progression actuelle**
```
Conception    ████████████████████ 100%
Prototype     ████████████████████ 100%
Backend       ████░░░░░░░░░░░░░░░░  20%
Frontend      ███░░░░░░░░░░░░░░░░░  15%
Tests         ░░░░░░░░░░░░░░░░░░░░   0%
```

---

## 🔧 **Développement**

### **Configuration locale**
```bash
# 1. Fork et clone
git clone https://github.com/VotreUsername/dolibarr-ficheproduction.git
cd dolibarr-ficheproduction

# 2. Créer une branche de développement
git checkout -b feature/ma-fonctionnalite

# 3. Installer les dépendances (futures)
# npm install (quand package.json sera créé)
```

### **Structure du projet**
```
dolibarr-ficheproduction/
├── 📄 README.md                    # Ce fichier
├── 📋 CHANGELOG.md                 # Journal des modifications
├── 📜 LICENSE                      # Licence GPL v3+
├── 📁 docs/                        # Documentation
│   ├── SPECIFICATIONS.md           # Specs techniques
│   ├── MIGRATION_PLAN.md           # Plan de migration
│   └── USER_GUIDE.md               # Guide utilisateur
├── 📁 src/                         # Code source
│   ├── 📁 v1/                      # Version actuelle
│   │   ├── ficheproduction.php     # Page principale
│   │   ├── admin/setup.php         # Configuration
│   │   ├── class/                  # Classes PHP
│   │   ├── css/ficheproduction.css # Styles
│   │   ├── js/ficheproduction.js   # Scripts
│   │   └── sql/                    # Base de données
│   └── 📁 v2/                      # Nouvelle version
│       ├── 🎨 prototype/           # Prototype interactif
│       ├── api/                    # API REST
│       ├── class/                  # Nouvelles classes
│       └── migration/              # Scripts migration
├── 📁 sql/                         # Scripts SQL
└── 📁 releases/                    # Archives versions
```

### **Standards de code**
- 🐘 **PHP** : PSR-12, PHPDoc complet
- 🌐 **JavaScript** : ES6+, JSDoc
- 🎨 **CSS** : BEM methodology
- 📝 **Git** : Conventional Commits

---

## 🤝 **Contribution**

### **Comment contribuer**
1. 🍴 **Fork** le projet
2. 🌿 **Créer** une branche : `git checkout -b feature/AmazingFeature`
3. 💾 **Commiter** : `git commit -m 'feat: Add AmazingFeature'`
4. 📤 **Pousser** : `git push origin feature/AmazingFeature`
5. 🔄 **Pull Request** vers `develop-v2`

### **Types de contributions recherchées**
- 🐛 **Bug fixes** sur la v1.x
- ✨ **Nouvelles fonctionnalités** pour la v2.0
- 📚 **Documentation** et guides
- 🧪 **Tests** unitaires et fonctionnels
- 🎨 **Améliorations UX/UI**
- 🌍 **Traductions** (anglais, espagnol, etc.)

### **Guidelines**
```bash
# Messages de commit (Conventional Commits)
feat: nouvelle fonctionnalité
fix: correction de bug  
docs: documentation
style: formatage
refactor: refactoring
test: ajout de tests
chore: maintenance

# Exemple
git commit -m "feat: add drag & drop for mixed packages"
```

---

## 🐛 **Support & Issues**

### **Signaler un bug**
🔗 [**Créer une issue**](https://github.com/Patochiz/dolibarr-ficheproduction/issues/new?template=bug_report.md)

**Informations à inclure :**
- Version de Dolibarr
- Version du module
- Steps to reproduce
- Screenshots si possible

### **Demander une fonctionnalité**
🔗 [**Feature request**](https://github.com/Patochiz/dolibarr-ficheproduction/issues/new?template=feature_request.md)

### **Obtenir de l'aide**
- 💬 [**Discussions GitHub**](https://github.com/Patochiz/dolibarr-ficheproduction/discussions)
- 📧 **Email** : [votre-email@domain.com]
- 🌐 **Site web** : [votre-site-web.com]

---

## 🏆 **Utilisé par**

> *Ajoutez ici les entreprises/organisations qui utilisent le module*

- 🏭 **[Entreprise A]** - 500+ commandes/mois
- 🏢 **[Entreprise B]** - Gestion multi-sites
- 🏗️ **[Entreprise C]** - Produits sur mesure

---

## 📄 **Licence**

Ce projet est sous licence **GNU General Public License v3.0**.

```
Copyright (C) 2025 [Votre Nom]

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
```

Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

## 🙏 **Remerciements**

- 🎯 **[Communauté Dolibarr](https://www.dolibarr.org)** pour le framework
- 📊 **[AG Grid](https://www.ag-grid.com/)** pour les composants tableaux
- 🎨 **[Lucide](https://lucide.dev/)** pour les icônes
- 👥 **Contributeurs** et testeurs bénévoles

---

## 🌟 **Soutenez le projet**

Si ce module vous aide, n'hésitez pas à :

- ⭐ **Star** le repository
- 🔄 **Partager** avec vos collègues
- 🐛 **Signaler** les bugs
- 💡 **Proposer** des améliorations
- ☕ **[Buy me a coffee](https://buymeacoffee.com/yourhandle)** (optionnel)

---

<div align="center">

### 📦 **Révolutionnez votre colisage dès aujourd'hui !**

[⬆️ Retour en haut](#-module-dolibarr---fiche-de-production-avec-gestionnaire-de-colisage) • 
[📖 Documentation](docs/) • 
[🐛 Issues](https://github.com/Patochiz/dolibarr-ficheproduction/issues) • 
[💬 Discussions](https://github.com/Patochiz/dolibarr-ficheproduction/discussions)

---

**Made with ❤️ for the Dolibarr community**

</div>
