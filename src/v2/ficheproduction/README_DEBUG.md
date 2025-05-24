# 🔍 Debug Tools pour Fiche de Production v2.0

Si la page principale `ficheproduction.php` affiche une page vide, utilisez ces outils de diagnostic :

## 🛠️ Outils de Debug Disponibles

### 1. `debug.php` - Diagnostic Complet
```
http://votre-dolibarr/custom/ficheproduction/debug.php?id=1
```
**Fonction :** Teste tous les aspects du module
- ✅ Chargement PHP et Dolibarr
- ✅ Présence des fichiers
- ✅ Activation du module
- ✅ Permissions utilisateur
- ✅ Tables de base de données
- ✅ Syntaxe PHP

### 2. `test_permissions.php` - Test des Droits
```
http://votre-dolibarr/custom/ficheproduction/test_permissions.php?id=1
```
**Fonction :** Vérifie spécifiquement les permissions
- 🔐 Droits utilisateur
- 🔐 Permissions du module
- 🔐 Accès aux commandes
- 🔐 Simulation des vérifications

### 3. `ficheproduction_minimal.php` - Test Minimal
```
http://votre-dolibarr/custom/ficheproduction/ficheproduction_minimal.php?id=1
```
**Fonction :** Version simplifiée pour isoler les problèmes
- 🧪 Test de base Dolibarr
- 🧪 Chargement des objets essentiels
- 🧪 Interface Dolibarr basique

### 4. `quick_fix.php` - Réparation Rapide
```
http://votre-dolibarr/custom/ficheproduction/quick_fix.php
```
**Fonction :** Corrige automatiquement les problèmes courants
- ⚡ Activation du module
- ⚡ Création des tables manquantes
- ⚡ Attribution des permissions
- ⚠️ **Réservé aux administrateurs**

## 🚨 Problèmes Courants et Solutions

### Page Vide (Écran Blanc)
**Causes possibles :**
- ❌ Erreur PHP fatale
- ❌ Module non activé
- ❌ Permissions insuffisantes
- ❌ Fichiers manquants
- ❌ Tables de base de données manquantes

**Diagnostic :**
1. Commencer par `debug.php?id=1`
2. Vérifier la section qui échoue
3. Utiliser `quick_fix.php` si admin

### Module Non Activé
```
❌ Module ficheproduction is NOT enabled
```
**Solution :**
1. Aller dans **Configuration > Modules**
2. Rechercher "**Fiche de Production**"
3. Cliquer sur **Activer**
4. Ou utiliser `quick_fix.php`

### Permissions Manquantes
```
❌ User does NOT have ficheproduction read permission
```
**Solution :**
1. **Utilisateurs & Groupes > Utilisateurs**
2. **Éditer l'utilisateur**
3. **Onglet Permissions**
4. **Section Fiche de Production**
5. **Cocher "Lire"**
6. **Sauvegarder**

### Tables Manquantes
```
❌ Table llx_ficheproduction_session does NOT exist
```
**Solution :**
1. Utiliser `quick_fix.php` → "Create Database Tables"
2. Ou réactiver le module (supprime/recrée les tables)
3. Ou exécuter manuellement les scripts SQL dans `/sql/`

### Fichiers Manquants
```
❌ Main PHP: Not found
```
**Solution :**
1. Vérifier que tous les fichiers sont copiés dans `/custom/ficheproduction/`
2. Vérifier les permissions fichiers (755 pour dossiers, 644 pour fichiers)
3. Redéployer le module si nécessaire

## 📋 Procédure de Debug Systématique

### Étape 1: Test Initial
```bash
# Accéder au diagnostic complet
http://votre-dolibarr/custom/ficheproduction/debug.php?id=1
```

### Étape 2: Identifier le Problème
Chercher les ❌ dans le rapport et noter :
- Section qui échoue
- Message d'erreur spécifique
- Tests qui passent ✅

### Étape 3: Appliquer la Solution

**Si vous êtes administrateur :**
```bash
# Tentative de réparation automatique
http://votre-dolibarr/custom/ficheproduction/quick_fix.php
```

**Si vous n'êtes pas administrateur :**
- Contacter l'administrateur Dolibarr
- Fournir le rapport de `debug.php`
- Demander activation module + permissions

### Étape 4: Vérification
```bash
# Test des permissions spécifiques
http://votre-dolibarr/custom/ficheproduction/test_permissions.php?id=1

# Test de la page simplifiée
http://votre-dolibarr/custom/ficheproduction/ficheproduction_minimal.php?id=1

# Test de la page principale
http://votre-dolibarr/custom/ficheproduction/ficheproduction.php?id=1
```

## 🔧 Debug Avancé

### Logs Dolibarr
```bash
# Localisation des logs
/var/www/dolibarr/documents/dolibarr.log

# Surveillance en temps réel
tail -f /var/www/dolibarr/documents/dolibarr.log
```

### Logs Serveur Web
```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log
```

### Console Navigateur
1. **F12** pour ouvrir les outils développeur
2. **Onglet Console** pour voir les erreurs JavaScript
3. **Onglet Réseau** pour voir les requêtes qui échouent

### Test Syntaxe PHP
```bash
# Vérifier la syntaxe du fichier principal
php -l /var/www/dolibarr/custom/ficheproduction/ficheproduction.php
```

## 📞 Support

Si les outils de debug ne résolvent pas le problème :

1. **Exécuter** `debug.php` et sauvegarder le rapport complet
2. **Vérifier** les logs d'erreur serveur
3. **Noter** les étapes pour reproduire le problème
4. **Contacter** le support avec ces informations

---

**🚀 Une fois le problème résolu, vous pouvez supprimer ces fichiers de debug pour des raisons de sécurité.**