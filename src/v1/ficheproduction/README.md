# Module Fiche de Production pour Dolibarr

Ce module permet de gérer les plans de colisage pour les commandes clients dans Dolibarr.

## Fonctionnalités

- Ajout d'un onglet "Fiche de Production" dans les commandes clients
- Tableau récapitulatif de la commande avec informations essentielles
- Affichage des produits par couleur avec leurs caractéristiques
- Gestion du colisage avec jspreadsheet
- Calcul automatique des quantités
- Sauvegarde des données de colisage

## Prérequis

- Dolibarr version 20.0.0 ou supérieure
- Module Commandes activé

## Installation

1. Décompressez l'archive dans le dossier `custom` de votre installation Dolibarr
2. Activez le module dans la liste des modules (Menu Accueil > Configuration > Modules)

## Utilisation

1. Ouvrez une commande client
2. Cliquez sur l'onglet "Fiche de Production"
3. Renseignez les informations de référence chantier et commentaires si nécessaire
4. Pour chaque groupe de produits de même couleur, utilisez le tableau de colisage
   - Utilisez "Ajouter" pour créer une nouvelle ligne
   - Remplissez les colonnes Nbr Colis, Nbr éléments, et Longueur
   - La colonne Largeur est automatiquement remplie avec la largeur du produit
   - La colonne Quantité est calculée automatiquement selon la formule : A*B*C/1000*D/1000
5. Les modifications sont sauvegardées automatiquement après chaque modification

## Développement

### Structure du module

```
custom/ficheproduction/
├── admin/
│   └── setup.php               # Page de configuration
├── class/
│   └── actions_ficheproduction.class.php  # Hooks
├── core/
│   └── modules/
│       └── modficheproduction.class.php   # Descripteur du module
├── css/
│   └── ficheproduction.css     # Styles CSS
├── js/
│   └── ficheproduction.js      # Script JS
├── langs/
│   └── fr_FR/
│       └── ficheproduction.lang  # Traductions
├── lib/
│   └── ficheproduction.lib.php   # Fonctions utilitaires
├── sql/
│   ├── llx_ficheproduction.key.sql  # Index et contraintes
│   └── llx_ficheproduction.sql      # Structure de la table
├── ficheproduction.php           # Page principale
└── README.md                     # Documentation
```

### Base de données

Le module utilise une table dédiée `llx_ficheproduction` qui stocke les informations de colisage pour chaque produit de commande.

### Points techniques

- Utilisation de jspreadsheet v5 pour la gestion des tableaux interactifs
- Les données sont sauvegardées en JSON dans la base de données
- Chaque tableau a un ID basé sur l'ID de la ligne produit
- Architecture modulaire et propre

## Évolutions prévues

- Export PDF de la fiche de production
- Historique des modifications
- Interface d'administration améliorée

## Support

Pour toute question ou demande de support, veuillez contacter l'auteur du module.

---

Ce module est distribué sous licence GPL v3 ou ultérieure.